### README - Sistema de Gerenciamento de Doações no WordPress com Integração ao WooCommerce

---

#### **Descrição do Projeto**  
Este sistema foi desenvolvido para gerenciar doações no WordPress, integrado ao WooCommerce. Ele permite cadastrar instituições, atribuir doações realizadas por clientes e gerenciar status de pagamentos de forma eficiente. O sistema é ideal para marketplaces ou sites que desejam implementar funcionalidades de doação com controle detalhado.

---

#### **Funcionalidades Principais**

1. **Painel Administrativo de Doações**  
   - Exibe as doações realizadas com informações detalhadas:
     - Nome e e-mail do doador.
     - Nome da instituição beneficiada.
     - Chave Pix da instituição.
     - Status do pagamento: Pendente ou Pago.
   - Possibilidade de alterar o status de pagamento diretamente no painel.

2. **Formulário de Cadastro de Instituições**  
   - Formulário gerado via shortcode para coletar dados das instituições:
     - Nome, CNPJ, telefone, WhatsApp.
     - Tipo de instituição (hospital, igreja, entidade beneficente, etc.).
     - Endereço completo (rua, número, bairro, cidade, estado, CEP).
   - Estado disponibilizado como campo `select` com todas as opções brasileiras.
   - Instituições cadastradas aparecem automaticamente no painel administrativo.

3. **Integração ao Carrinho do WooCommerce**  
   - Campo adicional no carrinho com um `select` para escolher a instituição que receberá a doação.
   - Permite configurar percentuais de doação (padrão 30%, configurável entre 2% e 30%).
   - Percentual é aplicado automaticamente ao valor total dos produtos no carrinho.

4. **Notificações por E-mail**  
   - **Administrador**:
     - Recebe informações detalhadas sobre cada doação realizada.
   - **Instituição**:
     - Notificação sobre a doação, incluindo prazo de pagamento (15 dias úteis).
   - **Cliente**:
     - Confirmação com informações da instituição beneficiada, incluindo contato.
   - **Após Confirmação de Pagamento**:
     - Notificações automáticas para o administrador, instituição e cliente.

5. **Automação de Processos**  
   - Dados de doadores puxados automaticamente do WooCommerce.
   - Integração direta com banco de dados para gerenciar doações e instituições.

---

#### **Como Instalar**

1. Copie o código fornecido e salve em um arquivo PHP no diretório de temas ou plugins do WordPress.
   - Exemplo: `wp-content/plugins/sistema-doacoes/sistema-doacoes.php`.
2. Ative o plugin pelo painel administrativo do WordPress.
3. Use o shortcode `[form_doacao]` para inserir o formulário de cadastro em qualquer página.
4. Configure as opções no painel de administração de doações.

---

#### **Dependências**
- WordPress.
- WooCommerce.
- Servidor com suporte a PHP 7.4 ou superior.
- E-mail SMTP configurado no WordPress para envio de notificações.

---

#### **Como Usar**

1. Acesse o painel de administração e cadastre instituições utilizando o formulário shortcode.  
2. Certifique-se de que o WooCommerce está ativo e funcional no site.  
3. Ao adicionar produtos ao carrinho, o cliente poderá selecionar a instituição para doação.  
4. Gerencie os status de pagamento das doações diretamente no painel administrativo.  
5. Verifique notificações por e-mail para acompanhar o processo de doação.

---

#### **Licença**  
Este projeto está disponível para uso pessoal ou comercial. Por favor, mantenha os créditos ao autor.

---

#### **Contato**  
Para mais informações, visite nosso site oficial:  
[https://juntoaqui.com.br](https://juntoaqui.com.br)
