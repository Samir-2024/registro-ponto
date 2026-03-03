<?php

namespace App\Jobs;

use App\Models\AfdImport;
use App\Services\AfdParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAfdImport implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     */
    public $tries = 3;

    /**
     * O número de segundos antes do job dar timeout.
     */
    public $timeout = 300; // 5 minutos

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AfdImport $afdImport
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AfdParserService $parser): void
    {
        try {
            Log::info("Iniciando processamento assíncrono do AFD #{$this->afdImport->id}");

            // Atualizar status para processando
            $this->afdImport->update(['status' => 'processing']);

            // Obter caminho completo do arquivo
            $fullPath = storage_path('app/' . $this->afdImport->file_path);

            // Verificar se arquivo existe
            if (!file_exists($fullPath)) {
                throw new \Exception("Arquivo não encontrado: {$fullPath}");
            }

            // Processar o arquivo AFD - assinatura correta: parse($filePath, $afdImport, $formatHint)
            $result = $parser->parse($fullPath, $this->afdImport);

            // Recarregar o modelo para pegar dados atualizados
            $this->afdImport->refresh();

            // Atualizar status para completo
            $this->afdImport->update([
                'status' => 'completed',
                'processed_at' => now()
            ]);

            Log::info("AFD #{$this->afdImport->id} processado com sucesso. {$this->afdImport->records_imported} registros importados. Formato: {$this->afdImport->format_type}");

        } catch (\Exception $e) {
            Log::error("Erro ao processar AFD #{$this->afdImport->id}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            $this->afdImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de importação AFD #{$this->afdImport->id} falhou após todas as tentativas: " . $exception->getMessage());
        
        $this->afdImport->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}
