<?php
/**
 * Plugin Name: Painel de Doações
 * Plugin URI: https://github.com/elib19/eli-silva-donation/
 * Description: Plugin para adicionar funcionalidades de doação ao WooCommerce, com seleção de instituição na página do produto e envio de e-mail para o administrador.
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
        <input id="nome" type="text" name="nome" placeholder="Nome da Instituição" required>
        <br>

        <label for="cnpj">CNPJ</label><br>
        <input id="cnpj" type="text" name="cnpj" placeholder="CNPJ" required>
        <br>

        <label for="atividades">Descreva suas atividades</label><br>
        <textarea id="atividades" name="atividades" placeholder="Descreva suas atividades" required></textarea>
        <br>

        <label for="telefone">Telefone</label><br>
        <input id="telefone" type="text" name="telefone" placeholder="Telefone">
        <br>

        <label for="whatsapp">WhatsApp</label><br>
        <input id="whatsapp" type="text" name="whatsapp" placeholder="WhatsApp">
        <br>

        <label for="tipo">Tipo de Instituição</label><br>
        <select id="tipo" name="tipo" required>
            <option value="">Tipo de Instituição</option>
            <option value="hospital_cancer">Hospital de Câncer</option>
            <option value="igreja">Igreja</option>
            <option value="casa_recuperacao">Casa de Recuperação</option>
            <option value="instituicao_beneficiente">Instituição Beneficente</option>
        </select>
        <br>

        <label for="endereco">Endereço</label><br>
        <textarea id="endereco" name="endereco" placeholder="Endereço"></textarea>
        <br>

        <label for="bairro">Bairro</label><br>
        <input id="bairro" type="text" name="bairro" placeholder="Bairro">
        <br>

        <label for="cidade">Cidade</label><br>
        <input id="cidade" type="text" name="cidade" placeholder="Cidade">
        <br>

        <label for="estado">Estado</label><br>
        <select id="estado" name="estado" required>
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
        <input id="cep" type="text" name="cep" placeholder="CEP">
        <br>

        <label for="email">E-mail</label><br>
        <input id="email" type="email" name="email" placeholder="E-mail" required>
        <br>

        <label for="facebook">URL do Facebook</label><br>
        <input id="facebook" type="text" name="facebook" placeholder="URL do Facebook">
        <br>

        <label for="instagram">URL do Instagram</label><br>
        <input id="instagram" type="text" name="instagram" placeholder="URL do Instagram">
        <br>

        <label for="site_oficial">Site Oficial (opcional)</label><br>
        <input id="site_oficial" type="text" name="site_oficial" placeholder="Site Oficial (opcional)">
        <br>

        <label for="chave_pix">Chave PIX</label><br>
        <input id="chave_pix" type="text" name="chave_pix" placeholder="Chave PIX" required>
        <br>

        <label for="uso_das_doacoes">Como você pretende usar as doações?</label><br>
        <textarea id="uso_das_doacoes" name="uso_das_doacoes" placeholder="Descreva como você pretende usar as doações" required></textarea>
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

        // Geração de senha para o usuário
        $password = wp_generate_password();

        // Criação do usuário no WordPress
        $user_id = wp_create_user(sanitize_text_field($_POST['nome']), $password, sanitize_email($_POST['email']));
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
        update_user_meta($user_id, 'chave_pix', sanitize_text_field($_POST['chave_pix']));
        update_user_meta($user_id, 'uso_das_doacoes', sanitize_textarea_field($_POST['uso_das_do acoes']));

        // Inserir dados da instituição na tabela personalizada
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
            'user_id' => $user_id
        ));

        // Enviar e-mail para o administrador
        $admin_email = get_option('admin_email');
        $admin_subject = 'Nova Instituição Cadastrada';
        $admin_message = 'Uma nova instituição foi cadastrada: ' . sanitize_text_field($_POST['nome']) . ' - Tipo: ' . sanitize_text_field($_POST['tipo']);
        wp_mail($admin_email, $admin_subject, $admin_message);

        // Enviar e-mail para a instituição
        $institution_email = sanitize_email($_POST['email']);
        $institution_subject = 'Cadastro Realizado com Sucesso';
        $institution_message = 'Seu cadastro foi realizado com sucesso. Em breve entraremos em contato. Sua senha de acesso é: ' . $password;
        wp_mail($institution_email, $institution_subject, $institution_message);

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

    // Paginação
    $per_page = 16;
    $total_instituicoes = count($instituicoes);
    $total_pages = ceil($total_instituicoes / $per_page);
    $current_page = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
    $offset = ($current_page - 1) * $per_page;

    // Filtrar instituições excluídas
    $instituicoes = array_filter($instituicoes, function($instituicao) {
        return get_userdata($instituicao->user_id) !== false; // Verifica se o usuário ainda existe
    });

    // Limitar a exibição
    $instituicoes = array_slice($instituicoes, $offset, $per_page);

    // Exibir total geral doado
    echo '<h4>Total Geral Doado: R$ ' . number_format(array_sum(array_map(function($instituicao) {
        return get_post_meta($instituicao->user_id, '_donation_amount', true) ?: 0;
    }, $instituicoes)), 2, ',', '.') . '</h4>';

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
            echo '<p><strong>Valor de doações recebidas:</strong> R$ ' . number_format(get_post_meta($instituicao->user_id, '_donation_amount', true) ?: 0, 2, ',', '.') . '</p>';
            echo '<p><strong>Como as doações serão usadas:</strong> ' . esc_html(get_user_meta($instituicao->user_id, 'uso_das_doacoes', true)) . '</p>';
            echo '</div>'; // Fechar div da instituição
        }
        echo '</div>'; // Fechar div das instituições
    } else {
        echo '<p>Nenhuma instituição encontrada.</p>';
    }

    // Exibir paginação
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<a href="?paged=' . $i . '">' . $i . '</a> ';
        }
        echo '</div>'; // Fechar div de paginação
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
    $depoimento = get_user_meta($user_id, 'depoimento', true);

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
            return;
        }

        $user_id = get_current_user_id();
        $depoimento = sanitize_textarea_field($_POST['depoimento']);

        update_user_meta($user_id, 'depoimento', $depoimento);

        wp_redirect(home_url('/?depoimento=sucesso'));
        exit;
    }
}
add_action('init', 'cid_process_depoimento_form');

// Adicionar campo de seleção de instituição na página do produto
function cid_add_donation_field_to_product() {
    global $product;

    if ($product->is_type('simple') || $product->is_type('variable')) {
        echo '<div class="product-donation-field">';
        echo '<h3>' . __('Escolha uma instituição para doação') . '</h3>';
        
        $instituicoes = cid_get_instituicoes();
        $is_instituicoes_empty = count($instituicoes) <= 1;

        woocommerce_form_field('instituicao', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => __('Para qual instituição você deseja doar?'),
            'options' ```php
 => $instituicoes,
            'required' => !$is_instituicoes_empty,
        ), '');
        
        echo '</div>';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'cid_add_donation_field_to_product');

// Validar a seleção da instituição antes de adicionar ao carrinho
function cid_validate_donation_field($passed, $product_id) {
    $instituicoes = cid_get_instituicoes();
    $is_instituicoes_empty = count($instituicoes) <= 1;

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
        
        $donation_percentage = get_option('doacao_percentual', 0);
        $order = wc_get_order($order_id);
        $subtotal = $order->get_subtotal();
        $donation_amount = $subtotal * ($donation_percentage / 100);
        update_post_meta($order_id, '_donation_amount', $donation_amount);

        $doador_nome = get_post_meta($order_id, '_billing_first_name', true) . ' ' . get_post_meta($order_id, '_billing_last_name', true);
        $doador_email = get_post_meta($order_id, '_billing_email', true);
        
        $instituicao_id = sanitize_text_field($_POST['instituicao']);
        $instituicao_email = get_userdata($instituicao_id)->user_email;
        $instituicao_nome = get_userdata($instituicao_id)->display_name;
        $instituicao_chave_pix = get_user_meta($instituicao_id, 'chave_pix', true);

        // Enviar e-mail para o doador
        $message = "Obrigado por sua doação!\n\n";
        $message .= "Você doou R$ " . number_format($donation_amount, 2, ',', '.') . " para a instituição " . $instituicao_nome . ".\n";
        $message .= "Chave PIX da instituição: " . $instituicao_chave_pix . "\n";
        wp_mail($doador_email, 'Confirmação de Doação', $message);

        // Enviar e-mail para a instituição
        $message_instituicao = "Você recebeu uma nova doação!\n\n";
        $message_instituicao .= "Cliente: " . $doador_nome . "\n";
        $message_instituicao .= "Valor da doação: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        $message_instituicao .= "Chave PIX: " . $instituicao_chave_pix . "\n";
        wp_mail($instituicao_email, 'Nova Doação Recebida', $message_instituicao);

        // Enviar e-mail para o administrador
        $admin_message = "Nova doação recebida!\n\n";
        $admin_message .= "Cliente: " . $doador_nome . "\n";
        $admin_message .= "Instituição: " . $instituicao_nome . "\n";
        $admin_message .= "Valor da doação: R$ " . number_format($donation_amount, 2, ',', '.') . "\n";
        $admin_message .= "Chave PIX: " . $instituicao_chave_pix . "\n";
        wp_mail(get_option('admin_email'), 'Nova Doação Recebida', $admin_message);
    }
}
add_action('woocommerce_thankyou', 'cid_save_donation_field');

// Obter instituições para o campo de seleção
function cid_get_instituicoes() {
    $instituicoes = array();
    $instituicoes[0] = __('Selecione uma instituição');

    $args = array(
        'role' => 'subscriber',
        'fields' => array('ID', 'display_name')
    );
    $users = get_users($args);

    foreach ($users as $user) {
        $instituicoes[$user->ID] = $user->display_name;
    }
    return $instituicoes;
}

// Página de administração das doações
function cid_add_donation_menu() {
    add_menu_page('Doações', 'Doações', 'manage_options', 'cid-doacoes', ' cid_doacoes_page');
}
add_action('admin_menu', 'cid_add_donation_menu');

function cid_doacoes_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'posts'; // Tabela de pedidos
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
                    <th>Chave PIX</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->ID); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_instituicao', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_donation_amount', true)); ?></td>
                        <td><?php echo esc_html($row->post_status); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_billing_first_name', true) . ' ' . get_post_meta($row->ID, '_billing_last_name', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($row->ID, '_billing_email', true)); ?></td>
                        <td>
                            <?php
                            $instituicao_id = get_post_meta($row->ID, '_instituicao', true);
                            echo esc_html(get_userdata($instituicao_id)->user_email);
                            ?>
                        </td>
                        <td>
                            <?php
                            echo esc_html(get_user_meta($instituicao_id, 'chave_pix', true));
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

// Garantir que o CSS e JS já criados estejam funcionando
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('meu-estilo', plugin_dir_url(__FILE__) . 'assets/css/eli-silva-donation.css');
    wp_enqueue_script('meu-script', plugin_dir_url(__FILE__) . 'assets/js/eli-silva-donation.js', [], false, true);
});
