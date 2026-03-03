# Correção de Erros em Produção

## Data: 2026-03-03

Este documento descreve os erros identificados nos logs de produção e as correções aplicadas.

---

## ✅ 1. ERRO: Trait Queueable com namespace incorreto

**Erro:**
```
Trait "Illuminate\Foundation\Queue\Queueable" not found
```

**Arquivos:** 
- `app/Jobs/ProcessAfdImport.php`
- `app/Jobs/ImportEmployeesFromCsv.php`

**Causa:** O namespace da trait `Queueable` estava incorreto. Em Laravel 12, a trait correta é `Illuminate\Bus\Queueable`.

**Correção Aplicada:** ✅ CORRIGIDA
- Alterado de: `use Illuminate\Foundation\Queue\Queueable;`
- Para: `use Illuminate\Bus\Queueable;`
- Ambos os arquivos foram corrigidos

---

## ⚠️ 2. ERRO: Target class [files] does not exist

**Erro:**
```
Target class [files] does not exist
ReflectionException: Class "files" does not exist
```

**Causa Provável:** 
- Cache de rotas/configuração corrompido no servidor
- Alguma configuração antiga referenciando uma classe 'files'

**Solução Recomendada:**
No servidor de produção, execute:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
composer dump-autoload
```

---

## ⚠️ 3. ERRO: Target class [admin] does not exist (resolvido parcialmente)

**Erro:**
```
Target class [admin] does not exist
ReflectionException: Class "admin" does not exist
```

**Status:** O middleware `IsAdmin` existe em `app/Http/Middleware/IsAdmin.php` e está registrado corretamente em `bootstrap/app.php`.

**Causa Provável:** 
- Cache de rotas/configuração corrompido no servidor

**Solução:**
Execute os mesmos comandos do erro #2:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear
```

---

## ⚠️ 4. ERRO: Laravel Sanctum não encontrado

**Erro:**
```
Class "Laravel\Sanctum\Sanctum" not found at config/sanctum.php:21
```

**Causa:** Existe um arquivo `config/sanctum.php` no servidor de produção, mas o pacote Sanctum não está instalado no `composer.json`.

**Soluções Possíveis:**

### Opção 1: Remover Sanctum (Recomendado se não for usado)
No servidor de produção:
```bash
rm config/sanctum.php
php artisan config:clear
```

### Opção 2: Instalar Sanctum (se for necessário)
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## ⚠️ 5. ERROS: Configuração do Banco de Dados

### 5.1 Driver MySQL não encontrado
**Erro:**
```
could not find driver (Connection: mysql, SQL: delete from `cache`)
```

**Solução:**
Instalar extensão PHP PDO MySQL:
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-mysql
sudo systemctl restart php8.2-fpm

# CentOS/RHEL
sudo yum install php-mysql
sudo systemctl restart php-fpm
```

### 5.2 explode() recebendo array ao invés de string
**Erro:**
```
explode(): Argument #2 ($string) must be of type string, array given
at PostgresBuilder.php:281
```

**Causa:** Configuração incorreta de schema do PostgreSQL. O schema deve ser uma string, não um array.

**Solução:**
Verificar arquivo `.env` e garantir que:
```env
DB_CONNECTION=pgsql
DB_HOST=177.105.175.39
DB_PORT=5432
DB_DATABASE=nome_do_banco
DB_USERNAME=usuario
DB_PASSWORD=senha
# NÃO usar array, apenas string:
DB_SCHEMA=public
```

### 5.3 Senha de autenticação incorreta
**Erro:**
```
password authentication failed for user "postgres"
```

**Solução:**
Verificar credenciais no `.env`:
```env
DB_USERNAME=usuario_correto
DB_PASSWORD=senha_correta
```

### 5.4 Database não existe
**Erro:**
```
database "RELOGIO-PONTO" does not exist
```

**Solução:**
Criar o banco de dados ou corrigir o nome no `.env`:
```sql
-- Conectar ao PostgreSQL e criar o banco:
CREATE DATABASE "nome_correto_do_banco";
```

Ou ajustar no `.env`:
```env
DB_DATABASE=nome_do_banco_existente
```

### 5.5 Database connection vazia
**Erro:**
```
Database connection [] not configured
```

**Solução:**
Verificar `.env` e garantir que `DB_CONNECTION` está definido:
```env
DB_CONNECTION=pgsql
```

### 5.6 Banco iniciando
**Erro:**
```
SQLSTATE[08006] [7] the database system is starting up
```

**Causa:** PostgreSQL reiniciando.

**Solução:** Aguardar alguns segundos e tentar novamente. Se persistir, verificar logs do PostgreSQL.

---

## 📋 CHECKLIST DE CORREÇÕES NO SERVIDOR

Execute os seguintes comandos no servidor de produção:

```bash
# 1. Limpar todos os caches
php artisan optimize:clear

# 2. Verificar arquivo .env
cat .env | grep DB_

# 3. Remover arquivo sanctum.php (se não usar Sanctum)
rm -f config/sanctum.php

# 4. Regenerar cache
php artisan config:cache
php artisan route:cache

# 5. Atualizar autoload
composer dump-autoload --optimize

# 6. Reiniciar serviços
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx  # ou apache2
```

---

## 🔍 Diagnóstico Adicional

Para verificar se os problemas foram resolvidos, execute:

```bash
# Testar artisan
php artisan about

# Verificar erros de configuração
php artisan config:show database

# Verificar rotas
php artisan route:list

# Verificar queue worker
php artisan queue:work --once
```

---

## 📝 Observações

1. **Erro do 'files'**: Não foi identificado no código local, sugere problema de cache no servidor
2. **Erro do 'admin'**: Middleware existe e está configurado corretamente
3. **Trait Queueable**: ✅ Corrigida no código
4. **Sanctum**: Arquivo de configuração no servidor sem o pacote instalado
5. **Banco de dados**: Múltiplos problemas de configuração (credenciais, schema, driver)

---

## 🚀 Ações Imediatas Recomendadas

1. ✅ Fazer commit do fix da trait Queueable
2. ⚠️ Limpar todos os caches no servidor de produção
3. ⚠️ Verificar e corrigir arquivo `.env` no servidor
4. ⚠️ Remover ou instalar Sanctum
5. ⚠️ Verificar instalação das extensões PHP necessárias

---

## 📊 Resumo de Erros por Categoria

| Categoria | Quantidade | Status |
|-----------|------------|--------|
| Cache corrompido | 2 | ⚠️ Pendente |
| Namespace incorreto | 1 | ✅ Corrigido |
| Pacote faltando | 1 | ⚠️ Pendente |
| Config. banco de dados | 6 | ⚠️ Pendente |
| **TOTAL** | **10** | **90% Pendente** |

