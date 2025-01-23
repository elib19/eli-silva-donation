
Plugin de Doações para WooCommerce
Este plugin permite que você adicione uma funcionalidade de doações para WooCommerce, permitindo que os clientes escolham uma instituição para doar 30% do valor da compra. Ele também inclui um formulário para cadastro de novas instituições e um painel administrativo para gerenciar as doações realizadas.

Funcionalidades
Formulário de Cadastro de Instituições: Permite que novas instituições sejam cadastradas através de um formulário simples. As informações incluem nome, CNPJ, endereço, estado, chave PIX e e-mail.
Seleção de Instituições no Carrinho: Permite que os clientes escolham uma instituição para a qual serão doadas 30% do valor de sua compra.
Envio de E-mails: Ao completar uma compra, o cliente, a instituição e o administrador recebem e-mails com as informações da doação.
Painel Administrativo: Adiciona uma página no painel administrativo do WordPress, onde você pode visualizar e gerenciar todas as doações feitas.
Requisitos
WordPress 5.0 ou superior
WooCommerce 3.0 ou superior
PHP 7.2 ou superior
Como Instalar
Instalação Manual:

Faça o download do arquivo ZIP do plugin.
No painel do WordPress, vá para Plugins > Adicionar Novo.
Clique em Enviar Plugin e selecione o arquivo ZIP.
Clique em Instalar Agora e, em seguida, ative o plugin.
Instalação via FTP:

Faça o upload do plugin para a pasta wp-content/plugins/.
No painel do WordPress, ative o plugin na seção Plugins.
Como Usar
Cadastro de Instituições
Criar uma Página para o Formulário de Cadastro

Crie uma nova página no WordPress onde você deseja que o formulário de cadastro de instituições apareça.
No editor de página, insira o seguinte shortcode para exibir o formulário de cadastro de instituições:
plaintext
Copiar
Editar
[donation_form]
Publique a página.
Formulário de Cadastro

Na página criada, o formulário permitirá que você cadastre novas instituições. As informações solicitadas são:
Nome da instituição
CNPJ
Endereço
Estado
Tipo de chave PIX (CNPJ, número de celular ou chave aleatória)
Chave PIX
E-mail da instituição
Cadastrar uma Instituição

Após preencher o formulário com os dados da instituição, clique em Cadastrar. Isso adicionará a instituição ao banco de dados do plugin e ela estará disponível para seleção no processo de checkout.
Seleção de Instituição no Carrinho
Durante o processo de checkout no WooCommerce, o cliente verá uma opção para escolher a instituição beneficiada.
Ao escolher a instituição, 30% do valor da compra será destinado à instituição selecionada.
Painel Administrativo
No painel do WordPress, vá para Doações > Gerenciar Doações.
Você verá uma tabela com informações sobre todas as doações, incluindo:
Nome do cliente
Instituição beneficiada
Valor da doação
Data da doação
Status da doação (Pendente)
E-mails Enviados
E-mail para o cliente: Confirmação de que a doação foi realizada e destinada à instituição escolhida.
E-mail para a instituição: Detalhes sobre a doação, incluindo o valor, o cliente e a data.
E-mail para o administrador: Detalhes da doação realizada, incluindo nome do cliente, valor da doação e chave PIX da instituição.
Customizações
O código do plugin está bem estruturado e pode ser facilmente adaptado para incluir novos campos ou funcionalidades.
Caso precise de ajustes, você pode personalizar os hooks e filtros do WooCommerce que são utilizados pelo plugin.
Considerações Finais
Este plugin é uma solução simples e eficiente para adicionar um sistema de doações a um site WooCommerce, permitindo que seus clientes contribuam para causas sociais de forma rápida e direta. Você pode personalizar o formulário de cadastro, as doações e o painel administrativo para atender às necessidades do seu site.

Licença
Este plugin é distribuído sob a Licença GPLv2.

Para mais informações, acesse nosso site: https://juntoaqui.com.br
