@extends('layouts.main')

@section('content')
<div class="mb-6">
    <div class="mb-6">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center mb-4">
            <i class="fas fa-arrow-left mr-2"></i>Voltar para busca individual
        </a>

        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-building text-purple-600 mr-3"></i>Cartões de Ponto por Secretaria / Departamento
        </h1>
        <p class="text-gray-600 mt-2">Gere cartões de ponto para todos os funcionários ativos de uma secretaria ou departamento</p>
    </div>

    <div class="max-w-4xl mx-auto w-full">
        <form action="{{ route('timesheets.generate-by-department') }}" method="POST">
            @csrf

            {{-- Filtro de Estabelecimento --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-city text-blue-600 mr-2"></i>
                    1. Selecione o Estabelecimento
                </h2>
                <select id="establishment_filter" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">-- Todos os estabelecimentos --</option>
                    @foreach($establishments as $est)
                        <option value="{{ $est->id }}">{{ $est->corporate_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Seleção de Departamento/Secretaria --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-sitemap text-purple-600 mr-2"></i>
                    2. Selecione a Secretaria / Departamento
                </h2>

                <div id="departments-list" class="space-y-6">
                    @foreach($establishments as $est)
                        @php $depts = $departments->get($est->id, collect()); @endphp
                        @if($depts->isNotEmpty())
                            <div class="establishment-group" data-establishment="{{ $est->id }}">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">
                                    <i class="fas fa-building mr-1"></i>{{ $est->corporate_name }}
                                </p>
                                <div class="space-y-2">
                                    @foreach($depts as $department)
                                        <label class="flex items-center justify-between p-4 border-2 rounded-lg cursor-pointer hover:bg-purple-50 transition department-item border-gray-200">
                                            <div class="flex items-center">
                                                <input
                                                    type="radio"
                                                    name="department_id"
                                                    value="{{ $department->id }}"
                                                    class="department-radio w-5 h-5 text-purple-600"
                                                    required
                                                >
                                                <span class="ml-4 font-semibold text-gray-900">{{ $department->name }}</span>
                                            </div>
                                            <div>
                                                @if($department->employee_registrations_count > 0)
                                                    <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full">
                                                        <i class="fas fa-users mr-1"></i>{{ $department->employee_registrations_count }} ativo(s)
                                                    </span>
                                                @else
                                                    <span class="bg-gray-100 text-gray-500 text-sm font-semibold px-3 py-1 rounded-full">
                                                        <i class="fas fa-user-slash mr-1"></i>Sem ativos
                                                    </span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Departamentos sem estabelecimento (fallback) --}}
                    @php $semEst = $departments->get(null, collect()); @endphp
                    @if($semEst->isNotEmpty())
                        <div class="establishment-group" data-establishment="none">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Sem estabelecimento</p>
                            <div class="space-y-2">
                                @foreach($semEst as $department)
                                    <label class="flex items-center justify-between p-4 border-2 rounded-lg cursor-pointer hover:bg-purple-50 transition department-item border-gray-200">
                                        <div class="flex items-center">
                                            <input type="radio" name="department_id" value="{{ $department->id }}" class="department-radio w-5 h-5 text-purple-600" required>
                                            <span class="ml-4 font-semibold text-gray-900">{{ $department->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <p id="no-departments-msg" class="text-gray-500 text-center py-4 hidden">
                        <i class="fas fa-info-circle mr-2"></i>Nenhum departamento encontrado para o estabelecimento selecionado.
                    </p>
                </div>
            </div>

            {{-- Período --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-calendar text-blue-600 mr-2"></i>
                    3. Período
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data Inicial *</label>
                        <input type="date" name="start_date"
                            value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data Final *</label>
                        <input type="date" name="end_date"
                            value="{{ old('end_date', now()->endOfMonth()->format('Y-m-d')) }}"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            {{-- Aviso --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-3"></i>
                    <p class="text-sm text-yellow-700">
                        Será gerado um arquivo <strong>ZIP</strong> com os cartões de ponto de todos os funcionários ativos da secretaria/departamento selecionado. Dependendo da quantidade, este processo pode demorar alguns segundos.
                    </p>
                </div>
            </div>

            {{-- Botões --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('timesheets.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-lg shadow-lg transition border border-gray-300">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition">
                    <i class="fas fa-file-archive mr-2"></i>Gerar Cartões (ZIP)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterSelect = document.getElementById('establishment_filter');
    const groups = document.querySelectorAll('.establishment-group');
    const noMsg = document.getElementById('no-departments-msg');

    // Destaque ao selecionar rádio
    document.querySelectorAll('.department-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.department-item').forEach(item => {
                item.classList.remove('border-purple-500', 'bg-purple-50');
                item.classList.add('border-gray-200');
            });
            this.closest('.department-item').classList.add('border-purple-500', 'bg-purple-50');
            this.closest('.department-item').classList.remove('border-gray-200');
        });
    });

    // Filtro por estabelecimento
    filterSelect.addEventListener('change', function () {
        const selected = this.value;
        let visibleCount = 0;

        groups.forEach(group => {
            const estId = group.dataset.establishment;
            if (!selected || estId == selected) {
                group.style.display = '';
                visibleCount++;
            } else {
                group.style.display = 'none';
                // Desmarcar rádios ocultos
                group.querySelectorAll('.department-radio').forEach(r => r.checked = false);
                group.querySelectorAll('.department-item').forEach(item => {
                    item.classList.remove('border-purple-500', 'bg-purple-50');
                    item.classList.add('border-gray-200');
                });
            }
        });

        noMsg.classList.toggle('hidden', visibleCount > 0);
    });
});
</script>
@endsection
