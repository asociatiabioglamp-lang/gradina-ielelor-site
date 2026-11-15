<?php
/**
 * Zona privată „Grădina” (administrare WordPress, cu utilizator și parolă) — copaci și locuri de văzut, cu cod QR.
 *
 * Cum funcționează (decizia proprietarei, 14.11.2026): în administrare creezi un „loc” (de ex. un copac): titlu, poză, textul pe care
 * îl vor citi vizitatorii. Site-ul îi dă o pagină publică cu adresă stabilă (/in-gradina/<nume>/) și generează codul QR al ei.
 * Printezi codul, îl pui în teren; vizitatorul îl scanează și ajunge pe pagina locului. Textul se poate schimba oricând, fără să
 * se schimbe codul.
 *
 * - Codul QR se desenează în browser (assets/js/vendor/qrcode.js, licență MIT); nu se trimite nimic către un serviciu extern.
 * - Adresa din cod are parametri utm (utm_source=qr&utm_medium=print): în statistici (Google Analytics) apare sursa „qr / print”.
 * - Dacă se schimbă adresa unei pagini deja publicate, WordPress redirecționează singur adresa veche: codurile printate merg în continuare.
 * - Accesul: administratori, editori și manageri de magazin (utilizator + parolă); autorii și colaboratorii nu au acces. Vouchere: numai cine administrează magazinul.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Puncte {
    const TYPE = 'gi_punct';
    const SLUG = 'in-gradina';
    const MENU = 'gi-gradina';
    /** Cine are acces: administratori, editori și manageri de magazin (nu autorii și colaboratorii). */
    const CAP = 'edit_others_posts';

    public static function init() {
        add_action('init', array(__CLASS__, 'register'), 5);
        add_action('wp_loaded', array(__CLASS__, 'maybe_flush'));
        add_action('admin_menu', array(__CLASS__, 'menu'), 9);
        add_action('add_meta_boxes_' . self::TYPE, array(__CLASS__, 'meta_boxes'));
        add_action('save_post_' . self::TYPE, array(__CLASS__, 'save'), 10, 2);
        add_filter('manage_' . self::TYPE . '_posts_columns', array(__CLASS__, 'columns'));
        add_action('manage_' . self::TYPE . '_posts_custom_column', array(__CLASS__, 'column'), 10, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_assets'));
        add_action('admin_post_gi_qr_print', array(__CLASS__, 'print_page'));
        add_filter('template_include', array(__CLASS__, 'template'), 98);
        // Editor clasic (titlu, text, poză): mai simplu pentru proprietară decât editorul de blocuri; datele rămân disponibile și prin REST.
        add_filter('use_block_editor_for_post_type', array(__CLASS__, 'classic_editor'), 10, 2);
        add_filter('old_slug_redirect_url', array(__CLASS__, 'keep_utm'));
    }

    public static function classic_editor($use, $post_type) {
        return self::TYPE === $post_type ? false : $use;
    }

    /* ---------- Tipuri de locuri ---------- */

    public static function types() {
        return array(
            'copac' => 'Copac',
            'planta' => 'Plantă',
            'loc' => 'Loc din Grădină',
            'altceva' => 'Altceva',
        );
    }

    public static function type_label($post_id) {
        $types = self::types();
        $key = (string) get_post_meta($post_id, '_gi_tip', true);
        return isset($types[$key]) ? $types[$key] : '';
    }

    /* ---------- Tipul de conținut ---------- */

    public static function register() {
        register_post_type(self::TYPE, array(
            'labels' => array(
                'name' => 'Copaci și locuri',
                'singular_name' => 'Loc din Grădină',
                'menu_name' => 'Copaci și locuri',
                'all_items' => 'Toate locurile',
                'add_new' => 'Adaugă un loc',
                'add_new_item' => 'Adaugă un loc din Grădină',
                'edit_item' => 'Modifică locul',
                'new_item' => 'Loc nou',
                'view_item' => 'Vezi pagina locului',
                'search_items' => 'Caută un loc',
                'not_found' => 'Nu ai adăugat încă niciun loc.',
                'not_found_in_trash' => 'Nimic în coșul de gunoi.',
                'featured_image' => 'Poza locului',
                'set_featured_image' => 'Alege poza locului',
                'remove_featured_image' => 'Scoate poza',
                'use_featured_image' => 'Folosește ca poza locului',
            ),
            'public' => true,
            'show_in_menu' => self::MENU,
            'show_in_rest' => true,
            'rest_base' => 'puncte',
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'), // custom-fields: fără el, REST nu arată tipul și numele științific
            'rewrite' => array('slug' => self::SLUG, 'with_front' => false),
            'has_archive' => false,
            'capability_type' => 'post',
            // Numai cine are acces la zona Grădina poate crea, modifica și publica locuri (altfel autorii ar putea publica pagini).
            'capabilities' => array('edit_posts' => self::CAP, 'create_posts' => self::CAP, 'publish_posts' => self::CAP),
            'map_meta_cap' => true, // fără el, „edit_post” (pe un loc anume) nu se mai potrivește cu drepturile de mai sus și nimeni nu poate edita
        ));
        register_post_meta(self::TYPE, '_gi_tip', array(
            'type' => 'string', 'single' => true, 'show_in_rest' => true,
            'sanitize_callback' => array(__CLASS__, 'sanitize_type'),
            'auth_callback' => array(__CLASS__, 'can_edit_meta'),
        ));
        register_post_meta(self::TYPE, '_gi_stiintific', array(
            'type' => 'string', 'single' => true, 'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => array(__CLASS__, 'can_edit_meta'),
        ));
    }

    public static function sanitize_type($value) {
        $value = sanitize_key((string) $value);
        return isset(self::types()[$value]) ? $value : '';
    }

    public static function can_edit_meta() {
        return current_user_can(self::CAP);
    }

    /**
     * Adresele noi (/in-gradina/...) au nevoie de regulile de adrese reîncărcate o dată, la prima rulare a unei versiuni noi.
     */
    public static function maybe_flush() {
        if (get_option('gi_puncte_rewrite') !== GI_VERSION) {
            flush_rewrite_rules(false);
            update_option('gi_puncte_rewrite', GI_VERSION, true);
        }
    }

    /* ---------- Adresa din cod ---------- */

    /**
     * Adresa din codul QR: pagina locului, cu parametri utm (în statistici apare sursa „qr / print”, vizitele venite din teren).
     */
    public static function qr_url($post) {
        $post = get_post($post);
        if (!$post) {
            return '';
        }
        return add_query_arg(array('utm_source' => 'qr', 'utm_medium' => 'print', 'utm_campaign' => 'gradina'), get_permalink($post));
    }

    /**
     * Dacă adresa unei pagini a fost schimbată, WordPress redirecționează adresa veche, dar pierde parametrii: păstrăm utm_*,
     * ca vizitele venite din codurile printate să rămână vizibile în statistici.
     */
    public static function keep_utm($link) {
        $keep = array();
        foreach (array('utm_source', 'utm_medium', 'utm_campaign') as $k) {
            // Doar valori simple (litere, cifre, _ . -), scurte: nimic din ele nu poate schimba adresa spre care se redirecționează.
            if (isset($_GET[$k]) && is_string($_GET[$k])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $value = preg_replace('/[^A-Za-z0-9_.-]/', '', wp_unslash($_GET[$k])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                if ('' !== $value) {
                    $keep[$k] = substr($value, 0, 60);
                }
            }
        }
        return $keep && is_string($link) ? add_query_arg($keep, $link) : $link;
    }

    /**
     * Pe site-ul de test sau local, codurile ar duce la adresa de test: avertizare vizibilă.
     */
    public static function is_production() {
        $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        return in_array($host, (array) apply_filters('gi_production_hosts', array('gradina-ielelor.ro', 'www.gradina-ielelor.ro')), true);
    }

    public static function address_notice() {
        $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (self::is_production()) {
            return;
        }
        echo '<div class="notice notice-warning inline"><p><strong>Atenție:</strong> acesta nu e site-ul real. Codurile de aici duc la <code>' . esc_html($host) . '</code>, iar adresa aceea poate dispărea. <strong>Generează și printează codurile numai de pe gradina-ielelor.ro.</strong></p></div>';
    }

    /* ---------- Meniul „Grădina” ---------- */

    public static function menu() {
        add_menu_page('Grădina', 'Grădina', self::CAP, self::MENU, array(__CLASS__, 'overview'), 'dashicons-palmtree', 26);
        add_submenu_page(self::MENU, 'Grădina — prezentare', 'Prezentare', self::CAP, self::MENU, array(__CLASS__, 'overview'));
        add_submenu_page(self::MENU, 'Coduri QR de printat', 'Coduri QR de printat', self::CAP, 'gi-qr', array(__CLASS__, 'qr_screen'));
    }

    public static function overview() {
        if (!current_user_can(self::CAP)) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        $new = admin_url('post-new.php?post_type=' . self::TYPE);
        $list = admin_url('edit.php?post_type=' . self::TYPE);
        $count = (int) wp_count_posts(self::TYPE)->publish;
        ?>
        <div class="wrap">
            <h1>Grădina — zona ta privată</h1>
            <?php self::address_notice(); ?>
            <p>Aici faci lucrurile care nu se văd pe site: pagini pentru copaci și locuri, coduri QR de printat, vouchere. Intri cu utilizatorul și parola contului tău.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;max-width:1000px;margin-top:16px;">
                <div class="card" style="margin:0;max-width:none;">
                    <h2 style="margin-top:0;">Copaci și locuri</h2>
                    <p>Creezi o pagină pentru fiecare copac (sau loc, sau plantă). Scrii ce vrei să afle vizitatorii; site-ul face codul QR.</p>
                    <p><a class="button button-primary" href="<?php echo esc_url($new); ?>">Adaugă un loc</a> <a class="button" href="<?php echo esc_url($list); ?>">Toate locurile (<?php echo esc_html((string) $count); ?>)</a></p>
                </div>
                <div class="card" style="margin:0;max-width:none;">
                    <h2 style="margin-top:0;">Coduri QR de printat</h2>
                    <p>Alegi locurile, alegi mărimea, apeși „Printează”. Pui codul în teren, pe copac.</p>
                    <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=gi-qr')); ?>">Coduri QR de printat</a></p>
                </div>
                <?php if (class_exists('GI_Vouchere') && GI_Vouchere::allowed()) : ?>
                <div class="card" style="margin:0;max-width:none;">
                    <h2 style="margin-top:0;">Vouchere (reduceri)</h2>
                    <p>Generezi coduri de reducere, valabile 18 luni, fiecare cu codul ei QR.</p>
                    <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=gi-vouchere')); ?>">Vouchere</a></p>
                </div>
                <?php endif; ?>
            </div>
            <p style="margin-top:24px;"><a href="<?php echo esc_url(admin_url('options-general.php?page=gi-integration')); ?>">Setările site-ului (Setări → Grădina Ielelor)</a></p>
        </div>
        <?php
    }

    /* ---------- Căsuțele din pagina locului ---------- */

    public static function meta_boxes() {
        add_meta_box('gi_punct_detalii', 'Despre acest loc', array(__CLASS__, 'box_details'), self::TYPE, 'normal', 'high');
        add_meta_box('gi_punct_qr', 'Cod QR', array(__CLASS__, 'box_qr'), self::TYPE, 'side', 'high');
        // Câmpurile personalizate generice nu au ce căuta aici (tipul și numele științific au căsuța lor).
        remove_meta_box('postcustom', self::TYPE, 'normal');
    }

    public static function box_details($post) {
        wp_nonce_field('gi_punct_salveaza', 'gi_punct_nonce');
        $tip = (string) get_post_meta($post->ID, '_gi_tip', true);
        $stiintific = (string) get_post_meta($post->ID, '_gi_stiintific', true);
        ?>
        <p class="description">La poză (în dreapta), completează „Text alternativ”: o frază care descrie ce se vede (ajută cititoarele de ecran și Google). Textul de mai sus (în editor) e ce citesc vizitatorii după ce scanează codul: povestea locului, ce îl face special, ce pot observa. „Rezumatul” de mai jos apare sub titlu.</p>
        <p>
            <label for="gi_tip"><strong>Ce este?</strong></label><br>
            <select name="gi_tip" id="gi_tip">
                <option value="">— alege —</option>
                <?php foreach (self::types() as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($tip, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="gi_stiintific"><strong>Numele științific</strong> (opțional)</label><br>
            <input type="text" name="gi_stiintific" id="gi_stiintific" class="regular-text" value="<?php echo esc_attr($stiintific); ?>" placeholder="ex. Quercus robur">
        </p>
        <?php
    }

    public static function box_qr($post) {
        if ('publish' !== $post->post_status) {
            echo '<p>Codul apare după ce <strong>publici</strong> pagina. Adresa din cod devine definitivă la publicare: nu schimba adresa paginii după ce ai printat codul.</p>';
            return;
        }
        self::address_notice();
        $url = self::qr_url($post);
        $print = wp_nonce_url(admin_url('admin-post.php?action=gi_qr_print&ids[]=' . (int) $post->ID . '&size=8'), 'gi_qr_print');
        ?>
        <div class="gi-qr-box" data-qr-url="<?php echo esc_attr($url); ?>" style="max-width:220px;margin:0 auto 8px;"></div>
        <p style="word-break:break-all;"><small><?php echo esc_html($url); ?></small></p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($print); ?>" target="_blank" rel="noopener">Printează acest cod</a>
            <button type="button" class="button" data-qr-download data-qr-source=".gi-qr-box" data-filename="<?php echo esc_attr('cod-qr-' . $post->post_name . '.svg'); ?>">Descarcă (SVG)</button>
        </p>
        <p class="description">Nu schimba adresa paginii după ce ai printat codul. Dacă o schimbi totuși, WordPress redirecționează adresa veche, dar verifică.</p>
        <?php
    }

    public static function save($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['gi_punct_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gi_punct_nonce'])), 'gi_punct_salveaza')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $tip = isset($_POST['gi_tip']) ? self::sanitize_type(wp_unslash($_POST['gi_tip'])) : '';
        $stiintific = isset($_POST['gi_stiintific']) ? sanitize_text_field(wp_unslash($_POST['gi_stiintific'])) : '';
        update_post_meta($post_id, '_gi_tip', $tip);
        update_post_meta($post_id, '_gi_stiintific', $stiintific);
    }

    /* ---------- Lista locurilor ---------- */

    public static function columns($columns) {
        $out = array();
        foreach ($columns as $key => $label) {
            $out[$key] = $label;
            if ('title' === $key) {
                $out['gi_tip'] = 'Ce este';
                $out['gi_qr'] = 'Cod QR';
            }
        }
        return $out;
    }

    public static function column($column, $post_id) {
        if ('gi_tip' === $column) {
            echo esc_html(self::type_label($post_id));
        } elseif ('gi_qr' === $column) {
            if ('publish' === get_post_status($post_id)) {
                echo '<div class="gi-qr-mini" data-qr-url="' . esc_attr(self::qr_url($post_id)) . '" style="width:64px;"></div>';
            } else {
                echo '<span class="description">după publicare</span>';
            }
        }
    }

    /* ---------- Scripturi și stiluri de administrare ---------- */

    public static function admin_assets($hook) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $on_ours = $screen && (self::TYPE === $screen->post_type || false !== strpos((string) $screen->id, 'gi-qr') || false !== strpos((string) $screen->id, 'gi-vouchere'));
        if (!$on_ours) {
            return;
        }
        wp_enqueue_script('gi-qrcode', GI_URL . 'assets/js/vendor/qrcode.js', array(), GI_VERSION, true);
        wp_enqueue_script('gi-admin-qr', GI_URL . 'assets/js/admin-qr.js', array('gi-qrcode'), GI_VERSION, true);
    }

    /* ---------- Ecranul „Coduri QR de printat” ---------- */

    public static function qr_screen() {
        if (!current_user_can(self::CAP)) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        $posts = get_posts(array('post_type' => self::TYPE, 'post_status' => 'publish', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC'));
        ?>
        <div class="wrap">
            <h1>Coduri QR de printat</h1>
            <?php self::address_notice(); ?>
            <?php if (!$posts) : ?>
                <p>Nu ai încă niciun loc publicat. <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . self::TYPE)); ?>">Adaugă primul loc</a>, apoi revino aici.</p>
            <?php else : ?>
                <form method="get" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
                    <input type="hidden" name="action" value="gi_qr_print">
                    <?php wp_nonce_field('gi_qr_print', '_wpnonce', false); ?>
                    <p>
                        <label for="gi_qr_size"><strong>Mărimea codului:</strong></label>
                        <select name="size" id="gi_qr_size">
                            <option value="4">mic (4 cm)</option>
                            <option value="6">mediu (6 cm)</option>
                            <option value="8" selected>mare (8 cm)</option>
                            <option value="10">foarte mare (10 cm)</option>
                        </select>
                        <span class="description">Pentru un cod pus pe un copac, la câțiva pași de cărare, 8 cm e potrivit.</span>
                    </p>
                    <table class="widefat striped" style="max-width:800px;">
                        <thead><tr><td class="check-column"><input type="checkbox" data-qr-select-all aria-label="Alege toate"></td><th>Loc</th><th>Ce este</th><th>Cod</th></tr></thead>
                        <tbody>
                        <?php foreach ($posts as $p) : ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo esc_attr((string) $p->ID); ?>" aria-label="<?php echo esc_attr($p->post_title); ?>"></th>
                                <td><a href="<?php echo esc_url(get_edit_post_link($p->ID)); ?>"><?php echo esc_html(get_the_title($p)); ?></a></td>
                                <td><?php echo esc_html(self::type_label($p->ID)); ?></td>
                                <td><div class="gi-qr-mini" data-qr-url="<?php echo esc_attr(self::qr_url($p)); ?>" style="width:56px;"></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><button type="submit" class="button button-primary">Printează codurile alese</button> <span class="description">Se deschide o pagină curată, gata de printat.</span></p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ---------- Pagina de printare (fără meniurile administrării) ---------- */

    /**
     * Pagină curată, gata de printat, cu codurile alese. Folosită și de vouchere (tip=vouchere).
     * Acces: cont cu drept de editare (locuri) / drept de magazin (vouchere), plus cod de siguranță (nonce) în adresă.
     */
    public static function print_page() {
        $kind = isset($_GET['tip']) ? sanitize_key(wp_unslash($_GET['tip'])) : 'puncte'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ('vouchere' === $kind) {
            if (!class_exists('GI_Vouchere') || !GI_Vouchere::allowed()) {
                wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
            }
        } elseif (!current_user_can(self::CAP)) {
            wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
        }
        check_admin_referer('gi_qr_print');

        $ids = isset($_GET['ids']) ? array_map('absint', (array) wp_unslash($_GET['ids'])) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $ids = array_slice(array_values(array_filter(array_unique($ids))), 0, 200);
        $size = isset($_GET['size']) ? absint(wp_unslash($_GET['size'])) : 6; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!in_array($size, array(4, 6, 8, 10), true)) {
            $size = 6;
        }

        $tiles = array();
        if ('vouchere' === $kind) {
            $tiles = GI_Vouchere::print_tiles($ids);
        } else {
            foreach ($ids as $id) {
                $p = get_post($id);
                if ($p && self::TYPE === $p->post_type && 'publish' === $p->post_status) {
                    $tiles[] = array('url' => self::qr_url($p), 'title' => get_the_title($p), 'note' => 'Scanează și afli mai multe', 'foot' => preg_replace('#^https?://#', '', get_permalink($p)));
                }
            }
        }

        self::render_print($tiles, $size);
    }

    /**
     * Pagina de printat, gata de trimis browserului (HTML curat, fără meniurile administrării sau ale site-ului). Încheie cererea.
     *
     * @param array<int,array<string,string>> $tiles
     * @param int $size mărimea codului, în cm (4, 6, 8 sau 10)
     */
    public static function render_print($tiles, $size) {
        nocache_headers();
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        header('X-Robots-Tag: noindex, nofollow');
        ?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="<?php echo esc_attr(get_option('blog_charset')); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Coduri QR de printat</title>
<style>
:root { --w: <?php echo (int) $size; ?>cm; }
* { box-sizing: border-box; }
body { margin: 0; padding: 16px; font: 16px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1c2b33; background: #fff; }
.gi-bar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin: 0 0 18px; padding: 12px 14px; background: #eef4f6; border-radius: 10px; }
.gi-bar button, .gi-bar a { font: inherit; padding: 8px 16px; border-radius: 8px; border: 1px solid #1d6380; background: #1d6380; color: #fff; text-decoration: none; cursor: pointer; }
.gi-bar a { background: #fff; color: #1d6380; }
.gi-sheet { display: grid; grid-template-columns: repeat(auto-fill, minmax(calc(var(--w) + 1cm), 1fr)); gap: 8mm; }
.gi-tile { margin: 0; padding: 5mm; border: 1px dashed #8a9aa3; border-radius: 4mm; text-align: center; break-inside: avoid; page-break-inside: avoid; }
.gi-tile .brand { display: flex; align-items: center; justify-content: center; gap: 3mm; margin: 0 0 3mm; text-align: left; }
.gi-tile .brand img { display: block; width: 22mm; height: auto; }
.gi-tile .brand b { display: block; font-size: 11pt; line-height: 1.15; }
.gi-tile .brand small { display: block; margin: 0; font-size: 7.5pt; color: #44545c; word-break: normal; }
.gi-tile .qr { width: var(--w); margin: 0 auto 3mm; }
.gi-tile .qr svg { display: block; width: 100%; height: auto; }
.gi-tile strong { display: block; font-size: 15pt; line-height: 1.2; }
.gi-tile span { display: block; margin-top: 1mm; font-size: 10pt; color: #44545c; }
.gi-tile small { display: block; margin-top: 2mm; font-size: 7.5pt; color: #6a7b84; word-break: break-all; }
@page { margin: 12mm; }
@media print { body { padding: 0; } .gi-bar { display: none; } .gi-sheet { gap: 6mm; } }
</style>
</head>
<body>
<div class="gi-bar">
    <button type="button" onclick="window.print()">Printează</button>
    <a href="javascript:window.close()">Închide</a>
    <span><?php echo esc_html((string) count($tiles)); ?> cod(uri), mărimea <?php echo (int) $size; ?> cm. Taie pe linia punctată.</span>
    <?php if (!self::is_production()) : ?>
        <strong style="color:#a33;">Atenție: acesta nu e site-ul real; codurile duc la <?php echo esc_html((string) wp_parse_url(home_url('/'), PHP_URL_HOST)); ?>. Nu le printa pentru teren.</strong>
    <?php endif; ?>
</div>
<?php if (!$tiles) : ?>
    <p>Nu ai ales niciun cod de printat. Închide această filă și alege cel puțin unul.</p>
<?php else : ?>
    <main class="gi-sheet">
        <?php foreach ($tiles as $t) : ?>
            <figure class="gi-tile">
                <?php if (!empty($t['brand'])) : ?>
                    <div class="brand"><img src="<?php echo esc_url(GI_URL . 'assets/logo-gradina-ielelor.png'); ?>" alt="Grădina Ielelor" width="150" height="70"><div><b>Grădina Ielelor</b><small>Sâmbăta de Sus, Brașov</small></div></div>
                <?php endif; ?>
                <div class="qr" data-qr-url="<?php echo esc_attr($t['url']); ?>" role="img" aria-label="<?php echo esc_attr('Cod QR: ' . $t['title']); ?>"></div>
                <figcaption>
                    <strong><?php echo esc_html($t['title']); ?></strong>
                    <?php if (!empty($t['note'])) : ?><span><?php echo esc_html($t['note']); ?></span><?php endif; ?>
                    <?php if (!empty($t['foot'])) : ?><small><?php echo esc_html($t['foot']); ?></small><?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </main>
<?php endif; ?>
<script src="<?php echo esc_url(GI_URL . 'assets/js/vendor/qrcode.js?ver=' . GI_VERSION); ?>"></script>
<script src="<?php echo esc_url(GI_URL . 'assets/js/admin-qr.js?ver=' . GI_VERSION); ?>"></script>
</body>
</html>
        <?php
        exit;
    }

    /* ---------- Pagina publică a locului ---------- */

    public static function template($template) {
        if (is_singular(self::TYPE)) {
            return GI_PATH . 'templates/point-page.php';
        }
        return $template;
    }

    /**
     * Alte locuri din Grădină, pentru „Mai multe de văzut” (fără locul curent).
     */
    public static function others($current_id, $limit = 6) {
        return get_posts(array(
            'post_type' => self::TYPE,
            'post_status' => 'publish',
            'numberposts' => $limit,
            'post__not_in' => array((int) $current_id),
            'orderby' => 'rand',
        ));
    }
}
