<?php

namespace App\Jobs;

use App\Models\AfdImport;
use App\Services\AfdParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAfdImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     */
    public $tries = 3;

    /**
     * O número de segundos antes do job dar timeout.
     */
    public $timeout = 300; // 5 minutos

    /**
     * A importação AFD a ser processada.
     */
    public $afdImport;

    /**
     * Create a new job instance.
     */
    public function __construct(AfdImport $afdImport)
    {
        $this->afdImport = $afdImport;
    }

    /**
     * Execute the job.
     */
    public function handle(AfdParserService $parser): void
    {
        try {
            Log::info("Iniciando processamento assíncrono do AFD #{$this->afdImport->id}");

            // Atualizar status para processando
            $this->afdImport->update(['status' => 'processing']);



            // Sempre passar o caminho RELATIVO para o parser
            $filePath = ltrim($this->afdImport->file_path, '/\\');
            $result = $parser->parse($filePath, $this->afdImport);

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
