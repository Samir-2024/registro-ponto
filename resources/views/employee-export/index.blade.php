@extends('layouts.main')

@section('title', 'Exportar Colaboradores')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex items-center">
        <a href="{{ route('exports.index') }}" class="mr-4 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Configurar Exportação</h2>
            <p class="text-gray-500">Colaboradores (rep_colaboradores)</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8">
            <form id="exportForm" action="{{ route('employee-export.download') }}" method="GET" class="space-y-6">
                <input type="hidden" name="export_type" id="export_type" value="csv">
                
                <!-- Filter: Secretaria -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Secretaria (Departamento)</label>
                    <div class="relative">
                        <select name="department_id" class="block w-full bg-gray-50 border-0 rounded-xl px-4 py-4 text-gray-900 focus:ring-2 focus:ring-blue-500 transition-all appearance-none cursor-pointer">
                            <option value="">Todas as Secretarias</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>

                <!-- Filter: CPFs -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Filtrar por CPFs (opcional)</label>
                    <p class="text-xs text-gray-400 mb-3">Insira um ou mais CPFs separados por linha ou vírgula.</p>
                    <textarea 
                        name="cpfs" 
                        rows="4" 
                        placeholder="Ex: 12345678901, 98765432100"
                        class="block w-full bg-gray-50 border-0 rounded-xl px-4 py-4 text-gray-900 focus:ring-2 focus:ring-blue-500 transition-all resize-none font-mono text-sm"
                    ></textarea>
                </div>

                <!-- Filter: Simple Name Search -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Nome do Colaborador (opcional)</label>
                    <input 
                        type="text" 
                        name="name" 
                        placeholder="Pesquisar por nome..."
                        class="block w-full bg-gray-50 border-0 rounded-xl px-4 py-4 text-gray-900 focus:ring-2 focus:ring-blue-500 transition-all"
                    >
                </div>

                <!-- Buttons -->
                <div class="pt-6 grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100">
                    <button type="button" onclick="submitExport('csv')" class="flex items-center justify-center px-8 py-4 bg-gray-900 text-white font-bold rounded-xl hover:bg-black transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-file-csv mr-2"></i> EXPORTAR CSV
                    </button>
                    <button type="button" onclick="submitExport('txt')" class="flex items-center justify-center px-8 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-file-alt mr-2"></i> EXPORTAR TXT
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100">
            <p class="text-xs text-gray-400">
                <i class="fas fa-info-circle mr-1"></i> Os arquivos serão gerados com o nome padrão <strong>rep_colaboradores</strong>.
            </p>
        </div>
    </div>
</div>

<script>
function formatCpfs() {
    let textarea = document.querySelector('textarea[name="cpfs"]');
    if (!textarea) return;
    let raw = textarea.value;
    let parts = raw.split(/[\s,;]+/);
    let formatted = parts.map(p => {
        let cleaned = p.replace(/\D/g, '');
        if (cleaned.length === 11) {
            return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
        }
        return p;
    }).filter(p => p.trim() !== '').join('\n');
    textarea.value = formatted;
}

function submitExport(type) {
    formatCpfs(); // Garante a máscara ao clicar
    document.getElementById('export_type').value = type;
    document.getElementById('exportForm').submit();
}

// Helper to format CPFs in textarea (manter blur para feedback visual)
document.querySelector('textarea[name="cpfs"]').addEventListener('blur', formatCpfs);
</script>
@endsection

