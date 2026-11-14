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
        add_action('wp_loaded', array(__CLASS__, 'catch_code'), 20);
        add_action('woocommerce_before_cart', array(__CLASS__, 'apply_saved_code'), 5);
        add_action('woocommerce_before_checkout_form', array(__CLASS__, 'apply_saved_code'), 5);
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

    public static function generate() {
        if (!self::allowed()) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        check_admin_referer('gi_voucher_gen');

        $type = isset($_POST['tip']) && 'procent' === sanitize_key(wp_unslash($_POST['tip'])) ? 'procent' : 'suma';
        $value = isset($_POST['valoare']) ? (float) str_replace(',', '.', sanitize_text_field(wp_unslash($_POST['valoare']))) : 0.0;
        $count = isset($_POST['numar']) ? absint(wp_unslash($_POST['numar'])) : 1;
        $prefix = isset($_POST['prefix']) ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', sanitize_text_field(wp_unslash($_POST['prefix'])))) : '';
        $note = isset($_POST['nota']) ? sanitize_text_field(wp_unslash($_POST['nota'])) : '';

        $errors = array();
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

        $key = 'gi_voucher_rezultat_' . get_current_user_id();
        if ($errors) {
            set_transient($key, array('errors' => $errors, 'codes' => array()), 300);
            wp_safe_redirect(admin_url('admin.php?page=' . self::SCREEN));
            exit;
        }

        $expires = self::expiry();
        $lot = gmdate('YmdHis') . '-' . wp_generate_password(4, false, false);
        $created = array();
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($try = 0; $try < 12; $try++) {
                $candidate = self::make_code($prefix);
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
            $coupon->set_individual_use(false);
            $coupon->set_date_expires($expires->getTimestamp());
            $coupon->set_description($note ? $note : 'Voucher generat din zona Grădina');
            $coupon->update_meta_data('_gi_voucher_lot', $lot);
            $coupon->save();
            $created[] = $coupon->get_id();
        }
        set_transient($key, array('errors' => $errors, 'ids' => $created, 'type' => $type, 'value' => $value), 300);
        wp_safe_redirect(admin_url('admin.php?page=' . self::SCREEN));
        exit;
    }

    /* ---------- Ecranul „Vouchere” ---------- */

    public static function vouchers($limit = 200) {
        return get_posts(array(
            'post_type' => 'shop_coupon',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_gi_voucher_lot', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'suppress_filters' => true,
        ));
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
                        <td><input type="text" id="gi_v_nota" name="nota" class="large-text" placeholder="opțional, ex. cadou de Crăciun"> <span class="description">se vede numai în administrare</span></td>
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
                        <select name="size" id="gi_v_size"><option value="4">mic (4 cm)</option><option value="6" selected>mediu (6 cm)</option><option value="8">mare (8 cm)</option></select>
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
            if (!$post || 'shop_coupon' !== $post->post_type || !metadata_exists('post', $post->ID, '_gi_voucher_lot')) {
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
        if (is_admin() || !isset($_GET[self::COOKIE])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
        wp_safe_redirect(home_url('/') . '#rezervare');
        exit;
    }

    /**
     * Pe pagina coșului și la plată: dacă vizitatorul a scanat un cod și are ceva în coș, încercăm să-l aplicăm o singură dată.
     * Dacă WooCommerce îl refuză (expirat, folosit, nu se aplică acestei rezervări), apare mesajul lui obișnuit.
     */
    public static function apply_saved_code() {
        if (empty($_COOKIE[self::COOKIE]) || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }
        $code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE]))));
        if ('' === $code) {
            return;
        }
        // O singură încercare pentru fiecare cod, notată în sesiunea WooCommerce: un cod refuzat nu reapare la fiecare pagină.
        $session = WC()->session;
        if ($session && $session->get('gi_cod_incercat') === $code) {
            return;
        }
        if (!WC()->cart->has_discount($code)) {
            WC()->cart->apply_coupon($code);
        }
        if ($session) {
            $session->set('gi_cod_incercat', $code);
        }
    }
}
