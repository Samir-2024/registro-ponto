@extends('layouts.main')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-users text-blue-600 mr-3"></i>Pessoas e Vínculos
            </h1>
            <p class="text-gray-600 mt-2">Gerencie pessoas e seus vínculos empregatícios</p>
        </div>
        <a href="{{ route('employees.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition">
            <i class="fas fa-plus mr-2"></i>Nova Pessoa
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('employees.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Busca por Nome/CPF -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-search mr-1"></i>Buscar por Nome ou CPF
                    </label>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Digite o nome ou CPF..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                <!-- Filtro por Estabelecimento -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-building mr-1"></i>Estabelecimento
                    </label>
                    <select name="establishment_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos</option>
                        @foreach($establishments as $establishment)
                            <option value="{{ $establishment->id }}" {{ request('establishment_id') == $establishment->id ? 'selected' : '' }}>
                                {{ $establishment->corporate_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Departamento -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sitemap mr-1"></i>Departamento
                    </label>
                    <select name="department_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Filtros Especiais -->
            <div class="flex gap-4 items-center">
                <label class="flex items-center">
                    <input type="checkbox" name="without_registrations" value="1" {{ request('without_registrations') == '1' ? 'checked' : '' }} class="w-4 h-4 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Sem vínculos ativos</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="without_workshift" value="1" {{ request('without_workshift') == '1' ? 'checked' : '' }} class="w-4 h-4 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Com vínculos sem jornada</span>
                </label>
            </div>

            <!-- Botões -->
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="{{ route('employees.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-2 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Pessoas -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        @if($people->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-users text-gray-400 text-6xl mb-4"></i>
                <p class="text-xl font-semibold">Nenhuma pessoa encontrada</p>
                <p class="mt-2">Clique em "Nova Pessoa" para adicionar alguém.</p>
            </div>
        @else
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">CPF</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">PIS/PASEP</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Vínculos Ativos</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Total Vínculos</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($people as $person)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $person->full_name }}</div>
                                @if($person->activeRegistrations->isNotEmpty())
                                    <div class="text-sm text-gray-600 mt-1">
                                        @foreach($person->activeRegistrations->take(2) as $reg)
                                            <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mr-1">
                                                {{ $reg->matricula }} - {{ $reg->position }}
                                            </span>
                                        @endforeach
                                        @if($person->activeRegistrations->count() > 2)
                                            <span class="text-xs text-gray-500">+{{ $person->activeRegistrations->count() - 2 }} mais</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-700">{{ $person->cpf_formatted }}</td>
                            <td class="px-6 py-4 text-gray-700">{{ $person->pis_pasep_formatted ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($person->active_registrations_count > 0)
                                    <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full">
                                        {{ $person->active_registrations_count }}
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-sm font-semibold px-3 py-1 rounded-full">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700">
                                {{ $person->employee_registrations_count }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('employees.show', $person) }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition">
                                    <i class="fas fa-eye mr-1"></i>Ver
                                </a>
                                <a href="{{ route('employees.edit', $person) }}" class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm transition">
                                    <i class="fas fa-edit mr-1"></i>Editar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Paginação -->
            <div class="px-6 py-4 border-t">
                {{ $people->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
