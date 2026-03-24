<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use App\Models\EmployeeRegistration;
use App\Models\Department;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeExportController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::all();
        return view('employee-export.index', compact('departments'));
    }

    public function download(Request $request)
    {
        $query = Person::query();

        // Filtro por Secretaria (Departamento)
        if ($request->filled('department_id')) {
            $query->whereHas('activeRegistrations', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filtro por Múltiplos CPFs (via campo textarea ou input simples)
        if ($request->filled('cpfs') || $request->filled('cpf')) {
            $rawCpfs = $request->input('cpfs') . ' ' . $request->input('cpf');
            $cpfs = preg_split('/[\s,;]+/', $rawCpfs, -1, PREG_SPLIT_NO_EMPTY);
            $cpfs = array_map(function($c) { return preg_replace('/[^0-9]/', '', $c); }, $cpfs);
            $cpfs = array_filter($cpfs);
            if (!empty($cpfs)) {
                $query->whereIn('cpf', $cpfs);
            }
        }

        // Filtro por Nome
        if ($request->filled('name')) {
            $query->where('full_name', 'like', '%'.$request->name.'%');
        }

        $people = $query->with('activeRegistrations.department')->get();
        $exportType = $request->input('export_type', 'csv');
        $date = date('Ymd_His');
        $suffix = "rep_colaboradores";

        if ($exportType === 'txt') {
            $filename = "{$suffix}.txt";
            $headers = [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($people) {
                $file = fopen('php://output', 'w');
                foreach ($people as $person) {
                    // Se não tiver registro, usa o ID como matrícula fallback (conforme padrão anterior)
                    if ($person->activeRegistrations->isEmpty()) {
                        $linha = sprintf(
                            "1+1+I[%s[%s[1[1[%s\n",
                            $person->cpf_formatted ?: ($person->cpf ?? '-'),
                            strtoupper($person->full_name),
                            $person->id
                        );
                        fwrite($file, $linha);
                    } else {
                        foreach ($person->activeRegistrations as $reg) {
                            $linha = sprintf(
                                "1+1+I[%s[%s[1[1[%s\n",
                                $person->cpf_formatted ?: ($person->cpf ?? '-'),
                                strtoupper($person->full_name),
                                $reg->matricula ?? $person->id
                            );
                            fwrite($file, $linha);
                        }
                    }
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } else {
            $filename = "{$suffix}.csv";
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($people) {
                $file = fopen('php://output', 'w');
                // Adiciona BOM UTF-8 para Excel abrir corretamente
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                fputcsv($file, ['CPF', 'Nome', 'Matricula', 'Secretaria', 'Status'], ';');
                
                foreach ($people as $person) {
                    if ($person->activeRegistrations->isEmpty()) {
                        fputcsv($file, [
                            $person->cpf_formatted ?: ($person->cpf ?? '-'),
                            strtoupper($person->full_name),
                            '',
                            '',
                            'Inativo'
                        ], ';');
                    } else {
                        foreach ($person->activeRegistrations as $reg) {
                            fputcsv($file, [
                                $person->cpf_formatted ?: ($person->cpf ?? '-'),
                                strtoupper($person->full_name),
                                $reg->matricula ?? '',
                                optional($reg->department)->name ?? '',
                                'Ativo'
                            ], ';');
                        }
                    }
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
    }
    
    public function process(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt',
        ]);

        $file = $request->file('file');
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $data = [];
        foreach ($lines as $line) {
            // Exemplo: 1+1+I[07281097948[FERNANDO FERNANDES RIBEIRO[1[1[3253
            $parts = explode('[', $line);
            if (count($parts) >= 4) {
                $cpf = $parts[1];
                $nome = $parts[2];
                $matricula = $parts[5] ?? '';
                $data[] = [$cpf, $nome, $matricula];
            }
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="colaboradores_export.csv"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['CPF', 'Nome', 'Matricula']);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
