<?php
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Settings {
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
    }

    public static function register() {
        register_setting('gi_integration_group', 'gi_integration_options', array(
            'type' => 'array',
            'sanitize_callback' => array(__CLASS__, 'sanitize'),
            'default' => gi_default_options(),
        ));
    }

    public static function sanitize($input) {
        $defaults = gi_default_options();
        $output = array();
        $input = is_array($input) ? $input : array();
        $output['result_url'] = !empty($input['result_url']) ? esc_url_raw(wp_unslash($input['result_url'])) : $defaults['result_url'];

        foreach (array('category_camping_cort', 'category_camping_rulota', 'category_glamping', 'category_bungalow') as $key) {
            $output[$key] = !empty($input[$key]) ? sanitize_title(wp_unslash($input[$key])) : $defaults[$key];
        }
        return $output;
    }

    public static function menu() {
        add_options_page(
            'Grădina Ielelor — integrare',
            'Grădina Ielelor',
            'manage_options',
            'gi-integration',
            array(__CLASS__, 'render')
        );
    }

    public static function render() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $options = gi_options();
        $woo = class_exists('WooCommerce');
        $brw = shortcode_exists('ovabrw_search');
        ?>
        <div class="wrap">
            <h1>Grădina Ielelor — Site + rezervări</h1>
            <p>Interfața este randată server-side. Formularul React demonstrativ este <strong>dezactivat permanent și nu este inclus în pachet</strong>.</p>

            <h2>Stare dependențe</h2>
            <table class="widefat striped" style="max-width: 760px; margin-bottom: 24px;">
                <tbody>
                    <tr><th>WooCommerce</th><td><?php echo $woo ? '✓ detectat' : '✗ lipsă/inactiv'; ?></td></tr>
                    <tr><th>OVABRW — <code>[ovabrw_search]</code></th><td><?php echo $brw ? '✓ detectat' : '✗ lipsă/inactiv'; ?></td></tr>
                    <tr><th>Motor activ în template</th><td>WooCommerce + OVABRW</td></tr>
                    <tr><th>React demo</th><td>Dezactivat; fără JS React, fără handler de rezervare paralel</td></tr>
                </tbody>
            </table>

            <form method="post" action="options.php">
                <?php settings_fields('gi_integration_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="gi-result-url">URL rezultate BRW</label></th>
                        <td>
                            <input class="regular-text" type="url" id="gi-result-url" name="gi_integration_options[result_url]" value="<?php echo esc_attr($options['result_url']); ?>">
                            <p class="description">Pagina WooCommerce pe care BRW afișează rezultatele căutării. Implicit: pagina Magazin configurată în WooCommerce, nu pagina Blog. Valoare detectată: <code><?php echo esc_html(gi_default_options()['result_url']); ?></code>.</p>
                        </td>
                    </tr>
                    <?php
                    $categories = array(
                        'category_camping_cort' => 'Categorie Camping — cort',
                        'category_camping_rulota' => 'Categorie Camping — rulotă',
                        'category_glamping' => 'Categorie Glamping',
                        'category_bungalow' => 'Categorie Bungalow',
                    );
                    foreach ($categories as $key => $label) :
                        ?>
                        <tr>
                            <th scope="row"><label for="gi-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <input class="regular-text" type="text" id="gi-<?php echo esc_attr($key); ?>" name="gi_integration_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($options[$key]); ?>">
                                <p class="description">Slug-ul categoriei din selectorul BRW, nu numele afișat.</p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button('Salvează integrarea'); ?>
            </form>

            <h2>Activarea paginii</h2>
            <ol>
                <li>Creează sau editează pagina principală WordPress.</li>
                <li>Selectează template-ul <strong>Grădina Ielelor — Pagina principală (rezervare)</strong>.</li>
                <li>Setează pagina drept pagină statică de pornire în Setări → Citire.</li>
                <li>Verifică o căutare reală BRW până la rezultate; nu folosi plăți live pe staging.</li>
            </ol>
            <p>Shortcode opțional pentru alte pagini: <code>[gradina_ielelor_booking]</code>. Acesta randează tot motorul BRW, nu un formular demonstrativ.</p>
        </div>
        <?php
    }
}
