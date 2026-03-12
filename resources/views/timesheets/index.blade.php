@extends('layouts.main')

@section('content')
<div class="mb-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-calendar-check text-blue-600 mr-3"></i>Cartão de Ponto
        </h1>
        <p class="text-gray-600 mt-2">Busque uma pessoa e selecione os vínculos (matrículas) para gerar cartões de ponto</p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="max-w-4xl mx-auto w-full">
        <!-- Opções de Geração -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 border-2 border-blue-500">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Por Pessoa</h3>
                        <p class="text-sm text-gray-600">Buscar pessoa e selecionar vínculos</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('timesheets.by-department') }}" class="bg-white rounded-lg shadow p-4 border-2 border-gray-200 hover:border-purple-500 hover:bg-purple-50 transition">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-building text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Por Departamento</h3>
                        <p class="text-sm text-gray-600">Gerar para todos do departamento</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Painel de Busca de Pessoa -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-search text-blue-600 mr-2"></i>
                Buscar Pessoa
            </h2>

            <form action="{{ route('timesheets.search-person') }}" method="POST">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-user text-blue-600 mr-2"></i>
                        Pesquisar por CPF ou Nome
                    </label>
                    <input 
                        type="text" 
                        name="search" 
                        id="search"
                        value="{{ old('search') }}"
                        required
                        placeholder="Digite o CPF (123.456.789-00) ou Nome (João Silva)"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('search') border-red-500 @enderror"
                    >
                    @error('search')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Digite o CPF completo ou pelo menos 3 letras do nome
                    </p>
                </div>

                <div class="flex justify-end">
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg shadow-lg transition flex items-center"
                    >
                        <i class="fas fa-search mr-2"></i>
                        Buscar Pessoa
                    </button>
                </div>
            </form>
        </div>

        <!-- Informação sobre o fluxo -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                Como funciona?
            </h3>
            <ol class="space-y-2 text-sm text-blue-800">
                <li class="flex items-start">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">1</span>
                    <span><strong>Busque a pessoa:</strong> Digite o CPF ou nome completo/parcial</span>
                </li>
                <li class="flex items-start">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">2</span>
                    <span><strong>Veja os vínculos:</strong> O sistema exibirá todos os vínculos (matrículas) ativos da pessoa</span>
                </li>
                <li class="flex items-start">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">3</span>
                    <span><strong>Selecione os vínculos:</strong> Marque um ou mais vínculos para gerar os cartões</span>
                </li>
                <li class="flex items-start">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center mr-3 mt-0.5 flex-shrink-0">4</span>
                    <span><strong>Gere o PDF:</strong> Para vários vínculos, será gerado um arquivo ZIP com todos os cartões</span>
                </li>
            </ol>
        </div>
    </div>
</div>
@endsection
