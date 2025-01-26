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

    // Tabela de instituições
    $table_name_instituicoes = $wpdb->prefix . 'instituicoes';
    $sql_instituicoes = "CREATE TABLE $table_name_instituicoes (
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
        chave_pix varchar(50),
        email varchar(100),
        user_id bigint(20),
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Tabela de feedback
    $table_name_feedback = $wpdb->prefix . 'feedback';
    $sql_feedback = "CREATE TABLE $table_name_feedback (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        instituicao_id bigint(20) NOT NULL,
        feedback text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_instituicoes);
    dbDelta($sql_feedback);
}

// Criação do formulário de cadastro
function cid_instituicao_form() {
    ob_start(); ?>
    <form action="" method="post">
        <!-- Campos do formulário -->
        <label for="nome">Nome da Instituição</label><br>
        <input type="text" name="nome" id="nome" required><br><br>

        <label for="cnpj">CNPJ</label><br>
        <input type="text" name="cnpj" id="cnpj" required><br><br>

        <label for="telefone">Telefone</label><br>
        <input type="text" name="telefone" id="telefone"><br><br>

        <label for="whatsapp">WhatsApp</label><br>
        <input type="text" name="whatsapp" id="whatsapp"><br><br>

        <label for="tipo">Tipo de Instituição</label><br>
        <select name="tipo" id="tipo" required>
            <option value="">Selecione o tipo</option>
            <option value="hospital_cancer">Hospital de Câncer</option>
            <option value="igreja">Igreja</option>
            <option value="casa_recuperacao">Casa de Recuperação</option>
            <option value="instituicao_beneficiente">Instituição Beneficente</option>
        </select><br><br>

        <label for="endereco">Endereço</label><br>
        <textarea name="endereco" id="endereco"></textarea><br><br>

        <label for="bairro">Bairro</label><br>
        <input type="text" name="bairro" id="bairro"><br><br>

        <label for="cidade">Cidade</label><br>
        <input type="text" name="cidade" id="cidade"><br><br>

        <label for="estado">Estado</label><br>
        <select name="estado" id="estado" required>
            <option value="">Selecione o estado</option>
            <?php
            $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
            foreach ($estados as $estado) {
                echo "<option value='$estado'>$estado</option>";
            }
            ?>
        </select><br><br>

        <label for="cep">CEP</label><br>
        <input type="text" name="cep" id="cep"><br><br>

        <label for="chave_pix">Chave PIX</label><br>
        <input type="text" name="chave_pix" id="chave_pix"><br><br>

        <label for="email">E-mail</label><br>
        <input type="email" name="email" id="email" required><br><br>

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
        update_user_meta($user_id, 'chave_pix', sanitize_text_field($_POST['chave_pix']));
        update_user_meta($user_id, 'email', sanitize_email($_POST['email']));

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
            'chave_pix' => sanitize_text_field($_POST['chave_pix']),
            'email' => sanitize_email($_POST['email']),
            'user_id' => $user_id
        ));

        // Enviar e-mail para o administrador
        wp_mail(get_option('admin_email'), 'Nova Instituição Cadastrada', 'Uma nova instituição foi cadastrada: ' . sanitize_text_field($_POST['nome']) . ' - Tipo: ' . sanitize_text_field($_POST['tipo']));

        // Enviar e-mail para a instituição
        wp_mail(sanitize_email($_POST['email']), 'Cadastro Realizado com Sucesso', 'Seu cadastro foi realizado com sucesso. Em breve entraremos em contato. Sua senha de acesso é: ' . wp_generate_password());

        // Redirecionar com mensagem de sucesso
        wp_redirect(home_url('/?cadastro=sucesso'));
        exit;
    }
}
add_action('init', 'cid_process_instituicao_form');

// Adicionar campo de seleção de instituição na página do produto
function cid_add_donation_field_to_product() {
    global $product;

    // Verifica se o produto é do tipo "simples" ou "variável"
    if ($product->is_type('simple') || $product->is_type('variable')) {
        echo '<div class="product-donation-field">';
        echo '<h3>' . __('Escolha uma instituição para doação') . '</h3>';
        woocommerce_form_field('instituicao', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => __('Para qual instituição você deseja doar?'),
            'options' => cid_get_instituicoes(),
            'required' => false, // Torna o campo não obrigatório
        ), '');
        echo '</div>';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'cid_add_donation_field_to_product');

