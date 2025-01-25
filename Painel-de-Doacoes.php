<?php
/*
Plugin Name: Cadastro de Instituições para Doação
Description: Plugin para cadastrar instituições que podem receber doações e integrar com o WooCommerce.
Version: 1.0
Author: Seu Nome
*/

// Ativação do plugin
register_activation_hook(__FILE__, 'cid_create_tables');
function cid_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'instituicoes';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome tinytext NOT NULL,
        cnpj varchar(18) NOT NULL,
        telefone varchar(15),
        whatsapp varchar(15),
        tipo varchar(50),
        endereco text,
        chave_pix varchar(50),
        email varchar(100),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Criação do formulário de cadastro
function cid_instituicao_form() {
    ob_start(); ?>
    <form action="" method="post">
        <!-- Campos do formulário -->
        <input type="text" name="nome" placeholder="Nome da Instituição" required>
        <input type="text" name="cnpj" placeholder="CNPJ" required>
        <input type="text" name="telefone" placeholder="Telefone">
        <input type="text" name="whatsapp" placeholder="WhatsApp">
        <input type="text" name="tipo" placeholder="Tipo (ex: hospital, igreja)">
        <textarea name="endereco" placeholder="Endereço"></textarea>
        <input type="text" name="chave_pix" placeholder="Chave PIX">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="submit" name="submit_instituicao" value="Cadastrar Instituição">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('form_doacao', 'cid_instituicao_form');

// Processamento do formulário
function cid_process_instituicao_form() {
    if (isset($_POST['submit_instituicao'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'instituicoes';

        $wpdb->insert($table_name, array(
            'nome' => sanitize_text_field($_POST['nome']),
            'cnpj' => sanitize_text_field($_POST['cnpj']),
            'telefone' => sanitize_text_field($_POST['telefone']),
            'whatsapp' => sanitize_text_field($_POST['whatsapp']),
            'tipo' => sanitize_text_field($_POST['tipo']),
            'endereco' => sanitize_textarea_field($_POST['endereco']),
            'chave_pix' => sanitize_text_field($_POST['chave_pix']),
            'email' => sanitize_email($_POST['email'])
        ));

        // Enviar e-mail para o administrador
        wp_mail(get_option('admin_email'), 'Nova Instituição Cadastrada', 'Uma nova instituição foi cadastrada.');

        // Redirecionar com mensagem de sucesso
        wp_redirect(home_url('/?cadastro=sucesso'));
        exit;
    }
}
add_action('init', 'cid_process_instituicao_form');

// Adicionar campo de seleção de instituição no checkout do WooCommerce
function cid_add_donation_field_to_checkout($checkout) {
    woocommerce_form_field('instituicao', array(
        'type' => 'select',
        'class' => array('form-row-wide'),
        'label' => __('Escolha uma instituição para doação'),
        'options' => cid_get_instituicoes()
    ), $checkout->get_value('instituicao'));
}
add_action('woocommerce_after_order_notes', 'cid_add_donation_field_to_checkout');

// Salvar instituição escolhida no pedido
function cid_save_donation_field($order_id) {
    if ($_POST['instituicao']) {
        update_post_meta($order_id, '_instituicao', sanitize_text_field($_POST['instituicao']));
    }
}
add_action('woocommerce_checkout_update_order_meta', 'cid_save_donation_field');

// Exibir instituição escolhida na administração do WooCommerce
function cid_display_donation_in_order_admin($order) {
    $instituicao = get_post_meta($order->get_id(), '_instituicao', true);
    if ($instituicao) {
        echo '<p><strong>' . __('Instituição para Doação') . ':</strong> ' . $instituicao . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'cid_display_donation_in_order_admin');

// Obter instituições para o campo de seleção
function cid_get_instituicoes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'instituicoes';
    $results = $wpdb->get_results("SELECT id, nome FROM $table_name");
    $instituicoes = array();
    foreach ($results as $row) {
        $instituicoes[$row->id] = $row->nome;
    }
    return $instituicoes;
}

// Página de administração das doações
function cid_add_donation_menu() {
    add_menu_page('Doações', 'Doações', 'manage_options', 'cid-doacoes', 'cid_doacoes_page');
}
add_action('admin_menu', 'cid_add_donation_menu');

function cid_doacoes_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'orders';
    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE meta_key = '_instituicao'");
    ?>
    <div class="wrap">
        <h1>Doações</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID do Pedido</th>
                    <th>Instituição</th>
                    <th>Valor da Doação</th>
                    <th>Status da Transação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->order_id); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->order_id, '_instituicao', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->order_id, '_order_total', true) * (get_option('doacao_percentual') / 100)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->order_id, '_order_status', true)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Adicionar configuração de porcentagem de doação
function cid_add_settings_page() {
    add_options_page('Configurações de Doação', 'Doação', 'manage_options', 'cid-doacao-settings', 'cid_doacao_settings_page');
    add_action('admin_init', 'cid_register_settings');
}
add_action('admin_menu', 'cid_add_settings_page');

function cid_register_settings() {
    register_setting('cid_doacao_options', 'doacao_percentual');
    add_settings_section('cid_doacao_section', 'Configurações de Doação', null, 'cid_doacao_settings');
    add_settings_field('doacao_percentual', 'Percentual de Doação', 'cid_doacao_percentual_callback', 'cid_doacao_settings', 'cid_doacao_section');
}

function cid_doacao_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurações de Doação</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cid_doacao_options');
            do_settings_sections('cid_doacao_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function cid_doacao_percentual_callback() {
    $percentual = get_option('doacao_percentual', 10);
    echo '<input type="number" name="doacao_percentual" value="' . esc_attr($percentual) . '" min="0" max="100" /> %';
}

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    // Enqueue seu CSS
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    
    // Enqueue seu JS
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
});
