# Power BI Reports Plugin para GLPI 11

## Descrição
Este plugin permite integrar relatórios do Power BI ao GLPI, funcionando como uma **Central de Relatórios** onde é possível cadastrar e visualizar múltiplos relatórios diretamente na interface do GLPI.

## Funcionalidades
- ✅ Cadastro de múltiplos relatórios do Power BI
- ✅ Central de Relatórios com cards visuais
- ✅ Ícones personalizados para cada relatório
- ✅ Visualização de relatórios embutidos na interface do GLPI
- ✅ Gerenciamento de credenciais (Tenant ID, Client ID, Client Secret)
- ✅ Cache de tokens para melhor performance
- ✅ Controle de permissões do plugin (READ para visualizar, UPDATE para gerenciar)
- ✅ Permissões por relatório (segmentação por usuários e grupos)

## Requisitos
- GLPI 11.x
- PHP 8.1 ou superior
- Acesso ao banco de dados MySQL/MariaDB
- Extensão cURL do PHP habilitada
- Extensão fileinfo do PHP habilitada (para upload de ícones)

## Instalação

### Passo 1: Criar as tabelas no banco de dados

**IMPORTANTE:** O GLPI 11 não permite a criação de tabelas via queries diretas em plugins. As tabelas devem ser criadas manualmente antes de instalar o plugin.

Execute os comandos SQL abaixo no banco de dados do GLPI (geralmente `glpidb`):

```bash
mysql glpidb < /var/www/glpi/plugins/powerbireports/install/mysql/plugin_powerbireports_config.sql
mysql glpidb < /var/www/glpi/plugins/powerbireports/install/mysql/plugin_powerbireports_reports.sql
mysql glpidb < /var/www/glpi/plugins/powerbireports/install/mysql/plugin_powerbireports_permissions.sql
mysql glpidb < /var/www/glpi/plugins/powerbireports/install/mysql/migration_add_update_fields.sql
```

Ou execute os scripts SQL diretamente:

```sql
-- Tabela de configuração (credenciais do Power BI)
CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) DEFAULT NULL,
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `group_id` varchar(255) DEFAULT NULL,
  `report_id` varchar(255) DEFAULT NULL,
  `last_token` text DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `last_embed_token` text DEFAULT NULL,
  `embed_token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relatórios (múltiplos relatórios)
CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `group_id` varchar(255) NOT NULL,
  `report_id` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_path` varchar(255) DEFAULT NULL,
   `update_mode` varchar(30) NOT NULL DEFAULT 'api',
   `update_table` varchar(255) DEFAULT NULL,
   `update_column` varchar(255) DEFAULT NULL,
   `dataset_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de permissões por usuário
CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports_users` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_powerbireports_reports_id` int(11) NOT NULL,
   `users_id` int(11) NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unique_report_user` (`plugin_powerbireports_reports_id`,`users_id`),
   KEY `plugin_powerbireports_reports_id` (`plugin_powerbireports_reports_id`),
   KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de permissões por grupo
CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports_groups` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_powerbireports_reports_id` int(11) NOT NULL,
   `groups_id` int(11) NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unique_report_group` (`plugin_powerbireports_reports_id`,`groups_id`),
   KEY `plugin_powerbireports_reports_id` (`plugin_powerbireports_reports_id`),
   KEY `groups_id` (`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Passo 1.1: Criar diretório para ícones

```bash
mkdir -p /var/www/glpi/plugins/powerbireports/pics/icons
chown www-data:www-data /var/www/glpi/plugins/powerbireports/pics/icons
chmod 755 /var/www/glpi/plugins/powerbireports/pics/icons
```

### Passo 2: Instalar o plugin no GLPI

1. Acesse o GLPI como administrador.
2. Vá em **Configuração > Plugins**.
3. Localize o plugin **Power BI Reports** e clique em **Instalar**.
4. Após a instalação, clique em **Ativar**.

### Passo 3: Configurar as credenciais do Power BI

1. Vá em **Plugins > Power BI Reports > Configuração** (ou pelo menu lateral).
2. Preencha os campos na seção **Authorization Settings**:
   - **Tenant ID**: ID do tenant do Azure AD.
   - **Client ID**: ID do aplicativo registrado no Azure AD.
   - **Client Secret**: Segredo do aplicativo.
3. Clique em **Salvar**.

### Passo 4: Cadastrar relatórios

1. Na mesma página de configuração, localize a seção **Add New Report**.
2. Preencha os campos:
   - **Report Name**: Nome amigável para o relatório.
   - **Group ID (Workspace ID)**: ID do workspace no Power BI.
   - **Report ID**: ID do relatório no Power BI.
   - **Description**: Descrição opcional do relatório.
   - **Icon**: Ícone personalizado para o relatório (opcional).
3. Clique em **Add Report**.
4. Repita para adicionar mais relatórios.

### Passo 5: Definir permissões por relatório

1. Em **Configuração > Power BI Reports**, localize a lista de relatórios.
2. Clique em **Edit** no relatório desejado.
3. Na seção **Permissões de Visualização**:
   - Selecione **Usuários Autorizados** e/ou **Grupos Autorizados**.
4. Clique em **Salvar**.

> Regra de acesso: se nenhum usuário/grupo for definido no relatório, ele fica visível para todos os usuários com direito READ do plugin.

## Ícones Personalizados

Cada relatório pode ter um ícone personalizado que será exibido na Central de Relatórios.

### Formatos suportados
- PNG (recomendado)
- JPG/JPEG
- GIF
- SVG
- WebP

### Tamanhos recomendados
| Tamanho | Uso |
|---------|-----|
| **64x64 px** | Ideal para exibição padrão |
| **128x128 px** | Boa qualidade em telas retina |
| **256x256 px** | Máximo recomendado |

> **Dica:** Use imagens quadradas para melhor visualização. Imagens maiores serão redimensionadas automaticamente para 64x64px na exibição.

### Alterando o ícone
1. Acesse **Plugins > Power BI Reports > Configuração**.
2. Clique em **Edit** no relatório desejado.
3. Faça upload de um novo ícone ou marque "Remover ícone atual" para voltar ao ícone padrão.

## Uso

### Visualizar Relatórios
1. Acesse **Plugins > Power BI Reports**.
2. Na Central de Relatórios, clique em **View Report** no card do relatório desejado.
3. O relatório será carregado diretamente na interface do GLPI.

### Gerenciar Relatórios
1. Acesse **Plugins > Power BI Reports > Configuração**.
2. Na tabela de relatórios registrados, você pode:
   - **View**: Visualizar o relatório
   - **Edit**: Editar os dados do relatório (nome, IDs, descrição, ícone)
   - **Delete**: Excluir o relatório

## Atualização de versões anteriores

### Método recomendado (v1.1.0 → v2.1.0)

> **Não desinstale o plugin para atualizar.** A atualização deve ser feita “em cima” da versão atual para preservar configuração e vínculos.

1. **Agende janela de manutenção** (evite uso simultâneo durante a atualização).
2. **Faça backup do banco e dos arquivos**:

```bash
mysqldump glpidb > /root/backup_glpidb_before_powerbireports_2_1_0.sql
cp -a /var/www/glpi/plugins/powerbireports /var/www/glpi/plugins/powerbireports_bkp_2_1_0
```

3. **Atualize os arquivos do plugin** para a versão nova (Git ou cópia de release).

4. **Execute as migrações SQL**:

```bash
mysql glpidb < /var/www/glpi/plugins/powerbireports/install/mysql/plugin_powerbireports_permissions.sql
mysql glpidb < /var/www/glpi/plugins/powerbireports/install/mysql/migration_add_update_fields.sql
```

5. **Garanta o diretório de ícones**:

```bash
mkdir -p /var/www/glpi/plugins/powerbireports/pics/icons
chown www-data:www-data /var/www/glpi/plugins/powerbireports/pics/icons
chmod 755 /var/www/glpi/plugins/powerbireports/pics/icons
```

6. **No GLPI**, desative e ative o plugin novamente (ou recarregue o serviço web/caches da aplicação).

7. **Valide o funcionamento**:
   - Credenciais e geração de token
   - Listagem e abertura de relatórios
   - Edição de relatório
   - Permissões por usuário e grupo

### Migração manual (caso necessário)

Se precisar aplicar migração manualmente, execute:

```sql
ALTER TABLE `glpi_plugin_powerbireports_reports`
ADD COLUMN `icon_path` varchar(255) DEFAULT NULL AFTER `description`;

ALTER TABLE `glpi_plugin_powerbireports_reports`
ADD COLUMN `update_mode` varchar(30) NOT NULL DEFAULT 'api' AFTER `icon_path`,
ADD COLUMN `update_table` varchar(255) DEFAULT NULL AFTER `update_mode`,
ADD COLUMN `update_column` varchar(255) DEFAULT NULL AFTER `update_table`,
ADD COLUMN `dataset_id` varchar(255) DEFAULT NULL AFTER `update_column`;

CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports_users` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_powerbireports_reports_id` int(11) NOT NULL,
   `users_id` int(11) NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unique_report_user` (`plugin_powerbireports_reports_id`,`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports_groups` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_powerbireports_reports_id` int(11) NOT NULL,
   `groups_id` int(11) NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unique_report_group` (`plugin_powerbireports_reports_id`,`groups_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Rollback (se necessário)

Em caso de falha, restaure backup do banco e pasta do plugin:

```bash
mysql glpidb < /root/backup_glpidb_before_powerbireports_2_1_0.sql
rm -rf /var/www/glpi/plugins/powerbireports
mv /var/www/glpi/plugins/powerbireports_bkp_2_1_0 /var/www/glpi/plugins/powerbireports
```

## Desinstalação

**IMPORTANTE:** O GLPI 11 não permite a remoção de tabelas via queries diretas em plugins. As tabelas devem ser removidas manualmente após a desinstalação do plugin.

1. Desinstale o plugin pelo GLPI.
2. Execute os comandos SQL abaixo para remover as tabelas:

```sql
DROP TABLE IF EXISTS `glpi_plugin_powerbireports_configs`;
DROP TABLE IF EXISTS `glpi_plugin_powerbireports_reports`;
DROP TABLE IF EXISTS `glpi_plugin_powerbireports_reports_users`;
DROP TABLE IF EXISTS `glpi_plugin_powerbireports_reports_groups`;
```

Ou via terminal:

```bash
mysql -e 'DROP TABLE IF EXISTS glpi_plugin_powerbireports_configs, glpi_plugin_powerbireports_reports, glpi_plugin_powerbireports_reports_users, glpi_plugin_powerbireports_reports_groups;' glpidb
```

3. Remova os arquivos de ícones:

```bash
rm -rf /var/www/glpi/plugins/powerbireports/pics/icons/
```

## Estrutura de Arquivos

```
powerbireports/
├── ajax/
│   ├── get_embed_token.php     # Gera embed token para relatório específico
│   └── get_token.php           # Gera access token
├── front/
│   ├── index.php               # Central de Relatórios (página principal)
│   ├── config.form.php         # Configuração e gerenciamento de relatórios
│   ├── report.view.php         # Visualização de relatório individual
│   ├── report.form.php         # Edição de relatório
│   ├── icon.php                # Servidor de ícones (autenticado)
│   ├── test_token.php          # Teste de access token
│   └── test_embed_token.php    # Teste de embed token
├── inc/
│   ├── config.class.php        # Classe de configuração e tokens
│   ├── configitem.class.php    # Model para tabela de configs
│   ├── reportitem.class.php    # Model para tabela de relatórios
│   └── menu.class.php          # Definição do menu
├── install/
│   ├── install.php             # Script de instalação
│   └── mysql/                  # Scripts SQL individuais
│       ├── plugin_powerbireports_config.sql
│       ├── plugin_powerbireports_reports.sql
│       ├── plugin_powerbireports_permissions.sql
│       └── migration_add_update_fields.sql
├── upgrade_powerbireports.sh    # Script de atualização automatizada
├── pics/
│   └── icons/                  # Diretório para ícones dos relatórios
├── hook.php                    # Hooks do plugin
├── setup.php                   # Setup do plugin
└── README.md                   # Esta documentação
```

## Estrutura do Banco de Dados

### glpi_plugin_powerbireports_configs
Armazena as credenciais de acesso ao Power BI.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | Chave primária |
| tenant_id | varchar(255) | ID do tenant do Azure AD |
| client_id | varchar(255) | ID do aplicativo |
| client_secret | varchar(255) | Segredo do aplicativo |
| group_id | varchar(255) | ID do workspace (legacy) |
| report_id | varchar(255) | ID do relatório (legacy) |
| last_token | text | Último access token gerado |
| token_expiry | datetime | Data de expiração do token |
| last_embed_token | text | Último embed token gerado |
| embed_token_expiry | datetime | Data de expiração do embed token |

### glpi_plugin_powerbireports_reports
Armazena os relatórios cadastrados.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | Chave primária |
| name | varchar(255) | Nome do relatório |
| group_id | varchar(255) | ID do workspace do Power BI |
| report_id | varchar(255) | ID do relatório no Power BI |
| description | text | Descrição do relatório |
| icon_path | varchar(255) | Caminho do ícone personalizado |
| update_mode | varchar(30) | Fonte de atualização (`api` ou `table_column`) |
| update_table | varchar(255) | Tabela para leitura de data de atualização |
| update_column | varchar(255) | Coluna de data de atualização |
| dataset_id | varchar(255) | Dataset ID do Power BI |
| created_at | datetime | Data de criação |
| updated_at | datetime | Data de atualização |

### glpi_plugin_powerbireports_reports_users
Armazena permissões por usuário para cada relatório.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | Chave primária |
| plugin_powerbireports_reports_id | int | ID do relatório |
| users_id | int | ID do usuário GLPI autorizado |

### glpi_plugin_powerbireports_reports_groups
Armazena permissões por grupo para cada relatório.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | Chave primária |
| plugin_powerbireports_reports_id | int | ID do relatório |
| groups_id | int | ID do grupo GLPI autorizado |

## Troubleshooting

### Erro 500 ao acessar o plugin
- Verifique os logs do Apache: `tail -f /var/log/apache2/error.log`
- Certifique-se de que as tabelas foram criadas no banco de dados
- Verifique as permissões do diretório de ícones

### Ícone não aparece
- Verifique se o diretório `pics/icons/` existe e tem permissões corretas
- Formatos suportados: PNG, JPG, GIF, SVG, WebP
- Tamanho máximo recomendado: 256x256 pixels

### Relatório não carrega
- Teste o Access Token em **Configuração > Test Access Token**
- Teste o Embed Token em **Configuração > Test Embed Token**
- Verifique se as credenciais (Tenant ID, Client ID, Client Secret) estão corretas
- Verifique se o aplicativo no Azure AD tem permissões para Power BI

### Erro "Permission denied"
- O usuário precisa ter permissão READ no perfil para visualizar relatórios
- O usuário precisa ter permissão UPDATE no perfil para gerenciar relatórios
- Configure as permissões em **Administração > Perfis > [Perfil] > Power BI Reports**

## Suporte
Em caso de dúvidas ou problemas, entre em contato com o administrador do sistema.

## Changelog

### v2.1.0
- Adicionada segmentação de visualização por relatório (usuários e grupos)
- Incluídas tabelas de permissões por relatório (`reports_users` e `reports_groups`)
- Adicionados campos de atualização por relatório (`update_mode`, `update_table`, `update_column`, `dataset_id`)
- Atualizada documentação de instalação, upgrade e desinstalação para a nova estrutura

### v1.1.0
- Adicionado suporte a ícones personalizados para relatórios
- Novo script `icon.php` para servir ícones de forma segura
- Atualizada documentação com instruções de upgrade

### v1.0.0
- Versão inicial
- Central de Relatórios com cards visuais
- Gerenciamento de múltiplos relatórios
- Integração com Power BI via Azure AD

## Licença
Este plugin é distribuído sob a licença GPLv2+.