// Salvar instituição escolhida no pedido
function cid_save_donation_field($order_id) {
    if (isset($_POST['instituicao']) && !empty($_POST['instituicao'])) {
        update_post_meta($order_id, '_instituicao', sanitize_text_field($_POST['instituicao']));
        
        // Calcular 30% do valor total da compra
        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $donation_amount = $total * 0.30; // 30% do total

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
        $admin_message .= "Chave PIX da instituição: " . get_user_meta($instituicao_id, 'chave_pix', true) . "\n";
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
    add_submenu_page('cid-doacoes', 'Relatórios de Doações', 'Relatórios', 'manage_options', 'cid-reports', 'cid_reports_page');
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
                    <th>Chave PIX da Instituição</th>
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
                        <td><?php echo esc_html(get_user_meta(get_post_meta($row->ID, '_instituicao', true), 'chave_pix', true)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function cid_reports_page() {
    global $wpdb;

    // Obter total de doações
    $total_donations = $wpdb->get_var("SELECT SUM(meta_value) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_donation_amount'");

    // Obter número de doadores
    $total_donors = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}instituicoes");

    // Obter instituições
    $institutions = $wpdb->get_results("SELECT nome, COUNT(*) as donation_count FROM {$wpdb->prefix}instituicoes GROUP BY nome");

    ?>
    <div class="wrap">
        <h1>Relatórios de Doações</h1>
        <p><strong>Total de Doações:</strong> R$ <?php echo number_format($total_donations, 2, ',', '.'); ?></p>
        <p><strong>Total de Doadores:</strong> <?php echo $total_donors; ?></p>

        <h2>Instituições Mais Beneficiadas</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Instituição</th>
                    <th>Número de Doações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($institutions as $institution) : ?>
                    <tr>
                        <td><?php echo esc_html($institution->nome); ?></td>
                        <td><?php echo esc_html($institution->donation_count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Implementar feedback
function cid_feedback_form() {
    ob_start(); ?>
    <form action="" method="post">
        <label for="instituicao_feedback">Instituição</label><br>
        <select name="instituicao_feedback" id="instituicao_feedback" required>
            <?php
            $instituicoes = cid_get_instituicoes();
            foreach ($instituicoes as $id => $nome) {
                echo "<option value='$id'>$nome</option>";
            }
            ?>
        </select><br><br>

        <label for="feedback">Seu Feedback</label><br>
        <textarea name="feedback" id="feedback" required></textarea><br><br>

        <input type="submit" name="submit_feedback" value="Enviar Feedback">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('form_feedback', 'cid_feedback_form');

function cid_process_feedback_form() {
    if (isset($_POST['submit_feedback'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'feedback';

        $wpdb->insert($table_name, array(
            'instituicao_id' => sanitize_text_field($_POST['instituicao_feedback']),
            'feedback' => sanitize_textarea_field($_POST['feedback']),
            'created_at' => current_time('mysql')
        ));

        // Enviar e-mail para a instituição
        $instituicao_email = get_userdata(sanitize_text_field($_POST['instituicao_feedback']))->user_email;
        wp_mail($instituicao_email, 'Novo Feedback Recebido', 'Você recebeu um novo feedback.');

        // Redirecionar com mensagem de sucesso
        wp_redirect(home_url('/?feedback=sucesso'));
        exit;
    }
}
add_action('init', 'cid_process_feedback_form');

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    // Enqueue seu CSS
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    
    // Enqueue seu JS
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
    
    // Enqueue scripts e estilos adicionais para feedback e relatórios
    wp_enqueue_style('feedback-style', plugin_dir_url(__FILE__) .'assets/css/feedback.css');
    wp_enqueue_script('feedback-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
    
    wp_enqueue_style('reports-style', plugin_dir_url(__FILE__) .'assets/css/reports.css');
    wp_enqueue_script('reports-script', plugin_dir_url(__FILE__) .'assets/js/reports.js', [], false, true);
