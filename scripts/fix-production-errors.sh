#!/bin/bash

# Script de Correção de Erros em Produção
# Data: 2026-03-03
# Descrição: Corrige erros identificados nos logs de produção

set -e  # Parar em caso de erro

echo "======================================"
echo "CORREÇÃO DE ERROS EM PRODUÇÃO"
echo "======================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para imprimir com cor
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "ℹ️  $1"
}

# Verificar se está na raiz do projeto Laravel
if [ ! -f "artisan" ]; then
    print_error "Este script deve ser executado na raiz do projeto Laravel!"
    exit 1
fi

print_info "Iniciando correções..."
echo ""

# 1. Limpar todos os caches
echo "1. Limpando caches..."
php artisan optimize:clear && print_success "Caches limpos" || print_error "Erro ao limpar caches"
echo ""

# 2. Verificar extensões PHP necessárias
echo "2. Verificando extensões PHP..."
php -m | grep -i pdo_mysql > /dev/null && print_success "PDO MySQL instalado" || print_warning "PDO MySQL NÃO instalado - Execute: apt-get install php8.2-mysql"
php -m | grep -i pdo_pgsql > /dev/null && print_success "PDO PostgreSQL instalado" || print_warning "PDO PostgreSQL NÃO instalado - Execute: apt-get install php8.2-pgsql"
echo ""

# 3. Verificar configuração do banco de dados
echo "3. Verificando configuração do banco de dados..."
if [ -f ".env" ]; then
    print_success "Arquivo .env encontrado"
    
    DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
    
    if [ -z "$DB_CONNECTION" ]; then
        print_error "DB_CONNECTION não está definido no .env"
    else
        print_success "DB_CONNECTION: $DB_CONNECTION"
    fi
    
    if [ -z "$DB_HOST" ]; then
        print_error "DB_HOST não está definido no .env"
    else
        print_success "DB_HOST: $DB_HOST"
    fi
    
    if [ -z "$DB_DATABASE" ]; then
        print_error "DB_DATABASE não está definido no .env"
    else
        print_success "DB_DATABASE: $DB_DATABASE"
    fi
else
    print_error "Arquivo .env não encontrado!"
fi
echo ""

# 4. Verificar e remover arquivo sanctum.php se não necessário
echo "4. Verificando arquivo Sanctum..."
if [ -f "config/sanctum.php" ]; then
    if grep -q "laravel/sanctum" composer.json; then
        print_success "Sanctum instalado no composer.json"
    else
        print_warning "Arquivo config/sanctum.php existe mas Sanctum não está instalado"
        read -p "Deseja remover o arquivo config/sanctum.php? (s/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Ss]$ ]]; then
            rm -f config/sanctum.php
            print_success "Arquivo config/sanctum.php removido"
        fi
    fi
else
    print_success "Arquivo sanctum.php não existe (OK)"
fi
echo ""

# 5. Atualizar autoload
echo "5. Atualizando autoload..."
composer dump-autoload --optimize && print_success "Autoload atualizado" || print_error "Erro ao atualizar autoload"
echo ""

# 6. Regenerar cache (se necessário)
echo "6. Regenerando caches..."
read -p "Deseja regenerar os caches? (s/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Ss]$ ]]; then
    php artisan config:cache && print_success "Config cache gerado" || print_error "Erro ao gerar config cache"
    php artisan route:cache && print_success "Route cache gerado" || print_error "Erro ao gerar route cache"
    php artisan view:cache && print_success "View cache gerado" || print_error "Erro ao gerar view cache"
fi
echo ""

# 7. Testar conexão com banco de dados
echo "7. Testando conexão com banco de dados..."
php artisan db:show && print_success "Conexão com banco OK" || print_error "Erro na conexão com banco"
echo ""

# 8. Testar artisan
echo "8. Testando artisan..."
php artisan about && print_success "Artisan funcionando" || print_error "Erro no artisan"
echo ""

# 9. Verificar queue worker
echo "9. Verificando queue worker..."
php artisan queue:work --once && print_success "Queue worker funcionando" || print_warning "Erro no queue worker - verifique a configuração"
echo ""

echo "======================================"
echo "RESUMO"
echo "======================================"
print_info "Verificações concluídas!"
echo ""
print_warning "AÇÕES MANUAIS NECESSÁRIAS:"
echo "1. Verificar e corrigir credenciais do banco no .env"
echo "2. Reiniciar PHP-FPM: systemctl restart php8.2-fpm"
echo "3. Reiniciar servidor web: systemctl restart nginx"
echo "4. Reiniciar queue worker: supervisorctl restart laravel-worker:*"
echo ""
print_info "Para mais informações, consulte: docs/CORRECAO_ERROS_PRODUCAO.md"
