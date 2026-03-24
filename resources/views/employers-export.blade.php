@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-briefcase text-blue-600 mr-3"></i>Exportar Empregadores
    </h1>
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-search mr-1"></i>Buscar por Nome/Razão Social
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Digite o nome ou razão social..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-1"></i>CNPJ
                    </label>
                    <input type="text" name="cnpj" value="{{ request('cnpj') }}" placeholder="CNPJ..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>Cidade
                    </label>
                    <input type="text" name="city" value="{{ request('city') }}" placeholder="Cidade..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Limpar
                </a>
                <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-file-alt mr-2"></i>Exportar DIXI
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Empregadores (exemplo, ajuste conforme seu controller) -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Razão Social</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">CNPJ</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Cidade</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($employers ?? [] as $employer)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">{{ $employer->corporate_name }}</td>
                        <td class="px-6 py-4">{{ $employer->cnpj }}</td>
                        <td class="px-6 py-4">{{ $employer->city }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-8 text-gray-500">Nenhum empregador encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
