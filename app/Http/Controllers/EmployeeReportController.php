<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\Establishment;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EmployeeReportController extends Controller
{
    public function index()
    {
        $establishments = Establishment::orderBy('corporate_name')->get();
        $departments = Department::orderBy('name')->get();
        
        return view('reports.employees.index', compact('establishments', 'departments'));
    }

    public function generate(Request $request)
    {
        $query = EmployeeRegistration::with(['person', 'establishment', 'department'])
            ->join('people', 'employee_registrations.person_id', '=', 'people.id');

        // Filtros
        if ($request->filled('establishment_id')) {
            $query->where('employee_registrations.establishment_id', $request->establishment_id);
        }

        if ($request->filled('department_id')) {
            $query->where('employee_registrations.department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('employee_registrations.termination_date', null);
            } elseif ($request->status === 'terminated') {
                $query->whereNotNull('employee_registrations.termination_date');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('people.full_name', 'like', "%{$search}%")
                  ->orWhere('people.cpf', 'like', "%{$search}%")
                  ->orWhere('employee_registrations.matricula', 'like', "%{$search}%");
            });
        }

        // Campos selecionados
        $fields = $request->input('fields', [
            'full_name', 'cpf', 'matricula', 'position', 
            'establishment', 'department', 'admission_date', 'status'
        ]);

        // Ordenação
        $sortBy = $request->input('sort_by', 'people.full_name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        $query->select('employee_registrations.*')
              ->orderBy($sortBy, $sortOrder);

        $registrations = $query->get();

        // Formato de saída
        if ($request->format === 'csv') {
            return $this->exportCsv($registrations, $fields);
        }

        return view('reports.employees.result', compact('registrations', 'fields', 'request'));
    }

    protected function exportCsv($registrations, $fields)
    {
        $filename = 'relacao_colaboradores_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($registrations, $fields) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalho
            $headerRow = [];
            foreach ($fields as $field) {
                $headerRow[] = $this->getFieldLabel($field);
            }
            fputcsv($file, $headerRow, ';');
            
            // Dados
            foreach ($registrations as $registration) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = $this->getFieldValue($registration, $field);
                }
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    protected function getFieldLabel($field)
    {
        $labels = [
            'full_name' => 'Nome Completo',
            'cpf' => 'CPF',
            'rg' => 'RG',
            'birth_date' => 'Data de Nascimento',
            'gender' => 'Sexo',
            'email' => 'E-mail',
            'phone' => 'Telefone',
            'address' => 'Endereço',
            'city' => 'Cidade',
            'state' => 'Estado',
            'zip_code' => 'CEP',
            'pis_pasep' => 'PIS/PASEP',
            'ctps' => 'CTPS',
            'matricula' => 'Matrícula',
            'position' => 'Cargo',
            'establishment' => 'Estabelecimento',
            'department' => 'Departamento',
            'admission_date' => 'Data de Admissão',
            'termination_date' => 'Data de Desligamento',
            'status' => 'Status',
            'work_shift' => 'Jornada de Trabalho',
        ];

        return $labels[$field] ?? $field;
    }

    protected function getFieldValue($registration, $field)
    {
        switch ($field) {
            case 'full_name':
                return $registration->person->full_name;
            case 'cpf':
                return $registration->person->cpf_formatted ?? $registration->person->cpf;
            case 'rg':
                return $registration->person->rg ?? '-';
            case 'birth_date':
                return $registration->person->birth_date ? $registration->person->birth_date->format('d/m/Y') : '-';
            case 'gender':
                return $registration->person->gender === 'M' ? 'Masculino' : ($registration->person->gender === 'F' ? 'Feminino' : 'Outro');
            case 'email':
                return $registration->person->email ?? '-';
            case 'phone':
                return $registration->person->phone ?? '-';
            case 'address':
                return $registration->person->address ?? '-';
            case 'city':
                return $registration->person->city ?? '-';
            case 'state':
                return $registration->person->state ?? '-';
            case 'zip_code':
                return $registration->person->zip_code ?? '-';
            case 'pis_pasep':
                return $registration->person->pis_pasep_formatted ?? $registration->person->pis_pasep ?? '-';
            case 'ctps':
                return $registration->person->ctps ?? '-';
            case 'matricula':
                return $registration->matricula ?? '-';
            case 'position':
                return $registration->position ?? '-';
            case 'establishment':
                return $registration->establishment->corporate_name ?? '-';
            case 'department':
                return $registration->department->name ?? '-';
            case 'admission_date':
                return $registration->admission_date ? $registration->admission_date->format('d/m/Y') : '-';
            case 'termination_date':
                return $registration->termination_date ? $registration->termination_date->format('d/m/Y') : '-';
            case 'status':
                return $registration->termination_date ? 'Desligado' : 'Ativo';
            case 'work_shift':
                $assignment = $registration->currentWorkShiftAssignment;
                return $assignment ? $assignment->template->name : '-';
            default:
                return '-';
        }
    }
}
