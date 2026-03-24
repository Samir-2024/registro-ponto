<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Department;

class EmployerExportController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::all();
        return view('employer-export.index', compact('departments'));
    }

    public function download(Request $request)
    {
        $query = Employer::query();

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('cpf')) {
            $query->whereHas('person', function($q) use ($request) {
                $q->where('cpf', 'like', '%'.$request->cpf.'%');
            });
        }
        if ($request->filled('cnpj')) {
            $query->where('cnpj', 'like', '%'.$request->cnpj.'%');
        }
        if ($request->filled('razao_social')) {
            $query->where('razao_social', 'like', '%'.$request->razao_social.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employers = $query->with(['department', 'person'])->get();

        $exportType = $request->input('export_type', 'csv');

        if ($exportType === 'txt') {
            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="empregadores_export.txt"',
            ];
            $callback = function() use ($employers) {
                $file = fopen('php://output', 'w');
                foreach ($employers as $employer) {
                    $linha = sprintf(
                        "1+1+I[%s[%s[%s[%s[%s\n",
                        $employer->cnpj,
                        optional($employer->person)->cpf,
                        optional($employer->person)->full_name,
                        $employer->department_id,
                        $employer->id
                    );
                    fwrite($file, $linha);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } else {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="empregadores_export.csv"',
            ];
            $callback = function() use ($employers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['CNPJ', 'CPF', 'Nome', 'Departamento', 'ID']);
                foreach ($employers as $employer) {
                    fputcsv($file, [
                        $employer->cnpj,
                        optional($employer->person)->cpf,
                        optional($employer->person)->full_name,
                        $employer->department_id,
                        $employer->id
                    ]);
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
            'export_type' => 'nullable|in:csv,txt',
        ]);

        $file = $request->file('file');
        $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $data = [];
        foreach ($lines as $line) {
            // Exemplo: 1+1+I[12345678901[12345678901234[EMPREGADOR EXEMPLO[1[1[9999
            $parts = explode('[', $line);
            if (count($parts) >= 5) {
                $cnpj = $parts[1] ?? '';
                $cpf = $parts[2] ?? '';
                $nome = $parts[3] ?? '';
                $matricula = $parts[5] ?? '';
                $data[] = [$cnpj, $cpf, $nome, $matricula];
            }
        }

        $exportType = $request->input('export_type', 'csv');

        if ($exportType === 'txt') {
            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="empregadores_export.txt"',
            ];
            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                foreach ($data as $row) {
                    // Formato: 1+1+I[CNPJ[CPF[NOME[1[1[MATRICULA\n
                    $linha = sprintf("1+1+I[%s[%s[%s[1[1[%s\n", $row[0], $row[1], $row[2], $row[3]);
                    fwrite($file, $linha);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } else {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="empregadores_export.csv"',
            ];
            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['CNPJ', 'CPF', 'Nome', 'Matricula']);
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
    }
}
