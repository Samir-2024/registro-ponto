@extends('layouts.main')

@section('content')
<div class="mb-6">
    <div class="mb-6">
        <a href="{{ route('timesheets.index') }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center mb-4">
            <i class="fas fa-arrow-left mr-2"></i>Voltar para busca individual
        </a>
        
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-building text-purple-600 mr-3"></i>Cartões de Ponto por Departamento
        </h1>
        <p class="text-gray-600 mt-2">Gere cartões de ponto para todos os funcionários ativos de um departamento</p>
    </div>

    <div class="max-w-4xl mx-auto w-full">
        <form action="{{ route('timesheets.generate-by-department') }}" method="POST">
            @csrf

            <!-- Seleção de Departamento -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-sitemap text-purple-600 mr-2"></i>
                    Selecione o Departamento
                </h2>

                <div class="space-y-3">
                    @foreach($departments as $department)
                        <label class="flex items-center justify-between p-4 border-2 rounded-lg cursor-pointer hover:bg-purple-50 transition department-item {{ $loop->first ? 'border-purple-500 bg-purple-50' : 'border-gray-200' }}">
                            <div class="flex items-center">
                                <input 
                                    type="radio" 
                                    name="department_id" 
                                    value="{{ $department->id }}"
                                    class="department-radio w-5 h-5 text-purple-600"
                                    {{ $loop->first ? 'checked' : '' }}
                                    required
                                >
                                <div class="ml-4">
                                    <h3 class="font-bold text-gray-900">
                                        {{ $department->name }}
                                    </h3>
                                </div>
                            </div>
                            <div class="flex items-center">
                                @if($department->employee_registrations_count > 0)
                                    <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full">
                                        <i class="fas fa-users mr-1"></i>
                                        {{ $department->employee_registrations_count }} funcionário(s) ativo(s)
                                    </span>
                                @else
                                    <span class="bg-gray-100 text-gray-500 text-sm font-semibold px-3 py-1 rounded-full">
                                        <i class="fas fa-user-slash mr-1"></i>
                                        Sem funcionários ativos
                                    </span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Período -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-calendar text-blue-600 mr-2"></i>
                    Período
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Data Inicial *
                        </label>
                        <input 
                            type="date" 
                            name="start_date" 
                            id="start_date"
                            value="{{ old('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Data Final *
                        </label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="end_date"
                            value="{{ old('end_date', now()->endOfMonth()->format('Y-m-d')) }}"
                            required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        >
                    </div>
                </div>
            </div>

            <!-- Aviso -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-3"></i>
                    <div class="text-sm text-yellow-700">
                        <p class="font-semibold mb-1">Atenção:</p>
                        <p>Será gerado um arquivo ZIP contendo os cartões de ponto de todos os funcionários ativos do departamento selecionado. Dependendo da quantidade de funcionários, este processo pode demorar alguns segundos.</p>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex justify-end gap-3">
                <a 
                    href="{{ route('timesheets.index') }}" 
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-lg shadow-lg transition border border-gray-300"
                >
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </a>
                <button 
                    type="submit" 
                    id="generateBtn"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition"
                >
                    <i class="fas fa-file-archive mr-2"></i>
                    Gerar Cartões (ZIP)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.department-radio');
    
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Remove seleção de todos
            document.querySelectorAll('.department-item').forEach(item => {
                item.classList.remove('border-purple-500', 'bg-purple-50');
                item.classList.add('border-gray-200');
            });
            
            // Adiciona seleção ao item atual
            const item = this.closest('.department-item');
            item.classList.add('border-purple-500', 'bg-purple-50');
            item.classList.remove('border-gray-200');
        });
    });
});
</script>
@endsection
