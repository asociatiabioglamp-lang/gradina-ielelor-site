<?php
/**
 * Întăriri de securitate, sigure pentru vizitatori, clienți și plata cu cardul.
 *
 * Ce face:
 *  1. Antete de protecție pe paginile publice (fără încadrare în alte site-uri, fără scurgeri de adresă,
 *     fără acces la cameră și microfon, HTTPS obligatoriu pentru vizitatorii care revin, numai pe domeniul live).
 *  2. Reduce ce se poate afla din afară despre conturile din administrare: lista de utilizatori din REST,
 *     paginile de autor, oEmbed, harta „users” din sitemap-ul WordPress și numele autorului din fluxul RSS.
 *     Nu pretinde că ascunde totul: numele afișat al autorului pe articole rămâne cum l-a ales proprietara.
 *  3. Același mesaj la autentificare greșită și la „am uitat parola”: nu spune dacă numele există.
 *  4. Ascunde versiunea WordPress din cod (parțial: adresele CSS/JS păstrează ?ver=, iar Elementor își pune
 *     propria etichetă; protecția reală e actualizarea). Oprește editorul de fișiere din administrare,
 *     XML-RPC-ul cu autentificare și pingback-ul.
 *
 * Ce NU face (la hosting, nu în plugin): firewall, limitarea încercărilor de parolă, copii de siguranță,
 * monitorizare. Vezi docs/securitate.md.
 *
 * Nu atinge: administrarea, REST pentru utilizatorii care pot edita conținut, AJAX, WooCommerce,
 * confirmările de plată (NETOPIA), paginile de plată. Se pot opri cu filtrele de mai jos
 * (gi_security_headers, gi_security_hsts_max_age, gi_security_hsts_hosts, gi_security_block_author_pages)
 * sau dezactivând pluginul; celelalte măsuri se opresc numai dezactivând pluginul.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Security {
    private const MSG_LOGIN = 'Datele de autentificare nu sunt corecte. Încearcă din nou.';
    private const LOGIN_CODES = array('invalid_username', 'incorrect_password', 'invalid_email');

    public static function init() {
        // Editorul de fișiere din administrare: un atacator care ajunge în cont nu poate scrie cod PHP de acolo.
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }

        add_action('send_headers', array(__CLASS__, 'send_headers'));

        add_filter('rest_endpoints', array(__CLASS__, 'hide_user_endpoints'));
        add_filter('oembed_response_data', array(__CLASS__, 'strip_oembed_author'));
        add_filter('wp_sitemaps_add_provider', array(__CLASS__, 'drop_users_sitemap'), 10, 2);
        add_filter('the_author', array(__CLASS__, 'feed_author'));
        add_action('template_redirect', array(__CLASS__, 'block_author_pages'), 1);

        add_filter('authenticate', array(__CLASS__, 'generic_login_error'), 99, 3);
        add_action('login_form_lostpassword', array(__CLASS__, 'uniform_lost_password'));
        add_action('wp_loaded', array(__CLASS__, 'uniform_wc_lost_password'), 5);
        add_filter('shake_error_codes', array(__CLASS__, 'shake_codes'));

        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('xmlrpc_methods', array(__CLASS__, 'drop_pingback_methods'));
    }

    /**
     * Domeniile pe care se trimite HSTS. Copia de test (alt domeniu) nu primește HSTS: un browser care ar deschide-o
     * ar rămâne forțat pe HTTPS pentru tot domeniul acela, iar anularea se poate face numai de pe același domeniu.
     *
     * @return string[]
     */
    private static function hsts_hosts() {
        $hosts = apply_filters('gi_security_hsts_hosts', array('gradina-ielelor.ro', 'www.gradina-ielelor.ro'));
        return is_array($hosts) ? array_map('strtolower', array_filter($hosts, 'is_string')) : array();
    }

    /**
     * Antetele trimise pe paginile publice. Public, ca să se poată testa fără cerere HTTP.
     *
     * Filtre: gi_security_headers (tabloul de antete), gi_security_hsts_max_age (secunde; 0 = fără HSTS),
     * gi_security_hsts_hosts (domeniile cu HSTS).
     *
     * @return array<string,string>
     */
    public static function headers() {
        $headers = array(
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // Locația rămâne permisă paginii proprii (hărți încorporate cu „locația mea”); cameră și microfon nu.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self), usb=(), magnetometer=(), gyroscope=()',
        );

        // HSTS: începem cu 30 de zile și fără subdomenii. Se ridică după o lună fără probleme.
        $max_age = (int) apply_filters('gi_security_hsts_max_age', 30 * DAY_IN_SECONDS);
        $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        if ($max_age > 0 && is_ssl() && '' !== $host && in_array($host, self::hsts_hosts(), true)) {
            $headers['Strict-Transport-Security'] = 'max-age=' . $max_age;
        }

        $filtered = apply_filters('gi_security_headers', $headers);
        return is_array($filtered) ? $filtered : $headers;
    }

    public static function send_headers() {
        // Administrarea, AJAX, REST și XML-RPC au antetele lor. Paginile „embed” trebuie să poată fi încadrate
        // de alte site-uri WordPress, ca în nucleul WordPress.
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || is_embed()) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        foreach (self::headers() as $name => $value) {
            if (is_string($name) && '' !== $name && is_string($value) && '' !== $value) {
                header($name . ': ' . $value);
            }
        }
    }

    /**
     * Vizitatorii nu mai pot lista conturile prin /wp-json/wp/v2/users.
     * Administratorii, editorii și autorii (editorul de blocuri are nevoie de listă) o văd în continuare.
     */
    public static function hide_user_endpoints($endpoints) {
        if (!is_array($endpoints) || current_user_can('list_users') || current_user_can('edit_posts')) {
            return $endpoints;
        }
        unset($endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        return $endpoints;
    }

    /**
     * oEmbed (/wp-json/oembed/1.0/embed) spune numele și adresa autorului: le scoatem.
     */
    public static function strip_oembed_author($data) {
        if (is_array($data)) {
            unset($data['author_name'], $data['author_url']);
        }
        return $data;
    }

    /**
     * Harta „users” din sitemap-ul WordPress listează adresele /author/nume/.
     */
    public static function drop_users_sitemap($provider, $name = '') {
        return 'users' === $name ? false : $provider;
    }

    /**
     * Fluxurile RSS afișau numele autorului (<dc:creator>); acum arată numele site-ului.
     */
    public static function feed_author($author) {
        return is_feed() ? get_bloginfo('name') : $author;
    }

    /**
     * /?author=1 și /author/nume/ arată numele de utilizator. Vizitatorii sunt duși pe prima pagină.
     * Redirecționare temporară (302), ca în redirects.php: nu rămâne ținută minte în browsere.
     * Filtru: gi_security_block_author_pages (true/false).
     */
    public static function block_author_pages() {
        if (!apply_filters('gi_security_block_author_pages', true)) {
            return;
        }
        $has_author_query = isset($_GET['author']) || isset($_GET['author_name']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$has_author_query && !is_author()) {
            return;
        }
        if (current_user_can('list_users')) {
            return;
        }
        wp_safe_redirect(home_url('/'), 302);
        exit;
    }

    /**
     * „Numele nu există” și „parola e greșită” primesc același mesaj, la autentificarea din WordPress și din WooCommerce.
     * Se înlocuiește numai când toate erorile sunt cele trei coduri de mai sus: erorile altor pluginuri
     * (limitare de încercări, captcha) rămân neatinse.
     */
    public static function generic_login_error($user, $username = '', $password = '') {
        if (is_wp_error($user)) {
            $codes = $user->get_error_codes();
            if ($codes && !array_diff($codes, self::LOGIN_CODES)) {
                return new WP_Error('gi_login_failed', self::MSG_LOGIN);
            }
        }
        return $user;
    }

    private static function account_exists($login) {
        $login = (string) $login;
        $user = false !== strpos($login, '@') ? get_user_by('email', sanitize_email($login)) : get_user_by('login', sanitize_user($login));
        return $user instanceof WP_User;
    }

    /**
     * „Am uitat parola” din wp-login.php: un nume sau un email inexistent primește același răspuns ca unul real
     * („Verifică emailul”), ca să nu se poată afla ce conturi există. Conturile reale merg mai departe prin nucleul WordPress.
     */
    public static function uniform_lost_password() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        if ('POST' !== $method) {
            return;
        }
        $login = isset($_POST['user_login']) ? trim((string) wp_unslash($_POST['user_login'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ('' === $login || self::account_exists($login)) {
            return;
        }
        wp_safe_redirect(add_query_arg('checkemail', 'confirm', wp_login_url()));
        exit;
    }

    /**
     * Același lucru la „Am uitat parola” din Contul meu (WooCommerce): cont inexistent → pagina „link trimis”.
     */
    public static function uniform_wc_lost_password() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        if ('POST' !== $method || !function_exists('wc_get_account_endpoint_url') || !isset($_POST['wc_reset_password'], $_POST['user_login'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }
        $login = trim((string) wp_unslash($_POST['user_login'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ('' === $login || self::account_exists($login)) {
            return;
        }
        wp_safe_redirect(add_query_arg('reset-link-sent', 'true', wc_get_account_endpoint_url('lost-password')));
        exit;
    }

    public static function shake_codes($codes) {
        $codes = is_array($codes) ? $codes : array();
        $codes[] = 'gi_login_failed';
        return $codes;
    }

    /**
     * XML-RPC e blocat de server pe live; aici închidem și pingback-ul (folosit la atacuri de amplificare).
     */
    public static function drop_pingback_methods($methods) {
        if (is_array($methods)) {
            unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
        }
        return $methods;
    }
}
