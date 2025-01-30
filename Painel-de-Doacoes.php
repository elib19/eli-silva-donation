<?php
/**
 * Plugin Name: Painel de Doações
 * Plugin URI: https://github.com/elib19/eli-silva-donation/
 * Description: Plugin para adicionar funcionalidades de doação ao WooCommerce, com seleção de instituição na página do produto e envio de e-mail para o administrador.
 * Version: 1.2.0
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
        total_doacoes decimal(10,2) DEFAULT 0.00,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Criar role personalizada para instituições
    add_role('instituicao', 'Instituição', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'upload_files' => true
    ));
}

// Função para calcular a porcentagem da doação
function cid_calculate_donation_percentage($total) {
    $percentual = get_option('doacao_percentual', 0);
    return ($total * $percentual) / 100;
}

// Atualizar o total de doações da instituição
function cid_update_institution_total($instituicao_id, $donation_amount) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'instituicoes';
    
    $current_total = $wpdb->get_var($wpdb->prepare(
        "SELECT total_doacoes FROM $table_name WHERE user_id = %d",
        $instituicao_id
    ));
    
    $new_total = floatval($current_total) + floatval($donation_amount);
    
    $wpdb->update(
        $table_name,
        array('total_doacoes' => $new_total),
        array('user_id' => $instituicao_id),
        array('%f'),
        array('%d')
    );
}

// Modificar o processo de criação de usuário para usar role 'instituicao'
function cid_process_instituicao_form() {
    if (isset($_POST['submit_instituicao']) && isset($_POST['cid_instituicao_form_nonce_field']) && wp_verify_nonce($_POST['cid_instituicao_form_nonce_field'], 'cid_instituicao_form_nonce')) {
        global $wpdb;

        // Validação de CNPJ
        if (!preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', sanitize_text_field($_POST['cnpj']))) {
            echo 'CNPJ inválido.';
            return;
        }

        $table_name = $wpdb->prefix . 'instituicoes';

        // Criação do usuário no WordPress com role 'instituicao'
        $user_id = wp_create_user(
            sanitize_text_field($_POST['nome']),
            wp_generate_password(),
            sanitize_email($_POST['email'])
        );
        
        if (is_wp_error($user_id)) {
            echo 'Erro ao criar usuário: ' . $user_id->get_error_message();
            return;
        }

        // Definir role como 'instituicao'
        $user = new WP_User($user_id);
        $user->set_role('instituicao');

        // Resto do código permanece o mesmo...
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
        update_user_meta($user_id, 'chave_pix', sanitize_text_field($_POST['chave_pix']));
        update_user_meta($user_id, 'uso_das_doacoes', sanitize_textarea_field($_POST['uso_das_doacoes']));

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
            'chave_pix' => sanitize_text_field($_POST['chave_pix']),
            'uso_das_doacoes' => sanitize_textarea_field($_POST['uso_das_doacoes']),
            'user_id' => $user_id,
            'total_doacoes' => 0.00
        ));

        // Enviar e-mails...
        wp_mail(get_option('admin_email'), 'Nova Instituição Cadastrada', 'Uma nova instituição foi cadastrada: ' . sanitize_text_field($_POST['nome']));
        wp_mail(sanitize_email($_POST['email']), 'Cadastro Realizado com Sucesso', 'Seu cadastro foi realizado com sucesso. Em breve entraremos em contato.');

        wp_redirect(home_url('/?cadastro=sucesso'));
        exit;
    }
}
add_action('init', 'cid_process_instituicao_form');

// Atualizar a exibição de instituições para mostrar o total de doações
function cid_exibir_instituicoes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'instituicoes';
    $instituicoes = $wpdb->get_results("SELECT * FROM $table_name");

    if (!empty($instituicoes)) {
        echo '<div class="instituicoes">';
        foreach ($instituicoes as $instituicao) {
            echo '<div class="instituicao" style="border: 1px solid #ccc; padding: 10px; margin: 10px;">';
            echo '<h3>' . esc_html($instituicao->nome) . '</h3>';
            echo '<p>' . esc_html($instituicao->atividades) . '</p>';
            echo '<p><strong>CNPJ:</strong> ' . esc_html($instituicao->cnpj) . '</p>';
            echo '<p><strong>Telefone:</strong> ' . esc_html($instituicao->telefone) . '</p>';
            echo '<p><strong>WhatsApp:</strong> ' . esc_html($instituicao->whatsapp) . '</p>';
            echo '<p><strong>Facebook:</strong> <a href="' . esc_url($instituicao->facebook) . '">' . esc_html($instituicao->facebook) . '</a></p>';
            echo '<p><strong>Instagram:</strong> <a href="' . esc_url($instituicao->instagram) . '">' . esc_html($instituicao->instagram) . '</a></p>';
            echo '<p><strong>Site Oficial:</strong> <a href="' . esc_url($instituicao->site_oficial) . '">' . esc_html($instituicao->site_oficial) . '</a></p>';
            echo '<p><strong>Total de doações recebidas:</strong> R$ ' . number_format($instituicao->total_doacoes, 2, ',', '.') . '</p>';
            echo '<p><strong>Como as doações serão usadas:</strong> ' . esc_html($instituicao->uso_das_doacoes) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>Nenhuma instituição encontrada.</p>';
    }
}

// Atualizar o processamento do pedido para incluir o cálculo da porcentagem
function cid_save_donation_field($order_id) {
    if (isset($_POST['instituicao']) && !empty($_POST['instituicao'])) {
        $instituicao_id = sanitize_text_field($_POST['instituicao']);
        update_post_meta($order_id, '_instituicao', $instituicao_id);
        
        // Calcular valor da doação baseado na porcentagem configurada
        $order = wc_get_order($order_id);
        $total = $order->get_total();
        $donation_amount = cid_calculate_donation_percentage($total);
        
        // Salvar o valor da doação
        update_post_meta($order_id, '_donation_amount', $donation_amount);
        
        // Atualizar o total de doações da instituição
        cid_update_institution_total($instituicao_id, $donation_amount);
        
        // Enviar e-mails...
        $instituicao_data = get_userdata($instituicao_id);
        $instituicao_nome = $instituicao_data->display_name;
        $instituicao_email = $instituicao_data->user_email;
        
        // E-mail para o cliente
        $message = "Obrigado por sua doação!\n\n";
        $message .= "Você doou R$ " . number_format($donation_amount, 2, ',', '.') . " para a instituição " . $instituicao_nome . ".\n";
        $message .= "A doação será processada em até 30 dias.\n";
        wp_mail($order->get_billing_email(), 'Confirmação de Doação', $message);
        
        // E-mail para a instituição
        $message_inst = "Nova doação recebida!\n\n";
        $message_inst .= "Valor: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        $message_inst .= "Doador: " . $order->get_billing_first_name() . " " . $order->get_billing_last_name() . "\n";
        wp_mail($instituicao_email, 'Nova Doação Recebida', $message_inst);
    }

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
                    <th>Chave PIX</th> <!-- Nova coluna para Chave PIX -->
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
                        <td>
                            <?php
                            // Obter a ID da instituição
                            $instituicao_id = get_post_meta($row->ID, '_instituicao', true);
                            // Verificar se o usuário atual é o administrador ou a instituição correspondente
                            if (current_user_can('administrator') || get_current_user_id() == $instituicao_id) {
                                // Exibir a chave PIX
                                echo esc_html(get_user_meta($instituicao_id, 'chave_pix', true));
                            } else {
                                echo 'Acesso restrito'; // Mensagem para usuários não autorizados
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Adicionar aviso de uso do plugin no painel administrativo
function cid_admin_notice() {
    ?>
    <div class="notice notice-info is-dismissible">
        <h2><?php _e('Bem-vindo ao Painel de Doações!', 'Painel de Doações'); ?></h2>
        <p><?php _e('Para começar a usar o plugin, siga as instruções abaixo:', 'Painel de Doações'); ?></p>
        <ul>
            <li><?php _e('Crie uma nova página para exibir o formulário de cadastro das instituições e adicione o shortcode: <code>[form_doacao]</code>', 'Painel de Doações'); ?></li>
            <li><?php _e('Crie uma nova página para exibir as instituições cadastradas e adicione o shortcode: <code>[exibir_instituicoes]</code>', 'Painel de Doações'); ?></li>
            <li><?php _e('Crie uma nova página para exibir os depoimentos e adicione o shortcode: <code>[exibir_depoimentos]</code>', 'Painel de Doações'); ?></li>
            <li><?php _e('Crie uma nova página para as instituições adicionarem os depoimentos e adicione o shortcode: <code>[form_depoimento]</code>', 'Painel de Doações'); ?></li>
            <li><?php _e('Configure a porcentagem das doações clicando em <code>Configurações</code> e depois em <code>Doação</code> é possível configurar o percentual de 0 a 40% do valor do pedido para doações.', 'Painel de Doações'); ?></li>
        </ul>
        <p><?php _e('Para mais informações, visite nosso site: <a href="https://juntoaqui.com.br" target="_blank">juntoaqui.com.br</a>', 'Painel de Doações'); ?></p>
    </div>
    <?php
}
add_action('admin_notices', 'cid_admin_notice');

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
    $percentual = get_option('doacao_percentual', 0); // Alterado para 0%
    echo '<input type="number" name="doacao_percentual" value="' . esc_attr($percentual) . '" min="0" max="40" /> %';
}

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    // Enqueue seu CSS
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) .'assets/css/eli-silva-donation.css');
    
    // Enqueue seu JS
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) .'assets/js/eli-silva-donation.js', [], false, true);
});
