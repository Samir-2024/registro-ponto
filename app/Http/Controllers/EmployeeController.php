<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\Establishment;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Lista todas as pessoas com seus vínculos
     */
    public function index(Request $request)
    {
        $query = Person::withCount(['employeeRegistrations', 'activeRegistrations'])
            ->with(['activeRegistrations' => function($q) {
                $q->with(['establishment', 'department'])->orderBy('created_at', 'desc');
            }]);

        // Busca por nome ou CPF
        if ($request->filled('search')) {
            $search = $request->search;
            $cleanSearch = preg_replace('/[^0-9]/', '', $search);
            
            $query->where(function($q) use ($search, $cleanSearch) {
                $q->where('full_name', 'ILIKE', "%{$search}%");
                
                // Se tem números suficientes, buscar por CPF
                if (strlen($cleanSearch) >= 11) {
                    $q->orWhere('cpf', $cleanSearch);
                }
            });
        }

        // Filtro por estabelecimento (via vínculos ativos)
        if ($request->filled('establishment_id')) {
            $query->whereHas('activeRegistrations', function($q) use ($request) {
                $q->where('establishment_id', $request->establishment_id);
            });
        }

        // Filtro por departamento (via vínculos ativos)
        if ($request->filled('department_id')) {
            $query->whereHas('activeRegistrations', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filtro: pessoas sem vínculos ativos
        if ($request->filled('without_registrations') && $request->without_registrations == '1') {
            $query->doesntHave('activeRegistrations');
        }

        // Filtro: vínculos sem jornada
        if ($request->filled('without_workshift') && $request->without_workshift == '1') {
            $query->whereHas('activeRegistrations', function($q) {
                $q->doesntHave('currentWorkShiftAssignment');
            });
        }

        $people = $query->orderBy('full_name')->paginate(50);
        $establishments = Establishment::orderBy('corporate_name')->get();
        $departments = Department::orderBy('name')->get();

        return view('employees.index', compact('people', 'establishments', 'departments'));
    }

    /**
     * Form para criar nova pessoa (+ primeiro vínculo opcional)
     */
    public function create()
    {
        $establishments = Establishment::orderBy('corporate_name')->get();
        $departments = Department::orderBy('name')->get();
        return view('employees.create', compact('establishments', 'departments'));
    }

    /**
     * Criar nova pessoa e opcionalmente criar primeiro vínculo
     */
    public function store(Request $request)
    {
        // Limpar CPF e PIS/PASEP ANTES da validação
        $request->merge([
            'cpf' => preg_replace('/[^0-9]/', '', $request->cpf),
            'pis_pasep' => $request->pis_pasep ? preg_replace('/[^0-9]/', '', $request->pis_pasep) : null,
        ]);

        $validated = $request->validate([
            // Dados da Pessoa
            'full_name' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:people,cpf',
            'pis_pasep' => 'nullable|string|max:15|unique:people,pis_pasep',
            'ctps' => 'nullable|string|max:20',
            
            // Dados do Primeiro Vínculo (opcional)
            'create_registration' => 'nullable|boolean',
            'matricula' => 'required_if:create_registration,1|nullable|string|max:20|unique:employee_registrations,matricula',
            'establishment_id' => 'required_if:create_registration,1|nullable|exists:establishments,id',
            'department_id' => 'nullable|exists:departments,id',
            'admission_date' => 'required_if:create_registration,1|nullable|date',
            'position' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {

            // Criar pessoa
            $person = Person::create([
                'full_name' => $validated['full_name'],
                'cpf' => $validated['cpf'],
                'pis_pasep' => $validated['pis_pasep'] ?? null,
                'ctps' => $validated['ctps'] ?? null,
            ]);

            // Se marcou para criar vínculo, criar
            if ($request->boolean('create_registration')) {
                EmployeeRegistration::create([
                    'person_id' => $person->id,
                    'matricula' => $validated['matricula'],
                    'establishment_id' => $validated['establishment_id'],
                    'department_id' => $validated['department_id'] ?? null,
                    'admission_date' => $validated['admission_date'],
                    'position' => $validated['position'] ?? null,
                    'status' => 'active',
                ]);
            }

            DB::commit();

            return redirect()->route('employees.show', $person)
                ->with('success', 'Pessoa criada com sucesso!' . ($request->boolean('create_registration') ? ' Vínculo também criado.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Erro ao criar pessoa: ' . $e->getMessage());
        }
    }

    /**
     * Exibe detalhes da pessoa e todos os seus vínculos
     */
    public function show(Person $person)
    {
        $person->load([
            'employeeRegistrations' => function($q) {
                $q->with(['establishment', 'department', 'currentWorkShiftAssignment.template'])
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderBy('created_at', 'desc');
            }
        ]);
        
        return view('employees.show', compact('person'));
    }

    /**
     * Form para editar dados pessoais
     */
    public function edit(Person $person)
    {
        return view('employees.edit', compact('person'));
    }

    /**
     * Atualizar dados pessoais da pessoa
     */
    public function update(Request $request, Person $person)
    {
        // Limpar CPF e PIS/PASEP ANTES da validação
        $request->merge([
            'cpf' => preg_replace('/[^0-9]/', '', $request->cpf),
            'pis_pasep' => $request->pis_pasep ? preg_replace('/[^0-9]/', '', $request->pis_pasep) : null,
        ]);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:people,cpf,' . $person->id,
            'pis_pasep' => 'nullable|string|max:15|unique:people,pis_pasep,' . $person->id,
            'ctps' => 'nullable|string|max:20',
        ]);

        $person->update($validated);

        return redirect()->route('employees.show', $person)
            ->with('success', 'Dados pessoais atualizados com sucesso!');
    }

    /**
     * Excluir pessoa e todos os seus vínculos
     */
    public function destroy(Person $person)
    {
        $registrationsCount = $person->employeeRegistrations()->count();
        
        // Verificar se tem vínculos com registros de ponto
        $hasTimeRecords = DB::table('time_records')
            ->whereIn('employee_registration_id', function($query) use ($person) {
                $query->select('id')
                    ->from('employee_registrations')
                    ->where('person_id', $person->id);
            })
            ->exists();

        if ($hasTimeRecords) {
            return back()->with('error', 'Não é possível excluir esta pessoa pois possui registros de ponto vinculados.');
        }

        $person->delete(); // Cascade irá deletar os vínculos

        return redirect()->route('employees.index')
            ->with('success', 'Pessoa excluída com sucesso!' . ($registrationsCount > 0 ? " ({$registrationsCount} vínculo(s) também removido(s))" : ''));
    }
}
