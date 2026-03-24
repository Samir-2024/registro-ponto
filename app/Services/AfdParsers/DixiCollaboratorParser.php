<?php

namespace App\Services\AfdParsers;

use App\Models\AfdImport;
use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\Establishment;
use Illuminate\Support\Facades\Log;

class DixiCollaboratorParser implements AfdParserInterface
{
    public function canParse(string $filePath): bool
    {
        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) return false;

            $firstLine = fgets($handle);
            fclose($handle);

            if ($firstLine && str_contains($firstLine, '1+1+I[')) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFormatName(): string
    {
        return 'DIXI (Colaboradores)';
    }

    public function getFormatDescription(): string
    {
        return 'Formato de importação de lista de colaboradores do REP (e.g. DIXI)';
    }

    public function parse(string $filePath, AfdImport $afdImport): array
    {
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new \Exception("Não foi possível abrir o arquivo");
            }

            $afdImport->update(['status' => 'processing']);
            $defaultEstablishmentId = Establishment::first()->id ?? 1;

            $lineNumber = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $line = trim($line);
                if (empty($line)) continue;

                $parts = explode('[', $line);
                // 1+1+I[CPF[NOME[1[1[MATRICULA
                if (count($parts) >= 4 && str_starts_with($parts[0], '1+1+I')) {
                    try {
                        $cpfRaw = $parts[1];
                        $cpf = preg_replace('/[^0-9]/', '', $cpfRaw);
                        $nome = trim($parts[2]);
                        $matricula = trim($parts[5] ?? '');

                        if (empty($cpf)) {
                            $errors[] = "Linha {$lineNumber}: CPF vazio";
                            $skippedCount++;
                            continue;
                        }

                        $person = Person::updateOrCreate(
                            ['cpf' => $cpf],
                            ['full_name' => $nome]
                        );

                        if (!empty($matricula)) {
                            EmployeeRegistration::firstOrCreate(
                                ['matricula' => $matricula],
                                [
                                    'person_id' => $person->id,
                                    'establishment_id' => $defaultEstablishmentId,
                                    'status' => 'active'
                                ]
                            );
                        }
                        
                        $importedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Linha {$lineNumber}: " . $e->getMessage();
                        $skippedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }
            fclose($handle);

            $afdImport->update([
                'status' => 'completed',
                'imported_records' => $importedCount,
            ]);

        } catch (\Exception $e) {
            $afdImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            $errors[] = $e->getMessage();
        }

        return [
            'success' => true,
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors
        ];
    }
}
