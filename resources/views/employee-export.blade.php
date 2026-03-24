@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Exportação de Colaboradores</h1>
        <form action="{{ route('employee-export.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Selecione o arquivo TXT de colaboradores</label>
                <input class="form-control" type="file" id="file" name="file" accept=".txt" required>
            </div>
            <button type="submit" class="btn btn-primary">Processar e Exportar</button>
        </form>
    </div>
@endsection
