<?php
/**
 * Vouchere (coduri de reducere) în zona privată „Grădina”: se generează din administrare, valabile 18 luni, fiecare cu codul ei QR.
 *
 * Cerut de proprietară (14.11.2026): „zona administrativă de unde se generează coduri vouchere de discount valabile 18 luni”.
 *
 * - Sunt cupoane WooCommerce obișnuite (WooCommerce → Marketing → Cupoane): se văd, se modifică și se pot opri și de acolo.
 * - Valoarea (sumă fixă în lei sau procent), numărul de coduri și prefixul le alege ea în formular; nu există valori implicite inventate.
 * - Valabilitatea: 18 luni de la generare (constanta MONTHS). O singură folosire pe cod.
 * - Codul QR duce la site (/?gi_cod=COD): codul se reține 30 de zile și se aplică singur în coș sau la plată, dacă rezervarea
 *   permite reduceri. Prețul final vine numai din rezervări (BRW) și WooCommerce, ca până acum.
 * - Fără date personale în cod sau în cupon.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Vouchere {
    const MONTHS = 18;
    const COOKIE = 'gi_cod';
    const SCREEN = 'gi-vouchere';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 11);
        add_action('admin_post_gi_voucher_gen', array(__CLASS__, 'generate'));
        // template_redirect (nu wp_loaded): cererile REST, AJAX, de autentificare și cron nu ajung aici, deci nu pot seta cookie-ul.
        add_action('template_redirect', array(__CLASS__, 'catch_code'), 1);
        // template_redirect: antetele nu au plecat încă, deci cookie-ul se poate șterge după încercare.
        add_action('template_redirect', array(__CLASS__, 'apply_saved_code'), 20);
    }

    /**
     * Cine poate genera vouchere: cine administrează magazinul (administrator, manager de magazin).
     */
    public static function allowed() {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        return current_user_can('manage_woocommerce') || current_user_can('manage_options');
    }

    public static function menu() {
        if (class_exists('WooCommerce')) {
            add_submenu_page(GI_Puncte::MENU, 'Vouchere', 'Vouchere', 'manage_woocommerce', self::SCREEN, array(__CLASS__, 'screen'));
        }
    }

    /* ---------- Generare ---------- */

    /**
     * Cod ușor de dictat și de citit: fără 0/O, 1/I/L. Exemplu: GRADINA-K7M2-Q9XP.
     */
    public static function make_code($prefix) {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $part = function () use ($alphabet, $max) {
            $s = '';
            for ($i = 0; $i < 4; $i++) {
                $s .= $alphabet[random_int(0, $max)];
            }
            return $s;
        };
        return strtoupper($prefix) . '-' . $part() . '-' . $part();
    }

    public static function expiry() {
        $date = new DateTimeImmutable('now', wp_timezone());
        return $date->modify('+' . self::MONTHS . ' months')->setTime(23, 59, 59);
    }

    /**
     * Limitele pentru echipa de vânzări (cei fără drept de a vedea toate voucherele): procent maxim, sumă maximă, număr maxim de coduri
     * într-o generare. Le setează proprietara în Zona echipei → Echipă. Valorile de pornire sunt provizorii.
     */
    public static function default_limits() {
        return array('procent' => 10, 'suma' => 100, 'numar' => 10);
    }

    public static function limits() {
        $saved = get_option('gi_zona_limite');
        $d = self::default_limits();
        if (!is_array($saved)) {
            return $d;
        }
        return array(
            'procent' => isset($saved['procent']) ? max(0, min(100, (float) $saved['procent'])) : $d['procent'],
            'suma' => isset($saved['suma']) ? max(0, (float) $saved['suma']) : $d['suma'],
            'numar' => isset($saved['numar']) ? max(0, min(100, (int) $saved['numar'])) : $d['numar'],
        );
    }

    /**
     * Limitele care se aplică unui utilizator, sau null dacă nu are limite (administratori și manageri de magazin).
     */
    public static function limits_for($user_id) {
        $user = get_userdata((int) $user_id);
        if ($user && ($user->has_cap('gi_vouchere_toate') || $user->has_cap('manage_woocommerce'))) {
            return null;
        }
        return self::limits();
    }

    /**
     * Generează un lot de vouchere (cupoane WooCommerce) în numele unui utilizator. Întoarce array('errors' => [...], 'ids' => [...]).
     * Verificările de drepturi (cine are voie să cheme funcția) se fac la apelant; limitele de valoare se aplică aici.
     */
    public static function create_batch($type, $value, $count, $prefix, $note, $user_id) {
        $errors = array();
        $type = 'procent' === $type ? 'procent' : 'suma';
        $value = (float) $value;
        $count = (int) $count;
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $prefix));
        if ($value <= 0) {
            $errors[] = 'Scrie valoarea reducerii (un număr mai mare ca 0).';
        } elseif ('procent' === $type && $value > 100) {
            $errors[] = 'Procentul nu poate fi mai mare de 100.';
        }
        if ($count < 1 || $count > 100) {
            $errors[] = 'Numărul de coduri trebuie să fie între 1 și 100.';
        }
        if (strlen($prefix) < 2 || strlen($prefix) > 10) {
            $errors[] = 'Începutul codului trebuie să aibă între 2 și 10 litere sau cifre (de ex. GRADINA).';
        }
        $limits = self::limits_for($user_id);
        if (!$errors && $limits) {
            $nice = function ($n) {
                return rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
            };
            if ('procent' === $type && $value > $limits['procent']) {
                $errors[] = $limits['procent'] > 0 ? 'Poți da cel mult ' . $nice($limits['procent']) . '% reducere. Pentru mai mult, cere-i proprietarei.' : 'Nu ai voie să generezi reduceri în procente. Cere-i proprietarei.';
            }
            if ('suma' === $type && $value > $limits['suma']) {
                $errors[] = $limits['suma'] > 0 ? 'Poți da cel mult ' . $nice($limits['suma']) . ' lei reducere. Pentru mai mult, cere-i proprietarei.' : 'Nu ai voie să generezi reduceri în lei. Cere-i proprietarei.';
            }
            if ($limits['numar'] < 1) {
                $errors[] = 'Nu ai voie să generezi coduri. Cere-i proprietarei.';
            } elseif ($count > $limits['numar']) {
                $errors[] = 'Poți genera cel mult ' . (int) $limits['numar'] . ' coduri deodată.';
            }
        }
        if ($errors) {
            return array('errors' => $errors, 'ids' => array());
        }

        $expires = self::expiry();
        $lot = gmdate('YmdHis') . '-' . wp_generate_password(4, false, false);
        $created = array();
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($try = 0; $try < 12; $try++) {
                try {
                    $candidate = self::make_code($prefix);
                } catch (Exception $e) {
                    $candidate = '';
                    $errors[] = 'Serverul nu poate face acum coduri sigure. Încearcă din nou peste câteva minute.';
                    break 2;
                }
                if (!wc_get_coupon_id_by_code($candidate)) {
                    $code = $candidate;
                    break;
                }
            }
            if ('' === $code) {
                $errors[] = 'Nu am putut face un cod nou, încearcă din nou.';
                break;
            }
            $coupon = new WC_Coupon();
            $coupon->set_code($code);
            $coupon->set_discount_type('procent' === $type ? 'percent' : 'fixed_cart');
            $coupon->set_amount($value);
            $coupon->set_usage_limit(1);
            // Codurile celor cu limite se aplică singure la o rezervare: altfel zece coduri de 10% s-ar aduna la 100% reducere.
            $coupon->set_individual_use($limits ? true : false);
            $coupon->set_date_expires($expires->getTimestamp());
            $coupon->set_description($note ? $note : 'Voucher generat din zona Grădina');
            $coupon->update_meta_data('_gi_voucher_lot', $lot);
            $coupon->update_meta_data('_gi_voucher_creator', (int) $user_id);
            $coupon->save();
            $created[] = $coupon->get_id();
        }
        return array('errors' => $errors, 'ids' => $created);
    }

    public static function generate() {
        if (!self::allowed()) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        check_admin_referer('gi_voucher_gen');

        $type = isset($_POST['tip']) && 'procent' === sanitize_key(wp_unslash($_POST['tip'])) ? 'procent' : 'suma';
        $value = isset($_POST['valoare']) ? (float) str_replace(',', '.', sanitize_text_field(wp_unslash($_POST['valoare']))) : 0.0;
        $count = isset($_POST['numar']) ? absint(wp_unslash($_POST['numar'])) : 1;
        $prefix = isset($_POST['prefix']) ? sanitize_text_field(wp_unslash($_POST['prefix'])) : '';
        $note = isset($_POST['nota']) ? sanitize_text_field(wp_unslash($_POST['nota'])) : '';

        $result = self::create_batch($type, $value, $count, $prefix, $note, get_current_user_id());
        set_transient('gi_voucher_rezultat_' . get_current_user_id(), array('errors' => $result['errors'], 'ids' => $result['ids'], 'type' => $type, 'value' => $value), 300);
        wp_safe_redirect(admin_url('admin.php?page=' . self::SCREEN));
        exit;
    }

    /* ---------- Ecranul „Vouchere” ---------- */

    /**
     * Voucherele generate din zona Grădina. Cu $creator (id de utilizator): numai cele generate de acel utilizator.
     */
    public static function vouchers($limit = 200, $creator = 0) {
        $args = array(
            'post_type' => 'shop_coupon',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_gi_voucher_lot', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'suppress_filters' => true,
        );
        if ($creator) {
            $args['meta_query'] = array(array('key' => '_gi_voucher_creator', 'value' => (int) $creator, 'compare' => '=', 'type' => 'NUMERIC')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        }
        return get_posts($args);
    }

    /**
     * Poate utilizatorul să vadă și să printeze acest voucher? Cine are „gi_vouchere_toate” sau administrează magazinul: toate;
     * ceilalți (vânzări): numai pe ale lor.
     */
    public static function can_see($coupon_id, $user_id = 0) {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        $user = get_userdata($user_id);
        if (!$user || !metadata_exists('post', $coupon_id, '_gi_voucher_lot')) {
            return false;
        }
        if ($user->has_cap('gi_vouchere_toate') || $user->has_cap('manage_woocommerce')) {
            return true;
        }
        return $user->has_cap('gi_vouchere') && (int) get_post_meta($coupon_id, '_gi_voucher_creator', true) === $user_id;
    }

    public static function describe($coupon) {
        $amount = (float) $coupon->get_amount();
        $nice = rtrim(rtrim(number_format($amount, 2, ',', ''), '0'), ',');
        return 'percent' === $coupon->get_discount_type() ? $nice . '%' : $nice . ' lei';
    }

    public static function status($coupon) {
        $expires = $coupon->get_date_expires();
        if ($expires && $expires->getTimestamp() < time()) {
            return 'expirat';
        }
        $limit = (int) $coupon->get_usage_limit();
        if ($limit && (int) $coupon->get_usage_count() >= $limit) {
            return 'folosit';
        }
        return 'valabil';
    }

    public static function code_url($code) {
        return add_query_arg(self::COOKIE, rawurlencode(strtoupper($code)), home_url('/'));
    }

    public static function screen() {
        if (!self::allowed()) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        $key = 'gi_voucher_rezultat_' . get_current_user_id();
        $result = get_transient($key);
        if ($result) {
            delete_transient($key);
        }
        $list = self::vouchers();
        ?>
        <div class="wrap">
            <h1>Vouchere</h1>
            <?php GI_Puncte::address_notice(); ?>
            <?php if (function_exists('wc_coupons_enabled') && !wc_coupons_enabled()) : ?>
                <div class="notice notice-warning inline"><p><strong>Atenție:</strong> cupoanele sunt oprite în WooCommerce, deci voucherele nu se aplică la plată. Pornește-le în <em>WooCommerce → Setări → General → „Activează cupoanele”</em>.</p></div>
            <?php endif; ?>
            <p>Generezi coduri de reducere, <strong>valabile <?php echo (int) self::MONTHS; ?> luni</strong> de azi, o singură folosire pe cod. Fiecare cod are un cod QR de printat. Voucherele sunt și cupoane WooCommerce: le găsești, le modifici sau le oprești și în <em>WooCommerce → Marketing → Cupoane</em>.</p>

            <?php if (!empty($result['errors'])) : ?>
                <div class="notice notice-error"><ul style="margin:.6em 0;"><?php foreach ($result['errors'] as $e) : ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if (!empty($result['ids'])) : ?>
                <div class="notice notice-success" style="padding:10px 14px;">
                    <p><strong>Am generat <?php echo esc_html((string) count($result['ids'])); ?> cod(uri):</strong></p>
                    <p style="font-family:monospace;font-size:15px;line-height:1.8;">
                        <?php foreach ($result['ids'] as $id) : $c = new WC_Coupon($id); ?>
                            <?php echo esc_html(strtoupper($c->get_code())); ?><br>
                        <?php endforeach; ?>
                    </p>
                    <p class="description">Le găsești și în lista de mai jos, unde le poți printa cu codul QR.</p>
                </div>
            <?php endif; ?>

            <h2>Generează vouchere noi</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:760px;">
                <input type="hidden" name="action" value="gi_voucher_gen">
                <?php wp_nonce_field('gi_voucher_gen'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Tipul reducerii</th>
                        <td>
                            <label><input type="radio" name="tip" value="suma" checked> sumă fixă, în lei</label><br>
                            <label><input type="radio" name="tip" value="procent"> procent din rezervare</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gi_v_valoare">Valoarea</label></th>
                        <td><input type="text" id="gi_v_valoare" name="valoare" class="small-text" inputmode="decimal" required> <span class="description">lei sau %, după tipul ales</span></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gi_v_numar">Câte coduri</label></th>
                        <td><input type="number" id="gi_v_numar" name="numar" class="small-text" min="1" max="100" value="1" required> <span class="description">între 1 și 100, cu aceeași valoare</span></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gi_v_prefix">Începutul codului</label></th>
                        <td><input type="text" id="gi_v_prefix" name="prefix" class="regular-text" maxlength="10" value="GRADINA" required> <span class="description">litere și cifre; ex. GRADINA → GRADINA-K7M2-Q9XP</span></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gi_v_nota">Pentru ce / pentru cine</label></th>
                        <td><input type="text" id="gi_v_nota" name="nota" class="large-text" placeholder="opțional, ex. cadou de Crăciun"> <span class="description">se vede numai în administrare; nu scrie nume de persoane</span></td>
                    </tr>
                </table>
                <p><button type="submit" class="button button-primary">Generează codurile</button></p>
            </form>

            <h2 style="margin-top:32px;">Voucherele generate</h2>
            <?php if (!$list) : ?>
                <p>Nu ai generat încă niciun voucher.</p>
            <?php else : ?>
                <form method="get" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
                    <input type="hidden" name="action" value="gi_qr_print">
                    <input type="hidden" name="tip" value="vouchere">
                    <?php wp_nonce_field('gi_qr_print', '_wpnonce', false); ?>
                    <table class="widefat striped" style="max-width:980px;">
                        <thead><tr><td class="check-column"><input type="checkbox" data-qr-select-all aria-label="Alege toate"></td><th>Cod</th><th>Reducere</th><th>Folosit</th><th>Valabil până la</th><th>Stare</th><th>Cod QR</th></tr></thead>
                        <tbody>
                        <?php foreach ($list as $p) : $c = new WC_Coupon($p->ID); $exp = $c->get_date_expires(); ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo esc_attr((string) $p->ID); ?>" aria-label="<?php echo esc_attr(strtoupper($c->get_code())); ?>"></th>
                                <td style="font-family:monospace;"><a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>"><?php echo esc_html(strtoupper($c->get_code())); ?></a><?php if ($c->get_description()) : ?><br><small><?php echo esc_html($c->get_description()); ?></small><?php endif; ?></td>
                                <td><?php echo esc_html(self::describe($c)); ?></td>
                                <td><?php echo esc_html((string) $c->get_usage_count() . ' / ' . ($c->get_usage_limit() ? (string) $c->get_usage_limit() : '∞')); ?></td>
                                <td><?php echo esc_html($exp ? wp_date('d.m.Y', $exp->getTimestamp()) : '—'); ?></td>
                                <td><?php echo esc_html(self::status($c)); ?></td>
                                <td><div class="gi-qr-mini" data-qr-url="<?php echo esc_attr(self::code_url($c->get_code())); ?>" style="width:56px;"></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <label for="gi_v_size"><strong>Mărimea codului:</strong></label>
                        <select name="size" id="gi_v_size"><option value="4">mic (4 cm)</option><option value="6" selected>mediu (6 cm)</option><option value="8">mare (8 cm)</option><option value="10">foarte mare (10 cm)</option></select>
                        <button type="submit" class="button button-primary">Printează voucherele alese</button>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Bucăți de printat pentru voucherele alese (folosit de pagina de printare din GI_Puncte).
     *
     * @param int[] $ids
     * @return array<int,array<string,string>>
     */
    public static function print_tiles($ids) {
        $tiles = array();
        foreach ($ids as $id) {
            $post = get_post($id);
            if (!$post || 'shop_coupon' !== $post->post_type || !self::can_see($post->ID)) {
                continue;
            }
            $c = new WC_Coupon($post->ID);
            $exp = $c->get_date_expires();
            $tiles[] = array(
                'url' => self::code_url($c->get_code()),
                'title' => strtoupper($c->get_code()),
                'note' => 'Reducere ' . self::describe($c) . ($exp ? ' · valabil până la ' . wp_date('d.m.Y', $exp->getTimestamp()) : ''),
                'foot' => preg_replace('#^https?://#', '', home_url('/')),
            );
        }
        return $tiles;
    }

    /* ---------- Codul din QR: reținut și aplicat în coș ---------- */

    /**
     * Cineva a scanat codul QR (/?gi_cod=COD): reținem codul 30 de zile și ducem vizitatorul la rezervare.
     * Nu se verifică și nu se dezvăluie nimic aici; codul se verifică de WooCommerce când se aplică.
     */
    public static function catch_code() {
        if (is_admin() || !isset($_GET[self::COOKIE]) || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : 'GET';
        if (!in_array($method, array('GET', 'HEAD'), true) || (isset($GLOBALS['pagenow']) && 'wp-login.php' === $GLOBALS['pagenow'])) {
            return;
        }
        $code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', sanitize_text_field(wp_unslash($_GET[self::COOKIE])))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ('' === $code || strlen($code) > 40) {
            return;
        }
        setcookie(self::COOKIE, $code, array(
            'expires' => time() + 30 * DAY_IN_SECONDS,
            'path' => COOKIEPATH ? COOKIEPATH : '/',
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ));
        $_COOKIE[self::COOKIE] = $code;
        nocache_headers(); // redirecționarea nu se păstrează în nicio memorie intermediară
        wp_safe_redirect(home_url('/') . '#rezervare');
        exit;
    }

    /**
     * Pe pagina coșului și la plată, când vizitatorul are o rezervare în coș: aplicăm o singură dată codul scanat.
     * Se aplică numai vouchere generate de plugin (nu orice cupon). După încercare, cookie-ul se șterge (și după comandă),
     * ca un cod refuzat (expirat, folosit) să nu reapară. Dacă WooCommerce îl refuză, apare mesajul lui obișnuit.
     */
    public static function apply_saved_code() {
        if (empty($_COOKIE[self::COOKIE]) || !function_exists('WC') || !function_exists('is_cart') || !(is_cart() || is_checkout())) {
            return;
        }
        $clear = function () {
            setcookie(self::COOKIE, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
            unset($_COOKIE[self::COOKIE]);
        };
        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
            $clear(); // comanda e făcută: codul nu mai trebuie reținut
            return;
        }
        if (!WC()->cart || WC()->cart->is_empty()) {
            return; // încă nu e nimic în coș: păstrăm codul
        }
        $code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE]))));
        $id = '' !== $code ? (int) wc_get_coupon_id_by_code($code) : 0;
        if ($id && metadata_exists('post', $id, '_gi_voucher_lot') && !WC()->cart->has_discount($code)) {
            WC()->cart->apply_coupon($code);
        }
        $clear();
    }
}
