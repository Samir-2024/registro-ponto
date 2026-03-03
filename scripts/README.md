# Scripts de Manutenção

Este diretório contém scripts úteis para manutenção e correção de problemas do sistema.

## Scripts Disponíveis

### fix-production-errors.sh

Script para diagnosticar e corrigir erros comuns em produção.

**Uso:**
```bash
chmod +x scripts/fix-production-errors.sh
./scripts/fix-production-errors.sh
```

**O que o script faz:**
1. ✅ Limpa todos os caches do Laravel
2. ✅ Verifica extensões PHP necessárias (PDO MySQL/PostgreSQL)
3. ✅ Valida configuração do banco de dados no .env
4. ✅ Remove arquivo sanctum.php se necessário
5. ✅ Atualiza autoload do Composer
6. ✅ Regenera caches (opcional)
7. ✅ Testa conexão com banco de dados
8. ✅ Verifica funcionamento do Artisan
9. ✅ Testa queue worker

**Quando usar:**
- Após deploy em produção
- Quando houver erros de classe não encontrada
- Quando houver problemas de cache
- Quando houver erros de conexão com banco

**Observações:**
- Execute o script na raiz do projeto Laravel
- Tenha certeza de fazer backup antes
- Leia o output com atenção e siga as recomendações

## Correções Aplicadas no Código

Ver documentação completa em: `docs/CORRECAO_ERROS_PRODUCAO.md`

### Correções Automáticas (já aplicadas no código)
- ✅ Namespace da trait Queueable corrigido em 2 arquivos:
  - `app/Jobs/ProcessAfdImport.php`
  - `app/Jobs/ImportEmployeesFromCsv.php`

### Correções Manuais (necessárias no servidor)
- ⚠️ Limpar caches
- ⚠️ Verificar configuração do banco de dados
- ⚠️ Remover ou instalar Sanctum
- ⚠️ Instalar extensões PHP necessárias

## Comandos Úteis

### Limpar Caches
```bash
php artisan optimize:clear
```

### Ver Informações do Sistema
```bash
php artisan about
```

### Testar Banco de Dados
```bash
php artisan db:show
```

### Listar Rotas
```bash
php artisan route:list
```

### Testar Queue
```bash
php artisan queue:work --once
```

## Monitoramento

Para monitorar logs em tempo real:
```bash
php artisan pail
# ou
tail -f storage/logs/laravel.log
```

## Suporte

Para mais informações sobre os erros e correções, consulte:
- `docs/CORRECAO_ERROS_PRODUCAO.md` - Documentação detalhada dos erros
- Logs do sistema: `storage/logs/laravel.log`
