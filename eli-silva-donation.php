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

// Adiciona o campo de seleção no carrinho
add_action('woocommerce_before_cart', function() {
    $institutions = get_option('donation_institutions', []);
    echo '<div><label for="donation_institution">Escolha a Instituição:</label><select id="donation_institution" name="donation_institution">';
    echo '<option value="">Selecione uma instituição</option>';
    foreach ($institutions as $institution) {
        echo '<option value="' . esc_attr($institution['name']) . '">' . esc_html($institution['name']) . '</option>';
    }
    echo '</select></div>';
});

// Salva a instituição escolhida no meta do pedido
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (!empty($_POST['donation_institution'])) {
        update_post_meta($order_id, '_donation_institution', sanitize_text_field($_POST['donation_institution']));
    }
});

// Envia e-mails e registra o pedido no painel somente após o pagamento
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    $institution_name = get_post_meta($order_id, '_donation_institution', true);
    $institutions = get_option('donation_institutions', []);

    $institution = array_filter($institutions, function($inst) use ($institution_name) {
        return $inst['name'] === $institution_name;
    });
    $institution = reset($institution);

    if (!$institution) return;

    $donation_value = $order->get_total() * 0.3;

    // Envia e-mail para o cliente
    $to_customer = $order->get_billing_email();
    $subject_customer = 'Confirmação de Doação';
    $message_customer = "Obrigado pela sua compra! Sua doação foi destinada a {$institution_name}.";
    wp_mail($to_customer, $subject_customer, $message_customer);

    // Envia e-mail para a instituição
    $to_institution = $institution['email'];
    $subject_institution = 'Nova Doação Recebida';
    $message_institution = "Você recebeu uma doação através do site.\n\n" . 
        "Cliente: " . $order->get_billing_first_name() . "\n" . 
        "Produto: " . implode(', ', wp_list_pluck($order->get_items(), 'name')) . "\n" . 
        "Valor da Doação: R$ " . number_format($donation_value, 2, ',', '.') . "\n" . 
        "Data: " . wc_format_datetime($order->get_date_created()) . "\n" . 
        "Pagamento será efetuado em 15 dias.";
    wp_mail($to_institution, $subject_institution, $message_institution);

    // Envia e-mail para o administrador
    $admin_email = get_option('admin_email');
    $subject_admin = 'Nova Doação Realizada';
    $message_admin = "Uma nova compra foi realizada com doação.\n\n" . 
        "Cliente: " . $order->get_billing_first_name() . "\n" . 
        "Instituição Beneficiada: " . $institution_name . "\n" . 
        "Valor da Doação: R$ " . number_format($donation_value, 2, ',', '.') . "\n" . 
        "Chave PIX da Instituição: " . $institution['pix_key'] . "\n" . 
        "Data: " . wc_format_datetime($order->get_date_created());
    wp_mail($admin_email, $subject_admin, $message_admin);
});

// Shortcode para o formulário de cadastro de instituições
add_shortcode('donation_form', function() {
    if ($_POST['donation_form_submitted']) {
        $new_institution = [
            'name' => sanitize_text_field($_POST['name']),
            'cnpj' => sanitize_text_field($_POST['cnpj']),
            'address' => sanitize_text_field($_POST['address']),
            'state' => sanitize_text_field($_POST['state']),
            'pix_type' => sanitize_text_field($_POST['pix_type']),
            'pix_key' => sanitize_text_field($_POST['pix_key']),
            'email' => sanitize_email($_POST['email']),
            'type' => sanitize_text_field($_POST['type'])
        ];
        $institutions = get_option('donation_institutions', []);
        $institutions[] = $new_institution;
        update_option('donation_institutions', $institutions);
        echo '<div class="success">Instituição cadastrada com sucesso!</div>';
    }

    ob_start();
    ?>
    <form method="POST">
        <input type="hidden" name="donation_form_submitted" value="1">
        <label for="name">Nome da Instituição:</label><br>
        <input type="text" name="name" required><br>
        <label for="cnpj">CNPJ:</label><br>
        <input type="text" name="cnpj" required><br>
        <label for="address">Endereço:</label><br>
        <input type="text" name="address" required><br>
        <label for="state">Estado:</label><br>
        <select name="state" required><br>
            <option value="">Selecione um estado</option>
            <?php foreach (["AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO"] as $state): ?>
                <option value="<?= $state ?>"><?= $state ?></option>
            <?php endforeach; ?>
        </select><br>
        <label for="type">Tipo de Instituição:</label><br>
        <select name="type" required><br>
            <option value="">Selecione o tipo</option>
            <option value="hospital_de_cancer">Hospital de Câncer</option>
            <option value="igreja">Igreja</option>
            <option value="instituicao_caridade">Instituição de Caridade</option>
            <option value="outro">Outro</option>
        </select><br>
        <label for="pix_type">Tipo de Chave PIX:</label><br>
        <select name="pix_type" required><br>
            <option value="">Selecione o tipo</option>
            <option value="cnpj">CNPJ</option>
            <option value="phone">Número de Celular</option>
            <option value="random">Chave Aleatória</option>
        </select><br>
        <label for="pix_key">Chave PIX:</label><br>
        <input type="text" name="pix_key" required><br>
        <label for="email">E-mail:</label><br>
        <input type="email" name="email" required><br>
        <button type="submit">Cadastrar</button>
    </form>
    <?php
    return ob_get_clean();
});

// Página no admin para gerenciar as doações
add_action('admin_menu', function() {
    add_menu_page('Gerenciar Doações', 'Doações', 'manage_options', 'manage_donations', function() {
        $orders = wc_get_orders(['status' => 'completed', 'meta_key' => '_donation_institution']);

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Cliente</th><th>Instituição</th><th>Chave PIX</th><th>Valor</th><th>Data</th><th>Status</th></tr></thead><tbody>';

        foreach ($orders as $order) {
            $institution_name = get_post_meta($order->get_id(), '_donation_institution', true);
            $institutions = get_option('donation_institutions', []);

            $institution = array_filter($institutions, function($inst) use ($institution_name) {
                return $inst['name'] === $institution_name;
            });
            $institution = reset($institution);

            echo '<tr>';
            echo '<td>' . esc_html($order->get_billing_first_name()) . '</td>';
            echo '<td>' . esc_html($institution_name) . '</td>';
            echo '<td>' . esc_html($institution['pix_key']) . '</td>';
            echo '<td>R$ ' . number_format($order->get_total() * 0.3, 2, ',', '.') . '</td>';
            echo '<td>' . wc_format_datetime($order->get_date_created()) . '</td>';
            echo '<td>Pendente</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    });
});

// Adiciona página de cadastro de instituição no admin
add_action('init', function() {
    add_rewrite_rule('^instituicao-cadastrar/?$', 'index.php?donation_form=1', 'top');
});

// Redireciona para o shortcode da página de cadastro de instituição
add_filter('query_vars', function($vars) {
    $vars[] = 'donation_form';
    return $vars;
});

add_action('template_redirect', function() {
    $donation_form = get_query_var('donation_form');
    if ($donation_form) {
        echo do_shortcode('[donation_form]');
        exit;
    }
});

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
});
?>
