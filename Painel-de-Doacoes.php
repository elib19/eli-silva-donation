<?php
/**
 * Plugin Name: Painel de Doações
 * Plugin URI: https://github.com/elib19/eli-silva-donation/
 * Description: Plugin para adicionar funcionalidades de doação ao WooCommerce, com seleção de instituição na página de checkout e envio de e-mail para o administrador.
 * Version: 1.1.2
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: Painel de Doações
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Impedir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

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
        bairro varchar(50),
        cidade varchar(50),
        estado varchar(2),
        cep varchar(9),
        email varchar(100),
        atividades text,
        facebook varchar(255),
        instagram varchar(255),
        site_oficial varchar(255),
        chave_pix varchar(255),
        uso_das_doacoes text,
        user_id bigint(20),
        depoimento text,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
// Criação do formulário de cadastro
function cid_instituicao_form() {
    ob_start(); ?>
    <form action="" method="post">
        <?php wp_nonce_field('cid_instituicao_form_nonce', 'cid_instituicao_form_nonce_field'); ?>
        
        <label for="nome">Nome da Instituição</label><br>
        <input type="text" name="nome" placeholder="Nome da Instituição" required>
        <br>

        <label for="cnpj">CNPJ</label><br>
        <input type="text" name="cnpj" placeholder="CNPJ" required>
        <br>

        <label for="atividades">Descreva suas atividades</label><br>
        <textarea name="atividades" placeholder="Descreva suas atividades" required></textarea>
        <br>

        <label for="telefone">Telefone</label><br>
        <input type="text" name="telefone" placeholder="Telefone">
        <br>

        <label for="whatsapp">WhatsApp</label><br>
        <input type="text" name="whatsapp" placeholder="WhatsApp">
        <br>

        <label for="tipo">Tipo de Instituição</label><br>
        <select name="tipo" required>
            <option value="">Tipo de Instituição</option>
            <option value="hospital_cancer">Hospital de Câncer</option>
            <option value="igreja">Igreja</option>
            <option value="casa_recuperacao">Casa de Recuperação</option>
            <option value="instituicao_beneficiente">Instituição Beneficente</option>
        </select>
        <br>

        <label for="endereco">Endereço</label><br>
        <textarea name="endereco" placeholder="Endereço"></textarea>
        <br>

        <label for="bairro">Bairro</label><br>
        <input type="text" name="bairro" placeholder="Bairro">
        <br>

        <label for="cidade">Cidade</label><br>
        <input type="text" name="cidade" placeholder="Cidade">
        <br>

        <label for="estado">Estado</label><br>
        <select name="estado" required>
            <option value="">Estado</option>
            <?php
            $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
            foreach ($estados as $estado) {
                echo "<option value='$estado'>$estado</option>";
            }
            ?>
        </select>
        <br>

        <label for="cep">CEP</label><br>
        <input type="text" name="cep" placeholder="CEP">
        <br>

        <label for="email">E-mail</label><br>
        <input type="email" name="email" placeholder="E-mail" required>
        <br>

        <label for="facebook">URL do Facebook</label><br>
        <input type="text" name="facebook" placeholder="URL do Facebook">
        <br>

        <label for="instagram">URL do Instagram</label><br>
        <input type="text" name="instagram" placeholder="URL do Instagram">
        <br>

        <label for="site_oficial">Site Oficial (opcional)</label><br>
        <input type="text" name="site_oficial" placeholder="Site Oficial (opcional)">
        <br>

        <label for="chave_pix">Chave PIX</label><br>
        <input type="text" name="chave_pix" placeholder="Chave PIX" required>
        <br>

        <label for="uso_das_doacoes">Como você pretende usar as doações?</label><br>
        <textarea name="uso_das_doacoes" placeholder="Descreva como você pretende usar as doações" required></textarea>
        <br>

        <input type="submit" name="submit_instituicao" value="Cadastrar Instituição">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('form_doacao', 'cid_instituicao_form');
// Processamento do formulário
function cid_process_instituicao_form() {
    if (isset($_POST['submit_instituicao']) && isset($_POST['cid_instituicao_form_nonce_field']) && wp_verify_nonce($_POST['cid_instituicao_form_nonce_field'], 'cid_instituicao_form_nonce')) {
        global $wpdb;

        // Validação de CNPJ
        if (!preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', sanitize_text_field($_POST['cnpj']))) {
            echo 'CNPJ inválido.';
            return;
        }

        $table_name = $wpdb->prefix . 'instituicoes';

        // Criação do usuário no WordPress
        $user_id = wp_create_user(sanitize_text_field($_POST['nome']), wp_generate_password(), sanitize_email($_POST['email']));
        if (is_wp_error($user_id)) {
            echo 'Erro ao criar usuário: ' . $user_id->get_error_message();
            return;
        }

        // Adicionar a função de assinante ao usuário
        $user = new WP_User($user_id);
        $user->set_role('subscriber');

        // Armazenar dados da instituição no perfil do usuário
        update_user_meta($user_id, 'telefone', sanitize_text_field($_POST['telefone']));
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['whatsapp']));
        update_user_meta($user_id, 'tipo', sanitize_text_field($_POST['tipo']));
        update_user_meta($user_id, 'endereco', sanitize_textarea_field($_POST['endereco']));
        update_user_meta($user_id, 'bairro', sanitize_text_field($_POST['bairro']));
        update_user_meta($user_id, 'cidade', sanitize_text_field($_POST['cidade']));
        update_user_meta($user_id, 'estado', sanitize_text_field($_POST['estado']));
        update_user_meta($user_id, 'cep', sanitize_text_field($_POST['cep']));
        update_user_meta($user_id, 'email', sanitize_email($_POST['email']));
        update_user_meta($user_id, 'atividades', sanitize_textarea_field($_POST['atividades']));
        update_user_meta($user_id, 'facebook', sanitize_text_field($_POST['facebook']));
        update_user_meta($user_id, 'instagram', sanitize_text_field($_POST['instagram']));
        update_user_meta($user_id, 'site_oficial', sanitize_text_field($_POST['site_oficial']));
        update_user_meta($user_id, 'chave_pix', sanitize_text_field($_POST['chave_pix'])); // Salvar chave PIX
        update_user_meta($user_id, 'uso_das_doacoes', sanitize_textarea_field($_POST['uso_das_doacoes'])); // Salvar uso das doações

        $wpdb->insert($table_name, array(
            'nome' => sanitize_text_field($_POST['nome']),
            'cnpj' => sanitize_text_field($_POST['cnpj']),
            'telefone' => sanitize_text_field($_POST['telefone']),
            'whatsapp' => sanitize_text_field($_POST['whatsapp']),
            'tipo' => sanitize_text_field($_POST['tipo']),
            'endereco' => sanitize_textarea_field($_POST['endereco']),
            'bairro' => sanitize_text_field($_POST['bairro']),
            'cidade' => sanitize_text_field($_POST['cidade']),
            'estado' => sanitize_text_field($_POST['estado']),
            'cep' => sanitize_text_field($_POST['cep']),
            'email' => sanitize_email($_POST['email']),
            'atividades' => sanitize_textarea_field($_POST['atividades']),
            'facebook' => sanitize_text_field($_POST['facebook']),
            'instagram' => sanitize_text_field($_POST['instagram']),
            'site_oficial' => sanitize_text_field($_POST['site_oficial']),
            'chave_pix' => sanitize_text_field($_POST['chave_pix']), // Salvar chave PIX
            'user_id' => $user_id
        ));

        // Enviar e-mail para o administrador
        wp_mail(get_option('admin_email'), 'Nova Instituição Cadastrada', 'Uma nova instituição foi cadastrada: ' . sanitize_text_field($_POST['nome']) . ' - Tipo: ' . sanitize_text_field($_POST['tipo']));

        // Enviar e-mail para a instituição
        $password = wp_generate_password();
        wp_mail(sanitize_email($_POST['email']), 'Cadastro Realizado com Sucesso', 'Seu cadastro foi realizado com sucesso. Em breve entraremos em contato. Sua senha de acesso é: ' . $password);

        // Redirecionar com mensagem de sucesso
        wp_redirect(home_url('/?cadastro=sucesso'));
        exit;
    }
}
add_action('init', 'cid_process_instituicao_form');
// Adicionar o campo de seleção de instituição no checkout
function cid_add_donation_field_to_checkout() {
    echo '<div class="woocommerce-billing-fields__field-wrapper">';
    echo '<h3>' . __('Escolha uma instituição para doação') . '</h3>';
    
    $instituicoes = cid_get_instituicoes();
    $is_instituicoes_empty = count($instituicoes) <= 1;

    woocommerce_form_field('instituicao', array(
        'type' => 'select',
        'class' => array('form-row-wide'),
        'label' => __('Para qual instituição você deseja doar?'),
        'options' => $instituicoes,
        'required' => !$is_instituicoes_empty,
    ), '');
    
    echo '</div>';
}
add_action('woocommerce_checkout_billing', 'cid_add_donation_field_to_checkout');
// Salvar a seleção da instituição no pedido
function cid_save_donation_field($order_id) {
    if (isset($_POST['instituicao']) && !empty($_POST['instituicao'])) {
        update_post_meta($order_id, '_instituicao', sanitize_text_field($_POST['instituicao']));
        
        // Calcular percentual de doação
        $donation_percentage = get_option('doacao_percentual', 0);
        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $donation_amount = $total * ($donation_percentage / 100);

        // Salvar o valor da doação
        update_post_meta($order_id, '_donation_amount', $donation_amount);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'cid_save_donation_field');
// Função para obter instituições
function cid_get_instituicoes() {
    global $wpdb;
    $instituicoes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}instituicoes");
    $options = array('' => __('Selecione uma instituição'));

    foreach ($instituicoes as $instituicao) {
        $options[$instituicao->id] = $instituicao->nome;
    }

    return $options;
}
// Enviar e-mails após a doação
function cid_send_donation_email($order_id) {
    $order = wc_get_order($order_id);
    $instituicao_id = get_post_meta($order_id, '_instituicao', true);
    $instituicao = get_instituicao_by_id($instituicao_id);
    
    if ($instituicao) {
        $donation_amount = get_post_meta($order_id, '_donation_amount', true);
        $to = $instituicao->email;
        $subject = 'Nova Doação Recebida';
        $message = 'Você recebeu uma nova doação de R$ ' . number_format($donation_amount, 2, ',', '.') . ' através do pedido #' . $order->get_order_number();
        
        wp_mail($to, $subject, $message);
    }
}
add_action('woocommerce_thankyou', 'cid_send_donation_email');

// Função para obter instituição por ID
function get_instituicao_by_id($id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}instituicoes WHERE id = %d", $id));
}

// Exibir doações na página de administração do WooCommerce
function cid_display_donations_in_admin($order) {
    $instituicao_id = get_post_meta($order->get_id(), '_instituicao', true);
    $donation_amount = get_post_meta($order->get_id(), '_donation_amount', true);
    
    if ($instituicao_id) {
        $instituicao = get_instituicao_by_id($instituicao_id);
        echo '<p><strong>Instituição:</strong> ' . esc_html($instituicao->nome) . '</p>';
        echo '<p><strong>Valor da Doação:</strong> R$ ' . number_format($donation_amount, 2, ',', '.') . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_order_details', 'cid_display_donations_in_admin');
// Adicionar a coluna de doações na lista de pedidos
function cid_add_donation_column($columns) {
    $columns['donation'] = __('Doação');
    return $columns;
}
add_filter('manage_edit-shop_order_columns', 'cid_add_donation_column');

// Preencher a coluna de doações
function cid_fill_donation_column($column, $post_id) {
    if ($column === 'donation') {
        $donation_amount = get_post_meta($post_id, '_donation_amount', true);
        echo $donation_amount ? 'R$ ' . number_format($donation_amount, 2, ',', '.') : 'Nenhuma doação';
    }
}
add_action('manage_shop_order_posts_custom_column', 'cid_fill_donation_column', 10, 2);
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
    $percentual = get_option('doacao_percentual', 0);
    echo '<input type="number" name="doacao_percentual" value="' . esc_attr($percentual) . '" min="0" max="40" /> %';
}
// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    // Enqueue seu CSS
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    
    // Enqueue seu JS
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
});
