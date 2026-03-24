<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistema de Ponto')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-link {
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
        }
        .sidebar-link.active {
            background-color: rgba(59, 130, 246, 0.15);
            border-left: 4px solid #3b82f6;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg flex flex-col">
            <!-- Logo/Header -->
            <div class="p-6 border-b border-gray-200">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">Sistema de Ponto</h1>
                        <p class="text-xs text-gray-500">Gestão de Pessoal</p>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home w-5"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Cadastros -->
                <div class="mt-6">
                    <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Cadastros</h3>
                    
                    <a href="{{ route('establishments.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('establishments.*') ? 'active' : '' }}">
                        <i class="fas fa-building w-5"></i>
                        <span>Estabelecimentos</span>
                    </a>
                    
                    <a href="{{ route('departments.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                        <i class="fas fa-sitemap w-5"></i>
                        <span>Departamentos</span>
                    </a>
                    
                    <a href="{{ route('employees.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <i class="fas fa-users w-5"></i>
                        <span>Colaboradores</span>
                    </a>
                    
                    <a href="{{ route('work-shift-templates.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('work-shift-templates.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt w-5"></i>
                        <span>Jornadas de Trabalho</span>
                    </a>
                </div>

                <!-- Importações -->
                <div class="mt-6">
                    <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">EQUIPAMENTOS</h3>
                    <a href="{{ route('afd-imports.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('afd-imports.*') ? 'active' : '' }}">
                        <i class="fas fa-file-import w-5"></i>
                        <span>Importar AFD</span>
                    </a>
                    <a href="{{ route('employee-imports.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('employee-imports.*') ? 'active' : '' }}">
                        <i class="fas fa-user-plus w-5"></i>
                        <span>Importar Colaboradores</span>
                    </a>
                    <a href="{{ route('exports.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('exports.*') || request()->routeIs('employee-export.*') ? 'active' : '' }}">
                        <i class="fas fa-file-export w-5 text-blue-600"></i>
                        <span>Exportações</span>
                    </a>
                    <a href="{{ route('vinculo-imports.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('vinculo-imports.*') ? 'active' : '' }}">
                        <i class="fas fa-link w-5"></i>
                        <span>Importar Vínculos</span>
                    </a>
                </div>

                <!-- Relatórios -->
                <div class="mt-6">
                    <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Relatórios</h3>
                    
                    <a href="{{ route('timesheets.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('timesheets.*') ? 'active' : '' }}">
                        <i class="fas fa-file-alt w-5"></i>
                        <span>Cartão de Ponto</span>
                    </a>
                    
                    <a href="{{ route('reports.employees.index') }}" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg mb-1 {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Relação de Colaboradores</span>
                    </a>
                </div>
            </nav>

            <!-- User/Logout -->
            <div class="p-4 border-t border-gray-200">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="sidebar-link flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg w-full hover:bg-red-50 hover:text-red-600">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">
                                <i class="far fa-user-circle mr-2"></i>
                                {{ auth()->user()->name ?? 'Usuário' }}
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                @if(session('success'))
                    <div class="bg-green-50 border-l-4 border-green-400 text-green-800 px-4 py-3 rounded mb-6 shadow-sm">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 border-l-4 border-red-400 text-red-800 px-4 py-3 rounded mb-6 shadow-sm">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
