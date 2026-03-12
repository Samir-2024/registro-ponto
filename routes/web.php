<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    EstablishmentController,
    DepartmentController,
    EmployeeController,
    EmployeeImportController,
    WorkScheduleController,
    AfdImportController,
    TimesheetController,
    AdminController,
    DashboardController,
    EmployeeReportController
};
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\FilterController;

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Rotas protegidas por autenticação
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Administradores (apenas para admins)
    Route::middleware('admin')->group(function () {
        Route::resource('admins', AdminController::class);
    });

    // Estabelecimentos
    Route::resource('establishments', EstablishmentController::class);

// Departamentos
Route::resource('departments', DepartmentController::class);

// Colaboradores (Pessoas)
Route::resource('employees', EmployeeController::class);

// Vínculos de Colaboradores (EmployeeRegistrations)
Route::prefix('people/{person}/registrations')->name('registrations.')->group(function () {
    Route::get('/create', [\App\Http\Controllers\EmployeeRegistrationController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\EmployeeRegistrationController::class, 'store'])->name('store');
});

Route::prefix('registrations')->name('registrations.')->group(function () {
    Route::get('/{registration}/edit', [\App\Http\Controllers\EmployeeRegistrationController::class, 'edit'])->name('edit');
    Route::put('/{registration}', [\App\Http\Controllers\EmployeeRegistrationController::class, 'update'])->name('update');
    Route::post('/{registration}/terminate', [\App\Http\Controllers\EmployeeRegistrationController::class, 'terminate'])->name('terminate');
    Route::post('/{registration}/reactivate', [\App\Http\Controllers\EmployeeRegistrationController::class, 'reactivate'])->name('reactivate');
    Route::delete('/{registration}', [\App\Http\Controllers\EmployeeRegistrationController::class, 'destroy'])->name('destroy');
});

