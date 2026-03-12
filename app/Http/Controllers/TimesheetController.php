<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\Department;
use App\Models\Establishment;
use App\Services\TimesheetGeneratorService;
use App\Services\ZipService;
use Illuminate\Http\Request;

class TimesheetController extends Controller
{
    protected TimesheetGeneratorService $timesheetService;
    protected ZipService $zipService;

    public function __construct(TimesheetGeneratorService $timesheetService, ZipService $zipService)
    {
        $this->timesheetService = $timesheetService;
        $this->zipService = $zipService;
    }

    /**
     * Tela inicial: buscar pessoa por CPF ou Nome
     */
    public function index()
    {
        return view('timesheets.index');
    }

    /**
     * Busca pessoa por CPF ou Nome e retorna seus vínculos
     */
    public function searchPerson(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:3',
        ], [
            'search.required' => 'Por favor, informe um CPF ou Nome para buscar.',
            'search.min' => 'Digite pelo menos 3 caracteres.',
        ]);

        $search = $request->input('search');
        
        // Limpar CPF (remover pontuação)
        $cleanSearch = preg_replace('/[^0-9]/', '', $search);

        // Buscar por CPF (exato) ou Nome (parcial)
        $people = Person::with(['activeRegistrations.department', 'activeRegistrations.establishment'])
            ->where(function ($query) use ($search, $cleanSearch) {
                // Busca por CPF
                if (strlen($cleanSearch) >= 11) {
                    $query->where('cpf', $cleanSearch);
                } else {
                    // Busca por nome
                    $query->where('full_name', 'ILIKE', "%{$search}%");
                }
            })
            ->get();

        if ($people->isEmpty()) {
            return back()->with('error', 'Nenhuma pessoa encontrada com os critérios informados.');
        }

        // Se encontrou apenas uma pessoa, vai direto para seleção de vínculos
        if ($people->count() === 1) {
            return view('timesheets.select-registrations', [
                'person' => $people->first(),
            ]);
        }

        // Se encontrou múltiplas pessoas, exibe lista para escolha
        return view('timesheets.select-person', [
            'people' => $people,
        ]);
    }

    /**
     * Exibe vínculos de uma pessoa específica para seleção
     */
    public function showPersonRegistrations(Person $person)
    {
        $person->load(['activeRegistrations.department', 'activeRegistrations.establishment', 'activeRegistrations.currentWorkShiftAssignment.template']);

        return view('timesheets.select-registrations', [
            'person' => $person,
        ]);
    }

    /**
     * Gera cartões de ponto para os vínculos selecionados
     */
    public function generateMultiple(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'registration_ids' => 'required|array|min:1',
            'registration_ids.*' => 'exists:employee_registrations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'registration_ids.required' => 'Por favor, selecione pelo menos um vínculo.',
            'registration_ids.min' => 'Por favor, selecione pelo menos um vínculo.',
            'start_date.required' => 'Por favor, informe a data inicial.',
            'end_date.required' => 'Por favor, informe a data final.',
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ]);

        $person = Person::findOrFail($request->person_id);
        $registrations = EmployeeRegistration::with(['person', 'establishment', 'department', 'currentWorkShiftAssignment.template'])
            ->whereIn('id', $request->registration_ids)
            ->get();

        if ($registrations->isEmpty()) {
            return back()->with('error', 'Nenhum vínculo encontrado.');
        }

        // Se apenas um vínculo, redireciona para visualização individual
        if ($registrations->count() === 1) {
            return redirect()->route('timesheets.show-registration', [
                'registration' => $registrations->first()->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        }

        // Múltiplos vínculos: gerar ZIP com PDFs
        return $this->generateZipForRegistrations($registrations, $request->start_date, $request->end_date, $person);
    }

    /**
     * Exibe cartão de ponto de um vínculo específico
     */
    public function showRegistration(Request $request, EmployeeRegistration $registration)
    {
        $registration->load(['person', 'establishment', 'department', 'currentWorkShiftAssignment.template']);

        $data = $this->timesheetService->generate(
            $registration,
            $request->start_date,
            $request->end_date
        );

        return view('timesheets.show', $data);
    }

    /**
     * Gera ZIP com PDFs de múltiplos vínculos
     */
    protected function generateZipForRegistrations($registrations, string $startDate, string $endDate, Person $person)
    {
        $pdfs = [];

        foreach ($registrations as $registration) {
            try {
                // Gerar dados do cartão de ponto
                $data = $this->timesheetService->generate($registration, $startDate, $endDate);

                // Renderizar HTML
                $html = view('timesheets.pdf', $data)->render();

                // Converter para PDF
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Arial',
                    'dpi' => 96,
                ]);

                // Nome do arquivo
                $fileName = $this->sanitizeFileName($registration->matricula . '_' . $registration->position) . 
                           '_' . str_replace('-', '', $startDate) . 
                           '_' . str_replace('-', '', $endDate) . '.pdf';

                $pdfs[] = [
                    'filename' => $fileName,
                    'content' => $pdf->output(),
                ];

            } catch (\Exception $e) {
                \Log::error("Erro ao gerar PDF para vínculo {$registration->id}: {$e->getMessage()}");
            }
        }

        if (empty($pdfs)) {
            return back()->with('error', 'Não foi possível gerar nenhum cartão de ponto.');
        }

        // Criar ZIP
        $zipName = $this->sanitizeFileName($person->full_name) . '_cartoes_' . str_replace('-', '', $startDate);
        $zipPath = $this->zipService->createZipFromPdfs($pdfs, $zipName);

        // Download
        return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);
    }

    /**
     * DEPRECATED: Mantido por compatibilidade
     * Use generateMultiple() para novo fluxo
     */
    public function downloadZip(Request $request)
    {
        return back()->with('error', 'Este método está depreciado. Use o novo fluxo de busca por pessoa.');
    }

    /**
     * Remove caracteres especiais do nome do arquivo
     */
    private function sanitizeFileName(string $name): string
    {
        // Remove acentos
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        // Remove caracteres especiais
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
        // Remove underscores duplicados
        $name = preg_replace('/_+/', '_', $name);
        // Remove underscores no início e fim
        return trim($name, '_');
    }

    /**
     * Exibe formulário para gerar cartões por departamento
     */
    public function byDepartment()
    {
        $establishments = Establishment::orderBy('corporate_name')->get();

        $departments = Department::with('establishment')
            ->withCount(['employeeRegistrations' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('name')
            ->get()
            ->groupBy('establishment_id');

        return view('timesheets.by-department', [
            'establishments' => $establishments,
            'departments'    => $departments,
        ]);
    }

    /**
     * Gera cartões de ponto para todos os funcionários de um departamento
     */
    public function generateByDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'department_id.required' => 'Selecione um departamento.',
            'department_id.exists' => 'Departamento não encontrado.',
            'start_date.required' => 'Informe a data inicial.',
            'end_date.required' => 'Informe a data final.',
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ]);

        $department = Department::findOrFail($request->department_id);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Buscar todos os vínculos ativos do departamento
        $registrations = EmployeeRegistration::with(['person', 'establishment', 'department', 'currentWorkShiftAssignment.template'])
            ->where('department_id', $department->id)
            ->where('status', 'active')
            ->get();

        if ($registrations->isEmpty()) {
            return back()->with('error', 'Nenhum funcionário ativo encontrado neste departamento.');
        }

        // Gerar PDFs
        $pdfs = [];
        $errors = [];

        foreach ($registrations as $registration) {
            try {
                // Gerar dados do cartão de ponto
                $data = $this->timesheetService->generate($registration, $startDate, $endDate);

                // Renderizar HTML
                $html = view('timesheets.pdf', $data)->render();

                // Converter para PDF
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('A4', 'portrait');
                $pdf->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Arial',
                    'dpi' => 96,
                ]);

                // Nome do arquivo
                $fileName = $this->sanitizeFileName($registration->person->full_name) . 
                           '_' . $registration->matricula . 
                           '_' . str_replace('-', '', $startDate) . 
                           '_' . str_replace('-', '', $endDate) . '.pdf';

                $pdfs[] = [
                    'filename' => $fileName,
                    'content' => $pdf->output(),
                ];

            } catch (\Exception $e) {
                \Log::error("Erro ao gerar PDF para vínculo {$registration->id}: {$e->getMessage()}");
                $errors[] = $registration->person->full_name . ' (Mat: ' . $registration->matricula . ')';
            }
        }

        if (empty($pdfs)) {
            return back()->with('error', 'Não foi possível gerar nenhum cartão de ponto. Erros: ' . implode(', ', $errors));
        }

        // Criar ZIP
        $zipName = $this->sanitizeFileName($department->name) . '_cartoes_' . str_replace('-', '', $startDate);
        $zipPath = $this->zipService->createZipFromPdfs($pdfs, $zipName);

        $successMsg = count($pdfs) . ' cartão(ões) gerado(s) com sucesso.';
        if (!empty($errors)) {
            $successMsg .= ' Erros em: ' . implode(', ', $errors);
        }

        // Download
        return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);
    }

    /**
     * Exporta cartão de ponto individual em XLS (corrigido para não depender de 'period')
     */
    public function exportRegistrationXls(Request $request, EmployeeRegistration $registration)
    {
        $registration->load(['person', 'establishment', 'department', 'currentWorkShiftAssignment.template']);
        $data = $this->timesheetService->generate(
            $registration,
            $request->start_date,
            $request->end_date
        );

        $start = \Carbon\Carbon::parse($data['startDate']);
        $end = \Carbon\Carbon::parse($data['endDate']);
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->copy()->addDay());

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cartão de Ponto');

        // Cabeçalho
        $sheet->setCellValue('A1', 'Colaborador:');
        $sheet->setCellValue('B1', $registration->person->full_name);
        $sheet->setCellValue('A2', 'Matrícula:');
        $sheet->setCellValue('B2', $registration->matricula);
        $sheet->setCellValue('A3', 'Período:');
        $sheet->setCellValue('B3', $data['startDate'] . ' a ' . $data['endDate']);
        $sheet->setCellValue('A4', 'Departamento:');
        $sheet->setCellValue('B4', $registration->department->name ?? '-');
        $sheet->setCellValue('A5', 'Estabelecimento:');
        $sheet->setCellValue('B5', $registration->establishment->corporate_name ?? '-');
        $sheet->setCellValue('A6', 'Jornada:');
        $sheet->setCellValue('B6', $registration->currentWorkShiftAssignment && $registration->currentWorkShiftAssignment->template ? $registration->currentWorkShiftAssignment->template->name : '-');

        // Tabela de dias
        $sheet->setCellValue('A8', 'Data');
        $sheet->setCellValue('B8', 'Dia Semana');
        $sheet->setCellValue('C8', 'Batidas');
        $sheet->setCellValue('D8', 'Trabalhado (min)');
        $sheet->setCellValue('E8', 'Esperado (min)');
        $sheet->setCellValue('F8', 'Extra (min)');
        $sheet->setCellValue('G8', 'Falta (min)');

        $row = 9;
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $sheet->setCellValue('A'.$row, $date->format('d/m/Y'));
            $sheet->setCellValue('B'.$row, __(ucfirst($date->format('l'))));
            $batidas = $data['dailyRecords'][$dateStr] ?? collect();
            $batidasStr = method_exists($batidas, 'map') ? $batidas->map(function($rec) { return $rec->record_time; })->implode(' | ') : '';
            $sheet->setCellValue('C'.$row, $batidasStr);
            $sheet->setCellValue('D'.$row, $data['calculations'][$dateStr]['worked'] ?? 0);
            $sheet->setCellValue('E'.$row, $data['calculations'][$dateStr]['expected'] ?? 0);
            $sheet->setCellValue('F'.$row, $data['calculations'][$dateStr]['overtime'] ?? 0);
            $sheet->setCellValue('G'.$row, $data['calculations'][$dateStr]['absence'] ?? 0);
            $row++;
        }

        $fileName = $this->sanitizeFileName($registration->person->full_name . '_' . $registration->matricula . '_cartao_ponto') . '.xls';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $xlsData = ob_get_clean();
        return response($xlsData, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
