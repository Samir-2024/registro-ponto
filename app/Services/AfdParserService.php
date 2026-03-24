<?php

namespace App\Services;

use App\Models\AfdImport;
use App\Services\AfdParsers\AfdParserFactory;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de parsing de arquivos AFD
 * 
 * Orquestra o processo de importação usando a arquitetura de parsers modulares
 */
class AfdParserService
{
    /**
     * Processa o arquivo AFD usando o parser apropriado
     * 
     * @param string $filePath Caminho do arquivo
     * @param AfdImport $afdImport Registro de importação
     * @param string|null $formatHint Dica opcional do formato
     * @return array Resultado do processamento
     */
    public function parse(string $filePath, AfdImport $afdImport, ?string $formatHint = null): array
    {
        try {
            $filePath = ltrim($filePath, '/\\');
            $storageBase = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/'));
            $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
            // Se já começa com storage_path('app/'), não concatena de novo
            if (strpos($normalized, $storageBase) === 0) {
                $fullPath = $normalized;
            } elseif (preg_match('/^[A-Za-z]:\\\\|^\//', $normalized)) {
                $fullPath = $normalized;
            } else {
                $fullPath = $storageBase . $normalized;
            }
            if (!file_exists($fullPath)) {
                throw new \Exception("Arquivo não encontrado: {$fullPath}");
            }
            
            Log::info("AfdParserService: Iniciando processamento do arquivo {$fullPath}");

            // Factory cria o parser apropriado baseado no arquivo ou dica
            $parser = AfdParserFactory::createParser($fullPath, $formatHint);
            
            Log::info("AfdParserService: Usando parser {$parser->getFormatName()}");

            // Delega o processamento para o parser específico
            return $parser->parse($fullPath, $afdImport);

        } catch (\Exception $e) {
            $afdImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("AfdParserService: Erro ao processar arquivo - " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'imported' => 0,
                'skipped' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Retorna lista de formatos suportados
     *
     * @return array
     */
    public function getSupportedFormats(): array
    {
        return AfdParserFactory::getSupportedFormats();
    }
}
