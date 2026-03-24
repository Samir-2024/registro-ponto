@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Exportação de Empregadores</h1>
        <form id="export-csv-form" action="{{ route('employer-export.download') }}" method="GET" style="display:inline-block;">
            @csrf
            <input type="hidden" name="export_type" value="csv">
            <button type="submit" class="btn btn-primary">Exportar CSV</button>
        </form>
        <form id="export-txt-form" action="{{ route('employer-export.download') }}" method="GET" style="display:inline-block; margin-left: 10px;">
            @csrf
            <input type="hidden" name="export_type" value="txt">
            <button type="submit" class="btn btn-secondary">Exportar TXT</button>
        </form>
    </div>
@endsection
