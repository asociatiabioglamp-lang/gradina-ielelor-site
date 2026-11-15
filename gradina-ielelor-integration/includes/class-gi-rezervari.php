<?php
/**
 * Codul QR al rezervării: fiecare rezervare finalizată (comandă WooCommerce în starea „În procesare”, „Finalizată” sau „În așteptare”,
 * adică plata la locație) primește un cod scurt de forma XXXX-XXXX. Codul din QR conține NUMAI codul și o sumă de control (GI1.XXXX-XXXX.YY):
 * fără nume, fără date, fără link către date. La recepție, personalul autentificat caută rezervarea după cod (Zona echipei → Recepție).
 *
 * Unde îl vede turistul: pe pagina de mulțumire și în Contul meu (sub tabelul comenzii), în emailul de confirmare (text + link) și pe pagina
 * /cod-rezervare/<cod>/ (cod QR mare, de arătat sau de printat). Pagina nu conține date personale și nu apare în Google.
 *
 * Datele rezervării (datele de sosire și plecare, adulți, copii) se citesc din eticheta câmpurilor pe care le pune modulul de rezervări
 * în comandă („Check in”, „Check out”, „Adulți”, „Copii”...), nu din chei interne: așa merge cu ce afișează modulul.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Rezervari {
    const META = '_gi_cod_rezervare';
    const VAR = 'gi_cod_rezervare';
    /** Caractere pentru cod: fără 0/O și 1/I/L, ca să se dicteze și să se citească fără greșeli. */
    const ALFABET_COD = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    /** Alfabetul sumei de control: cel al codului, plus 0 și 1 (33 de caractere). */
    const ALFABET_SUMA = '0123456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function init() {
        add_action('init', array(__CLASS__, 'rewrite'), 6);
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_filter('template_include', array(__CLASS__, 'template'), 96);
        add_filter('wp_robots', array(__CLASS__, 'robots'));
        add_filter('pre_get_document_title', array(__CLASS__, 'document_title'), 99);
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_action('template_redirect', array(__CLASS__, 'route'), 3);
        add_action('wp_loaded', array(__CLASS__, 'maybe_flush'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('woocommerce_order_details_after_order_table', array(__CLASS__, 'show_for_customer'), 20);
        // Plasă de siguranță: dacă o temă înlocuiește tabelul comenzii și nu mai cheamă cârligul de mai sus, codul tot apare (o singură dată pe pagină).
        add_action('woocommerce_thankyou', array(__CLASS__, 'show_after_order_id'), 30);
        add_action('woocommerce_view_order', array(__CLASS__, 'show_after_order_id'), 30);
        add_action('woocommerce_email_after_order_table', array(__CLASS__, 'show_in_email'), 20, 4);
        add_action('woocommerce_admin_order_data_after_order_details', array(__CLASS__, 'show_in_admin'));
    }

    /* ---------- Codul și suma de control ---------- */

    /**
     * Suma de control: două caractere calculate din cod. Prinde un cod citit greșit sau tăiat; nu e o parolă.
     * (Același calcul ca în prototipul „Recepție”, ca să se potrivească.)
     */
    public static function checksum($code) {
        $c = str_replace('-', '', strtoupper((string) $code));
        $s = 0;
        $n = strlen($c);
        for ($i = 0; $i < $n; $i++) {
            $k = strpos(self::ALFABET_SUMA, $c[$i]);
            if (false === $k) {
                return '';
            }
            $s = ($s * 31 + $k + 1) % 1089;
        }
        return self::ALFABET_SUMA[intdiv($s, 33) % 33] . self::ALFABET_SUMA[$s % 33];
    }

    /** Textul din codul QR: GI1.<cod>.<suma de control>. */
    public static function payload($code) {
        $code = self::normalize($code);
        return '' === $code ? '' : 'GI1.' . $code . '.' . self::checksum($code);
    }

    /** Cod în forma XXXX-XXXX (majuscule, cu cratimă), sau '' dacă nu are 8 caractere valide. */
    public static function normalize($code) {
        $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
        if (8 !== strlen($c) || strlen($c) !== strspn($c, self::ALFABET_SUMA)) {
            return '';
        }
        return substr($c, 0, 4) . '-' . substr($c, 4);
    }

    /**
     * Citește ce a scris sau a scanat personalul: tot textul din QR (GI1.XXXX-XXXX.YY) sau doar codul.
     * Întoarce array('cod' => 'XXXX-XXXX', 'suma' => true|false|null) (null: s-a scris numai codul, fără sumă de control) sau null.
     */
    public static function parse($text) {
        $t = strtoupper(preg_replace('/\s+/', '', (string) $text));
        if (1 === preg_match('/^GI1\.([A-Z0-9-]+)\.([A-Z0-9]{2})$/', $t, $m)) {
            $code = self::normalize($m[1]);
            return '' === $code ? null : array('cod' => $code, 'suma' => self::checksum($code) === $m[2]);
        }
        $code = self::normalize($t);
        return '' === $code ? null : array('cod' => $code, 'suma' => null);
    }

    private static function make_code() {
        $max = strlen(self::ALFABET_COD) - 1;
        for ($try = 0; $try < 12; $try++) {
            $out = '';
            for ($i = 0; $i < 8; $i++) {
                $out .= self::ALFABET_COD[random_int(0, $max)];
            }
            $code = substr($out, 0, 4) . '-' . substr($out, 4);
            if (!self::find_order($code)) {
                return $code;
            }
        }
        return '';
    }

    /** Rezervare finalizată: comandă plătită, în procesare, sau în așteptare (plata la locație). */
    public static function is_finalized($order) {
        return $order instanceof WC_Order && $order->has_status(array('processing', 'completed', 'on-hold'));
    }

    /**
     * Metodele „plată la locație” (cele pe care se aplică majorarea din Setări, implicit „cod”): WooCommerce le pune în „În procesare”
     * imediat ce comanda e plasată, deci „is_paid()” ar spune „plătită” deși banii se încasează la sosire.
     */
    public static function is_pay_on_site($order) {
        $gateways = array('cod');
        if (function_exists('gi_onsite_surcharge_settings')) {
            $s = gi_onsite_surcharge_settings();
            $gateways = array_merge($gateways, (array) $s['gateways']);
        }
        return in_array((string) $order->get_payment_method(), $gateways, true);
    }

    /** Plătită cu adevărat: WooCommerce o socotește plătită și nu e o plată care se încasează la locație. */
    public static function is_really_paid($order) {
        return (bool) $order->is_paid() && !self::is_pay_on_site($order) && !$order->has_status('on-hold');
    }

    /** Codul unei comenzi; dacă nu are încă și $create e adevărat, i se dă unul. */
    public static function code_for_order($order, $create = true) {
        if (!($order instanceof WC_Order)) {
            return '';
        }
        $code = (string) $order->get_meta(self::META);
        // Numai pentru o comandă salvată (o comandă fictivă, de previzualizare a emailurilor, n-are număr: nu o salvăm).
        if ('' === $code && $create && $order->get_id() > 0) {
            $code = self::make_code();
            if ('' !== $code) {
                $order->update_meta_data(self::META, $code);
                $order->save();
            }
        }
        return $code;
    }

    public static function find_order($code) {
        $code = self::normalize($code);
        if ('' === $code || !function_exists('wc_get_orders')) {
            return null;
        }
        $found = wc_get_orders(array('limit' => 1, 'status' => 'any', 'meta_key' => self::META, 'meta_value' => $code, 'return' => 'objects')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        return $found ? $found[0] : null;
    }

    /**
     * Rezervări făcute înainte de 1.6.0 (fără cod): se găsesc după numărul comenzii ȘI numele de familie al titularului (cele două apar în emailul de
     * confirmare). Numărul singur nu ajunge: recepția nu poate răsfoi comenzile după număr.
     */
    public static function find_by_number_and_name($number, $surname) {
        $id = absint(preg_replace('/\D+/', '', (string) $number));
        $fold = function ($s) {
            return preg_replace('/[^a-z0-9]+/', '', strtolower(remove_accents((string) $s)));
        };
        $want = $fold($surname);
        if ($id < 1 || strlen($want) < 2 || !function_exists('wc_get_order')) {
            return null;
        }
        $order = wc_get_order($id);
        if (!($order instanceof WC_Order) || 'shop_order' !== $order->get_type()) {
            return null;
        }
        $have = $fold($order->get_billing_last_name());
        return '' !== $have && $have === $want ? $order : null;
    }

    /**
     * Găsește rezervarea după ce a scris sau a scanat personalul: cod (sau tot textul din QR) ori număr de comandă + nume de familie.
     * Întoarce array('order' => WC_Order|null, 'label' => 'XXXX-XXXX' sau '#123', 'error' => text sau '', 'empty' => true dacă n-a căutat nimic).
     */
    public static function resolve($cod, $comanda, $nume) {
        $out = array('order' => null, 'label' => '', 'error' => '', 'empty' => false);
        if ('' !== trim((string) $cod)) {
            $parsed = self::parse($cod);
            if (!$parsed) {
                $out['error'] = 'Codul nu are forma așteptată: 8 caractere (litere și cifre), de ex. 7K4M-Q2XP. Verifică și caută din nou.';
            } elseif (false === $parsed['suma']) {
                $out['error'] = 'Suma de control din cod nu se potrivește: codul e greșit sau a fost citit incomplet. Scanează din nou.';
            } else {
                $out['label'] = $parsed['cod'];
                $out['order'] = self::find_order($parsed['cod']);
                if (!$out['order']) {
                    $out['error'] = 'Nu găsesc rezervarea ' . $parsed['cod'] . '. Verifică codul sau caută după numărul comenzii și nume.';
                }
            }
        } elseif ('' !== trim((string) $comanda) || '' !== trim((string) $nume)) {
            $out['label'] = '#' . absint(preg_replace('/\D+/', '', (string) $comanda));
            $out['order'] = self::find_by_number_and_name($comanda, $nume);
            if (!$out['order']) {
                $out['error'] = 'Nu găsesc o rezervare cu numărul și numele acestea. Verifică numărul comenzii și numele de familie (exact cum sunt în emailul de confirmare).';
            }
        } else {
            $out['empty'] = true;
        }
        return $out;
    }

    public static function page_url($code) {
        return home_url('/cod-rezervare/' . rawurlencode(self::normalize($code)) . '/');
    }

    /* ---------- Datele rezervării, pentru recepție ---------- */

    /** Dată din comandă în Y-m-d (acceptă zz-ll-aaaa, zz.ll.aaaa, zz/ll/aaaa, aaaa-ll-zz, cu sau fără oră), sau ''. */
    public static function parse_date($value) {
        $v = trim(wp_strip_all_tags((string) $value));
        if (1 === preg_match('/(\d{1,2})[-.\/](\d{1,2})[-.\/](\d{4})/', $v, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : '';
        }
        if (1 === preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]) : '';
        }
        return '';
    }

    /**
     * Rezumatul unei rezervări, cum îl vede recepția: client, plată, produse cu toate câmpurile din comandă, date de sosire și plecare,
     * adulți și copii (dacă modulul de rezervări le pune în comandă). Câmpurile necunoscute rămân în lista „detalii”, ca să nu se piardă nimic.
     *
     * @return array<string,mixed>
     */
    public static function summary($order) {
        $address = array_filter(array($order->get_billing_address_1(), $order->get_billing_address_2(), $order->get_billing_postcode() . ' ' . $order->get_billing_city(), $order->get_billing_state(), $order->get_billing_country()), function ($x) {
            return '' !== trim((string) $x);
        });
        $out = array(
            'cod' => self::code_for_order($order, false),
            'comanda' => $order->get_order_number(),
            'stare' => wc_get_order_status_name($order->get_status()),
            'finalizata' => self::is_finalized($order),
            'platita' => self::is_really_paid($order),
            'la_locatie' => self::is_pay_on_site($order),
            'plata' => $order->get_payment_method_title(),
            'total' => wp_strip_all_tags(wc_price($order->get_total(), array('currency' => $order->get_currency()))),
            'nume' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'firma' => $order->get_billing_company(),
            'telefon' => $order->get_billing_phone(),
            'adresa' => implode(', ', array_map('trim', $address)),
            'nota' => $order->get_customer_note(),
            'produse' => array(),
            'sosire' => '',
            'plecare' => '',
            'adulti' => null,
            'copii' => null,
        );
        foreach ($order->get_items() as $item) {
            $row = array('nume' => $item->get_name(), 'cantitate' => (int) $item->get_quantity(), 'detalii' => array());
            foreach ($item->get_formatted_meta_data('_', true) as $meta) {
                $label = trim(wp_strip_all_tags((string) $meta->display_key));
                $value = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $meta->display_value)));
                $row['detalii'][] = array('eticheta' => $label, 'valoare' => $value);
                if (1 === preg_match('/check\s*-?\s*in|sosire|arriv/i', $label) && '' === $out['sosire']) {
                    $out['sosire'] = self::parse_date($value);
                } elseif (1 === preg_match('/check\s*-?\s*out|plecare|depart/i', $label) && '' === $out['plecare']) {
                    $out['plecare'] = self::parse_date($value);
                } elseif (1 === preg_match('/adul[tțţ]|adult/iu', $label) && null === $out['adulti']) { // „Adulți” se scrie cu ț, deci „adult” singur nu l-ar prinde
                    $out['adulti'] = (int) $value;
                } elseif (1 === preg_match('/copi|child|minor/iu', $label) && null === $out['copii']) {
                    $out['copii'] = (int) $value;
                }
            }
            $out['produse'][] = $row;
        }
        $out['nopti'] = ('' !== $out['sosire'] && '' !== $out['plecare']) ? max(0, (int) round((strtotime($out['plecare'] . ' UTC') - strtotime($out['sosire'] . ' UTC')) / 86400)) : null;
        return $out;
    }

    /* ---------- Ce vede turistul ---------- */

    private static function styles() {
        static $done = false;
        if ($done) {
            return '';
        }
        $done = true;
        return '<style>.gi-cod-rezervare{margin:24px 0;padding:20px;border:1px solid rgba(27,58,75,.2);border-radius:6px;background:#f7fbfd;color:#22343f;max-width:560px}'
            . '.gi-cod-rezervare h2{margin:0 0 6px;font-size:22px}.gi-cod-rezervare .gi-cod{display:block;margin:6px 0 12px;font:700 28px/1.2 ui-monospace,Menlo,Consolas,monospace;letter-spacing:.06em}'
            . '.gi-cod-rezervare .gi-cod-qr{width:200px;max-width:100%;padding:8px;background:#fff;border:1px solid rgba(27,58,75,.15)}.gi-cod-rezervare .gi-cod-qr svg{display:block;width:100%;height:auto}'
            . '.gi-cod-rezervare p{margin:10px 0 0;font-size:15px;line-height:1.5}.gi-cod-rezervare a{color:#1d6380;font-weight:600}</style>';
    }

    /** Bloc cu codul și codul QR, sub tabelul comenzii (pagina de mulțumire și Contul meu). */
    private static $shown = array();

    public static function show_after_order_id($order_id) {
        $order = wc_get_order((int) $order_id);
        if ($order) {
            self::show_for_customer($order);
        }
    }

    public static function show_for_customer($order) {
        if (!($order instanceof WC_Order) || isset(self::$shown[$order->get_id()])) {
            return;
        }
        if ($order->has_status('pending')) {
            // Plata cu cardul se confirmă de la bancă după câteva minute: până atunci comanda nu e finalizată și nu are cod.
            self::$shown[$order->get_id()] = true;
            echo '<p class="gi-cod-asteptare">Codul tău de rezervare apare aici și în emailul de confirmare imediat ce banca confirmă plata (de obicei în câteva minute). Reîncarcă pagina după un minut.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }
        if (!self::is_finalized($order)) {
            return;
        }
        self::$shown[$order->get_id()] = true;
        $code = self::code_for_order($order);
        if ('' === $code) {
            return;
        }
        echo self::styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<section class="gi-cod-rezervare" aria-labelledby="gi-cod-titlu"><h2 id="gi-cod-titlu">Codul tău de rezervare</h2>'
            . '<strong class="gi-cod">' . esc_html($code) . '</strong>'
            . '<div class="gi-cod-qr" data-qr-url="' . esc_attr(self::payload($code)) . '" role="img" aria-label="' . esc_attr('Cod QR al rezervării ' . $code) . '"></div>'
            . '<p>Arată-l la recepție, de pe telefon sau printat. Codul conține numai numărul rezervării, fără date personale. <a href="' . esc_url(self::page_url($code)) . '">Deschide codul pe tot ecranul</a>.</p></section>';
    }

    /** În emailul de confirmare către client: codul, scris, și linkul spre pagina cu codul QR. */
    public static function show_in_email($order, $sent_to_admin = false, $plain_text = false, $email = null) {
        if ($sent_to_admin || !self::is_finalized($order)) {
            return;
        }
        $code = self::code_for_order($order);
        if ('' === $code) {
            return;
        }
        if ($plain_text) {
            echo "\n" . 'Codul tău de rezervare: ' . $code . "\n" . 'Codul QR, de arătat la recepție: ' . self::page_url($code) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }
        echo '<div style="margin:20px 0;padding:16px;border:1px solid #d8e3e8;border-radius:4px"><h2 style="margin:0 0 8px;font-size:18px">Codul tău de rezervare</h2>'
            . '<p style="margin:0 0 8px;font-family:Consolas,Menlo,monospace;font-size:24px;font-weight:700;letter-spacing:1px">' . esc_html($code) . '</p>'
            . '<p style="margin:0">Arată-l la recepție. <a href="' . esc_url(self::page_url($code)) . '">Deschide codul QR</a> (pe telefon sau printat).</p></div>';
    }

    /** În comanda din administrare: codul rezervării, ca să-l poată citi cine rezolvă o problemă. */
    public static function show_in_admin($order) {
        $code = self::code_for_order($order, false);
        if ('' !== $code) {
            echo '<p class="form-field form-field-wide"><strong>Cod rezervare (QR):</strong> ' . esc_html($code) . '</p>';
        }
    }

    /* ---------- Pagina /cod-rezervare/<cod>/ ---------- */

    public static function rewrite() {
        add_rewrite_rule('^cod-rezervare/([A-Za-z0-9-]+)/?$', 'index.php?' . self::VAR . '=$matches[1]', 'top');
    }

    public static function query_vars($vars) {
        $vars[] = self::VAR;
        return $vars;
    }

    public static function maybe_flush() {
        if (get_option('gi_rezervari_rewrite') !== GI_VERSION) {
            flush_rewrite_rules(false);
            update_option('gi_rezervari_rewrite', GI_VERSION, true);
        }
    }

    public static function is_page() {
        global $wp;
        return '' !== (string) get_query_var(self::VAR) && isset($wp->matched_rule) && 0 === strpos((string) $wp->matched_rule, '^cod-rezervare');
    }

    private static $page_code = '';

    /**
     * Pagina există numai pentru un cod care există. Un cod necunoscut dă „pagină negăsită” (nu arată un cod QR pentru orice text).
     */
    public static function route() {
        if (!self::is_page()) {
            return;
        }
        $code = self::normalize((string) get_query_var(self::VAR));
        $order = '' !== $code ? self::find_order($code) : null;
        self::no_cache();
        if (!$order || !self::is_finalized($order)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        self::$page_code = $code;
        status_header(200);
        global $wp_query;
        $wp_query->is_404 = false;
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }

    /** Nici navigatorul, nici serverul (LiteSpeed Cache, folosit pe site) nu păstrează pagina, nici măcar un 404. */
    private static function no_cache() {
        nocache_headers();
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        do_action('litespeed_control_set_nocache', 'Cod rezervare');
        header('X-LiteSpeed-Cache-Control: no-cache');
    }

    public static function page_code() {
        return self::$page_code;
    }

    public static function template($template) {
        if (self::is_page() && '' !== self::$page_code) {
            return GI_PATH . 'templates/cod-rezervare-page.php';
        }
        return $template;
    }

    public static function robots($robots) {
        if (self::is_page()) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }
        return $robots;
    }

    public static function document_title($title) {
        return self::is_page() ? 'Codul rezervării | ' . get_bloginfo('name') : $title;
    }

    public static function body_class($classes) {
        if (self::is_page()) {
            $classes[] = 'gi-one-page';
            $classes[] = 'gi-page-cod-rezervare';
        }
        return $classes;
    }

    /** Scripturile care desenează codul QR: pagina de mulțumire, Contul meu (comanda) și pagina codului. */
    public static function assets() {
        $order_page = function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('order-received') || is_wc_endpoint_url('view-order'));
        if (!$order_page && !self::is_page()) {
            return;
        }
        wp_enqueue_script('gi-qrcode', gi_asset_url('js/vendor/qrcode.js'), array(), GI_VERSION, true);
        wp_enqueue_script('gi-admin-qr', gi_asset_url('js/admin-qr.js'), array('gi-qrcode'), GI_VERSION, true);
        if (self::is_page()) {
            wp_enqueue_style('gi-one-page', gi_asset_url('css/site.css'), array(), GI_VERSION);
            wp_enqueue_style('gi-pages', gi_asset_url('css/pages.css'), array('gi-one-page'), GI_VERSION);
            wp_enqueue_style('gi-content-page', gi_asset_url('css/content-page.css'), array('gi-pages'), GI_VERSION);
            wp_enqueue_style('gi-zona', gi_asset_url('css/zona.css'), array('gi-content-page'), GI_VERSION);
            wp_enqueue_script('gi-one-page', gi_asset_url('js/site.js'), array(), GI_VERSION, true);
        }
    }
}
