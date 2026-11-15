<?php
/**
 * „Zona echipei”: partea privată a site-ului, cu intrare direct de pe site (căsuța „Administrare” din subsol), fără să se intre în
 * administrarea WordPress. Utilizator + parolă. Adresa: /zona-echipei/ (nu apare în Google, nu se păstrează în memorii intermediare).
 *
 * Ce se poate face aici (după drepturile contului):
 *  - Vouchere (reduceri de 18 luni, cu cod QR de printat): proprietara, managerii de magazin și echipa de vânzări (cu limite și numai pe ale lor);
 *  - Copaci și locuri (pagini publice cu cod QR, cu poză făcută chiar de pe telefon): proprietara, editorii, managerii de magazin;
 *  - Echipă (conturi pentru colegii de la vânzări, limitele lor): numai administratorii.
 *
 * Conturile de vânzări au rolul „Vânzări (Grădina)”: văd numai Zona echipei; administrarea WordPress îi trimite înapoi aici.
 * Nu se trimit emailuri cu parole: parola se arată o singură dată, pe ecran, celui care creează contul.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Zona {
    const VAR = 'gi_zona';
    const ID_VAR = 'gi_zona_id';
    const BASE = 'zona-echipei';
    const ROLE = 'gi_vanzari';

    /** @var array<string,string> */
    private static $ctx = array('sec' => '', 'id' => '');

    /** @var array<string,mixed> Mesaj afișat în chiar răspunsul la formular (parolele noi: nu se păstrează nicăieri). */
    private static $inline = array();

    public static function init() {
        add_action('init', array(__CLASS__, 'rewrite'), 6);
        add_action('init', array(__CLASS__, 'roles'), 7);
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_action('template_redirect', array(__CLASS__, 'route'), 2);
        add_filter('template_include', array(__CLASS__, 'template'), 97);
        add_filter('wp_robots', array(__CLASS__, 'robots'));
        add_filter('wpseo_robots', array(__CLASS__, 'robots_text'));
        add_filter('rank_math/frontend/robots', array(__CLASS__, 'robots_array'));
        add_filter('pre_get_document_title', array(__CLASS__, 'document_title'), 99);
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_action('wp_loaded', array(__CLASS__, 'maybe_flush'));
        // La „init”, nu la „admin_init”: ecranele care verifică drepturile (articole, fișiere, pagini ale pluginurilor) se opresc cu eroare înainte de „admin_init”.
        add_action('init', array(__CLASS__, 'keep_sales_out_of_admin'), 1);
        add_filter('show_admin_bar', array(__CLASS__, 'hide_admin_bar'));
        add_filter('login_redirect', array(__CLASS__, 'login_redirect'), 10, 3);
        // Parola colegilor de la vânzări o schimbă numai proprietara (hotărâre 15.11.2026): nici prin „Mi-am uitat parola”, nici din profil, nici prin REST.
        add_filter('allow_password_reset', array(__CLASS__, 'block_sales_reset'), 10, 2);
        add_filter('wp_pre_insert_user_data', array(__CLASS__, 'guard_sales_password'), 10, 3);
        add_filter('send_password_change_email', array(__CLASS__, 'no_change_email_for_sales'), 10, 2);
        add_filter('rest_pre_insert_user', array(__CLASS__, 'rest_block_sales_password'), 10, 2);
        add_action('woocommerce_save_account_details_errors', array(__CLASS__, 'account_block_sales_password'), 10, 2);
        add_action('wp_login_failed', array(__CLASS__, 'login_failed'), 9999, 2); // ultimul: alte pluginuri (limitare, jurnal) apucă să numere eșecul înainte de redirecționare
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
    }

    /* ---------- Adrese ---------- */

    public static function url($section = '', $id = '') {
        $path = self::BASE . '/' . ('' !== $section ? $section . '/' : '') . ('' !== $id ? $id . '/' : '');
        return home_url('/' . $path);
    }

    public static function rewrite() {
        add_rewrite_rule('^' . self::BASE . '/?$', 'index.php?' . self::VAR . '=acasa', 'top');
        add_rewrite_rule('^' . self::BASE . '/([a-z]+)/?$', 'index.php?' . self::VAR . '=$matches[1]', 'top');
        add_rewrite_rule('^' . self::BASE . '/([a-z]+)/([a-z0-9-]+)/?$', 'index.php?' . self::VAR . '=$matches[1]&' . self::ID_VAR . '=$matches[2]', 'top');
    }

    public static function query_vars($vars) {
        $vars[] = self::VAR;
        $vars[] = self::ID_VAR;
        return $vars;
    }

    /**
     * Pagina cerută e Zona echipei numai dacă a fost recunoscută de regulile noastre de adrese: un parametru ?gi_zona=… pus de cineva
     * pe altă adresă nu o transformă în Zonă (nici nu o scoate din Google, nici nu o ține în afara memoriei).
     */
    public static function is_zona() {
        global $wp;
        return '' !== (string) get_query_var(self::VAR) && isset($wp->matched_rule) && 0 === strpos((string) $wp->matched_rule, '^' . self::BASE);
    }

    /* ---------- Roluri și drepturi ---------- */

    /**
     * Rolul „Vânzări (Grădina)” și drepturile zonei, puse o singură dată la prima rulare a unei versiuni.
     */
    public static function roles() {
        if (get_option('gi_zona_roluri') === GI_VERSION && self::roles_complete()) {
            return;
        }
        $sales = array('read' => true, 'gi_zona' => true, 'gi_vouchere' => true);
        if (!get_role(self::ROLE)) {
            add_role(self::ROLE, 'Vânzări (Grădina)', $sales);
        } else {
            foreach ($sales as $cap => $grant) {
                get_role(self::ROLE)->add_cap($cap, $grant);
            }
        }
        $grants = array(
            'administrator' => array('gi_zona', 'gi_vouchere', 'gi_vouchere_toate', 'gi_copaci', 'gi_echipa'),
            'shop_manager' => array('gi_zona', 'gi_vouchere', 'gi_vouchere_toate', 'gi_copaci'),
            'editor' => array('gi_zona', 'gi_copaci'),
        );
        foreach ($grants as $role => $caps) {
            $r = get_role($role);
            if ($r) {
                foreach ($caps as $cap) {
                    $r->add_cap($cap, true);
                }
            }
        }
        update_option('gi_zona_roluri', GI_VERSION, true);
    }

    /**
     * Drepturile sunt puse peste tot unde există rolul? (Un rol creat mai târziu, de ex. „manager de magazin” după instalarea WooCommerce,
     * primește drepturile la următoarea cerere, nu abia la următoarea versiune.)
     */
    private static function roles_complete() {
        $sales = get_role(self::ROLE);
        if (!$sales || !$sales->has_cap('gi_zona')) {
            return false;
        }
        foreach (array('administrator', 'shop_manager', 'editor') as $name) {
            $r = get_role($name);
            if ($r && !$r->has_cap('gi_zona')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cont de vânzări: are rolul „Vânzări (Grădina)” și nimic mai mult (nu poate edita conținut). Aceste conturi nu intră în administrare.
     */
    public static function is_sales_only($user) {
        return $user instanceof WP_User && in_array(self::ROLE, (array) $user->roles, true) && !$user->has_cap('edit_posts');
    }

    public static function keep_sales_out_of_admin() {
        if (!is_admin() || !is_user_logged_in() || wp_doing_ajax() || !self::is_sales_only(wp_get_current_user())) {
            return;
        }
        wp_safe_redirect(self::url());
        exit;
    }

    /**
     * „Mi-am uitat parola” nu merge pentru conturile de vânzări: parola nouă o dă proprietara (Echipă → Parolă nouă).
     */
    public static function block_sales_reset($allow, $user_id) {
        $user = get_userdata((int) $user_id);
        if ($user && in_array(self::ROLE, (array) $user->roles, true)) {
            return new WP_Error('gi_parola_proprietara', 'Parola contului tău o schimbă numai proprietara. Cere-i una nouă.');
        }
        return $allow;
    }

    /**
     * Un cont de vânzări nu-și poate schimba parola nici prin profil, Contul meu sau REST: la orice modificare făcută de altcineva decât un
     * administrator, parola rămâne cea veche. (Parolele pe care le dă proprietara trec prin wp_set_password, deci nu sunt oprite.)
     */
    public static function guard_sales_password($data, $update, $user_id) {
        if (!$update || !$user_id || !isset($data['user_pass'])) {
            return $data;
        }
        $user = get_userdata((int) $user_id);
        if ($user && in_array(self::ROLE, (array) $user->roles, true) && !current_user_can('gi_echipa')) {
            $data['user_pass'] = $user->user_pass;
        }
        return $data;
    }

    private static function is_sales_id($user_id) {
        $user = get_userdata((int) $user_id);
        return $user && in_array(self::ROLE, (array) $user->roles, true);
    }

    /**
     * Nu se trimite emailul „Parola a fost schimbată” când schimbarea a fost oprită (contul de vânzări).
     */
    public static function no_change_email_for_sales($send, $user) {
        $id = is_array($user) && isset($user['ID']) ? (int) $user['ID'] : 0;
        return $id && self::is_sales_id($id) && !current_user_can('gi_echipa') ? false : $send;
    }

    /**
     * REST: un cont de vânzări care încearcă să-și schimbe parola primește o eroare clară (nu un „succes” fără efect).
     */
    public static function rest_block_sales_password($prepared, $request) {
        if (is_object($prepared) && !empty($prepared->ID) && isset($request['password']) && self::is_sales_id($prepared->ID) && !current_user_can('gi_echipa')) {
            return new WP_Error('gi_parola_proprietara', 'Parola contului o schimbă numai proprietara.', array('status' => 403));
        }
        return $prepared;
    }

    /**
     * Contul meu (WooCommerce): același lucru, cu mesaj pe pagină.
     */
    public static function account_block_sales_password($errors, $user) {
        if (is_object($user) && !empty($user->ID) && self::is_sales_id($user->ID) && !current_user_can('gi_echipa') && !empty($_POST['password_1'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $errors->add('gi_parola_proprietara', 'Parola contului tău o schimbă numai proprietara. Cere-i una nouă.');
        }
    }

    public static function hide_admin_bar($show) {
        return self::is_sales_only(wp_get_current_user()) ? false : $show;
    }

    public static function login_redirect($redirect_to, $requested, $user) {
        if ($user instanceof WP_User && self::is_sales_only($user)) {
            $base = self::url();
            $ok = 0 === strpos((string) $redirect_to, $base) && false === strpos(rawurldecode((string) $redirect_to), '..'); // fără „..”: nu se iese din Zonă
            return $ok ? $redirect_to : $base; // colegul de la vânzări ajunge numai în Zonă
        }
        return $redirect_to;
    }

    /**
     * Autentificare greșită din formularul de pe site: înapoi în Zona echipei, cu un mesaj, nu pe wp-login.php.
     */
    public static function login_failed($username, $error = null) {
        $ref = isset($_SERVER['HTTP_REFERER']) ? (string) wp_unslash($_SERVER['HTTP_REFERER']) : '';
        if ('' === $ref || false === strpos($ref, '/' . self::BASE . '/')) {
            return;
        }
        wp_safe_redirect(add_query_arg('eroare', '1', self::url()));
        exit;
    }

    /* ---------- Cereri ---------- */

    public static function robots($robots) {
        if (self::is_zona()) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
            $robots['noarchive'] = true;
        }
        return $robots;
    }

    public static function robots_text($robots) {
        return self::is_zona() ? 'noindex, nofollow' : $robots;
    }

    public static function robots_array($robots) {
        return self::is_zona() ? array('index' => 'noindex', 'follow' => 'nofollow') : $robots;
    }

    public static function document_title($title) {
        return self::is_zona() ? 'Zona echipei | ' . get_bloginfo('name') : $title;
    }

    public static function template($template) {
        if (self::is_zona()) {
            return GI_PATH . 'templates/zona-page.php';
        }
        return $template;
    }

    public static function body_class($classes) {
        if (self::is_zona()) {
            $classes[] = 'gi-one-page'; // aceleași reguli de pagină ca restul site-ului (steagul de limbă, bara de administrare)
            $classes[] = 'gi-page-zona';
        }
        return $classes;
    }

    /**
     * Adresele noi (/zona-echipei/...) au nevoie de regulile de adrese reîncărcate o dată, la prima rulare a unei versiuni noi.
     */
    public static function maybe_flush() {
        if (get_option('gi_zona_rewrite') !== GI_VERSION) {
            flush_rewrite_rules(false);
            update_option('gi_zona_rewrite', GI_VERSION, true);
        }
    }

    public static function assets() {
        if (!self::is_zona()) {
            return;
        }
        wp_enqueue_style('gi-one-page', gi_asset_url('css/site.css'), array(), GI_VERSION);
        wp_enqueue_style('gi-pages', gi_asset_url('css/pages.css'), array('gi-one-page'), GI_VERSION);
        wp_enqueue_style('gi-content-page', gi_asset_url('css/content-page.css'), array('gi-pages'), GI_VERSION); // antetul de pagină (titlu pe cer senin)
        wp_enqueue_style('gi-zona', gi_asset_url('css/zona.css'), array('gi-content-page'), GI_VERSION);
        wp_enqueue_script('gi-one-page', gi_asset_url('js/site.js'), array(), GI_VERSION, true);
        if (is_user_logged_in() && current_user_can('gi_zona')) {
            wp_enqueue_script('gi-qrcode', gi_asset_url('js/vendor/qrcode.js'), array(), GI_VERSION, true);
            wp_enqueue_script('gi-admin-qr', gi_asset_url('js/admin-qr.js'), array('gi-qrcode'), GI_VERSION, true);
        }
    }

    private static function flash($data) {
        set_transient('gi_zona_f_' . get_current_user_id(), $data, 300);
    }

    private static function take_flash() {
        $key = 'gi_zona_f_' . get_current_user_id();
        $data = get_transient($key);
        if ($data) {
            delete_transient($key);
        }
        return is_array($data) ? $data : array();
    }

    private static function go($section = '', $id = '') {
        wp_safe_redirect(self::url($section, $id));
        exit;
    }

    private static function posted($key) {
        return isset($_POST[$key]) && is_scalar($_POST[$key]) ? wp_unslash($_POST[$key]) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    }

    /**
     * Verifică codul de siguranță al formularului. Fără el (pagină expirată) nu se face nimic.
     */
    private static function check($action, $back_section, $back_id = '', $field = '_gi_nonce') {
        $nonce = sanitize_text_field((string) self::posted($field));
        if (!wp_verify_nonce($nonce, 'gi_zona_' . $action)) {
            self::flash(array('errors' => array('Pagina a expirat. Încearcă din nou.')));
            self::go($back_section, $back_id);
        }
    }

    private static function deny($section = '') {
        self::flash(array('errors' => array('Nu ai voie să faci asta cu acest cont.')));
        self::go($section);
    }

    public static function route() {
        if (!self::is_zona()) {
            return;
        }
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        do_action('litespeed_control_set_nocache', 'Zona echipei'); // LiteSpeed Cache (folosit pe site) nu păstrează pagina
        header('X-LiteSpeed-Cache-Control: no-cache');
        $sec = sanitize_key((string) get_query_var(self::VAR));
        $id = sanitize_title((string) get_query_var(self::ID_VAR));
        if (!in_array($sec, array('acasa', 'vouchere', 'copaci', 'echipa', 'istoric', 'tipar'), true)) {
            self::go();
        }
        self::$ctx = array('sec' => $sec, 'id' => $id);
        if (!is_user_logged_in() || !current_user_can('gi_zona')) {
            return; // se arată formularul de autentificare sau „contul nu are acces”
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        if ('POST' === $method && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            // Un fișier peste limita serverului: PHP aruncă tot formularul (și textul scris), nu doar poza. Spunem clar ce s-a întâmplat.
            self::flash(array('errors' => array('Poza e prea mare (maximum ' . self::photo_limit() . ') și nu s-a putut trimite, iar textul scris nu s-a păstrat. Alege o poză mai mică și scrie din nou.')));
            self::go($sec, $id);
        }
        if ('POST' === $method) {
            self::handle_post($sec, $id);
            if (!empty(self::$inline)) {
                return; // răspuns afișat direct (conține o parolă): nu trece prin baza de date
            }
        }
        if ('tipar' === $sec) {
            self::print_sheet();
        }
    }

    private static function handle_post($sec, $id) {
        $action = sanitize_key((string) self::posted('gi_actiune'));
        if ('vouchere' === $sec && 'genereaza' === $action) {
            self::post_voucher();
        } elseif ('vouchere' === $sec && 'sterge' === $action) {
            self::post_voucher_delete();
        } elseif ('copaci' === $sec && 'salveaza' === $action) {
            self::post_place($id);
        } elseif ('echipa' === $sec && in_array($action, array('adauga', 'parola', 'elimina', 'limite'), true)) {
            self::post_team($action);
        }
        if (!empty(self::$inline)) {
            return; // parola nouă se arată chiar în acest răspuns, nu după o redirecționare
        }
        self::go($sec, $id);
    }

    /* ---------- Vouchere ---------- */

    private static function post_voucher() {
        self::check('genereaza', 'vouchere');
        if (!current_user_can('gi_vouchere') || !class_exists('GI_Vouchere') || !class_exists('WooCommerce')) {
            self::deny('vouchere');
        }
        $type = 'procent' === sanitize_key((string) self::posted('tip')) ? 'procent' : 'suma';
        $value = (float) str_replace(',', '.', sanitize_text_field((string) self::posted('valoare')));
        $count = absint(self::posted('numar'));
        $result = GI_Vouchere::create_batch($type, $value, $count, sanitize_text_field((string) self::posted('prefix')), sanitize_text_field((string) self::posted('nota')), get_current_user_id());
        self::flash(array('errors' => $result['errors'], 'vouchere' => $result['ids']));
        self::go('vouchere');
    }

    /**
     * Șterge voucherele alese (numai management, numai cele nefolosite). Cererea vine din lista de vouchere, cu un cod de siguranță separat.
     */
    private static function post_voucher_delete() {
        self::check('sterge', 'vouchere', '', '_gi_nonce_sterge');
        if (!class_exists('GI_Vouchere') || !class_exists('WooCommerce') || !(current_user_can('gi_vouchere_toate') || current_user_can('manage_woocommerce'))) {
            self::deny('vouchere');
        }
        $ids = isset($_POST['ids']) ? array_map('absint', (array) wp_unslash($_POST['ids'])) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $ids = array_slice(array_values(array_filter(array_unique($ids))), 0, 200);
        if (!$ids) {
            self::flash(array('errors' => array('Alege cel puțin un voucher de șters.')));
            self::go('vouchere');
        }
        $r = GI_Vouchere::delete_vouchers($ids, get_current_user_id());
        $errors = array();
        if ($r['folosite']) {
            $errors[] = $r['folosite'] . ($r['folosite'] === 1 ? ' voucher nu s-a șters: e deja folosit' : ' vouchere nu s-au șters: sunt deja folosite') . ' (rămân ca dovadă a reducerii).';
        }
        if ($r['refuzate']) {
            $errors[] = $r['refuzate'] . ($r['refuzate'] === 1 ? ' voucher nu s-a putut șterge.' : ' vouchere nu s-au putut șterge.');
        }
        $kept = GI_Vouchere::trash_days();
        $tail = $kept > 0 ? ' Nu se mai pot folosi. Un administrator le poate readuce ' . $kept . ' de zile din WooCommerce → Marketing → Cupoane → Coș.' : ' Nu se mai pot folosi (au fost șterse definitiv: coșul e dezactivat pe acest site).';
        $ok = $r['sterse'] ? ($r['sterse'] === array_slice($r['sterse'], 0, 1) ? 'Am șters voucherul ' . $r['sterse'][0] . '.' : 'Am șters ' . count($r['sterse']) . ' vouchere.') . $tail : '';
        self::flash(array('ok' => $ok, 'errors' => $errors));
        self::go('vouchere');
    }

    /* ---------- Copaci și locuri ---------- */

    private static function photo_limit() {
        return size_format(min(8 * MB_IN_BYTES, wp_max_upload_size()));
    }

    private static function upload_photo($post_id, $alt) {
        if (empty($_FILES['foto']) || !is_array($_FILES['foto']) || UPLOAD_ERR_NO_FILE === (int) $_FILES['foto']['error']) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return '';
        }
        if (UPLOAD_ERR_OK !== (int) $_FILES['foto']['error'] || (int) $_FILES['foto']['size'] > min(8 * MB_IN_BYTES, wp_max_upload_size())) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return 'Poza nu s-a putut încărca (maximum ' . self::photo_limit() . '). Încearcă din nou, cu o poză mai mică.';
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $mimes = array('jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
        $att = media_handle_upload('foto', $post_id, array(), array('test_form' => false, 'mimes' => $mimes));
        if (is_wp_error($att)) {
            return 'Poza nu a fost acceptată: alege o poză JPG, PNG sau WebP.';
        }
        set_post_thumbnail($post_id, $att);
        return '';
    }

    private static function post_place($id) {
        self::check('salveaza', 'copaci', $id);
        if (!current_user_can('gi_copaci')) {
            self::deny('copaci');
        }
        $existing = null;
        if ('nou' !== $id) {
            $existing = get_post((int) $id);
            if (!$existing || GI_Puncte::TYPE !== $existing->post_type) {
                self::flash(array('errors' => array('Locul nu există.')));
                self::go('copaci');
            }
        }
        $title = sanitize_text_field((string) self::posted('titlu'));
        if ('' === $title) {
            self::flash(array('errors' => array('Scrie titlul locului (de ex. „Stejarul bătrân”).')));
            self::go('copaci', $id);
        }
        $args = array(
            'post_type' => GI_Puncte::TYPE,
            'post_title' => $title,
            'post_content' => wp_kses_post((string) self::posted('text')),
            'post_excerpt' => sanitize_text_field((string) self::posted('rezumat')),
            'post_status' => self::posted('publica') ? 'publish' : 'draft',
        );
        if ($existing) {
            $args['ID'] = $existing->ID;
            $post_id = wp_update_post(wp_slash($args), true);
        } else {
            $args['post_author'] = get_current_user_id();
            $post_id = wp_insert_post(wp_slash($args), true);
        }
        if (is_wp_error($post_id) || !$post_id) {
            self::flash(array('errors' => array('Nu s-a putut salva. Încearcă din nou.')));
            self::go('copaci', $id);
        }
        update_post_meta($post_id, '_gi_tip', GI_Puncte::sanitize_type((string) self::posted('tip')));
        update_post_meta($post_id, '_gi_stiintific', sanitize_text_field((string) self::posted('stiintific')));
        $alt = sanitize_text_field((string) self::posted('alt'));
        $problem = self::upload_photo($post_id, $alt);
        if ('' === $problem && has_post_thumbnail($post_id)) { // și un text gol: proprietara poate șterge „ce se vede în poză”
            update_post_meta(get_post_thumbnail_id($post_id), '_wp_attachment_image_alt', $alt);
        }
        if (class_exists('GI_Istoric')) {
            GI_Istoric::log($existing ? 'loc_modificat' : 'loc_creat', (string) $post_id, array('titlu' => $title, 'status' => $args['post_status']));
        }
        self::flash(array('ok' => 'Salvat.', 'errors' => '' !== $problem ? array($problem) : array()));
        self::go('copaci', (string) $post_id);
    }

    /* ---------- Echipă ---------- */

    /**
     * Parola dată de proprietară (sau generată, dacă lasă câmpul gol). Întoarce array(parola, eroare).
     *
     * @return array{0:string,1:string}
     */
    private static function chosen_password($field, $login, $email) {
        $typed = (string) self::posted($field);
        if ('' === $typed) {
            return array(wp_generate_password(16, false), '');
        }
        if (strlen($typed) < 12) {
            return array('', 'Parola trebuie să aibă cel puțin 12 caractere (sau lasă câmpul gol și se generează una).');
        }
        if (strlen($typed) > 128 || preg_match('/[\x00-\x1F\x7F]/', $typed)) {
            return array('', 'Parola are caractere nepermise sau e prea lungă (cel mult 128).');
        }
        if (0 === strcasecmp($typed, (string) $login) || ('' !== (string) $email && 0 === strcasecmp($typed, (string) $email))) {
            return array('', 'Parola nu poate fi la fel cu utilizatorul sau cu emailul.');
        }
        $lower = strtolower($typed);
        $local = strtolower((string) strstr((string) $email, '@', true));
        $obvious = array('gradina', 'ielelor', 'parola', 'password', 'bioglamp', strtolower((string) $login));
        if ('' !== $local && strlen($local) >= 4) {
            $obvious[] = $local;
        }
        foreach ($obvious as $word) {
            if (strlen($word) >= 4 && false !== strpos($lower, $word)) {
                return array('', 'Parola e prea ușor de ghicit: nu folosi numele Grădinii, „parola”, utilizatorul sau emailul. Apasă „Generează” pentru una bună.');
            }
        }
        if (1 === preg_match('/^(.)\1+$/u', $typed) || 1 === preg_match('/^[0-9]+$/', $typed)) {
            return array('', 'Parola e prea ușor de ghicit (aceleași caractere sau numai cifre). Apasă „Generează” pentru una bună.');
        }
        return array($typed, '');
    }

    private static function unique_login($email) {
        $base = sanitize_user(strstr($email, '@', true), true);
        $base = '' !== $base ? $base : 'colegul';
        $login = $base;
        for ($i = 2; username_exists($login) && $i < 100; $i++) {
            $login = $base . $i;
        }
        return $login;
    }

    private static function post_team($action) {
        self::check('echipa_' . $action, 'echipa');
        if (!current_user_can('gi_echipa')) {
            self::deny('');
        }
        if ('limite' === $action) {
            update_option('gi_zona_limite', array(
                'procent' => max(0, min(100, (float) str_replace(',', '.', sanitize_text_field((string) self::posted('lim_procent'))))),
                'suma' => max(0, (float) str_replace(',', '.', sanitize_text_field((string) self::posted('lim_suma')))),
                'numar' => max(0, min(100, absint(self::posted('lim_numar')))),
            ), false);
            if (class_exists('GI_Istoric') && class_exists('GI_Vouchere')) {
                $now = GI_Vouchere::limits();
                GI_Istoric::log('cont_limite', 'limite', array('procent' => $now['procent'], 'suma' => $now['suma'], 'numar' => $now['numar']));
            }
            self::flash(array('ok' => 'Limitele au fost salvate.'));
            self::go('echipa');
        }
        if ('adauga' === $action) {
            $name = sanitize_text_field((string) self::posted('nume'));
            $email = sanitize_email((string) self::posted('email'));
            if ('' === $name || !is_email($email)) {
                self::flash(array('errors' => array('Scrie numele și un email valid.')));
                self::go('echipa');
            }
            $existing_id = email_exists($email);
            if ($existing_id) {
                $old = get_userdata($existing_id);
                $old_roles = $old ? (array) $old->roles : array();
                if ($old && !empty($old_roles) && !in_array(self::ROLE, $old_roles, true) && !$old->has_cap('edit_posts') && !array_diff($old_roles, array('customer', 'subscriber'))) {
                    // Un cont de client (cineva din echipă care a rezervat și el): primește și rolul de vânzări, cu aceeași parolă ca până acum.
                    $old->add_role(self::ROLE);
                    if (class_exists('GI_Istoric')) {
                        GI_Istoric::log('cont_rol', $old->user_login, array('nume' => $old->display_name, 'utilizator' => $old->user_login));
                    }
                    self::flash(array('ok' => 'Contul exista deja (cont de client): ' . $old->display_name . ' (' . $old->user_login . '). Are acum acces la Zonă și intră cu parola lui obișnuită. De acum, parola lui o schimbi numai tu (Parolă nouă), inclusiv pentru contul de client.'));
                    self::go('echipa');
                }
                if ($old && in_array(self::ROLE, $old_roles, true)) {
                    self::flash(array('errors' => array('Acest coleg e deja în echipă.')));
                    self::go('echipa');
                }
                if ($old && empty($old_roles)) {
                    // Un cont căruia i s-a scos accesul înainte (rămas fără rol): îl reactivăm, cu o parolă nouă.
                    list($pass, $perr) = self::chosen_password('parola', $old->user_login, $email);
                    if ('' !== $perr) {
                        self::flash(array('errors' => array($perr)));
                        self::go('echipa');
                    }
                    $old->set_role(self::ROLE);
                    wp_set_password(wp_slash($pass), $old->ID); // WordPress compară la intrare parola cu „bare de escape” (ca în $_POST): o salvăm la fel, ca și cele cu \\ sau ghilimele să intre
                    WP_Session_Tokens::get_instance($old->ID)->destroy_all();
                    if (class_exists('GI_Istoric')) {
                        GI_Istoric::log('cont_reactivat', $old->user_login, array('nume' => $old->display_name, 'utilizator' => $old->user_login));
                    }
                    self::$inline = array('ok' => 'Accesul a fost reactivat.', 'cont' => array('name' => $old->display_name, 'login' => $old->user_login, 'pass' => $pass));
                    return;
                }
                self::flash(array('errors' => array('Există deja un cont cu acest email.')));
                self::go('echipa');
            }
            $typed_login = sanitize_user((string) self::posted('login'), true);
            if ('' !== $typed_login) {
                if (strlen($typed_login) < 3 || strlen($typed_login) > 60 || !validate_username($typed_login)) {
                    self::flash(array('errors' => array('Utilizatorul trebuie să aibă 3–60 de caractere: litere, cifre, punct, cratimă sau liniuță de subliniere.')));
                    self::go('echipa');
                }
                if (username_exists($typed_login)) {
                    self::flash(array('errors' => array('Utilizatorul „' . $typed_login . '” există deja. Alege altul.')));
                    self::go('echipa');
                }
            }
            $login = '' !== $typed_login ? $typed_login : self::unique_login($email);
            list($pass, $perr) = self::chosen_password('parola', $login, $email);
            if ('' !== $perr) {
                self::flash(array('errors' => array($perr)));
                self::go('echipa');
            }
            // Contul se creează cu o parolă provizorie, apoi se pune exact parola aleasă (wp_set_password o ia ca atare, fără alte prelucrări).
            $uid = wp_insert_user(array('user_login' => $login, 'user_email' => $email, 'user_pass' => wp_generate_password(24, true, true), 'display_name' => $name, 'first_name' => $name, 'role' => self::ROLE));
            if (is_wp_error($uid)) {
                self::flash(array('errors' => array('Contul nu s-a putut crea. Încearcă din nou.')));
                self::go('echipa');
            }
            wp_set_password(wp_slash($pass), $uid); // la fel ca mai sus
            if (class_exists('GI_Istoric')) {
                GI_Istoric::log('cont_creat', $login, array('nume' => $name, 'utilizator' => $login));
            }
            self::$inline = array('ok' => 'Contul a fost creat.', 'cont' => array('name' => $name, 'login' => $login, 'pass' => $pass));
            return;
        }
        $uid = absint(self::posted('utilizator'));
        $user = $uid ? get_userdata($uid) : false;
        if (!$user || !in_array(self::ROLE, (array) $user->roles, true) || $user->has_cap('edit_posts') || (int) $uid === get_current_user_id()) {
            self::flash(array('errors' => array('Acest cont nu se poate modifica de aici.')));
            self::go('echipa');
        }
        if ('parola' === $action) {
            list($pass, $perr) = self::chosen_password('parola_noua', $user->user_login, $user->user_email);
            if ('' !== $perr) {
                self::flash(array('errors' => array($perr)));
                self::go('echipa');
            }
            wp_set_password(wp_slash($pass), $uid); // la fel ca mai sus
            WP_Session_Tokens::get_instance($uid)->destroy_all();
            if (class_exists('GI_Istoric')) {
                GI_Istoric::log('cont_parola', $user->user_login, array('nume' => $user->display_name, 'utilizator' => $user->user_login));
            }
            self::$inline = array('ok' => 'Parola a fost schimbată.', 'cont' => array('name' => $user->display_name, 'login' => $user->user_login, 'pass' => $pass));
            return;
        } else {
            $user->remove_role(self::ROLE); // numai rolul de vânzări; alte roluri ale contului (dacă are) rămân
            if (class_exists('GI_Istoric')) {
                GI_Istoric::log('cont_scos', $user->user_login, array('nume' => $user->display_name, 'utilizator' => $user->user_login));
            }
            WP_Session_Tokens::get_instance($uid)->destroy_all();
            self::flash(array('ok' => 'Accesul lui ' . $user->display_name . ' a fost scos.'));
        }
        self::go('echipa');
    }

    /* ---------- Printare ---------- */

    private static function print_sheet() {
        $kind = isset($_GET['tip']) ? sanitize_key(wp_unslash($_GET['tip'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset($_GET['_gi_nonce']) ? sanitize_text_field(wp_unslash($_GET['_gi_nonce'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!wp_verify_nonce($nonce, 'gi_zona_tipar')) {
            wp_die('Linkul a expirat. Închide această filă și apasă din nou „Printează”.', '', array('response' => 403));
        }
        if (('vouchere' === $kind && !current_user_can('gi_vouchere')) || ('copaci' === $kind && !current_user_can('gi_copaci')) || !in_array($kind, array('vouchere', 'copaci'), true)) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        $ids = isset($_GET['ids']) ? array_map('absint', (array) wp_unslash($_GET['ids'])) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $ids = array_slice(array_values(array_filter(array_unique($ids))), 0, 200);
        $size = isset($_GET['size']) ? absint(wp_unslash($_GET['size'])) : 6; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $size = in_array($size, array(4, 6, 8, 10), true) ? $size : 6;
        $tiles = array();
        if ('vouchere' === $kind) {
            $tiles = class_exists('GI_Vouchere') ? GI_Vouchere::print_tiles($ids) : array();
        } else {
            foreach ($ids as $pid) {
                $p = get_post($pid);
                if ($p && GI_Puncte::TYPE === $p->post_type && 'publish' === $p->post_status) {
                    $tiles[] = array('url' => GI_Puncte::qr_url($p), 'title' => get_the_title($p), 'note' => 'Scanează și afli mai multe', 'foot' => preg_replace('#^https?://#', '', get_permalink($p)));
                }
            }
        }
        GI_Puncte::render_print($tiles, $size);
    }

    private static function print_url($kind, $ids, $size = 8) {
        return add_query_arg(array('tip' => $kind, 'ids' => array_map('intval', (array) $ids), 'size' => $size, '_gi_nonce' => wp_create_nonce('gi_zona_tipar')), self::url('tipar'));
    }

    /* ---------- Afișare ---------- */

    private static function nonce_field($action) {
        return '<input type="hidden" name="_gi_nonce" value="' . esc_attr(wp_create_nonce('gi_zona_' . $action)) . '">';
    }

    private static function title_for($sec) {
        $titles = array('acasa' => 'Zona echipei', 'vouchere' => 'Vouchere', 'copaci' => 'Copaci și locuri', 'echipa' => 'Echipă', 'istoric' => 'Istoric', 'tipar' => 'Printare');
        return isset($titles[$sec]) ? $titles[$sec] : 'Zona echipei';
    }

    public static function page_title() {
        return self::title_for(self::$ctx['sec']);
    }

    public static function section() {
        return self::$ctx['sec'];
    }

    public static function render() {
        $flash = array_merge(self::take_flash(), self::$inline);
        if (!is_user_logged_in()) {
            self::view_login();
            return;
        }
        if (!current_user_can('gi_zona')) {
            echo '<div class="gi-zona-card"><h2>Contul acesta nu are acces aici</h2><p>Zona echipei e numai pentru echipa Grădinii. Dacă ar trebui să ai acces, cere-i proprietarei să-ți dea un cont de echipă.</p><p><a class="button button-primary" href="' . esc_url(wp_logout_url(self::url())) . '">Ieși din cont</a></p></div>';
            return;
        }
        self::view_flash($flash);
        $sec = self::$ctx['sec'];
        if ('vouchere' === $sec) {
            self::view_vouchers($flash);
        } elseif ('copaci' === $sec) {
            self::view_places(self::$ctx['id']);
        } elseif ('echipa' === $sec) {
            self::view_team($flash);
        } elseif ('istoric' === $sec) {
            self::view_history();
        } else {
            self::view_home();
        }
    }

    private static function view_flash($flash) {
        if (!empty($flash['errors'])) {
            echo '<div class="gi-zona-notice gi-zona-err" role="alert"><ul>';
            foreach ((array) $flash['errors'] as $e) {
                echo '<li>' . esc_html($e) . '</li>';
            }
            echo '</ul></div>';
        }
        if (!empty($flash['ok'])) {
            echo '<div class="gi-zona-notice gi-zona-ok" role="status">' . esc_html($flash['ok']) . '</div>';
        }
    }

    private static function view_login() {
        $err = isset($_GET['eroare']) ? sanitize_key(wp_unslash($_GET['eroare'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="gi-zona-card gi-zona-login">
            <h2>Intră în zona echipei</h2>
            <p>Pentru echipa Grădinii. Intri cu utilizatorul și parola primite de la proprietară.</p>
            <?php if ('1' === $err) : ?><div class="gi-zona-notice gi-zona-err" role="alert">Datele de autentificare nu sunt corecte. Încearcă din nou.</div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(wp_login_url()); ?>" class="gi-zona-form">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(in_array(self::$ctx['sec'], array('vouchere', 'copaci', 'echipa'), true) ? self::url(self::$ctx['sec'], self::$ctx['id']) : self::url()); ?>">
                <label for="gi-zona-user">Utilizator sau email</label>
                <input type="text" id="gi-zona-user" name="log" autocomplete="username" autocapitalize="none" required>
                <label for="gi-zona-pass">Parola</label>
                <input type="password" id="gi-zona-pass" name="pwd" autocomplete="current-password" required>
                <p><button type="submit" class="button button-primary">Intră</button></p>
            </form>
            <p><a class="gi-text-link" href="<?php echo esc_url(wp_lostpassword_url(self::url())); ?>">Mi-am uitat parola</a></p>
            <p class="gi-zona-mic">Colegii de la vânzări: parola o dă proprietara, ea o și schimbă.</p>
        </div>
        <?php
    }

    private static function view_home() {
        $user = wp_get_current_user();
        ?>
        <p class="gi-zona-hello">Bună, <strong><?php echo esc_html($user->display_name); ?></strong>.</p>
        <div class="gi-zona-grid">
            <?php if (current_user_can('gi_vouchere') && class_exists('WooCommerce')) : ?>
                <a class="gi-zona-tile" href="<?php echo esc_url(self::url('vouchere')); ?>"><strong>Vouchere</strong><span>Generezi coduri de reducere, valabile 18 luni, cu cod QR de printat.</span></a>
            <?php endif; ?>
            <?php if (current_user_can('gi_copaci')) : ?>
                <a class="gi-zona-tile" href="<?php echo esc_url(self::url('copaci')); ?>"><strong>Copaci și locuri</strong><span>Pagina fiecărui copac, cu poză, poveste și cod QR de pus în teren.</span></a>
            <?php endif; ?>
            <?php if (current_user_can('gi_echipa')) : ?>
                <a class="gi-zona-tile" href="<?php echo esc_url(self::url('echipa')); ?>"><strong>Echipă</strong><span>Conturi pentru colegii de la vânzări și limitele lor.</span></a>
            <?php endif; ?>
            <?php if (self::history_prefixes()) : ?>
                <a class="gi-zona-tile" href="<?php echo esc_url(self::url('istoric')); ?>"><strong>Istoric</strong><span>Cine a creat sau a șters ce, și când: vouchere, locuri, conturi.</span></a>
            <?php endif; ?>
        </div>
        <p class="gi-zona-foot">
            <?php if (current_user_can('edit_posts')) : ?><a class="gi-text-link" href="<?php echo esc_url(admin_url()); ?>">Administrarea WordPress</a> · <?php endif; ?>
            <a class="gi-text-link" href="<?php echo esc_url(wp_logout_url(self::url())); ?>">Ieși din cont</a>
        </p>
        <?php
    }

    private static function back_link() {
        echo '<p class="gi-zona-back"><a class="gi-text-link" href="' . esc_url(self::url()) . '">← Zona echipei</a></p>';
    }

    /* --- Vouchere --- */

    private static function view_vouchers($flash) {
        self::back_link();
        if (!current_user_can('gi_vouchere') || !class_exists('WooCommerce') || !class_exists('GI_Vouchere')) {
            echo '<div class="gi-zona-card"><p>Contul tău nu are acces la vouchere.</p></div>';
            return;
        }
        $uid = get_current_user_id();
        $all = current_user_can('gi_vouchere_toate') || current_user_can('manage_woocommerce');
        $limits = GI_Vouchere::limits_for($uid);
        $nice = function ($n) {
            return rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
        };
        if (function_exists('wc_coupons_enabled') && !wc_coupons_enabled()) {
            echo '<div class="gi-zona-notice gi-zona-err">Cupoanele sunt oprite în WooCommerce, deci voucherele nu se aplică la plată. Spune-i proprietarei.</div>';
        }
        if (!empty($flash['vouchere'])) {
            echo '<div class="gi-zona-notice gi-zona-ok"><p><strong>Am generat ' . esc_html((string) count($flash['vouchere'])) . ' cod(uri):</strong></p><p class="gi-zona-coduri">';
            foreach ($flash['vouchere'] as $cid) {
                $c = new WC_Coupon((int) $cid);
                echo esc_html(strtoupper($c->get_code())) . '<br>';
            }
            echo '</p><p>Le găsești mai jos, unde le poți printa cu codul QR.</p></div>';
        }
        ?>
        <div class="gi-zona-card">
            <h2>Voucher nou</h2>
            <p>Valabil <strong><?php echo (int) GI_Vouchere::MONTHS; ?> luni</strong> de azi. Un cod se folosește <strong>o singură dată, în întregime</strong>: nu se poate folosi parțial, iar dacă rezervarea e mai mică decât valoarea lui, diferența se pierde (nu se împarte și nu rămâne pe cod).
                <?php if ($limits) : ?>Pe fiecare cod poți da cel mult <strong><?php echo esc_html($nice($limits['procent'])); ?>%</strong> sau <strong><?php echo esc_html($nice($limits['suma'])); ?> lei</strong> reducere, cel mult <strong><?php echo (int) $limits['numar']; ?></strong> coduri deodată. Codurile tale nu se combină cu alte coduri: la o rezervare se aplică unul singur.<?php endif; ?></p>
            <form method="post" data-gi-o-data action="<?php echo esc_url(self::url('vouchere')); ?>" class="gi-zona-form">
                <input type="hidden" name="gi_actiune" value="genereaza">
                <?php echo self::nonce_field('genereaza'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <fieldset>
                    <legend>Tipul reducerii</legend>
                    <label class="gi-zona-radio"><input type="radio" name="tip" value="suma" checked> sumă fixă, în lei</label>
                    <label class="gi-zona-radio"><input type="radio" name="tip" value="procent"> procent din rezervare</label>
                </fieldset>
                <label for="gi-v-valoare">Valoarea (lei sau %)</label>
                <input type="text" id="gi-v-valoare" name="valoare" inputmode="decimal" required>
                <label for="gi-v-numar">Câte coduri</label>
                <input type="number" id="gi-v-numar" name="numar" min="1" max="100" value="1" required>
                <label for="gi-v-prefix">Începutul codului (litere și cifre)</label>
                <input type="text" id="gi-v-prefix" name="prefix" maxlength="10" value="GRADINA" required>
                <label for="gi-v-nota">Pentru ce / pentru cine (opțional)</label>
                <input type="text" id="gi-v-nota" name="nota" maxlength="120" placeholder="ex. cadou de Crăciun">
                <p class="gi-zona-mic">Nu scrie nume de persoane aici.</p>
                <p><button type="submit" class="button button-primary">Generează codurile</button></p>
            </form>
        </div>
        <?php
        $list = GI_Vouchere::vouchers(200, $all ? 0 : $uid);
        ?>
        <div class="gi-zona-card">
            <h2><?php echo $all ? 'Toate voucherele' : 'Voucherele tale'; ?></h2>
            <?php if (!$list) : ?>
                <p>Nu ai generat încă niciun voucher.</p>
            <?php else : ?>
                <form method="get" action="<?php echo esc_url(self::url('tipar')); ?>" target="_blank">
                    <input type="hidden" name="tip" value="vouchere">
                    <input type="hidden" name="_gi_nonce" value="<?php echo esc_attr(wp_create_nonce('gi_zona_tipar')); ?>">
                    <?php if ($all) : ?><input type="hidden" name="_gi_nonce_sterge" value="<?php echo esc_attr(wp_create_nonce('gi_zona_sterge')); ?>"><?php endif; ?>
                    <ul class="gi-zona-lista">
                        <li class="gi-zona-cap"><label><input type="checkbox" data-qr-select-all-list> Alege toate</label></li>
                        <?php foreach ($list as $p) : $c = new WC_Coupon($p->ID); $exp = $c->get_date_expires(); ?>
                            <li>
                                <label class="gi-zona-bifa"><input type="checkbox" name="ids[]" value="<?php echo esc_attr((string) $p->ID); ?>" aria-label="<?php echo esc_attr(strtoupper($c->get_code())); ?>"></label>
                                <div class="gi-zona-rand">
                                    <strong class="gi-zona-cod"><?php echo esc_html(strtoupper($c->get_code())); ?></strong>
                                    <span><?php echo esc_html(GI_Vouchere::describe($c)); ?> · folosit <?php echo esc_html((string) $c->get_usage_count() . '/' . ($c->get_usage_limit() ? (string) $c->get_usage_limit() : '∞')); ?> · valabil până la <?php echo esc_html($exp ? wp_date('d.m.Y', $exp->getTimestamp()) : '—'); ?> · <em><?php echo esc_html(GI_Vouchere::status($c)); ?></em></span>
                                    <?php if ($c->get_description()) : ?><small><?php echo esc_html($c->get_description()); ?></small><?php endif; ?>
                                    <small>Creat la <?php echo esc_html(wp_date('d.m.Y H:i', strtotime($p->post_date_gmt . ' UTC'))); ?><?php if ($all) : $by = get_userdata((int) get_post_meta($p->ID, '_gi_voucher_creator', true)); ?> · generat de <?php echo esc_html($by ? $by->display_name : '—'); ?><?php endif; ?></small>
                                </div>
                                <div class="gi-qr-mini" data-qr-url="<?php echo esc_attr(GI_Vouchere::code_url($c->get_code())); ?>"></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="gi-zona-alege">
                        <label for="gi-v-size">Mărimea codului</label>
                        <select name="size" id="gi-v-size"><option value="4">mic (4 cm)</option><option value="6" selected>mediu (6 cm)</option><option value="8">mare (8 cm)</option><option value="10">foarte mare (10 cm)</option></select>
                    </p>
                    <p class="gi-zona-butoane"><button type="submit" class="button button-primary">Printează voucherele alese</button>
                        <?php if ($all) : ?><button type="submit" class="button button-outline" formmethod="post" formaction="<?php echo esc_url(self::url('vouchere')); ?>" formtarget="_self" name="gi_actiune" value="sterge" data-gi-confirma="Ștergi voucherele alese? Codurile nu vor mai putea fi folosite.">Șterge voucherele alese</button><?php endif; ?></p>
                    <?php if ($all) : ?><p class="gi-zona-mic">Se șterg numai voucherele nefolosite. <?php echo GI_Vouchere::trash_days() > 0 ? 'Cele șterse merg în Coșul WooCommerce (un administrator le poate readuce ' . (int) GI_Vouchere::trash_days() . ' de zile).' : 'Atenție: pe acest site coșul e dezactivat, deci ștergerea e definitivă.'; ?> Fiecare ștergere rămâne în <a href="<?php echo esc_url(self::url('istoric')); ?>">Istoric</a>.</p><?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /* --- Istoric --- */

    /**
     * Ce părți din istoric are voie să vadă contul curent (după drepturile pe care le are asupra lucrurilor respective).
     *
     * @return string[]
     */
    private static function history_prefixes() {
        $p = array();
        if (current_user_can('gi_vouchere_toate') && class_exists('WooCommerce')) {
            $p[] = 'voucher_';
        }
        if (current_user_can('gi_copaci')) {
            $p[] = 'loc_';
        }
        if (current_user_can('gi_echipa')) {
            $p[] = 'cont_';
        }
        return $p;
    }

    private static function view_history() {
        self::back_link();
        $allowed = self::history_prefixes();
        if (!$allowed || !class_exists('GI_Istoric')) {
            echo '<div class="gi-zona-card"><p>Contul tău nu are acces la istoric.</p></div>';
            return;
        }
        if (!GI_Istoric::exists()) {
            echo '<div class="gi-zona-card"><p><strong>Tabelul istoricului lipsește</strong>, deci nu s-a putut păstra nimic. Spune-i lui Claude (probabil contul de bază de date nu are drept să creeze tabele).</p></div>';
            return;
        }
        $kinds = array('' => 'Tot', 'voucher_' => 'Vouchere', 'loc_' => 'Locuri', 'cont_' => 'Conturi');
        $kind = isset($_GET['tip']) ? sanitize_text_field(wp_unslash($_GET['tip'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $kind = in_array($kind, $allowed, true) ? $kind : '';
        $page = isset($_GET['pagina']) ? max(1, absint(wp_unslash($_GET['pagina']))) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per = 100;
        $rows = GI_Istoric::recent('' === $kind ? $allowed : array($kind), $per, ($page - 1) * $per);
        $more = count($rows) > $per;
        $rows = array_slice($rows, 0, $per);
        $link = function ($k, $pg = 1) {
            $args = array();
            if ('' !== $k) {
                $args['tip'] = $k;
            }
            if ($pg > 1) {
                $args['pagina'] = $pg;
            }
            return esc_url(add_query_arg($args, self::url('istoric')));
        };
        ?>
        <div class="gi-zona-card">
            <h2>Istoric</h2>
            <p>Cine a creat sau a șters ce, și când. Rămâne și după ce un voucher sau un loc e șters. Parolele nu apar niciodată aici.</p>
            <p class="gi-zona-filtre">
                <?php foreach ($kinds as $k => $label) : if ('' !== $k && !in_array($k, $allowed, true)) { continue; } ?>
                    <a class="button <?php echo $k === $kind ? 'button-primary' : 'button-outline'; ?> gi-zona-mic-buton" href="<?php echo $link($k); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </p>
            <?php if (!$rows) : ?>
                <p>Nu e nimic în istoric încă. Apare aici de la această versiune: ce se creează de acum încolo.</p>
            <?php else : ?>
                <div class="gi-zona-tabel"><table>
                    <thead><tr><th>Când</th><th>Cine</th><th>Ce s-a întâmplat</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) : $d = GI_Istoric::describe($row); ?>
                        <tr>
                            <td><?php echo esc_html(wp_date('d.m.Y H:i', strtotime((string) $row->creat . ' UTC'))); ?></td>
                            <td><?php echo esc_html($row->utilizator); ?></td>
                            <td><?php echo esc_html($d['text']); ?>
                                <?php if ($d['coduri']) : ?><details><summary>Coduri (<?php echo esc_html((string) count($d['coduri'])); ?>)</summary><span class="gi-zona-coduri-istoric"><?php echo esc_html(implode(', ', $d['coduri'])); ?></span></details><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <p class="gi-zona-butoane">
                    <?php if ($page > 1) : ?><a class="button button-outline gi-zona-mic-buton" href="<?php echo $link($kind, $page - 1); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">Mai noi</a><?php endif; ?>
                    <?php if ($more) : ?><a class="button button-outline gi-zona-mic-buton" href="<?php echo $link($kind, $page + 1); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">Mai vechi</a><?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /* --- Copaci și locuri --- */

    private static function view_places($id) {
        self::back_link();
        if (!current_user_can('gi_copaci')) {
            echo '<div class="gi-zona-card"><p>Contul tău nu are acces la copaci și locuri.</p></div>';
            return;
        }
        if ('' !== $id) {
            self::view_place_form($id);
            return;
        }
        $posts = get_posts(array('post_type' => GI_Puncte::TYPE, 'post_status' => array('publish', 'draft'), 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC'));
        ?>
        <p><a class="button button-primary" href="<?php echo esc_url(self::url('copaci', 'nou')); ?>">Adaugă un loc</a></p>
        <div class="gi-zona-card">
            <h2>Locurile tale</h2>
            <?php if (!$posts) : ?>
                <p>Nu ai adăugat încă niciun loc. Apasă „Adaugă un loc”: titlu, poză, poveste; primești codul QR.</p>
            <?php else : ?>
                <form method="get" action="<?php echo esc_url(self::url('tipar')); ?>" target="_blank">
                    <input type="hidden" name="tip" value="copaci">
                    <input type="hidden" name="_gi_nonce" value="<?php echo esc_attr(wp_create_nonce('gi_zona_tipar')); ?>">
                    <ul class="gi-zona-lista">
                        <li class="gi-zona-cap"><label><input type="checkbox" data-qr-select-all-list> Alege toate</label></li>
                        <?php foreach ($posts as $p) : $pub = 'publish' === $p->post_status; ?>
                            <li>
                                <label class="gi-zona-bifa"><?php if ($pub) : ?><input type="checkbox" name="ids[]" value="<?php echo esc_attr((string) $p->ID); ?>" aria-label="<?php echo esc_attr(get_the_title($p)); ?>"><?php endif; ?></label>
                                <div class="gi-zona-rand">
                                    <strong><a href="<?php echo esc_url(self::url('copaci', (string) $p->ID)); ?>"><?php echo esc_html(get_the_title($p)); ?></a></strong>
                                    <span><?php echo esc_html(GI_Puncte::type_label($p->ID)); ?><?php echo $pub ? '' : ' · ciornă (nu se vede vizitatorilor)'; ?></span>
                                    <?php if ($pub) : ?><small><a href="<?php echo esc_url(get_permalink($p)); ?>" target="_blank" rel="noopener">Vezi pagina</a></small><?php endif; ?>
                                </div>
                                <?php if ($pub) : ?><div class="gi-qr-mini" data-qr-url="<?php echo esc_attr(GI_Puncte::qr_url($p)); ?>"></div><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="gi-zona-alege"><label for="gi-p-size">Mărimea codului</label>
                        <select name="size" id="gi-p-size"><option value="4">mic (4 cm)</option><option value="6">mediu (6 cm)</option><option value="8" selected>mare (8 cm)</option><option value="10">foarte mare (10 cm)</option></select></p>
                    <p><button type="submit" class="button button-primary">Printează codurile alese</button></p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function view_place_form($id) {
        $post = null;
        if ('nou' !== $id) {
            $post = get_post((int) $id);
            if (!$post || GI_Puncte::TYPE !== $post->post_type) {
                echo '<div class="gi-zona-card"><p>Locul nu există. <a href="' . esc_url(self::url('copaci')) . '">Înapoi la listă</a></p></div>';
                return;
            }
        }
        $pub = !$post || 'publish' === $post->post_status;
        $tip = $post ? (string) get_post_meta($post->ID, '_gi_tip', true) : 'copac';
        $thumb = $post && has_post_thumbnail($post->ID) ? get_the_post_thumbnail_url($post->ID, 'medium') : '';
        $alt = $post && has_post_thumbnail($post->ID) ? (string) get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true) : '';
        ?>
        <div class="gi-zona-card">
            <h2><?php echo $post ? 'Modifică locul' : 'Loc nou'; ?></h2>
            <form method="post" data-gi-o-data action="<?php echo esc_url(self::url('copaci', $post ? (string) $post->ID : 'nou')); ?>" enctype="multipart/form-data" class="gi-zona-form">
                <input type="hidden" name="gi_actiune" value="salveaza">
                <?php echo self::nonce_field('salveaza'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <label for="gi-p-titlu">Titlul (numele locului)</label>
                <input type="text" id="gi-p-titlu" name="titlu" maxlength="120" value="<?php echo esc_attr($post ? $post->post_title : ''); ?>" required placeholder="ex. Stejarul bătrân">
                <label for="gi-p-tip">Ce este?</label>
                <select id="gi-p-tip" name="tip">
                    <?php foreach (GI_Puncte::types() as $k => $l) : ?><option value="<?php echo esc_attr($k); ?>" <?php selected($tip, $k); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?>
                </select>
                <label for="gi-p-st">Numele științific (opțional)</label>
                <input type="text" id="gi-p-st" name="stiintific" maxlength="120" value="<?php echo esc_attr($post ? (string) get_post_meta($post->ID, '_gi_stiintific', true) : ''); ?>" placeholder="ex. Quercus robur">
                <label for="gi-p-rez">O frază sub titlu (opțional)</label>
                <input type="text" id="gi-p-rez" name="rezumat" maxlength="200" value="<?php echo esc_attr($post ? $post->post_excerpt : ''); ?>">
                <label for="gi-p-text">Povestea locului (ce citește vizitatorul după ce scanează codul)</label>
                <textarea id="gi-p-text" name="text" rows="9"><?php echo esc_textarea($post ? $post->post_content : ''); ?></textarea>
                <label for="gi-p-foto">Poza locului <?php echo $thumb ? '(alege alta ca s-o schimbi)' : ''; ?></label>
                <?php if ($thumb) : ?><img class="gi-zona-foto" src="<?php echo esc_url($thumb); ?>" alt=""><?php endif; ?>
                <input type="file" id="gi-p-foto" name="foto" accept="image/jpeg,image/png,image/webp">
                <p class="gi-zona-mic">De pe telefon poți face poza chiar acum. JPG, PNG sau WebP, maximum <?php echo esc_html(self::photo_limit()); ?>.</p>
                <label for="gi-p-alt">Ce se vede în poză (o frază scurtă)</label>
                <input type="text" id="gi-p-alt" name="alt" maxlength="160" value="<?php echo esc_attr($alt); ?>" placeholder="ex. Stejarul, văzut de pe cărare">
                <label class="gi-zona-radio"><input type="checkbox" name="publica" value="1" <?php checked($pub); ?>> Publică (se vede vizitatorilor)</label>
                <p class="gi-zona-mic">Dacă ai printat deja codul și scoți bifa, codul din teren nu mai duce nicăieri.</p>
                <p><button type="submit" class="button button-primary">Salvează</button> <a class="gi-text-link" href="<?php echo esc_url(self::url('copaci')); ?>">Înapoi la listă</a></p>
            </form>
        </div>
        <?php if ($post && 'publish' === $post->post_status) : ?>
        <div class="gi-zona-card">
            <h2>Codul QR</h2>
            <p class="gi-zona-mic">Adresa paginii nu se schimbă când modifici titlul sau textul, deci codul printat rămâne bun. Dacă scoți bifa „Publică” sau ștergi locul, codul din teren nu mai duce nicăieri.</p>
            <div class="gi-qr-box gi-zona-qr" data-qr-url="<?php echo esc_attr(GI_Puncte::qr_url($post)); ?>"></div>
            <p class="gi-zona-mic"><?php echo esc_html(GI_Puncte::qr_url($post)); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(self::print_url('copaci', array($post->ID), 8)); ?>" target="_blank" rel="noopener">Printează acest cod</a>
                <button type="button" class="button button-outline" data-qr-download data-qr-source=".gi-zona-qr" data-filename="<?php echo esc_attr('cod-qr-' . $post->post_name . '.svg'); ?>">Descarcă (SVG)</button>
                <a class="gi-text-link" href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener">Vezi pagina</a>
            </p>
        </div>
        <?php endif; ?>
        <?php
    }

    /* --- Echipă --- */

    private static function view_team($flash) {
        self::back_link();
        if (!current_user_can('gi_echipa')) {
            echo '<div class="gi-zona-card"><p>Numai proprietara poate gestiona echipa.</p></div>';
            return;
        }
        if (!empty($flash['cont'])) {
            $c = $flash['cont'];
            echo '<div class="gi-zona-notice gi-zona-ok"><p><strong>Date de intrare pentru ' . esc_html($c['name']) . '</strong> (se arată o singură dată, nu reîncărca pagina; notează-le și transmite-le):</p>'
                . '<p class="gi-zona-coduri">Adresa: ' . esc_html(self::url()) . '<br>Utilizator: ' . esc_html($c['login']) . '<br>Parola: ' . esc_html($c['pass']) . '</p></div>';
        }
        $team = get_users(array('role' => self::ROLE, 'orderby' => 'display_name'));
        $lim = class_exists('GI_Vouchere') ? GI_Vouchere::limits() : array('procent' => 0, 'suma' => 0, 'numar' => 0);
        ?>
        <div class="gi-zona-card">
            <h2>Colegii de la vânzări</h2>
            <p>Au cont cu rolul „Vânzări (Grădina)”: văd numai Zona echipei, generează vouchere (în limitele de mai jos) și le văd numai pe ale lor. Nu intră în administrarea WordPress.</p>
            <?php if (!$team) : ?>
                <p>Nu ai încă niciun cont de vânzări.</p>
            <?php else : ?>
                <ul class="gi-zona-lista">
                    <?php foreach ($team as $u) : ?>
                        <li>
                            <div class="gi-zona-rand">
                                <strong><?php echo esc_html($u->display_name); ?></strong>
                                <span>Utilizator: <?php echo esc_html($u->user_login); ?> · <?php echo esc_html($u->user_email); ?></span>
                            </div>
                            <form method="post" data-gi-o-data action="<?php echo esc_url(self::url('echipa')); ?>" class="gi-zona-inline">
                                <input type="hidden" name="gi_actiune" value="parola">
                                <input type="hidden" name="utilizator" value="<?php echo esc_attr((string) $u->ID); ?>">
                                <?php echo self::nonce_field('echipa_parola'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <input type="text" name="parola_noua" class="gi-zona-parola-noua" maxlength="128" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="parola nouă (gol = generată)" aria-label="Parola nouă pentru <?php echo esc_attr($u->display_name); ?>">
                                <button type="submit" class="button button-outline">Parolă nouă</button>
                            </form>
                            <form method="post" data-gi-o-data action="<?php echo esc_url(self::url('echipa')); ?>" class="gi-zona-inline">
                                <input type="hidden" name="gi_actiune" value="elimina">
                                <input type="hidden" name="utilizator" value="<?php echo esc_attr((string) $u->ID); ?>">
                                <?php echo self::nonce_field('echipa_elimina'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <button type="submit" class="button button-outline" data-gi-confirma="Scoți accesul acestui cont?">Scoate accesul</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="gi-zona-card">
            <h2>Adaugă un coleg</h2>
            <form method="post" data-gi-o-data action="<?php echo esc_url(self::url('echipa')); ?>" class="gi-zona-form">
                <input type="hidden" name="gi_actiune" value="adauga">
                <?php echo self::nonce_field('echipa_adauga'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <label for="gi-t-nume">Numele</label>
                <input type="text" id="gi-t-nume" name="nume" maxlength="80" required>
                <label for="gi-t-email">Emailul</label>
                <input type="email" id="gi-t-email" name="email" required>
                <label for="gi-t-login">Utilizatorul <span class="gi-zona-mic">(cu el intră în Zonă; lasă gol și se face din email)</span></label>
                <input type="text" id="gi-t-login" name="login" maxlength="60" autocomplete="off" autocapitalize="none" spellcheck="false">
                <label for="gi-t-parola">Parola <span class="gi-zona-mic">(cel puțin 12 caractere; lasă gol și se generează una)</span></label>
                <div class="gi-zona-parola-rand"><input type="text" id="gi-t-parola" name="parola" maxlength="128" autocomplete="off" autocapitalize="none" spellcheck="false"><button type="button" class="button button-outline gi-zona-mic-buton" data-gi-genereaza="#gi-t-parola">Generează</button></div>
                <p class="gi-zona-mic">Utilizatorul și parola le alegi tu. Colegul <strong>nu își poate schimba parola</strong>: numai tu, din „Parolă nouă”. Nu se trimite nimic prin email; după creare ți se arată o dată datele de intrare, ca să i le transmiți.</p>
                <p><button type="submit" class="button button-primary">Creează contul</button></p>
            </form>
        </div>
        <div class="gi-zona-card">
            <h2>Limite pentru vânzări</h2>
            <p>Cât pot da colegii de la vânzări. Tu (și managerii de magazin) nu aveți limite. <strong>Valorile de pornire sunt provizorii: schimbă-le după cum vrei.</strong> Cu 0, tipul respectiv e oprit.</p>
            <form method="post" data-gi-o-data action="<?php echo esc_url(self::url('echipa')); ?>" class="gi-zona-form">
                <input type="hidden" name="gi_actiune" value="limite">
                <?php echo self::nonce_field('echipa_limite'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <label for="gi-l-p">Reducere maximă în procente (%)</label>
                <input type="text" id="gi-l-p" name="lim_procent" inputmode="decimal" value="<?php echo esc_attr(rtrim(rtrim(number_format((float) $lim['procent'], 2, ',', ''), '0'), ',')); ?>">
                <label for="gi-l-s">Reducere maximă în lei</label>
                <input type="text" id="gi-l-s" name="lim_suma" inputmode="decimal" value="<?php echo esc_attr(rtrim(rtrim(number_format((float) $lim['suma'], 2, ',', ''), '0'), ',')); ?>">
                <label for="gi-l-n">Câte coduri deodată, cel mult</label>
                <input type="number" id="gi-l-n" name="lim_numar" min="0" max="100" value="<?php echo esc_attr((string) (int) $lim['numar']); ?>">
                <p><button type="submit" class="button button-primary">Salvează limitele</button></p>
            </form>
        </div>
        <?php
    }
}
