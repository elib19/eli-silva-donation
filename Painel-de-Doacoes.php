<?php
/**
 * Plugin Name: Painel de Doações
 * Plugin URI: https://juntoaqui.com.br
 * Description: Plugin para adicionar funcionalidades de doação ao WooCommerce, com seleção de instituição no checkout e envio de e-mail para o administrador.
 * Version: 1.1.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: Painel de Doações
 * Domain Path: /languages
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Ativação do plugin: Criação de tabelas no banco de dados
function donation_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'instituicoes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(255) NOT NULL,
        cnpj VARCHAR(18) NOT NULL,
        telefone VARCHAR(15) NOT NULL,
        whatsapp VARCHAR(15) NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        endereco TEXT NOT NULL,
        cidade VARCHAR(100) NOT NULL,
        estado VARCHAR(2) NOT NULL,
        cep VARCHAR(9) NOT NULL,
        chave_pix VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $doacoes_table = $wpdb->prefix . 'doacoes';
    $sql = "CREATE TABLE $doacoes_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        instituicao_id BIGINT(20) UNSIGNED NOT NULL,
        valor DECIMAL(10, 2) NOT NULL,
        pedido_id BIGINT(20) UNSIGNED NOT NULL,
        instituicao_nome VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}
register_activation_hook(__FILE__, 'donation_plugin_activate');

// Função para registrar o menu no painel de administração
function donation_register_menu() {
    add_menu_page(
        'Painel de Doações',
        'Doações',
        'manage_options',
        'painel-doacoes',
        'donation_admin_page',
        'dashicons-heart',
        26
    );
}
add_action('admin_menu', 'donation_register_menu');

// Página administrativa para gerenciar doações
function donation_admin_page() {
    global $wpdb;
    $instituicoes_table = $wpdb->prefix . 'instituicoes';
    $doacoes_table = $wpdb->prefix . 'doacoes';

    echo '<h1>Painel de Doações</h1>';

    echo '<h2>Doações Recentes</h2>';
    $results = $wpdb->get_results("SELECT * FROM $doacoes_table");
    if ($results) {
        echo '<table><tr><th>Instituição</th><th>Valor da Doação</th><th>Status</th><th>Pedido</th></tr>';
        foreach ($results as $row) {
            echo "<tr><td>{$row->instituicao_nome}</td><td>R$ {$row->valor}</td><td>{$row->status}</td><td><a href='post.php?post={$row->pedido_id}&action=edit'>Ver Pedido</a></td></tr>";
        }
        echo '</table>';
    } else {
        echo '<p>Nenhuma doação registrada ainda.</p>';
    }
}

// Adiciona a opção de configuração na página de configurações do WordPress
function esd_donation_settings() {
    add_option('esd_donation_percentage', 10);  // Valor padrão é 10%
    register_setting('general', 'esd_donation_percentage', 'intval');
    add_settings_section('esd_donation_section', 'Configurações de Doação', null, 'general');
    add_settings_field('esd_donation_percentage', 'Porcentagem de Doação', 'esd_donation_percentage_callback', 'general', 'esd_donation_section');
}
add_action('admin_init', 'esd_donation_settings');

// Adiciona o campo de seleção de instituição ao checkout do WooCommerce
function adicionar_campo_instituicao_checkout($fields) {
    global $wpdb;

    // Obter as instituições cadastradas
    $instituicoes = $wpdb->get_results("SELECT id, nome FROM {$wpdb->prefix}instituicoes");

    // Verifica se há instituições disponíveis
    if (!empty($instituicoes)) {
        $opcoes = array('' => 'Selecione uma instituição');
        foreach ($instituicoes as $instituicao) {
            $opcoes[$instituicao->id] = $instituicao->nome;
        }

        // Adiciona o campo de seleção ao checkout
        $fields['billing']['billing_instituicao'] = array(
            'type' => 'select',
            'label' => __('Instituição para Doação', 'woocommerce'),
            'required' => true,
            'options' => $opcoes,
        );
    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'adicionar_campo_instituicao_checkout');

// Salvar o campo de instituição no meta do pedido
function salvar_instituicao_meta_pedido($order_id) {
    if (!empty($_POST['billing_instituicao'])) {
        update_post_meta($order_id, '_instituicao', sanitize_text_field($_POST['billing_instituicao']));
    }
}
add_action('woocommerce_checkout_update_order_meta', 'salvar_instituicao_meta_pedido');

// Exibir a instituição selecionada no admin do pedido
function exibir_instituicao_admin_pedido($order) {
    $instituicao = get_post_meta($order->get_id(), '_instituicao', true);
    if ($instituicao) {
        echo '<p><strong>' . __('Instituição para Doação', 'woocommerce') . ':</strong> ' . esc_html($instituicao) . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'exibir_instituicao_admin_pedido', 10, 1);

// Função que cria o campo de input para a porcentagem de doação
function esd_donation_percentage_callback() {
    $value = get_option('esd_donation_percentage', 10);
    echo "<input type='number' name='esd_donation_percentage' value='" . esc_attr($value) . "' min='0' max='100' step='1' /> %";
}

// Shortcode para cadastro de instituições
function donation_form_shortcode() {
    ob_start();
    ?>
    <form method="post" action="">
        <label>Nome da Instituição:</label><br>
        <input type="text" name="nome" required><br>
        <label>CNPJ:</label><br>
        <input type="text" name="cnpj" required><br>
        <label>Telefone:</label><br>
        <input type="text" name="telefone" required><br>
        <label>WhatsApp:</label><br>
        <input type="text" name="whatsapp" required><br>
        <label>Tipo da Instituição:</label><br>
        <select name="tipo" required>
            <option value="hospital">Hospital de Câncer</option>
            <option value="igreja">Igreja</option>
            <option value="beneficente">Entidade Beneficente</option>
            <option value="apoio">Casa de Apoio</option>
            <option value="recuperacao">Casa de Recuperação</option>
        </select><br>
        <label>Endereço:</label><br>
        <textarea name="endereco" required></textarea><br>
        <label>Cidade:</label><br>
        <input type="text" name="cidade" required><br>
        <label>Estado:</label><br>
        <select name="estado" required>
            <option value="AC">Acre</option>
            <option value="AL">Alagoas</option>
            <option value="AP">Amapá</option>
            <option value="AM">Amazonas</option>
            <option value="BA">Bahia</option>
            <option value="CE">Ceará</option>
            <option value="DF">Distrito Federal</option>
            <option value="ES">Espírito Santo</option>
            <option value="GO">Goiás</option>
            <option value="MA">Maranhão</option>
            <option value="MT">Mato Grosso</option>
            <option value="MS">Mato Grosso do Sul</option>
            <option value="MG">Minas Gerais</option>
            <option value="PA">Pará</option>
            <option value="PB">Paraíba</option>
            <option value="PR">Paraná</option>
            <option value="PE">Pernambuco</option>
            <option value="PI">Piauí</option>
            <option value="RJ">Rio de Janeiro</option>
            <option value="RN">Rio Grande do Norte</option>
            <option value="RS">Rio Grande do Sul</option>
            <option value="RO">Rondônia</option>
            <option value="RR">Roraima</option>
            <option value="SC">Santa Catarina</option>
            <option value="SP">São Paulo</option>
            <option value="SE">Sergipe</option>
            <option value="TO">Tocantins</option>
        </select><br>
        <label>CEP:</label><br>
        <input type="text" name="cep" required><br>
        <label>Chave PIX:</label><br>
        <input type="text" name="chave_pix" required><br>
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        <button type="submit" name="submit_instituicao">Cadastrar Instituição</button>
    </form>
    <?php

    if (isset($_POST['submit_instituicao'])) {
        global $wpdb;

        $nome = sanitize_text_field($_POST['nome']);
        $cnpj = sanitize_text_field($_POST['cnpj']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $whatsapp = sanitize_text_field($_POST['whatsapp']);
        $tipo = sanitize_text_field($_POST['tipo']);
        $endereco = sanitize_textarea_field($_POST['endereco']);
        $cidade = sanitize_text_field($_POST['cidade']);
        $estado = sanitize_text_field($_POST['estado']);
        $cep = sanitize_text_field($_POST['cep']);
        $chave_pix = sanitize_text_field($_POST['chave_pix']);
        $email = sanitize_email($_POST['email']);

        $wpdb->insert(
            $wpdb->prefix . 'instituicoes',
            array(
                'nome' => $nome,
                'cnpj' => $cnpj,
                'telefone' => $telefone,
                'whatsapp' => $whatsapp,
                'tipo' => $tipo,
                'endereco' => $endereco,
                'cidade' => $cidade,
                'estado' => $estado,
                'cep' => $cep,
                'chave_pix' => $chave_pix,
                'email' => $email
            )
        );

        wp_mail(
            get_option('admin_email'), // E-mail do administrador
            'Nova Instituição Cadastrada',
            "Uma nova instituição foi cadastrada. Nome: $nome, CNPJ: $cnpj, Email: $email"
        );

        echo '<p>Instituição cadastrada com sucesso!</p>';
    }

    return ob_get_clean();
}
add_shortcode('form_doacao', 'donation_form_shortcode');

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    // Enqueue seu CSS
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    
    // Enqueue seu JS
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
});
