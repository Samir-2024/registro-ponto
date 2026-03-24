@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-users text-blue-600 mr-3"></i>Exportar Colaboradores
    </h1>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-search mr-1"></i>Buscar por Nome ou CPF
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Digite o nome ou CPF..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-building mr-1"></i>Estabelecimento
                    </label>
                    <input type="text" name="establishment" value="{{ request('establishment') }}" placeholder="Estabelecimento..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sitemap mr-1"></i>Departamento
                    </label>
                    <input type="text" name="department" value="{{ request('department') }}" placeholder="Departamento..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Limpar
                </a>
                <a href="{{ route('employees.exportDixi', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-file-alt mr-2"></i>Exportar DIXI
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Colaboradores (exemplo, ajuste conforme seu controller) -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">CPF</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Estabelecimento</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Departamento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($colaborators ?? [] as $colaborator)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">{{ $colaborator->full_name }}</td>
                        <td class="px-6 py-4">{{ $colaborator->cpf }}</td>
                        <td class="px-6 py-4">{{ $colaborator->establishment->corporate_name ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $colaborator->department->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-8 text-gray-500">Nenhum colaborador encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
