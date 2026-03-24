@extends('layouts.main')

@section('title', 'Exportar Empregador')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold text-blue-900 mb-4 flex items-center">
        <i class="fas fa-file-export mr-2"></i> Exportar Empregador
    </h2>
    <form action="{{ route('employer-export.download') }}" method="GET" class="space-y-4">
        <!-- Filtros funcionais -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Secretaria</label>
                <select name="department_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Todas</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                <input type="text" name="cpf" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                <input type="text" name="cnpj" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Razão Social</label>
                <input type="text" name="razao_social" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="block w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Todos</option>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>
        </div>
        <div class="flex space-x-2 mt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                <i class="fas fa-file-export mr-2"></i> Exportar CSV
            </button>
            <button type="button" onclick="exportTxt()" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                <i class="fas fa-file-export mr-2"></i> Exportar TXT
            </button>
        </div>
    </form>
    <form id="txt-export-form" action="{{ route('employer-export.download') }}" method="GET" style="display:none;">
        <input type="hidden" name="department_id">
        <input type="hidden" name="cpf">
        <input type="hidden" name="cnpj">
        <input type="hidden" name="razao_social">
        <input type="hidden" name="status">
        <input type="hidden" name="export_type" value="txt">
    </form>
    <script>
    function exportTxt() {
        // Copia os filtros do formulário principal para o formulário oculto
        var mainForm = document.querySelector('form[action="{{ route('employer-export.download') }}"]');
        var txtForm = document.getElementById('txt-export-form');
        txtForm.department_id.value = mainForm.department_id.value;
        txtForm.cpf.value = mainForm.cpf.value;
        txtForm.cnpj.value = mainForm.cnpj.value;
        txtForm.razao_social.value = mainForm.razao_social.value;
        txtForm.status.value = mainForm.status.value;
        txtForm.submit();
    }
    </script>
</div>
@endsection
