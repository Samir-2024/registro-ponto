<?php

namespace App\Http\Controllers;

use App\Jobs\ImportCollaboratorsJob;
use App\Models\EmployeeImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class EmployeeImportController extends Controller
{
    public function index()
    {
        $imports = EmployeeImport::orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total' => EmployeeImport::count(),
            'completed' => EmployeeImport::where('status', 'completed')->count(),
            'processing' => EmployeeImport::where('status', 'processing')->count(),
            'failed' => EmployeeImport::where('status', 'failed')->count(),
        ];
        
        return view('employee-imports.index', compact('imports', 'stats'));
    }

    public function create()
    {
        return view('employee-imports.create');
    }

    /**
     * Download do template CSV
     */
    public function downloadTemplate()
    {
        $filePath = public_path('modelo-importacao-colaboradores.csv');
        
        if (file_exists($filePath)) {
            return Response::download($filePath, 'modelo-importacao-colaboradores.csv');
        }

        // Fallback: gerar CSV dinamicamente
        $headers = [
            'full_name',
            'cpf',
            'pis_pasep',
            'matricula',
            'establishment_id',
            'department_id',
            'admission_date',
            'role'
        ];

        $example = [
            'João da Silva',
            '123.456.789-01',
            '123.45678.90-1',
            '1234',
            '1',
            '1',
            '2024-01-15',
            'Analista de RH'
        ];

        $csv = implode(',', $headers) . "\n" . implode(',', $example);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="modelo-importacao-colaboradores.csv"',
        ]);
    }

    /**
     * Upload e validação inicial do arquivo
     */
    public function upload(Request $request)
    {
        try {
            $validated = $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:5120',
            ], [
                'csv_file.required' => 'Por favor, selecione um arquivo CSV.',
                'csv_file.file' => 'O arquivo enviado é inválido.',
                'csv_file.mimes' => 'O arquivo deve ser do tipo CSV ou TXT.',
                'csv_file.max' => 'O arquivo não pode ter mais de 5MB.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('employee-imports.create')
                ->withErrors($e->validator)
                ->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $fileSize = $file->getSize();
            
            $directory = storage_path('app/employee-imports');
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            
            $fullPath = $directory . '/' . $fileName;
            $file->move($directory, $fileName);
            
            if (!file_exists($fullPath)) {
                return redirect()->route('employee-imports.create')
                    ->with('error', 'Erro ao salvar o arquivo.');
            }

            // Validação prévia do arquivo
            $preview = $this->previewImport($fullPath);

            // Criar registro de importação
            $import = EmployeeImport::create([
                'file_path' => 'employee-imports/' . $fileName,
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $fileSize,
                'status' => 'pending',
                'total_rows' => $preview['total_rows'],
            ]);

            return view('employee-imports.preview', [
                'import' => $import,
                'preview' => $preview
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro no upload de CSV: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('employee-imports.create')
                ->with('error', 'Erro durante o upload: ' . $e->getMessage());
        }
    }

    /**
     * Confirmar e processar importação
     */
    public function process(EmployeeImport $import)
    {
        if ($import->status !== 'pending') {
            return redirect()->route('employee-imports.show', $import)
                ->with('error', 'Esta importação já foi processada.');
        }

        // Despachar job para processamento
        ImportCollaboratorsJob::dispatch(storage_path('app/' . $import->file_path), auth()->id() ?? 1);

        return redirect()->route('employee-imports.show', $import)
            ->with('success', 'Importação iniciada! O processamento está sendo realizado em segundo plano.');
    }

    public function show(EmployeeImport $import)
    {
        // Carregar detalhes dos erros se existirem
        $errorDetails = [];
        $errorFile = storage_path('app/employee-imports/errors-' . $import->id . '.json');
        
        if (file_exists($errorFile)) {
            $errorDetails = json_decode(file_get_contents($errorFile), true) ?? [];
        }
        
        return view('employee-imports.show', compact('import', 'errorDetails'));
    }

    /**
     * Exibir página detalhada dos erros de importação
     */
    public function showErrors(EmployeeImport $import)
    {
        // Carregar arquivo de erros
        $errorFile = storage_path('app/employee-imports/errors-' . $import->id . '.json');
        
        if (!file_exists($errorFile)) {
            return redirect()->route('employee-imports.show', $import)
                ->with('info', 'Nenhum erro encontrado para esta importação.');
        }
        
        $errorDetails = json_decode(file_get_contents($errorFile), true) ?? [];
        
        // Carregar o CSV original para pegar os dados das linhas com erro
        $csvFile = storage_path('app/' . $import->file_path);
        $errorRows = [];
        
        if (file_exists($csvFile)) {
            $handle = fopen($csvFile, 'r');
            $header = fgetcsv($handle, 1000, ',');
            $header = array_map('trim', $header);
            
            $lineNumber = 1;
            $errorLines = array_column($errorDetails, 'line');
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;
                
                // Se esta linha tem erro, adicionar aos dados
                $errorIndex = array_search($lineNumber, $errorLines);
                if ($errorIndex !== false) {
                    $row = array_map('trim', $row);
                    $rowData = array_combine($header, $row);
                    
                    $errorRows[] = [
                        'line' => $lineNumber,
                        'data' => $rowData,
                        'errors' => $errorDetails[$errorIndex]['errors']
                    ];
                }
            }
            
            fclose($handle);
        }
        
        return view('employee-imports.errors', compact('import', 'errorRows'));
    }

    /**
     * Pré-visualização dos dados do CSV
     */
    protected function previewImport(string $filePath): array
    {
        $preview = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'new_employees' => 0,
            'existing_employees' => 0,
            'errors' => [],
            'sample_data' => []
        ];

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 1000, ',');
        
        // Limpar espaços em branco do cabeçalho
        $header = array_map('trim', $header);
        
        $lineNumber = 1;
        $sampleCount = 0;

        while (($row = fgetcsv($handle, 1000, ',')) !== false && $sampleCount < 5) {
            $lineNumber++;
            $preview['total_rows']++;

            try {
                $isTxtLine = false;
                if (count($row) === 1 && str_starts_with($row[0], '1+1+I[')) {
                    $isTxtLine = true;
                    $parts = explode('[', $row[0]);
                    $data = [
                        'cpf' => $parts[1] ?? '',
                        'full_name' => $parts[2] ?? '',
                        'matricula' => $parts[5] ?? '',
                    ];
                } else {
                    // Limpar espaços em branco dos valores
                    $row = array_map('trim', $row);
                    $data = array_combine($header, $row);
                }
                
                // Limpar CPF e PIS antes de validar
                if (!empty($data['cpf'])) {
                    $data['cpf_cleaned'] = preg_replace('/[^0-9]/', '', $data['cpf']);
                }
                if (!empty($data['pis_pasep'])) {
                    $data['pis_cleaned'] = preg_replace('/[^0-9]/', '', $data['pis_pasep']);
                }
                
                if ($isTxtLine) {
                    $validator = Validator::make($data, [
                        'cpf_cleaned' => 'required|string|size:11',
                        'full_name' => 'required|string|max:255',
                    ]);
                } else {
                    $validator = Validator::make($data, [
                        'cpf' => 'nullable|string',
                        'full_name' => 'required|string|max:255',
                        'pis_pasep' => 'nullable|string',
                        'matricula' => 'nullable|string|max:20',
                        'establishment_id' => 'required|exists:establishments,id',
                        'department_id' => 'nullable|exists:departments,id',
                        'admission_date' => 'required|date',
                        'role' => 'nullable|string|max:255',
                    ]);
                }

                if ($validator->fails()) {
                    $preview['invalid_rows']++;
                    if (count($preview['errors']) < 10) {
                        $preview['errors'][] = [
                            'line' => $lineNumber,
                            'errors' => $validator->errors()->all()
                        ];
                    }
                } else {
                    $preview['valid_rows']++;
                    
                    // Verificar se pessoa já existe (buscar pelo CPF limpo)
                    $personExists = \App\Models\Person::where('cpf', $data['cpf_cleaned'])->exists();
                    
                    // Verificar se matrícula já existe
                    $registrationExists = isset($data['matricula']) && 
                        \App\Models\EmployeeRegistration::where('matricula', $data['matricula'])->exists();
                    
                    if ($personExists || $registrationExists) {
                        $preview['existing_employees']++; // Será atualizado
                    } else {
                        $preview['new_employees']++; // Será criado
                    }

                    if ($sampleCount < 5) {
                        $preview['sample_data'][] = $data;
                        $sampleCount++;
                    }
                }

            } catch (\Exception $e) {
                $preview['invalid_rows']++;
            }
        }

        // Contar o resto das linhas
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $preview['total_rows']++;
        }

        fclose($handle);

        return $preview;
    }
}
