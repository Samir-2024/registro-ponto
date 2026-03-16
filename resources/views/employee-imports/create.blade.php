@extends('layouts.main')

@section('content')
<div class="mb-6">
    <div class="flex items-center mb-6">
        <a href="{{ route('employee-imports.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-file-import text-blue-600 mr-3"></i>Nova Importação de Colaboradores
            </h1>
            <p class="text-gray-600 mt-2">Faça upload de um arquivo TXT para importar colaboradores em massa</p>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border-l-4 border-blue-600 rounded-lg p-6 mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 text-2xl mr-4 mt-1"></i>
            <div class="flex-1">
                <h3 class="font-bold text-blue-900 mb-2">Importante</h3>
                <ul class="text-sm text-blue-800 space-y-1 mb-4">
                    <li><i class="fas fa-check mr-2"></i>O arquivo deve estar no formato TXT</li>
                    <li><i class="fas fa-check mr-2"></i>Use o modelo disponível para garantir a formatação correta</li>
                    <li><i class="fas fa-check mr-2"></i>O processamento será realizado em segundo plano</li>
                    <li><i class="fas fa-check mr-2"></i>Você será notificado quando concluir</li>
                </ul>
                <a href="{{ route('employee-imports.template') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow">
                    <i class="fas fa-download mr-2"></i>Baixar Modelo TXT
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <form action="{{ route('employee-imports.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf

            <!-- File Upload Area -->
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-4">
                    <i class="fas fa-file-alt text-gray-400 mr-2"></i>Arquivo TXT *
                </label>
                
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-blue-500 transition cursor-pointer" id="dropZone">
                    <div class="space-y-4">
                        <div class="flex justify-center">
                            <i class="fas fa-cloud-upload-alt text-6xl text-gray-300"></i>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-700 mb-2">Arraste e solte o arquivo TXT aqui</p>
                            <p class="text-sm text-gray-500 mb-4">ou</p>
                            <label for="file" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg cursor-pointer transition shadow-lg hover:shadow-xl">
                                <i class="fas fa-folder-open mr-2"></i>Selecionar do Computador
                            </label>
                            <input id="file" name="txt_file" type="file" accept=".txt" class="sr-only" required>
                            <p class="text-xs text-gray-400 mt-4">Tamanho máximo: 10MB</p>
                        </div>
                        <div id="fileInfo" class="hidden">
                            <i class="fas fa-file-alt text-5xl text-green-500 mb-3"></i>
                            <p id="fileName" class="text-lg font-semibold text-gray-700"></p>
                            <p class="text-sm text-green-600 mt-2">
                                <i class="fas fa-check-circle mr-1"></i>Arquivo selecionado
                            </p>
                            <label for="file" class="inline-block mt-3 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">
                                <i class="fas fa-sync mr-1"></i>Escolher outro arquivo
                            </label>
                        </div>
                    </div>
                </div>

                @error('txt_file')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Preview Area -->
            <div id="previewArea" class="hidden mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-eye text-blue-600 mr-2"></i>
                    Pré-visualização
                </h3>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <div id="previewContent"></div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('employee-imports.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </a>
                <button 
                    type="submit" 
                    id="submitBtn"
                    class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled
                >
                    <i class="fas fa-upload mr-2"></i><span id="submitBtnText">Importar Colaboradores</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const fileInput = document.getElementById('file');
    const fileName = document.getElementById('fileName');
    const fileInfo = document.getElementById('fileInfo');
    const dropZone = document.getElementById('dropZone');
    const submitBtn = document.getElementById('submitBtn');
    const previewArea = document.getElementById('previewArea');

    // File input change handler
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0 && this.files[0].name.endsWith('.txt')) {
            fileName.textContent = this.files[0].name;
            fileInfo.classList.remove('hidden');
            dropZone.querySelector('.space-y-4 > div:first-child').classList.add('hidden');
            dropZone.querySelector('.space-y-4 > div:nth-child(2)').classList.add('hidden');
            submitBtn.disabled = false;
            readAndPreviewFile(this.files[0]);
        } else {
            submitBtn.disabled = true;
            fileInfo.classList.add('hidden');
            fileName.textContent = '';
        }
    });

    // Drag and drop handlers
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].name.endsWith('.txt')) {
            fileInput.files = files;
            fileName.textContent = files[0].name;
            fileInfo.classList.remove('hidden');
            dropZone.querySelector('.space-y-4 > div:first-child').classList.add('hidden');
            dropZone.querySelector('.space-y-4 > div:nth-child(2)').classList.add('hidden');
            submitBtn.disabled = false;
            readAndPreviewFile(files[0]);
        } else {
            submitBtn.disabled = true;
            fileInfo.classList.add('hidden');
            fileName.textContent = '';
        }
    });

    // Read and preview file
    function readAndPreviewFile(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const lines = text.split('\n').slice(0, 4); // Show first 3 rows
            const totalLines = text.split('\n').length - 1;
            
            previewArea.classList.remove('hidden');
            document.getElementById('previewContent').innerHTML = `
                <p class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-list mr-2"></i>Primeiras linhas do arquivo:
                </p>
                <pre class="text-xs bg-white p-4 rounded border border-gray-200 overflow-x-auto font-mono">${lines.join('\n')}</pre>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <p class="text-xs text-gray-600">Total de linhas</p>
                        <p class="text-2xl font-bold text-gray-900">${totalLines}</p>
                    </div>
                    <div class="bg-green-50 p-3 rounded border border-green-200">
                        <p class="text-xs text-green-700">Status</p>
                        <p class="text-sm font-bold text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Pronto para importar
                        </p>
                    </div>
                </div>
            `;
        };
        reader.readAsText(file);
    }

    // Form submit handler
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        submitBtn.disabled = true;
        document.getElementById('submitBtnText').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importando...';
    });
</script>
@endsection
