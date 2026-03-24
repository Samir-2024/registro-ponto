<?php

namespace App\Jobs;

use App\Models\Department;
use App\Models\EmployeeRegistration;
use App\Models\Establishment;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportCollaboratorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected int $userId;

    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        Log::info("========== INICIANDO IMPORTAÇÃO DE COLABORADORES ==========");
        Log::info("Arquivo: {$this->filePath}");

        $results = [
            'pessoas_criadas' => 0,
            'vinculos_criados' => 0,
            'erros' => 0,
            'detalhes_erros' => []
        ];

        DB::beginTransaction();

        try {
            $file = fopen($this->filePath, 'r');
            if (!$file) {
                throw new \Exception("Não foi possível abrir o arquivo: {$this->filePath}");
            }

            // Ler a primeira linha para identificar o formato
            $firstLine = fgets($file);
            if (!$firstLine) {
                throw new \Exception("Arquivo vazio.");
            }

            // Detectar formato
            $isTxtFormat = str_contains($firstLine, '1+1+I[');
            rewind($file);

            if ($isTxtFormat) {
                Log::info("Formato TXT de REP detectado.");
                $lineNumber = 0;
                while (($line = fgets($file)) !== false) {
                    $lineNumber++;
                    $line = trim($line);
                    if (empty($line)) continue;

                    $parts = explode('[', $line);
                    // Formato: 1+1+I[CPF[NOME[1[1[MATRICULA
                    if (count($parts) >= 4) {
                        try {
                            $cpf = $this->cleanCpf($parts[1]);
                            $nome = trim($parts[2]);
                            $matricula = trim($parts[5] ?? '');

                            if (empty($cpf)) {
                                $results['erros']++;
                                $results['detalhes_erros'][] = "Linha {$lineNumber}: CPF vazio, pulando";
                                continue;
                            }

                            $person = Person::updateOrCreate(
                                ['cpf' => $cpf],
                                ['full_name' => $nome]
                            );

                            if ($person->wasRecentlyCreated) {
                                $results['pessoas_criadas']++;
                            }

                            // Criar vínculo se tiver matrícula
                            if (!empty($matricula)) {
                                $registration = EmployeeRegistration::firstOrCreate(
                                    ['matricula' => $matricula],
                                    [
                                        'person_id' => $person->id,
                                        'establishment_id' => clone (\App\Models\Establishment::first())->id ?? 1,
                                        'status' => 'active'
                                    ]
                                );
                                if ($registration->wasRecentlyCreated) {
                                    $results['vinculos_criados']++;
                                }
                            }
                        } catch (\Exception $e) {
                            $results['erros']++;
                            $results['detalhes_erros'][] = "Linha {$lineNumber}: {$e->getMessage()}";
                        }
                    }
                }
            } else {
                Log::info("Formato CSV detectado.");
                // Ler cabeçalho
                $header = fgetcsv($file);
                
                $lineNumber = 1;
                while (($row = fgetcsv($file)) !== false) {
                    $lineNumber++;
                    
                    try {
                        $data = array_combine($header, $row);
                        
                        $cpf = $this->cleanCpf($data['cpf'] ?? null);
                        if (empty($cpf)) {
                            // Se for CSV, usa CPF como fallback ou PIS
                            if (empty($data['pis_pasep'])) {
                                Log::warning("Linha {$lineNumber}: CPF e PIS/PASEP vazios, pulando");
                                $results['erros']++;
                                $results['detalhes_erros'][] = "Linha {$lineNumber}: CPF e PIS vazios";
                                continue;
                            }
                        }

                        $pis = !empty($data['pis_pasep']) ? $this->cleanPis($data['pis_pasep']) : null;
                        $nome = trim($data['full_name'] ?? '');
                        $matricula = trim($data['matricula'] ?? '');
                        $establishmentId = (int)($data['establishment_id'] ?? 1);
                        $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
                        $admissionDate = !empty($data['admission_date']) ? Carbon::parse($data['admission_date']) : null;
                        $position = trim($data['role'] ?? '');

                        // Buscar por CPF primariamente, se não tiver usa o PIS. 
                        // UpdateOrCreate só suporta array nas condições.
                        $searchAttrs = [];
                        if ($cpf) $searchAttrs['cpf'] = $cpf;
                        elseif ($pis) $searchAttrs['pis_pasep'] = $pis;

                        $updateAttrs = ['full_name' => $nome];
                        if ($cpf) $updateAttrs['cpf'] = $cpf;
                        if ($pis) $updateAttrs['pis_pasep'] = $pis;

                        $person = Person::updateOrCreate($searchAttrs, $updateAttrs);

                        if ($person->wasRecentlyCreated) {
                            $results['pessoas_criadas']++;
                        }

                        // Criar vínculo se tiver matrícula
                        if (!empty($matricula)) {
                            $registration = EmployeeRegistration::updateOrCreate(
                                ['matricula' => $matricula],
                                [
                                    'person_id' => $person->id,
                                    'establishment_id' => $establishmentId,
                                    'department_id' => $departmentId,
                                    'admission_date' => $admissionDate,
                                    'position' => $position,
                                    'status' => 'active'
                                ]
                            );
                            if ($registration->wasRecentlyCreated) {
                                $results['vinculos_criados']++;
                            }
                        }
                    } catch (\Exception $e) {
                        $results['erros']++;
                        $erro = "Linha {$lineNumber}: {$e->getMessage()}";
                        $results['detalhes_erros'][] = $erro;
                        Log::error($erro);
                    }
                }
            }

            fclose($file);

            DB::commit();

            Log::info("========== IMPORTAÇÃO DE COLABORADORES CONCLUÍDA ==========");
            Log::info("Pessoas criadas: {$results['pessoas_criadas']}");
            Log::info("Vínculos criados: {$results['vinculos_criados']}");
            Log::info("Erros: {$results['erros']}");
            
            if (!empty($results['detalhes_erros'])) {
                Log::warning("Detalhes dos erros:");
                foreach ($results['detalhes_erros'] as $erro) {
                    Log::warning("  - {$erro}");
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ERRO CRÍTICO na importação de colaboradores: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    protected function cleanPis(string $pis): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $pis);
        if (strlen($cleaned) > 11) {
            Log::warning("PIS com mais de 11 dígitos detectado: {$pis}, usando apenas os primeiros 11 dígitos");
            $cleaned = substr($cleaned, 0, 11);
        }
        return $cleaned;
    }

    protected function cleanCpf(?string $cpf): ?string
    {
        if (empty($cpf)) {
            return null;
        }
        
        $cleaned = preg_replace('/[^0-9]/', '', $cpf);
        return empty($cleaned) ? null : $cleaned;
    }
}
