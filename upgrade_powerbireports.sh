#!/bin/bash

# Script de Atualização GLPI Power BI Reports Plugin (v1.1.0 para v2.1.0)

# Este script automatiza o processo de atualização do plugin Power BI Reports
# no GLPI, garantindo que as permissões por relatório e os novos campos
# de atualização de dados sejam configurados corretamente.

# ATENÇÃO:
# 1. Execute este script como root ou com sudo.
# 2. Faça backup completo do seu banco de dados GLPI e da pasta do plugin ANTES de executar.
# 3. Este script assume que o GLPI está instalado em /var/www/glpi.
# 4. A senha do usuário root do MySQL será solicitada para as operações no banco de dados.

GLPI_ROOT="/var/www/glpi"
PLUGIN_DIR="${GLPI_ROOT}/plugins/powerbireports"
DB_NAME="glpidb" # Nome do seu banco de dados GLPI

# --- Funções --- 

log_info() {
    echo "[INFO] $1"
}

log_success() {
    echo "[SUCCESS] $1"
}

log_error() {
    echo "[ERROR] $1"
    exit 1
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "Este script deve ser executado como root ou com sudo."
    fi
}

confirm_action() {
    read -p "$1 (y/N)? " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_error "Operação cancelada pelo usuário."
    fi
}

run_mysql_query() {
    local query="$1"
    log_info "Executando query MySQL..."
    # Usar sudo mysql para conectar como root e direcionar para o banco glpidb
    if ! sudo mysql "$DB_NAME" -e "$query"; then
        log_error "Falha ao executar query MySQL: $query"
    fi
}

# --- Início do Script --- 

check_root
log_info "Iniciando processo de atualização do Power BI Reports Plugin."

log_info "Verificando diretório do plugin: ${PLUGIN_DIR}"
if [[ ! -d "$PLUGIN_DIR" ]]; then
    log_error "Diretório do plugin não encontrado: ${PLUGIN_DIR}. Verifique o caminho."
fi

log_info "Verificando conexão com o banco de dados ${DB_NAME}..."
if ! sudo mysql "$DB_NAME" -e "SELECT 1" > /dev/null 2>&1; then
    log_error "Não foi possível conectar ao banco de dados GLPI \"${DB_NAME}\". Verifique as credenciais e o nome do banco."
fi
log_success "Conexão com o banco de dados OK."

confirm_action "Você fez backup completo do banco de dados e da pasta do plugin?"

# --- Backup Adicional (apenas para este script, se esquecerem o primeiro) ---
# log_info "Realizando backup da pasta atual do plugin..."
# cp -a "${PLUGIN_DIR}" "${PLUGIN_DIR}_bkp_$(date +%Y%m%d%H%M%S)"
# log_success "Backup da pasta do plugin realizado."

log_info "Atualizando arquivos do plugin (assumindo que já foram copiados ou git pull)"
confirm_action "Os novos arquivos da versão v2.1.0 já foram copiados para ${PLUGIN_DIR}?"

log_info "Executando migrações SQL para tabelas de permissões e novos campos..."

# Criar tabelas de permissões (se não existirem)
run_mysql_query "SOURCE ${PLUGIN_DIR}/install/mysql/plugin_powerbireports_permissions.sql;"

# Adicionar novos campos na tabela de relatórios (se não existirem)
run_mysql_query "SOURCE ${PLUGIN_DIR}/install/mysql/migration_add_update_fields.sql;"

log_success "Migrações SQL concluídas."

log_info "Garantindo diretório de ícones..."
mkdir -p "${PLUGIN_DIR}/pics/icons" || log_error "Falha ao criar diretório de ícones."
chown www-data:www-data "${PLUGIN_DIR}/pics/icons" || log_error "Falha ao ajustar dono do diretório de ícones."
chmod 755 "${PLUGIN_DIR}/pics/icons" || log_error "Falha ao ajustar permissões do diretório de ícones."
log_success "Diretório de ícones garantido e permissões ajustadas."

log_info "Recomendado: Desativar e ativar o plugin no GLPI para forçar recarregamento."
log_info "Você pode também reiniciar o serviço web (ex: sudo systemctl restart apache2 ou sudo systemctl restart php8.1-fpm)"

log_success "Atualização do Power BI Reports Plugin para v2.1.0 concluída!"
log_info "Lembre-se de validar o funcionamento no GLPI (token, relatórios, permissões)."
log_info "Em caso de problemas, consulte a seção 'Rollback' no README.md"

exit 0
