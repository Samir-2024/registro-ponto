<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function showForm()
    {
        return view('import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt',
            'type' => 'required|in:employee,employer',
        ]);

        $file = $request->file('file');
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $imported = 0;
        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $parts = explode('[', $line);
                if (count($parts) >= 4) {
                    $doc = $parts[1];
                    $nome = $parts[2];
                    $matricula = $parts[5] ?? null;
                    if ($request->type === 'employee') {
                        // Colaborador: salva em Person e Employee
                        $person = Person::firstOrCreate([
                            'cpf' => $doc
                        ], [
                            'full_name' => $nome
                        ]);
                        Employee::firstOrCreate([
                            'person_id' => $person->id,
                            'registration' => $matricula
                        ]);
                        $imported++;
                    } else {
                        // Empregador: salva em Employer
                        Employer::firstOrCreate([
                            'cnpj' => $doc
                        ], [
                            'razao_social' => $nome
                        ]);
                        $imported++;
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao importar: ' . $e->getMessage());
        }
        return back()->with('success', "Importação concluída: $imported registros importados.");
    }
}
