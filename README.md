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
     - Valor da doação.
     - Status do pagamento: Pendente ou Pago.
   - Possibilidade de alterar o status de pagamento diretamente no painel.

2. **Formulário de Cadastro de Instituições**  
   - Formulário gerado via shortcode para coletar dados das instituições:
     - Nome, CNPJ, telefone, WhatsApp.
     - Tipo de instituição (hospital, igreja, entidade beneficente, etc.).
     - Endereço completo (rua, número, bairro, cidade, estado, CEP).
   - Estado disponibilizado como campo `select` com todas as opções brasileiras.
   - Campo para depoimento da instituição.
   - Campo para descrever as atividades da instituição.
   - Campos para URL do banner, Facebook, Instagram e site oficial (opcional).
   - Instituições cadastradas aparecem automaticamente no painel administrativo.

3. **Formulário para Cadastrar ou Editar Depoimentos**  
   - Shortcode `[form_depoimento]` para permitir que as instituições logadas adicionem ou editem seus depoimentos.
   - O depoimento é armazenado como um meta dado do usuário.

4. **Integração ao Carrinho do WooCommerce**  
   - Campo adicional no carrinho com um `select` para escolher a instituição que receberá a doação.
   - Permite configurar percentuais de doação (configurável entre 0% e 40%).
   - O percentual é aplicado automaticamente ao valor total dos produtos no carrinho.
   - O campo de seleção da instituição é obrigatório apenas se houver pelo menos uma instituição cadastrada.

5. **Notificações por E-mail**  
   - **Administrador**:
     - Recebe informações detalhadas sobre cada doação realizada.
   - **Instituição**:
     - Notificação sobre a doação, incluindo prazo de pagamento (30 dias úteis).
   - **Cliente**:
     - Confirmação com informações da instituição beneficiada, incluindo contato.
   - **Após Confirmação de Pagamento**:
     - Notificações automáticas para o administrador, instituição e cliente.

6. **Automatização de Processos**  
   - Dados de doadores puxados automaticamente do WooCommerce.
   - Integração direta com banco de dados para gerenciar doações e instituições.

7. **Configuração de Percentual de Doação**  
   - O administrador pode ajustar o percentual de doação entre 0% e 40% diretamente no painel de administração.
   - A configuração é acessível através do menu de configurações do WordPress.

---

#### **Shortcodes Disponíveis**

- **[form_doacao]**: Exibe o formulário de cadastro de instituições.
- **[form_depoimento]**: Exibe o formulário para que as instituições logadas possam cadastrar ou editar seus depoimentos.
- **[exibir_depoimentos]**: Exibe os depoimentos das instituições cadastradas.
- **[exibir_instituicoes]**: Exibe uma lista das instituições cadastradas com suas informações e links para suas páginas.

---

#### **Como Instalar**

1. Copie o código fornecido e salve em um arquivo PHP no diretório de temas ou plugins do WordPress.
   - Exemplo: `wp-content/plugins/sistema-doacoes/sistema-doacoes.php`.
2. Ative o plugin pelo painel administrativo do WordPress.
3. Use os shortcodes mencionados para exibir os formulários e listas de instituições.
4. Configure as opções no painel de administração de doações.

---

#### **Dependências**
- WordPress.
- WooCommerce.
- Servidor com suporte a PHP 7.4 ou superior.
- E-mail SMTP configurado no WordPress para envio de notificações.

---

#### **Licença**  
Este plugin é distribuído sob a **Licença Pública Geral GNU (GPL) v2 ou posterior**. Isso significa que você tem liberdade para usá-lo, modificá-lo e distribuí-lo, desde que mantenha a mesma licença. Para mais informações, consulte [GNU.org](https://www.gnu.org/licenses/gpl-2.0.html).

---

#### **Contato**  
Para mais informações, visite nosso site oficial:  
[https://juntoaqui.com.br](https://juntoaqui.com.br)

