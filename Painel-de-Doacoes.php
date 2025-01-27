<?php
/**
 * Plugin Name: Painel de Doações
 * Plugin URI: https://juntoaqui.com.br
 * Description: Plugin para adicionar funcionalidades de doação ao WooCommerce, com seleção de instituição na página do produto e envio de e-mail para o administrador.
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
        banner varchar(255),
        facebook varchar(255),
        instagram varchar(255),
        site_oficial varchar(255),
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

        <label for="banner">URL do Banner</label><br>
        <input type="text" name="banner" placeholder="URL do Banner">
        <br>

        <label for="facebook">URL do Facebook</label><br>
        <input type="text" name="facebook" placeholder="URL do Facebook">
        <br>

        <label for="instagram">URL do Instagram</label>
        <input type="text" name="instagram" placeholder="URL do Instagram">
        <br>

        <label for="site_oficial">Site Oficial (opcional)</label><br>
        <input type="text" name="site_oficial" placeholder="Site Oficial (opcional)">
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
        update_user_meta($user_id, 'banner', sanitize_text_field($_POST['banner']));
        update_user_meta($user_id, 'facebook', sanitize_text_field($_POST['facebook']));
        update_user_meta($user_id, 'instagram', sanitize_text_field($_POST['instagram']));
        update_user_meta($user_id, 'site_oficial', sanitize_text_field($_POST['site_oficial']));

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
            'banner' => sanitize_text_field($_POST['banner']),
            'facebook' => sanitize_text_field($_POST['facebook']),
            'instagram' => sanitize_text_field($_POST['instagram']),
            'site_oficial' => sanitize_text_field($_POST['site_oficial']),
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

// Exibir instituições cadastradas
function cid_exibir_instituicoes() {
    global $wpdb;
    $instituicoes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}instituicoes");

    if ($instituicoes) {
        echo '<div class="instituicoes">';
        foreach ($instituicoes as $instituicao) {
            echo '<div class="instituicao">';
            echo '<h3>' . esc_html($instituicao->nome) . '</h3>';
            echo '<p>' . esc_html($instituicao->atividades) . '</p>'; // Exibir atividades
            echo '<p><strong>Facebook:</strong> <a href="' . esc_url($instituicao->facebook) . '">' . esc_html($instituicao->facebook) . '</a></p>';
            echo '<p><strong>Instagram:</strong> <a href="' . esc_url($instituicao->instagram) . '">' . esc_html($instituicao->instagram) . '</a></p>';
            echo '<p><strong>Site Oficial:</strong> <a href="' . esc_url($instituicao->site_oficial) . '">' . esc_html($instituicao->site_oficial) . '</a></p>';
            echo '<img src="' . esc_url($instituicao->banner) . '" alt="Banner da Instituição" style="max-width: 100%; height: auto;">'; // Exibir banner
            echo '</div>';
        }
        echo '</div>';
    }
}
add_shortcode('exibir_instituicoes', 'cid_exibir_instituicoes');

// Exibir depoimentos
function cid_exibir_depoimentos() {
    global $wpdb;
    $instituicoes = $wpdb->get_results("SELECT user_id, nome, depoimento FROM {$wpdb->prefix}instituicoes");

    if ($instituicoes) {
        echo '<div class="depoimentos">';
        echo '<h2>Depoimentos das Instituições</h2>';
        foreach ($instituicoes as $instituicao) {
            $depoimento = get_user_meta($instituicao->user_id, 'depoimento', true);
            if ($depoimento) {
                echo '<div class="depoimento">';
                echo '<h3>' . esc_html($instituicao->nome) . '</h3>';
                echo '<p>' . esc_html($depoimento) . '</p>';
                echo '</div>';
            }
        }
        echo '</div>';
    }
}
add_shortcode('exibir_depoimentos', 'cid_exibir_depoimentos');

// Formulário para cadastrar ou editar depoimento
function cid_depoimento_form() {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para deixar um depoimento.';
    }

    $user_id = get_current_user_id();
    $depoimento = get_user_meta($user_id, 'depoimento', true); // Recupera o depoimento existente, se houver

    ob_start(); ?>
    <form action="" method="post">
        <?php wp_nonce_field('cid_depoimento_nonce', 'cid_depoimento_nonce_field'); ?>
        <textarea name="depoimento" placeholder="Deixe seu depoimento" required><?php echo esc_textarea($depoimento); ?></textarea>
        <input type="submit" name="submit_depoimento" value="Salvar Depoimento">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('form_depoimento', 'cid_depoimento_form');

// Processamento do formulário de depoimento
function cid_process_depoimento_form() {
    if (isset($_POST['submit_depoimento']) && isset($_POST['cid_depoimento_nonce_field']) && wp_verify_nonce($_POST['cid_depoimento_nonce_field'], 'cid_depoimento_nonce')) {
        if (!is_user_logged_in()) {
            return; // Se não estiver logado, não faz nada
        }

        $user_id = get_current_user_id();
        $depoimento = sanitize_textarea_field($_POST['depoimento']);

        // Armazenar ou atualizar o depoimento
        update_user_meta($user_id, 'depoimento', $depoimento);

        // Redirecionar ou mostrar uma mensagem de sucesso
        wp_redirect(home_url('/?depoimento=sucesso')); // Redireciona para uma página de sucesso
        exit;
    }
}
add_action('init', 'cid_process_depoimento_form');

// Adicionar campo de seleção de instituição na página do produto
function cid_add_donation_field_to_product() {
    global $product;

    // Verifica se o produto é do tipo "simples" ou "variável"
    if ($product->is_type('simple') || $product->is_type('variable')) {
        echo '<div class="product-donation-field">';
        echo '<h3>' . __('Escolha uma instituição para doação') . '</h3>';
        
        // Verifica se há instituições cadastradas
        $instituicoes = cid_get_instituicoes();
        $is_instituicoes_empty = count($instituicoes) <= 1; // Se não houver instituições cadastradas, a opção padrão é a única

        woocommerce_form_field('instituicao', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => __('Para qual instituição você deseja doar?'),
            'options' => $instituicoes,
            'required' => !$is_instituicoes_empty, // Torna o campo obrigatório apenas se houver instituições cadastradas
        ), '');
        
        echo '</div>';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'cid_add_donation_field_to_product');

// Validar a seleção da instituição antes de adicionar ao carrinho
function cid_validate_donation_field($passed, $product_id) {
    $instituicoes = cid_get_instituicoes();
    $is_instituicoes_empty = count($instituicoes) <= 1; // Se não houver instituições cadastradas, a opção padrão é a única

    if (!$is_instituicoes_empty && isset($_POST['instituicao']) && empty($_POST['instituicao'])) {
        wc_add_notice(__('Por favor, escolha uma instituição para doação.'), 'error');
        return false;
    }
    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'cid_validate_donation_field', 10, 2);

// Salvar instituição escolhida no pedido
function cid_save_donation_field($order_id) {
    if (isset($_POST['instituicao']) && !empty($_POST['instituicao'])) {
        update_post_meta($order_id, '_instituicao', sanitize_text_field($_POST['instituicao']));
        
        // Calcular percentual de doação
        $donation_percentage = get_option('doacao_percentual', 30); // Padrão 30%
        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $donation_amount = $total * ($donation_percentage / 100); // Percentual do total

        // Salvar o valor da doação
        update_post_meta($order_id, '_donation_amount', $donation_amount);

        // Enviar e-mail para o cliente
        $instituicao_id = sanitize_text_field($_POST['instituicao']);
        $instituicao_email = get_userdata($instituicao_id)->user_email;
        $instituicao_nome = get_userdata($instituicao_id)->display_name;
        $instituicao_telefone = get_user_meta($instituicao_id, 'telefone', true);
        
        $site_name = get_bloginfo('name');
        $message = "Obrigado por sua doação!\n\n";
        $message .= "Você doou R$ " . number_format($donation_amount, 2, ',', '.') . " para a instituição " . $instituicao_nome . ".\n";
        $message .= "Telefone da instituição: " . $instituicao_telefone . "\n";
        $message .= "A doação pode demorar até 30 dias para ser paga.\n";
        wp_mail(get_post_meta($order_id, '_billing_email', true), 'Confirmação de Doação', $message);

        // Enviar e-mail para a instituição
        $message_instituicao = "Você recebeu uma nova doação!\n\n";
        $message_instituicao .= "Cliente: " . get_post_meta($order_id, '_billing_first_name', true) . " " . get_post_meta($order_id, '_billing_last_name', true) . "\n";
        $message_instituicao .= "Valor da doação: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        $message_instituicao .= "A doação pode demorar até 30 dias para ser paga.\n";
        wp_mail($instituicao_email, 'Nova Doação Recebida', $message_instituicao);

        // Enviar e-mail para o administrador
        $admin_message = "Nova doação recebida!\n\n";
        $admin_message .= "Cliente: " . get_post_meta($order_id, '_billing_first_name', true) . " " . get_post_meta($order_id, '_billing_last_name', true) . "\n";
        $admin_message .= "Instituição: " . $instituicao_nome . "\n";
        $admin_message .= "Valor da doação: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        wp_mail(get_option('admin_email'), 'Nova Doação Recebida', $admin_message);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'cid_save_donation_field');

// Exibir instituição escolhida e valor da doação na administração do WooCommerce
function cid_display_donation_in_order_admin($order) {
    $instituicao = get_post_meta($order->get_id(), '_instituicao', true);
    $donation_amount = get_post_meta($order->get_id(), '_donation_amount', true);
    if ($instituicao) {
        echo '<p><strong>' . __('Instituição para Doação') . ':</strong> ' . esc_html($instituicao) . '</p>';
        echo '<p><strong>' . __('Valor da Doação') . ':</strong> R$ ' . number_format($donation_amount, 2, ',', '.') . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'cid_display_donation_in_order_admin');

// Obter instituições para o campo de seleção
function cid_get_instituicoes() {
    $instituicoes = array();
    $instituicoes[0] = __('Selecione uma instituição'); // Adiciona uma opção padrão

    // Recuperar usuários com a função de assinante
    $args = array(
        'role' => 'subscriber',
        'fields' => array('ID', 'display_name')
    );
    $users = get_users($args);

    foreach ($users as $user) {
        $instituicoes[$user->ID] = $user->display_name; // Usar o nome de exibição do usuário
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
    $table_name = $wpdb->prefix . 'posts'; // Corrigido para a tabela de pedidos
    $results = $wpdb->get_results("SELECT ID FROM $table_name WHERE post_type = 'shop_order'");

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
                    <th>Nome do Doador</th>
                    <th>E-mail do Doador</th>
                    <th>E-mail da Instituição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->ID); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_instituicao', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_donation_amount', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_order_status', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_billing_first_name', true) . ' ' . get_post_meta($row->ID, '_billing_last_name', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_billing_email', true)); ?></td>
                        <td><?php echo esc_html(get_user_meta(get_post_meta($row->ID, '_instituicao', true), 'email', true)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Alterar status da doação para "pago"
function cid_change_donation_status($order_id) {
    if (isset($_POST['change_donation_status']) && $_POST['change_donation_status'] === 'pago') {
        // Atualizar o status da doação
        update_post_meta($order_id, '_order_status', 'pago');

        // Enviar e-mail para o cliente
        $instituicao_id = get_post_meta($order_id, '_instituicao', true);
        $instituicao_nome = get_userdata($instituicao_id)->display_name;
        $instituicao_telefone = get_user_meta($instituicao_id, 'telefone', true);
        $donation_amount = get_post_meta($order_id, '_donation_amount', true);

        $client_email = get_post_meta($order_id, '_billing_email', true);
        $client_message = "Sua doação de R$ " . number_format($donation_amount, 2, ',', '.') . " para a instituição " . $instituicao_nome . " foi paga.\n";
        $client_message .= "Telefone da instituição: " . $instituicao_telefone . "\n";
        $client_message .= "Se tiver dúvidas, entre em contato com a instituição.";
        wp_mail($client_email, 'Confirmação de Pagamento da Doação', $client_message);

        // Enviar e-mail para a instituição
        $instituicao_email = get_userdata($instituicao_id)->user_email;
        $instituicao_message = "Você recebeu um pagamento de doação!\n\n";
        $instituicao_message .= "Cliente: " . get_post_meta($order_id, '_billing_first_name', true) . " " . get_post_meta($order_id, '_billing_last_name', true) . "\n";
        $instituicao_message .= "Valor pago: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        $instituicao_message .= "Por favor, confira seu extrato bancário.";
        wp_mail($instituicao_email, 'Pagamento de Doação Recebido', $instituicao_message);

        // Enviar e-mail para o administrador
        $admin_message = "Doação paga!\n\n";
        $admin_message .= "Cliente: " . get_post_meta($order_id, '_billing_first_name', true) . " " . get_post_meta($order_id, '_billing_last_name', true) . "\n";
        $admin_message .= "Instituição: " . $instituicao_nome . "\n";
        $admin_message .= "Valor pago: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        wp_mail(get_option('admin_email'), 'Doação Paga', $admin_message);
    }
}
add_action('woocommerce_order_status_changed', 'cid_change_donation_status');

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
    $percentual = get_option('doacao_percentual', 30); // Alterado para 30%
    echo '<input type="number" name="doacao_percentual" value="' . esc_attr($percentual) . '" min="0" max="100" /> %';
}

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    // Enqueue seu CSS
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    
    // Enqueue seu JS
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
});
