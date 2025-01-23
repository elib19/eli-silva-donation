<?php
/**
 * Plugin Name: Eli Silva Donation
 * Plugin URI: https://juntoaqui.com.br
 * Description: Plugin para adicionar funcionalidades de doação ao WooCommerce, com seleção de instituição no checkout e envio de e-mail para o administrador.
 * Version: 1.0.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: eli-silva-donation
 * Domain Path: /languages
 */

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Carregar os arquivos CSS e JS do plugin
function eli_silva_donation_enqueue_assets() {
    // Verificar se estamos na página de checkout ou na página do formulário
    if (is_checkout() || is_page('cadastro-de-instituicoes')) {
        wp_enqueue_style('eli-silva-donation-css', plugin_dir_url(__FILE__) . 'assets/css/eli-silva-donation.css', array(), '1.0.0');
        wp_enqueue_script('eli-silva-donation-js', plugin_dir_url(__FILE__) . 'assets/js/eli-silva-donation.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'eli_silva_donation_enqueue_assets');


// Criar tabela de instituições ao ativar o plugin
register_activation_hook(__FILE__, 'create_donation_institutions_table');
function create_donation_institutions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'donation_institutions';

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        pix_key VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Adicionar o campo de seleção de instituição no checkout
add_action('woocommerce_before_order_notes', 'add_donation_institution_field');
function add_donation_institution_field($checkout) {
    global $wpdb;
    $institutions = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}donation_institutions", ARRAY_A);

    if (!empty($institutions)) {
        echo '<div id="donation_institution_field"><h3>' . __('Selecione uma Instituição para Doação', 'eli-silva-donation') . '</h3>';

        woocommerce_form_field('donation_institution', [
            'type' => 'select',
            'class' => ['form-row-wide'],
            'label' => __('Instituições', 'eli-silva-donation'),
            'required' => true,
            'options' => array_reduce($institutions, function ($options, $institution) {
                $options[$institution['id']] = $institution['name'];
                return $options;
            }, [
                '' => __('Selecione uma instituição', 'eli-silva-donation')
            ]),
        ], $checkout->get_value('donation_institution'));

        echo '</div>';
    }
}

// Validar o campo de seleção no checkout
add_action('woocommerce_checkout_process', 'validate_donation_institution_field');
function validate_donation_institution_field() {
    if (empty($_POST['donation_institution'])) {
        wc_add_notice(__('Por favor, selecione uma instituição para doação.', 'eli-silva-donation'), 'error');
    }
}

// Salvar o campo no meta do pedido
add_action('woocommerce_checkout_update_order_meta', 'save_donation_institution_field');
function save_donation_institution_field($order_id) {
    if (isset($_POST['donation_institution']) && !empty($_POST['donation_institution'])) {
        update_post_meta($order_id, '_donation_institution', sanitize_text_field($_POST['donation_institution']));
    }
}

// Exibir o campo no admin
add_action('woocommerce_admin_order_data_after_order_details', 'display_donation_institution_in_admin');
function display_donation_institution_in_admin($order) {
    $institution_id = get_post_meta($order->get_id(), '_donation_institution', true);

    if ($institution_id) {
        global $wpdb;
        $institution = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}donation_institutions WHERE id = %d", $institution_id), ARRAY_A);

        if ($institution) {
            echo '<p><strong>' . __('Instituição Selecionada', 'eli-silva-donation') . ':</strong> ' . esc_html($institution['name']) . '</p>';
        }
    }
}

// Enviar email com os detalhes da doação
add_action('woocommerce_order_status_completed', 'send_donation_email');
function send_donation_email($order_id) {
    $order = wc_get_order($order_id);
    $institution_id = get_post_meta($order_id, '_donation_institution', true);

    if ($institution_id) {
        global $wpdb;
        $institution = $wpdb->get_row($wpdb->prepare("SELECT name, pix_key FROM {$wpdb->prefix}donation_institutions WHERE id = %d", $institution_id), ARRAY_A);

        if ($institution) {
            $donation_amount = $order->get_total() * 0.3; // 30% do valor do pedido

            // Preparar o conteúdo do email
            $to = get_option('admin_email');
            $subject = __('Detalhes da Doação', 'eli-silva-donation') . ' - ' . $institution['name'];
            $message = sprintf(
                "Pedido #%s\nCliente: %s\nValor da Doação: R$ %.2f\nInstituição: %s\nChave PIX: %s",
                $order->get_id(),
                $order->get_billing_email(),
                $donation_amount,
                $institution['name'],
                $institution['pix_key']
            );

            // Enviar o email
            wp_mail($to, $subject, $message);
        }
    }
}

// Shortcode para formulário de cadastro de instituições
add_shortcode('donation_form', 'render_donation_form');
function render_donation_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donation_form_nonce']) && wp_verify_nonce($_POST['donation_form_nonce'], 'donation_form_action')) {
        global $wpdb;

        $name = sanitize_text_field($_POST['institution_name'] ?? '');
        $pix_key = sanitize_text_field($_POST['pix_key'] ?? '');

        if ($name && $pix_key) {
            $wpdb->insert($wpdb->prefix . 'donation_institutions', [
                'name' => $name,
                'pix_key' => $pix_key
            ]);

            echo '<p>' . __('Instituição cadastrada com sucesso!', 'eli-silva-donation') . '</p>';
        } else {
            echo '<p>' . __('Por favor, preencha todos os campos.', 'eli-silva-donation') . '</p>';
        }
    }

    ob_start();
    ?>
    <form method="POST">
        <?php wp_nonce_field('donation_form_action', 'donation_form_nonce'); ?>
        <p>
            <label for="institution_name"><?php _e('Nome da Instituição:', 'eli-silva-donation'); ?></label>
            <input type="text" name="institution_name" required />
        </p>
        <p>
            <label for="pix_key"><?php _e('Chave PIX:', 'eli-silva-donation'); ?></label>
            <input type="text" name="pix_key" required />
        </p>
        <p>
            <button type="submit"><?php _e('Cadastrar Instituição', 'eli-silva-donation'); ?></button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
