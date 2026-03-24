<?php
// Script para converter arquivo TXT de colaboradores/empregadores para CSV
// Uso: php export_txt_to_csv.php caminho/para/arquivo.txt caminho/para/saida.csv

if ($argc < 3) {
    echo "Uso: php export_txt_to_csv.php <arquivo_entrada.txt> <arquivo_saida.csv>\n";
    exit(1);
}

$input = $argv[1];
$output = $argv[2];

if (!file_exists($input)) {
    echo "Arquivo de entrada não encontrado: $input\n";
    exit(1);
}

$lines = file($input, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$out = fopen($output, 'w');
// Cabeçalho padrão para colaboradores/empregadores
fputcsv($out, ['CPF/CNPJ', 'Nome', 'Matrícula']);

foreach ($lines as $line) {
    $parts = explode('[', $line);
    if (count($parts) >= 4) {
        $doc = $parts[1]; // CPF ou CNPJ
        $nome = $parts[2];
        $matricula = $parts[5] ?? '';
        fputcsv($out, [$doc, $nome, $matricula]);
    }
}
fclose($out);
echo "Arquivo CSV gerado em: $output\n";
