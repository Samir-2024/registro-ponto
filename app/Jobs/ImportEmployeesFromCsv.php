<?php

namespace App\Jobs;

use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\EmployeeImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImportEmployeesFromCsv implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutos

    /**
     * Create a new job instance.
     */
    public function __construct(
        public EmployeeImport $import
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Iniciando importação de colaboradores #{$this->import->id}");

            $this->import->update(['status' => 'processing']);

            $filePath = storage_path('app/' . $this->import->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception("Arquivo não encontrado: {$filePath}");
            }

            $results = $this->processFile($filePath);

            $this->import->update([
                'status' => 'completed',
                'total_rows' => $results['total'],
                'success_count' => $results['success'],
                'error_count' => $results['errors'],
                'updated_count' => $results['updated'],
                'processed_at' => now()
            ]);

            Log::info("Importação #{$this->import->id} concluída: {$results['success']} criados, {$results['updated']} atualizados, {$results['errors']} erros");

        } catch (\Exception $e) {
            Log::error("Erro na importação #{$this->import->id}: " . $e->getMessage());
            
            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function processFile(string $filePath): array
    {
        $results = [
            'total' => 0,
            'success' => 0,
            'updated' => 0,
            'errors' => 0,
            'error_details' => []
        ];

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 1000, ',');
        
        // Limpar espaços em branco do cabeçalho
        $header = array_map('trim', $header);
        
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNumber++;
            $results['total']++;

            try {
                // Limpar espaços em branco dos valores
                $row = array_map('trim', $row);
                $data = array_combine($header, $row);
                
                // Limpar CPF, PIS e Matrícula
                $data['cpf'] = preg_replace('/[^0-9]/', '', $data['cpf']);
                $data['pis_pasep'] = preg_replace('/[^0-9]/', '', $data['pis_pasep']);
                if (isset($data['matricula'])) {
                    $data['matricula'] = trim($data['matricula']);
                }
                
                // Validar dados
                $validator = Validator::make($data, [
                    'cpf' => 'required|string|size:11',
                    'full_name' => 'required|string|max:255',
                    'pis_pasep' => 'required|string|size:11',
                    'matricula' => 'required|string|max:20',
                    'establishment_id' => 'required|exists:establishments,id',
                    'department_id' => 'nullable|exists:departments,id',
                    'admission_date' => 'required|date',
                    'role' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $results['errors']++;
                    $results['error_details'][] = [
                        'line' => $lineNumber,
                        'errors' => $validator->errors()->all()
                    ];
                    continue;
                }

                // NOVA LÓGICA: Pessoa + Vínculo (APENAS VINCULA, NÃO CRIA PESSOAS)
                $personNotFound = false;
                
                DB::transaction(function () use ($data, &$results, $lineNumber, &$personNotFound) {
                    // PASSO 1: BUSCAR PESSOA EXISTENTE (NÃO CRIA)
                    // Primeiro tenta pelo CPF, depois pelo PIS
                    $person = Person::where('cpf', $data['cpf'])->first();
                    
                    if (!$person && !empty($data['pis_pasep'])) {
                        $person = Person::where('pis_pasep', $data['pis_pasep'])->first();
                    }

                    if (!$person) {
                        // Pessoa não encontrada - marcar flag para registrar erro
                        $personNotFound = true;
                        return; // Sai da transaction sem fazer nada
                    }
                    
                    // Pessoa existe - atualizar dados se necessário
                    $updateData = [];
                    
                    if (empty($person->cpf) && !empty($data['cpf'])) {
                        $updateData['cpf'] = $data['cpf'];
                    }
                    
                    if (empty($person->pis_pasep) && !empty($data['pis_pasep'])) {
                        $updateData['pis_pasep'] = $data['pis_pasep'];
                    }
                    
                    if (!empty($updateData)) {
                        $person->update($updateData);
                    }

                    // PASSO 2: Buscar ou criar o VÍNCULO pela MATRÍCULA
                    $registration = EmployeeRegistration::where('matricula', $data['matricula'])->first();

                    if ($registration) {
                        // Vínculo já existe, atualizar
                        $registration->update([
                            'person_id' => $person->id,
                            'establishment_id' => $data['establishment_id'],
                            'department_id' => $data['department_id'] ?: $registration->department_id,
                            'admission_date' => $data['admission_date'],
                            'position' => $data['role'] ?: $registration->position,
                            'status' => 'active',
                        ]);
                        $results['updated']++;
                    } else {
                        // Criar novo Vínculo
                        EmployeeRegistration::create([
                            'person_id' => $person->id,
                            'matricula' => $data['matricula'],
                            'establishment_id' => $data['establishment_id'],
                            'department_id' => $data['department_id'] ?: null,
                            'admission_date' => $data['admission_date'],
                            'position' => $data['role'] ?: null,
                            'status' => 'active',
                        ]);
                        $results['success']++;
                    }
                });
                
                // Se pessoa não foi encontrada, registrar erro
                if ($personNotFound) {
                    $results['errors']++;
                    $results['error_details'][] = [
                        'line' => $lineNumber,
                        'errors' => ["Colaborador não encontrado no sistema (CPF: {$data['cpf']}, PIS: {$data['pis_pasep']})"]
                    ];
                }

            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_details'][] = [
                    'line' => $lineNumber,
                    'errors' => [$e->getMessage()]
                ];
            }
        }

        fclose($handle);

        // Salvar detalhes dos erros
        if (!empty($results['error_details'])) {
            $errorFile = storage_path('app/employee-imports/errors-' . $this->import->id . '.json');
            file_put_contents($errorFile, json_encode($results['error_details'], JSON_PRETTY_PRINT));
        }

        return $results;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job de importação de colaboradores #{$this->import->id} falhou: " . $exception->getMessage());
        
        $this->import->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}
