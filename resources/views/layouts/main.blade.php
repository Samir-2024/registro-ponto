<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ponto Digital Assaí')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div x-data="{ sidebarOpen: true }" class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm fixed top-0 left-0 right-0 z-30">
            <div class="flex items-center justify-between h-16 px-4">
                <!-- Left: Logo and Toggle -->
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('images/brasao-assai.png') }}" alt="Brasão Assaí" class="h-10 w-10" onerror="this.style.display='none'">
                        <div>
                            <h1 class="text-lg font-bold text-blue-900">Ponto Digital Assaí</h1>
                            <p class="text-xs text-gray-500">Prefeitura Municipal de Assaí</p>
                        </div>
                    </div>
                </div>

                <!-- Right: User Menu -->
                <div x-data="{ userMenuOpen: false }" class="relative">
                    <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-3 text-gray-700 hover:text-gray-900 focus:outline-none">
                        <div class="text-right">
                            <p class="text-sm font-medium">Olá, {{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->role === 'admin' ? 'Administrador' : 'Usuário' }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="userMenuOpen" @click.away="userMenuOpen = false" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i> Meu Perfil
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i> Configurações
                        </a>
                        <hr class="my-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sair
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <aside x-show="sidebarOpen" 
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-300"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed left-0 top-16 bottom-0 w-64 bg-white shadow-lg z-20 overflow-y-auto">
            <nav class="p-4 space-y-2">
                <!-- INÍCIO -->
                <div class="mb-6">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">INÍCIO</h3>
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- CADASTROS -->
                <div x-data="{ cadastrosOpen: {{ request()->is('establishments*') || request()->is('departments*') || request()->is('employees*') || request()->is('work-shift-templates*') ? 'true' : 'false' }} }" class="mb-6">
                    <button @click="cadastrosOpen = !cadastrosOpen" class="w-full flex items-center justify-between text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 hover:text-gray-700">
                        <span>CADASTROS</span>
                        <i :class="cadastrosOpen ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-xs"></i>
                    </button>
                    <div x-show="cadastrosOpen" x-collapse class="space-y-1">
                        <a href="{{ route('establishments.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('establishments.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-building"></i>
                            <span>Estabelecimentos</span>
                        </a>
                        <a href="{{ route('departments.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('departments.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-sitemap"></i>
                            <span>Departamentos</span>
                        </a>
                        <a href="{{ route('employees.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('employees.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-users"></i>
                            <span>Colaboradores</span>
                        </a>
                        <a href="{{ route('work-shift-templates.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('work-shift-templates.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-clock"></i>
                            <span>Jornadas de Trabalho</span>
                        </a>
                    </div>
                </div>

                <!-- EQUIPAMENTOS -->
                <div x-data="{ equipamentosOpen: {{ request()->is('afd-imports*') || request()->is('employee-imports*') || request()->is('vinculo-imports*') ? 'true' : 'false' }} }" class="mb-6">
                    <button @click="equipamentosOpen = !equipamentosOpen" class="w-full flex items-center justify-between text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 hover:text-gray-700">
                        <span>EQUIPAMENTOS</span>
                        <i :class="equipamentosOpen ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-xs"></i>
                    </button>
                    <div x-show="equipamentosOpen" x-collapse class="space-y-1">
                        <a href="{{ route('afd-imports.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('afd-imports.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-file-import"></i>
                            <span>Importar AFD</span>
                        </a>
                        <a href="{{ route('exports.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('exports.*') || request()->routeIs('employee-export.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-file-export"></i>
                            <span>Exportações</span>
                        </a>
                        <a href="{{ route('vinculo-imports.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('vinculo-imports.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-link"></i>
                            <span>Importar Vínculos</span>
                        </a>
                    </div>
                </div>

                <!-- RELATÓRIOS -->
                <div x-data="{ relatoriosOpen: {{ request()->is('timesheets*') ? 'true' : 'false' }} }" class="mb-6">
                    <button @click="relatoriosOpen = !relatoriosOpen" class="w-full flex items-center justify-between text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 hover:text-gray-700">
                        <span>RELATÓRIOS</span>
                        <i :class="relatoriosOpen ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-xs"></i>
                    </button>
                    <div x-show="relatoriosOpen" x-collapse class="space-y-1">
                        <a href="{{ route('timesheets.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('timesheets.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-file-alt"></i>
                            <span>Cartão de Ponto</span>
                        </a>
                    </div>
                </div>

                @if(auth()->user()->role === 'admin')
                <!-- ADMINISTRAÇÃO -->
                <div x-data="{ adminOpen: {{ request()->is('admins*') ? 'true' : 'false' }} }" class="mb-6">
                    <button @click="adminOpen = !adminOpen" class="w-full flex items-center justify-between text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 hover:text-gray-700">
                        <span>ADMINISTRAÇÃO</span>
                        <i :class="adminOpen ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-xs"></i>
                    </button>
                    <div x-show="adminOpen" x-collapse class="space-y-1">
                        <a href="{{ route('admins.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg {{ request()->routeIs('admins.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-user-shield"></i>
                            <span>Administradores</span>
                        </a>
                    </div>
                </div>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <main :class="sidebarOpen ? 'ml-64' : 'ml-0'" class="pt-16 transition-all duration-300">
            <div class="p-6">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-transition 
                         class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-transition 
                         class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
