<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employer;
use App\Models\Department;
use App\Models\Person;
use App\Models\EmployeeRegistration;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Tela central de exportações
     */
    public function index()
    {
        $departments = Department::all();
        return view('exports.index', compact('departments'));
    }

    /**
     * Download de colaboradores filtrado por secretaria (REP Colaboradores)
     */
    public function downloadByDepartment(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id'
        ]);

        $people = Person::whereHas('activeRegistrations', function($q) use ($request) {
            $q->where('department_id', $request->department_id);
        })->with(['activeRegistrations' => function($q) use ($request) {
            $q->where('department_id', $request->department_id);
        }])->get();

        $date = date('Ymd_His');
        $filename = "rep_colaboradores.txt";
        
        $headers = [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($people) {
            $file = fopen('php://output', 'w');
            foreach ($people as $person) {
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
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download direto de empregadores (REP Empregador)
     */
    public function downloadEmployer()
    {
        $employers = Employer::with(['department', 'person'])->get();

        $filename = "rep_empregador.txt";
        
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($employers) {
            $file = fopen('php://output', 'w');
            foreach ($employers as $employer) {
                // Formato padrão exportado anteriormente
                // 1+1+I[CNPJ[CPF[NOME[1[1[MATRICULA
                $linha = sprintf(
                    "1+1+I[%s[%s[%s[1[1[%s\n",
                    $employer->cnpj,
                    optional($employer->person)->cpf_formatted ?: (optional($employer->person)->cpf ?? '-'),
                    strtoupper(optional($employer->person)->full_name),
                    $employer->id
                );
                fwrite($file, $linha);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
