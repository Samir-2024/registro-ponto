@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Importar Colaboradores</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <form action="{{ route('import.process') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="employee">
        <div class="mb-3">
            <label for="file" class="form-label">Arquivo TXT de Colaboradores</label>
            <input class="form-control" type="file" id="file" name="file" accept=".txt" required>
        </div>
        <button type="submit" class="btn btn-primary">Importar Colaboradores</button>
    </form>
</div>
@endsection
