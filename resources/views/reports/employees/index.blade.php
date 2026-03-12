@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>
                Relatório: Relação de Colaboradores
            </h1>
            <p class="text-muted">Configure os filtros e campos para gerar o relatório personalizado</p>
        </div>
    </div>

    <form action="{{ route('reports.employees.generate') }}" method="POST" id="reportForm">
        @csrf
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Estabelecimento -->
                    <div class="col-md-4">
                        <label for="establishment_id" class="form-label">Estabelecimento</label>
                        <select name="establishment_id" id="establishment_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($establishments as $establishment)
                                <option value="{{ $establishment->id }}">{{ $establishment->corporate_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Departamento -->
                    <div class="col-md-4">
                        <label for="department_id" class="form-label">Departamento</label>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="active">Ativos</option>
                            <option value="terminated">Desligados</option>
                        </select>
                    </div>

                    <!-- Busca -->
                    <div class="col-md-12">
                        <label for="search" class="form-label">Buscar por Nome, CPF ou Matrícula</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Digite para buscar...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Campos do Relatório</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Selecione os campos que deseja incluir no relatório</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2">Dados Pessoais</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="full_name" id="field_name" checked>
                            <label class="form-check-label" for="field_name">Nome Completo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="cpf" id="field_cpf" checked>
                            <label class="form-check-label" for="field_cpf">CPF</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="rg" id="field_rg">
                            <label class="form-check-label" for="field_rg">RG</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="birth_date" id="field_birth">
                            <label class="form-check-label" for="field_birth">Data de Nascimento</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="gender" id="field_gender">
                            <label class="form-check-label" for="field_gender">Sexo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="email" id="field_email">
                            <label class="form-check-label" for="field_email">E-mail</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="phone" id="field_phone">
                            <label class="form-check-label" for="field_phone">Telefone</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="pis_pasep" id="field_pis">
                            <label class="form-check-label" for="field_pis">PIS/PASEP</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="ctps" id="field_ctps">
                            <label class="form-check-label" for="field_ctps">CTPS</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2">Dados do Vínculo</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="matricula" id="field_matricula" checked>
                            <label class="form-check-label" for="field_matricula">Matrícula</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="position" id="field_position" checked>
                            <label class="form-check-label" for="field_position">Cargo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="establishment" id="field_establishment" checked>
                            <label class="form-check-label" for="field_establishment">Estabelecimento</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="department" id="field_department" checked>
                            <label class="form-check-label" for="field_department">Departamento</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="admission_date" id="field_admission" checked>
                            <label class="form-check-label" for="field_admission">Data de Admissão</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="termination_date" id="field_termination">
                            <label class="form-check-label" for="field_termination">Data de Desligamento</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="status" id="field_status" checked>
                            <label class="form-check-label" for="field_status">Status</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="work_shift" id="field_work_shift">
                            <label class="form-check-label" for="field_work_shift">Jornada de Trabalho</label>
                        </div>

                        <h6 class="fw-bold mt-3 mb-2">Endereço</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="address" id="field_address">
                            <label class="form-check-label" for="field_address">Endereço</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="city" id="field_city">
                            <label class="form-check-label" for="field_city">Cidade</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="state" id="field_state">
                            <label class="form-check-label" for="field_state">Estado</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fields[]" value="zip_code" id="field_zip">
                            <label class="form-check-label" for="field_zip">CEP</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                        <i class="fas fa-check-double me-1"></i>Selecionar Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                        <i class="fas fa-times me-1"></i>Desmarcar Todos
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-sort me-2"></i>Ordenação</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="sort_by" class="form-label">Ordenar por</label>
                        <select name="sort_by" id="sort_by" class="form-select">
                            <option value="people.full_name">Nome</option>
                            <option value="employee_registrations.matricula">Matrícula</option>
                            <option value="employee_registrations.position">Cargo</option>
                            <option value="employee_registrations.admission_date">Data de Admissão</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="sort_order" class="form-label">Ordem</label>
                        <select name="sort_order" id="sort_order" class="form-select">
                            <option value="asc">Crescente (A-Z, 0-9)</option>
                            <option value="desc">Decrescente (Z-A, 9-0)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" name="format" value="online" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>Visualizar Online
                    </button>
                    <button type="submit" name="format" value="csv" class="btn btn-success">
                        <i class="fas fa-file-csv me-2"></i>Exportar CSV
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="fields[]"]').forEach(cb => cb.checked = true);
});

document.getElementById('deselectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="fields[]"]').forEach(cb => cb.checked = false);
});
</script>
@endpush
@endsection
