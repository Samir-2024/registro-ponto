<?php

namespace App\Services\AfdParsers;

use Illuminate\Support\Facades\Log;

/**
 * Factory para criação de parsers AFD
 * 
 * Implementa o padrão Factory para instanciar o parser correto baseado no formato do arquivo
 */
class AfdParserFactory
{
    /**
     * Lista de parsers disponíveis (ordem de tentativa)
     * 
     * Ordem importa: parsers mais específicos primeiro
     */
    protected static array $parsers = [
        HenryPrismaParser::class,      // Formato proprietário hex - mais específico
        HenryOrion5Parser::class,       // Formato com matrícula - linhas curtas
        HenrySuperFacilParser::class,   // Formato com PIS e data compacta
        DixiParser::class,              // Formato padrão Portaria 1510 com CPF
        DixiCollaboratorParser::class,  // Suporte a importação da listagem de colaboradores do REP
    ];

    /**
     * Detecta e retorna o parser apropriado para o arquivo
     *
     * @param string $filePath Caminho do arquivo
     * @param string|null $formatHint Dica opcional do formato (ex: 'henry-super-facil', 'henry-orion-5')
     * @return AfdParserInterface
     * @throws \Exception Se nenhum parser compatível for encontrado
     */
    public static function createParser(string $filePath, ?string $formatHint = null): AfdParserInterface
    {
        // Se foi fornecida uma dica de formato, tenta usar diretamente
        if ($formatHint) {
            $parser = self::createParserByHint($formatHint);
            if ($parser && $parser->canParse($filePath)) {
                Log::info("AFD Parser Factory: Usando parser {$parser->getFormatName()} (fornecido pelo usuário)");
                return $parser;
            }
        }

        // Tenta detectar automaticamente
        foreach (self::$parsers as $parserClass) {
            $parser = new $parserClass();
            
            if ($parser->canParse($filePath)) {
                Log::info("AFD Parser Factory: Detectado formato {$parser->getFormatName()}");
                return $parser;
            }
        }

        throw new \Exception("Nenhum parser compatível encontrado para o arquivo. Formatos suportados: " . self::getSupportedFormatsString());
    }

    /**
     * Cria parser baseado em dica textual
     */
    protected static function createParserByHint(string $hint): ?AfdParserInterface
    {
        $hint = strtolower(trim($hint));
        
        $mapping = [
            'dixi' => DixiParser::class,
            'henry-super-facil' => HenrySuperFacilParser::class,
            'henry-sf' => HenrySuperFacilParser::class,
            'super-facil' => HenrySuperFacilParser::class,
            'henry-prisma' => HenryPrismaParser::class,
            'prisma' => HenryPrismaParser::class,
            'henry-orion-5' => HenryOrion5Parser::class,
            'henry-orion' => HenryOrion5Parser::class,
            'orion-5' => HenryOrion5Parser::class,
            'orion' => HenryOrion5Parser::class,
        ];

        if (isset($mapping[$hint])) {
            return new $mapping[$hint]();
        }

        return null;
    }

    /**
     * Retorna lista de formatos suportados
     *
     * @return array
     */
    public static function getSupportedFormats(): array
    {
        $formats = [];
        
        foreach (self::$parsers as $parserClass) {
            $parser = new $parserClass();
            $formats[] = [
                'name' => $parser->getFormatName(),
                'description' => $parser->getFormatDescription(),
                'class' => $parserClass,
            ];
        }

        return $formats;
    }

    /**
     * Retorna string com formatos suportados
     */
    protected static function getSupportedFormatsString(): string
    {
        $formats = array_map(function($parserClass) {
            $parser = new $parserClass();
            return $parser->getFormatName();
        }, self::$parsers);

        return implode(', ', $formats);
    }

    /**
     * Registra um novo parser
     *
     * @param string $parserClass
     */
    public static function registerParser(string $parserClass): void
    {
        if (!in_array($parserClass, self::$parsers)) {
            self::$parsers[] = $parserClass;
            Log::info("AFD Parser Factory: Novo parser registrado: {$parserClass}");
        }
    }
}
