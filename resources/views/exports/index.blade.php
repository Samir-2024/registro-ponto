@extends('layouts.main')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            <i class="fas fa-file-export text-blue-600 mr-3"></i>Central de Exportações
        </h1>
        <p class="text-gray-600">Escolha o tipo de arquivo que deseja gerar para o seu sistema REP.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Option 1: Employer -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 hover:shadow-xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 transition-colors duration-300">
                <i class="fas fa-building text-3xl text-blue-600 group-hover:text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Exportar Empregador</h3>
            <p class="text-gray-500 mb-8 text-sm leading-relaxed">
                Gere o arquivo de dados da empresa no padrão REP para importação em relógios de ponto.
            </p>
            <a href="{{ route('employer-export.download-direct') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-100">
                <i class="fas fa-download mr-2"></i>Baixar TXT
            </a>
        </div>

        <!-- Option 2: Employees -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 hover:shadow-xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-green-600 transition-colors duration-300">
                <i class="fas fa-users text-3xl text-green-600 group-hover:text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Exportar Colaboradores</h3>
            <p class="text-gray-500 mb-8 text-sm leading-relaxed">
                Filtre colaboradores por secretaria ou CPF específicos para gerar a lista de funcionários.
            </p>
            <a href="{{ route('employee-export.index') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors shadow-lg shadow-green-100">
                <i class="fas fa-filter mr-2"></i>Configurar Filtros
            </a>
        </div>

        <!-- Option 3: Export by Department (Quick) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 hover:shadow-xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 transition-colors duration-300">
                <i class="fas fa-sitemap text-3xl text-purple-600 group-hover:text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Exportar por Secretaria</h3>
            <p class="text-gray-500 mb-6 text-sm leading-relaxed">
                Selecione uma secretaria para gerar o arquivo de colaboradores (`rep_colaborador`) rapidamente.
            </p>
            
            <form action="{{ route('exports.download-by-department') }}" method="GET" class="space-y-4">
                <div class="relative">
                    <select name="department_id" required class="block w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm text-gray-900 focus:ring-2 focus:ring-purple-500 transition-all appearance-none cursor-pointer">
                        <option value="">Selecione a Secretaria...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center justify-center w-full px-6 py-3 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors shadow-lg hover:shadow-purple-200">
                    <i class="fas fa-download mr-2"></i>Baixar TXT
                </button>
            </form>
        </div>
    </div>

    <!-- Help Card -->
    <div class="mt-12 bg-blue-50 rounded-2xl p-8 flex items-start">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
            <i class="fas fa-lightbulb text-blue-600"></i>
        </div>
        <div>
            <h4 class="font-bold text-blue-900">Precisa de ajuda com os formatos?</h4>
            <p class="text-blue-800 text-sm mt-1">
                Os arquivos gerados são compatíveis com os principais modelos de relógio (Henry Prisma, Orion 5, etc). 
                Caso precise de um formato customizado, entre em contato com o suporte técnico.
            </p>
        </div>
    </div>
</div>
@endsection
