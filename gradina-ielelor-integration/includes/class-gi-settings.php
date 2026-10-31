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
        $output['redirects_permanent'] = empty($input['redirects_permanent']) ? 0 : 1;
        $output['onsite_surcharge_enabled'] = empty($input['onsite_surcharge_enabled']) ? 0 : 1;
        $percent = isset($input['onsite_surcharge_percent']) ? (float) str_replace(',', '.', (string) wp_unslash($input['onsite_surcharge_percent'])) : $defaults['onsite_surcharge_percent'];
        $output['onsite_surcharge_percent'] = max(0, min(100, $percent));
        $gateways = isset($input['onsite_surcharge_gateways']) ? (array) wp_unslash($input['onsite_surcharge_gateways']) : array();
        $output['onsite_surcharge_gateways'] = array_values(array_filter(array_map('sanitize_key', $gateways)));
        foreach (array('bungalow', 'glamping', 'camping', 'rulote') as $key) {
            $output['season_' . $key] = isset($input['season_' . $key]) ? sanitize_text_field(wp_unslash($input['season_' . $key])) : $defaults['season_' . $key];
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
                    <tr>
                        <th scope="row">Adresele vechi</th>
                        <td>
                            <label><input type="checkbox" name="gi_integration_options[redirects_permanent]" value="1" <?php checked(!empty($options['redirects_permanent'])); ?>> Redirecționări permanente (301)</label>
                            <p class="description">Lasă nebifat în prima săptămână după lansare: redirecționările sunt temporare, iar dacă revii la site-ul vechi, browserele vizitatorilor nu rămân pe adresele noi. Bifează după 7 zile fără probleme (D+7), ca Google să mute definitiv adresele.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Plata la locație</th>
                        <td>
                            <label><input type="checkbox" name="gi_integration_options[onsite_surcharge_enabled]" value="1" <?php checked(!empty($options['onsite_surcharge_enabled'])); ?>> Tarif majorat când clientul alege plata la locație</label>
                            <p><label>Majorare: <input class="small-text" type="number" min="0" max="100" step="1" name="gi_integration_options[onsite_surcharge_percent]" value="<?php echo esc_attr($options['onsite_surcharge_percent']); ?>"> %</label></p>
                            <?php
                            $gateways = function_exists('WC') && WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
                            foreach ($gateways as $gateway_id => $gateway) :
                                ?>
                                <label style="display:block;margin:4px 0;"><input type="checkbox" name="gi_integration_options[onsite_surcharge_gateways][]" value="<?php echo esc_attr($gateway_id); ?>" <?php checked(in_array($gateway_id, (array) $options['onsite_surcharge_gateways'], true)); ?>> <?php echo esc_html(wp_strip_all_tags($gateway->get_method_title())); ?> <code><?php echo esc_html($gateway_id); ?></code><?php echo 'yes' === $gateway->enabled ? '' : ' (inactivă)'; ?></label>
                            <?php endforeach; ?>
                            <p class="description">Bifează numai metodele de plată la locație, niciodată NETOPIA. Majorarea apare ca rând separat înainte de plată și rămâne în comandă. Când e pornită, paginile spun și vizitatorilor: „Prețurile sunt pentru plata online, cu rezervarea garantată. Dacă alegi plata în locație, tariful e cu 10% mai mare, iar rezervarea nu e garantată.”</p>
                        </td>
                    </tr>
                    <?php foreach (array('bungalow' => 'Bungalow', 'glamping' => 'Glamping', 'camping' => 'Camping', 'rulote' => 'Rulote') as $key => $label) : ?>
                        <tr>
                            <th scope="row"><label for="gi-season-<?php echo esc_attr($key); ?>">Sezon <?php echo esc_html($label); ?></label></th>
                            <td>
                                <input class="regular-text" type="text" id="gi-season-<?php echo esc_attr($key); ?>" name="gi_integration_options[season_<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($options['season_' . $key]); ?>">
                                <p class="description">Apare pe pagina <?php echo esc_html($label); ?>, lângă prețuri și la întrebări (ex. „15 mai – 1 octombrie”). Gol = nu se afișează. Zilele din afara sezonului se închid separat, în fiecare produs BRW.</p>
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
