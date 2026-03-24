<?php

namespace App\Services\AfdParsers;

use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\TimeRecord;
use App\Models\AfdImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Classe base abstrata para parsers de AFD
 * 
 * Contém lógica comum a todos os parsers
 */
abstract class BaseAfdParser implements AfdParserInterface
{
    protected AfdImport $afdImport;
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $skippedCount = 0;
    
    // Novos arrays para armazenar pendentes
    protected array $pendingEmployees = [];
    protected array $pendingRecords = [];

    /**
     * Processa o arquivo AFD
     */
    public function parse(string $filePath, AfdImport $afdImport): array
    {
        $this->afdImport = $afdImport;
        $this->errors = [];
        $this->successCount = 0;
        $this->skippedCount = 0;
        $this->pendingEmployees = [];
        $this->pendingRecords = [];

        try {
            // Sempre trata file_path como relativo a storage/app
            $fullPath = storage_path('app/' . ltrim($filePath, '/'));
            if (!file_exists($fullPath)) {
                throw new \Exception("Arquivo não encontrado: {$fullPath}");
            }
            
            Log::info("AFD Parser ({$this->getFormatName()}): Processando arquivo {$fullPath}");

            DB::beginTransaction();

            // Registra o formato detectado (dentro da transação)
            $this->afdImport->update([
                'format_type' => $this->getFormatName(),
            ]);

            $this->processFile($fullPath);

            // Determina o status final baseado nos pendentes
            $hasPending = !empty($this->pendingEmployees);
            $finalStatus = $hasPending ? 'pending_review' : 'completed';

            $this->afdImport->update([
                'status' => $finalStatus,
                'total_records' => $this->successCount,
                'pending_employees' => $hasPending ? array_values($this->pendingEmployees) : null,
                'pending_records' => $hasPending ? array_values($this->pendingRecords) : null,
                'pending_count' => count($this->pendingEmployees),
            ]);

            DB::commit();

            return [
                'success' => true,
                'imported' => $this->successCount,
                'skipped' => $this->skippedCount,
                'pending' => count($this->pendingEmployees),
                'errors' => $this->errors,
                'format' => $this->getFormatName(),
                'needs_review' => $hasPending,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Atualiza fora de qualquer transação para garantir que o erro seja salvo
            try {
                $this->afdImport->refresh();
                $this->afdImport->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            } catch (\Exception $updateError) {
                Log::error("Erro ao atualizar status de falha: " . $updateError->getMessage());
            }

            Log::error("Erro ao processar AFD: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'imported' => $this->successCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Método abstrato para processar o arquivo - cada parser implementa sua lógica
     */
    abstract protected function processFile(string $filePath): void;

    /**
     * Busca um vínculo (matrícula) por PIS, Matrícula ou CPF
     * 
     * LÓGICA NOVA (Refatoração Pessoa + Vínculo):
     * - Prioridade 1: Busca direta por Matrícula (EmployeeRegistration)
     * - Prioridade 2: Busca por PIS (Person) e retorna primeiro vínculo ativo
     * - Prioridade 3: Busca por CPF (Person) e retorna primeiro vínculo ativo
     * 
     * @param string|null $pis PIS/PASEP
     * @param string|null $matricula Matrícula
     * @param string|null $cpf CPF
     * @return EmployeeRegistration|null
     */
    protected function findEmployeeRegistration(?string $pis = null, ?string $matricula = null, ?string $cpf = null): ?EmployeeRegistration
    {
        // PRIORIDADE 1: Busca direta por Matrícula (mais específico)
        if ($matricula) {
            $normalizedMatricula = preg_replace('/[^0-9A-Za-z]/', '', $matricula);
            $registration = EmployeeRegistration::where('matricula', $normalizedMatricula)
                ->where('status', 'active')
                ->first();
            
            if ($registration) {
                return $registration;
            }
        }

        // PRIORIDADE 2: Busca por PIS (identifica a Pessoa)
        if ($pis) {
            $normalizedPis = preg_replace('/[^0-9]/', '', $pis);
            $person = Person::where('pis_pasep', $normalizedPis)->first();
            
            if ($person) {
                // Retorna o primeiro vínculo ativo da pessoa
                // TODO: Se houver múltiplos vínculos, qual escolher?
                // Por enquanto, retorna o primeiro ativo
                return $person->activeRegistrations()->first();
            }
        }

        // PRIORIDADE 3: Busca por CPF (identifica a Pessoa)
        if ($cpf) {
            $normalizedCpf = preg_replace('/[^0-9]/', '', $cpf);
            $person = Person::where('cpf', $normalizedCpf)->first();
            
            if ($person) {
                // Retorna o primeiro vínculo ativo da pessoa
                return $person->activeRegistrations()->first();
            }
        }

        return null;
    }

    /**
     * DEPRECATED: Mantido por compatibilidade - usar findEmployeeRegistration()
     */
    protected function findEmployee(?string $pis = null, ?string $matricula = null, ?string $cpf = null): ?EmployeeRegistration
    {
        return $this->findEmployeeRegistration($pis, $matricula, $cpf);
    }

    /**
     * Cria um registro de ponto
     */
    protected function createTimeRecord(
        EmployeeRegistration $registration, 
        Carbon $recordedAt, 
        string $nsr, 
        string $recordType
    ): bool {
        // Verifica se já existe
        $exists = TimeRecord::where('employee_registration_id', $registration->id)
            ->where('recorded_at', $recordedAt)
            ->exists();

        if ($exists) {
            $this->skippedCount++;
            return false;
        }

        TimeRecord::create([
            'employee_registration_id' => $registration->id,
            'recorded_at' => $recordedAt,
            'record_date' => $recordedAt->format('Y-m-d'),
            'record_time' => $recordedAt->format('H:i:s'),
            'nsr' => $nsr,
            'record_type' => $recordType,
            'imported_from_afd' => true,
            'afd_file_name' => $this->afdImport->file_name,
        ]);

        $this->successCount++;
        return true;
    }

    /**
     * Parse de data/hora - múltiplos formatos
     */
    protected function parseDateTime(string $dateTimeStr): ?Carbon
    {
        try {
            // Tenta formato padrão ISO
            return Carbon::parse($dateTimeStr);
        } catch (\Exception $e) {
            // Tenta outros formatos comuns
            $formats = [
                'dmYHis',     // 28042014134621
                'd/m/Y H:i:s',
                'Y-m-d H:i:s',
                'Ymd His',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateTimeStr);
                } catch (\Exception $e) {
                    continue;
                }
            }

            return null;
        }
    }

    /**
     * Normaliza CPF removendo formatação
     */
    protected function normalizeCpf(string $cpfRaw): ?string
    {
        // Remove pontos, traços e espaços
        $cpf = preg_replace('/[^0-9]/', '', trim($cpfRaw));
        
        // CPF deve ter exatamente 11 dígitos
        if (strlen($cpf) != 11) {
            return null;
        }

        return $cpf;
    }

    /**
     * Normaliza PIS/PASEP removendo formatação
     * 
     * O PIS/PASEP brasileiro tem 11 dígitos, mas alguns sistemas AFD
     * armazenam com 12 dígitos (zero à esquerda). Este método normaliza
     * para 11 dígitos quando possível.
     */
    protected function normalizePis(string $pisRaw): ?string
    {
        $pis = preg_replace('/[^0-9]/', '', trim($pisRaw));
        
        // Se tem 12 dígitos e começa com 0, remove o zero à esquerda
        if (strlen($pis) == 12 && $pis[0] === '0') {
            $pis = substr($pis, 1);
        }
        
        // PIS deve ter 11 dígitos
        if (strlen($pis) != 11) {
            return null;
        }
        
        // Valida se não é um PIS fake (todos iguais)
        if (preg_match('/^(.)\1{10}$/', $pis)) {
            return null;
        }

        return $pis;
    }

    /**
     * Atualiza o header da importação
     */
    protected function updateImportHeader(array $headerData): void
    {
        $this->afdImport->update([
            'cnpj' => $headerData['cnpj_cpf_employer'] ?? null,
            'start_date' => $headerData['start_date'] ?? null,
            'end_date' => $headerData['end_date'] ?? null,
        ]);
    }

    /**
     * Adiciona um erro à lista
     */
    protected function addError(string $message): void
    {
        $this->errors[] = $message;
        Log::warning("AFD Parser Error: {$message}");
    }

    /**
     * Adiciona um colaborador não encontrado à lista de pendentes
     * 
     * @param string|null $matricula Matrícula do colaborador
     * @param string|null $pis PIS/PASEP do colaborador
     * @param string|null $cpf CPF do colaborador
     * @param \Carbon\Carbon $recordedAt Data/hora do registro
     * @param string $nsr NSR do registro
     * @param string $recordType Tipo do registro
     */
    protected function addPendingEmployee(
        ?string $matricula, 
        ?string $pis, 
        ?string $cpf, 
        \Carbon\Carbon $recordedAt,
        string $nsr,
        string $recordType
    ): void {
        // Cria uma chave única para identificar o colaborador
        $key = $this->generateEmployeeKey($matricula, $pis, $cpf);
        
        // Se já existe, apenas incrementa o contador e adiciona o registro
        if (isset($this->pendingEmployees[$key])) {
            $this->pendingEmployees[$key]['records_count']++;
            
            // Atualiza a primeira e última data do registro
            $currentFirst = $this->pendingEmployees[$key]['first_record'];
            $currentLast = $this->pendingEmployees[$key]['last_record'];
            
            if ($recordedAt->format('Y-m-d H:i:s') < $currentFirst) {
                $this->pendingEmployees[$key]['first_record'] = $recordedAt->format('Y-m-d H:i:s');
            }
            if ($recordedAt->format('Y-m-d H:i:s') > $currentLast) {
                $this->pendingEmployees[$key]['last_record'] = $recordedAt->format('Y-m-d H:i:s');
            }
        } else {
            // Novo colaborador pendente
            $this->pendingEmployees[$key] = [
                'key' => $key,
                'matricula' => $matricula,
                'pis' => $pis,
                'cpf' => $cpf,
                'records_count' => 1,
                'first_record' => $recordedAt->format('Y-m-d H:i:s'),
                'last_record' => $recordedAt->format('Y-m-d H:i:s'),
            ];
        }
        
        // Adiciona o registro pendente
        $this->pendingRecords[] = [
            'employee_key' => $key,
            'recorded_at' => $recordedAt->format('Y-m-d H:i:s'),
            'record_date' => $recordedAt->format('Y-m-d'),
            'record_time' => $recordedAt->format('H:i:s'),
            'nsr' => $nsr,
            'record_type' => $recordType,
        ];
    }

    /**
     * Gera uma chave única para identificar um colaborador
     */
    protected function generateEmployeeKey(?string $matricula, ?string $pis, ?string $cpf): string
    {
        if ($matricula) {
            return 'matricula_' . preg_replace('/[^0-9A-Za-z]/', '', $matricula);
        }
        if ($pis) {
            return 'pis_' . preg_replace('/[^0-9]/', '', $pis);
        }
        if ($cpf) {
            return 'cpf_' . preg_replace('/[^0-9]/', '', $cpf);
        }
        return 'unknown_' . uniqid();
    }
}