// Templates de Jornada
Route::prefix('work-shift-templates')->name('work-shift-templates.')->group(function () {
    Route::get('/', [\App\Http\Controllers\WorkShiftTemplateController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\WorkShiftTemplateController::class, 'create'])->name('create');
    Route::get('/select-type', [\App\Http\Controllers\WorkShiftTemplateController::class, 'selectType'])->name('select-type');
    Route::get('/create-weekly', [\App\Http\Controllers\WorkShiftTemplateController::class, 'createWeekly'])->name('create-weekly');
    Route::get('/create-rotating', [\App\Http\Controllers\WorkShiftTemplateController::class, 'createRotating'])->name('create-rotating');
    Route::get('/create-flexible', [\App\Http\Controllers\WorkShiftTemplateController::class, 'createFlexible'])->name('create-flexible');
    Route::post('/', [\App\Http\Controllers\WorkShiftTemplateController::class, 'store'])->name('store');
    Route::get('/{template}/edit', [\App\Http\Controllers\WorkShiftTemplateController::class, 'edit'])->name('edit');
    Route::put('/{template}', [\App\Http\Controllers\WorkShiftTemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [\App\Http\Controllers\WorkShiftTemplateController::class, 'destroy'])->name('destroy');
    Route::get('/bulk-assign', [\App\Http\Controllers\WorkShiftTemplateController::class, 'bulkAssignForm'])->name('bulk-assign');
    Route::post('/bulk-assign', [\App\Http\Controllers\WorkShiftTemplateController::class, 'bulkAssignStore'])->name('bulk-assign.store');
});

// Horários de Trabalho (nested routes)
Route::prefix('employees/{employee}')->name('employees.')->group(function () {
    Route::get('/work-schedules', [WorkScheduleController::class, 'index'])->name('work-schedules.index');
    Route::post('/work-schedules/apply-template', [WorkScheduleController::class, 'applyTemplate'])->name('work-schedules.apply-template');
    Route::get('/work-schedules/create', [WorkScheduleController::class, 'create'])->name('work-schedules.create');
    Route::post('/work-schedules', [WorkScheduleController::class, 'store'])->name('work-schedules.store');
    Route::get('/work-schedules/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->name('work-schedules.edit');
    Route::put('/work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])->name('work-schedules.update');
    Route::delete('/work-schedules/{workSchedule}', [WorkScheduleController::class, 'destroy'])->name('work-schedules.destroy');
});

// Importação AFD
Route::prefix('afd-imports')->group(function () {
    Route::get('/', [AfdImportController::class, 'index'])->name('afd-imports.index');
    Route::get('/create', [AfdImportController::class, 'create'])->name('afd-imports.create');
    Route::post('/', [AfdImportController::class, 'store'])->name('afd-imports.store');
    Route::get('/{afdImport}', [AfdImportController::class, 'show'])->name('afd-imports.show');
    
    // Rotas de revisão de colaboradores pendentes
    Route::get('/{afdImport}/review', [AfdImportController::class, 'review'])->name('afd-imports.review');
    Route::post('/{afdImport}/register/{employeeKey}', [AfdImportController::class, 'registerEmployee'])->name('afd-imports.register-employee');
    Route::post('/{afdImport}/skip/{employeeKey}', [AfdImportController::class, 'skipEmployee'])->name('afd-imports.skip-employee');
    Route::post('/{afdImport}/skip-all', [AfdImportController::class, 'skipAll'])->name('afd-imports.skip-all');
});

// Importação de Colaboradores (CSV)
Route::prefix('employee-imports')->group(function () {
    Route::get('/', [EmployeeImportController::class, 'index'])->name('employee-imports.index');
    Route::get('/create', [EmployeeImportController::class, 'create'])->name('employee-imports.create');
    Route::get('/template', [EmployeeImportController::class, 'downloadTemplate'])->name('employee-imports.template');
    Route::post('/upload', [EmployeeImportController::class, 'upload'])->name('employee-imports.upload');
    Route::post('/{import}/process', [EmployeeImportController::class, 'process'])->name('employee-imports.process');
    Route::get('/{import}', [EmployeeImportController::class, 'show'])->name('employee-imports.show');
    Route::get('/{import}/errors', [EmployeeImportController::class, 'showErrors'])->name('employee-imports.errors');
});

// Importação de Vínculos e Jornadas (CSV Legado)
Route::prefix('vinculo-imports')->name('vinculo-imports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\VinculoImportController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\VinculoImportController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\VinculoImportController::class, 'store'])->name('store');
    Route::get('/{import}', [\App\Http\Controllers\VinculoImportController::class, 'show'])->name('show');
    Route::get('/{import}/errors', [\App\Http\Controllers\VinculoImportController::class, 'showErrors'])->name('errors');
    Route::get('/{import}/download', [\App\Http\Controllers\VinculoImportController::class, 'download'])->name('download');
    Route::get('/{import}/download-errors', [\App\Http\Controllers\VinculoImportController::class, 'downloadErrors'])->name('download-errors');
});

// Cartão de Ponto (Novo Fluxo: Pessoa → Vínculos)
Route::prefix('timesheets')->group(function () {
    Route::get('/', [TimesheetController::class, 'index'])->name('timesheets.index');
    Route::post('/search-person', [TimesheetController::class, 'searchPerson'])->name('timesheets.search-person');
    Route::get('/person/{person}/registrations', [TimesheetController::class, 'showPersonRegistrations'])->name('timesheets.person-registrations');
    Route::post('/generate-multiple', [TimesheetController::class, 'generateMultiple'])->name('timesheets.generate-multiple');
    Route::get('/registration/{registration}', [TimesheetController::class, 'showRegistration'])->name('timesheets.show-registration');
    
    // Gerar por Departamento
    Route::get('/by-department', [TimesheetController::class, 'byDepartment'])->name('timesheets.by-department');
    Route::post('/generate-by-department', [TimesheetController::class, 'generateByDepartment'])->name('timesheets.generate-by-department');
    
    // Rotas antigas (deprecated)
    Route::post('/generate', [TimesheetController::class, 'generate'])->name('timesheets.generate');
    Route::get('/show', [TimesheetController::class, 'show'])->name('timesheets.show');
    Route::post('/download-zip', [TimesheetController::class, 'downloadZip'])->name('timesheets.download-zip');
});

// Relatórios
Route::prefix('reports')->name('reports.')->group(function () {
    // Relatório de Colaboradores
    Route::get('/employees', [EmployeeReportController::class, 'index'])->name('employees.index');
    Route::post('/employees/generate', [EmployeeReportController::class, 'generate'])->name('employees.generate');
});

    // API para filtros em cascata
    Route::prefix('api')->group(function () {
        Route::get('/establishments', [FilterController::class, 'getEstablishments']);
        Route::get('/departments', [FilterController::class, 'getDepartmentsByEstablishment']);
        Route::get('/employees/search', [FilterController::class, 'searchEmployees']);
        Route::get('/employees/by-department', [FilterController::class, 'getEmployeesByDepartment']);
    });
});
