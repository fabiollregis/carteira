# Sistema de Gerenciamento Financeiro

Sistema web desenvolvido em PHP para gerenciamento de finanças pessoais, com foco em controle de gastos, cartões de crédito e acompanhamento de objetivos financeiros.

## 📋 Funcionalidades

- Gerenciamento de cartões de crédito
- Controle de transações financeiras
- Dashboard com estatísticas mensais
- Acompanhamento de metas financeiras
- Sistema de autenticação de usuários
- Relatórios e gráficos de gastos

## 🚀 Tecnologias Utilizadas

- PHP 7.4+
- MySQL/MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap

## 📦 Pré-requisitos

- XAMPP, WAMP ou ambiente similar
- PHP 7.4 ou superior
- MySQL/MariaDB
- Servidor Web Apache

## 🔧 Instalação

1. Clone o repositório para sua pasta htdocs:
```bash
git clone [url-do-repositorio] carteira
```

2. Importe o banco de dados:
```bash
mysql -u root -p < database.sql
```

3. Configure o arquivo de conexão em `/config/database.php`

4. Acesse o sistema através do navegador:
```
http://localhost/carteira
```

## 📁 Estrutura do Projeto

```
carteira/
├── assets/          # Recursos estáticos (CSS, JS, imagens)
├── bd/             # Scripts e backups do banco de dados
├── config/         # Arquivos de configuração
├── controllers/    # Controladores do sistema
├── includes/       # Arquivos incluídos globalmente
├── models/         # Modelos de dados
└── views/          # Arquivos de visualização
```

## ⚙️ Configuração

1. Renomeie o arquivo `config/database.example.php` para `config/database.php`
2. Configure as credenciais do banco de dados
3. Ajuste as configurações do sistema em `config/config.php`

## 🔐 Segurança

- Sistema de autenticação de usuários
- Proteção contra SQL Injection
- Validação de dados de entrada
- Sessões seguras

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## ✨ Contribuição

1. Faça o fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 🤝 Suporte

Em caso de dúvidas ou problemas, abra uma issue no repositório ou entre em contato com a equipe de desenvolvimento.
#   c a r t e i r a  
 