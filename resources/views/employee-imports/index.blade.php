@extends('layouts.main')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-file-import text-blue-600 mr-3"></i>Importações de Colaboradores
            </h1>
            <p class="text-gray-600 mt-2">Histórico de importações de colaboradores via CSV</p>
        </div>
        <div class="flex gap-3">
            <a href="/modelo-importacao-colaboradores.txt" download class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition">
                <i class="fas fa-file-alt mr-2"></i>Baixar Modelo TXT (AFD)
            </a>
            <a href="{{ route('employee-imports.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition">
                <i class="fas fa-plus mr-2"></i>Nova Importação
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">Total</p>
                    <p class="text-3xl font-bold">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-file-csv text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">Concluídas</p>
                    <p class="text-3xl font-bold">{{ $stats['completed'] }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium mb-1">Processando</p>
                    <p class="text-3xl font-bold">{{ $stats['processing'] }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-spinner text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium mb-1">Com Erro</p>
                    <p class="text-3xl font-bold">{{ $stats['failed'] }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-6 py-4 font-semibold text-gray-700">Arquivo</th>
                        <th class="text-center px-6 py-4 font-semibold text-gray-700">Status</th>
                        <th class="text-center px-6 py-4 font-semibold text-gray-700">Total</th>
                        <th class="text-center px-6 py-4 font-semibold text-gray-700">Sucesso</th>
                        <th class="text-center px-6 py-4 font-semibold text-gray-700">Atualizados</th>
                        <th class="text-center px-6 py-4 font-semibold text-gray-700">Erros</th>
                        <th class="text-left px-6 py-4 font-semibold text-gray-700">Data</th>
                        <th class="text-right px-6 py-4 font-semibold text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($imports as $import)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white mr-3 shadow">
                                    <i class="fas fa-file-csv"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $import->original_filename }}</p>
                                    <p class="text-xs text-gray-500">ID: {{ $import->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($import->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-2"></i>Pendente
                                </span>
                            @elseif($import->status === 'processing')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Processando
                                </span>
                            @elseif($import->status === 'completed')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-2"></i>Concluído
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-exclamation-circle mr-2"></i>Falhou
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-semibold text-gray-900">{{ $import->total_rows }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 text-sm font-medium text-green-800">
                                <i class="fas fa-check mr-1"></i>{{ $import->success_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 text-sm font-medium text-blue-800">
                                <i class="fas fa-sync mr-1"></i>{{ $import->updated_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 text-sm font-medium text-red-800">
                                <i class="fas fa-times mr-1"></i>{{ $import->error_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-calendar text-gray-400 mr-2"></i>
                                {{ $import->created_at->format('d/m/Y H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('employee-imports.show', $import) }}" class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded transition" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i class="fas fa-file-csv text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">Nenhuma importação realizada</p>
                            <a href="{{ route('employee-imports.create') }}" class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-plus mr-2"></i>Realizar primeira importação
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($imports->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $imports->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Auto-refresh for processing imports -->
<script>
    // Auto-refresh page every 5 seconds if there are processing imports
    const hasProcessing = {{ $imports->where('status', 'processing')->count() > 0 ? 'true' : 'false' }};
    if (hasProcessing) {
        setTimeout(() => {
            window.location.reload();
        }, 5000);
    }
</script>
@endsection
