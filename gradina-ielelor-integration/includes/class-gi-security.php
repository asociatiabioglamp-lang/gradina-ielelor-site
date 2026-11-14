<?php
/**
 * Întăriri de securitate, sigure pentru vizitatori, clienți și plata cu cardul.
 *
 * Ce face:
 *  1. Antete de protecție pe paginile publice (fără încadrare în alte site-uri, fără scurgeri de adresă,
 *     fără acces la cameră/microfon/locație, HTTPS obligatoriu pentru vizitatorii care revin).
 *  2. Ascunde lista conturilor din administrare vizitatorilor (REST „users”, pagini de autor, oEmbed),
 *     ca să nu poată fi aflate numele de utilizator care apoi se atacă cu parole.
 *  3. Mesaj unic la autentificare greșită: nu spune dacă numele există sau nu.
 *  4. Ascunde versiunea WordPress din cod, oprește editorul de fișiere din administrare și XML-RPC.
 *
 * Ce NU face (la hosting, nu în plugin): firewall, limitarea încercărilor de parolă, copii de siguranță,
 * monitorizare. Vezi docs/securitate.md.
 *
 * Nu atinge: administrarea, REST pentru utilizatorii care au dreptul „list_users”, AJAX, WooCommerce,
 * confirmările de plată (NETOPIA), paginile de plată. Orice măsură se poate opri cu un filtru (listate mai jos)
 * sau dezactivând pluginul.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Security {
    private const MSG_LOGIN = 'Datele de autentificare nu sunt corecte. Încearcă din nou.';

    public static function init() {
        // Editorul de fișiere din administrare: un atacator care ajunge în cont nu poate scrie cod PHP de acolo.
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }

        add_action('send_headers', array(__CLASS__, 'send_headers'));

        add_filter('rest_endpoints', array(__CLASS__, 'hide_user_endpoints'));
        add_filter('oembed_response_data', array(__CLASS__, 'strip_oembed_author'));
        add_action('template_redirect', array(__CLASS__, 'block_author_pages'), 1);

        add_filter('authenticate', array(__CLASS__, 'generic_login_error'), 99, 3);
        add_filter('shake_error_codes', array(__CLASS__, 'shake_codes'));

        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
        add_filter('xmlrpc_enabled', '__return_false');
    }

    /**
     * Antetele trimise pe paginile publice. Public, ca să se poată testa fără cerere HTTP.
     *
     * Filtre: gi_security_headers (tabloul de antete), gi_security_hsts_max_age (secunde; 0 = fără HSTS).
     *
     * @return array<string,string>
     */
    public static function headers() {
        $headers = array(
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), usb=(), magnetometer=(), gyroscope=()',
        );

        // HSTS: începem cu 30 de zile și fără subdomenii. Se ridică după o lună fără probleme.
        $max_age = (int) apply_filters('gi_security_hsts_max_age', 30 * DAY_IN_SECONDS);
        if ($max_age > 0 && is_ssl()) {
            $headers['Strict-Transport-Security'] = 'max-age=' . $max_age;
        }

        $filtered = apply_filters('gi_security_headers', $headers);
        return is_array($filtered) ? $filtered : $headers;
    }

    public static function send_headers() {
        // Administrarea, autentificarea, AJAX și REST au antetele lor; nu le atingem.
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)) {
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
     * Administratorii (și editorul de blocuri, care are nevoie) văd în continuare lista.
     */
    public static function hide_user_endpoints($endpoints) {
        if (!is_array($endpoints) || current_user_can('list_users')) {
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
     * /?author=1 și /author/nume/ arată numele de utilizator. Vizitatorii sunt duși pe prima pagină.
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
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }

    /**
     * „Numele nu există” și „parola e greșită” primesc același mesaj, la autentificarea din WordPress și din WooCommerce.
     */
    public static function generic_login_error($user, $username = '', $password = '') {
        if (is_wp_error($user) && array_intersect($user->get_error_codes(), array('invalid_username', 'incorrect_password', 'invalid_email'))) {
            return new WP_Error('gi_login_failed', self::MSG_LOGIN);
        }
        return $user;
    }

    public static function shake_codes($codes) {
        $codes = is_array($codes) ? $codes : array();
        $codes[] = 'gi_login_failed';
        return $codes;
    }
}
