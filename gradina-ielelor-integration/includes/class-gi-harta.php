<?php
/**
 * Harta și ghidul Grădinii: un plan al Grădinii (poză sau desen încărcat din Zona echipei → Harta), pe care proprietara marchează fiecare loc cu un
 * cod QR (bucătărie, terasă, Wi-Fi, loc de joacă, băi, vatră, copaci, plante...). Cine scanează un cod ajunge pe pagina locului și vede „Ești aici”: planul cu
 * locul lui marcat. Pagina /in-gradina/harta/ arată tot planul, cu toate locurile, și ghidul (informații, locuri, copaci).
 *
 * Pozițiile se țin în procente (stânga și sus față de plan), deci merg pe orice mărime de ecran. Nu se afișează nimic despre hartă dacă planul nu e încărcat.
 * Nu se inventează poziții: le alege numai proprietara, apăsând pe plan în formularul locului.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Harta {
    const OPT = 'gi_harta';
    const VAR = 'gi_harta_pagina';
    const SLUG = 'harta';

    public static function init() {
        add_action('init', array(__CLASS__, 'rewrite'), 6);
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_action('template_redirect', array(__CLASS__, 'route'), 3);
        add_filter('template_include', array(__CLASS__, 'template'), 96);
        add_filter('pre_get_document_title', array(__CLASS__, 'document_title'), 99);
        add_filter('body_class', array(__CLASS__, 'body_class'));
        add_action('wp_loaded', array(__CLASS__, 'maybe_flush'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_filter('wp_unique_post_slug', array(__CLASS__, 'reserved_slug'), 10, 6);
    }

    /* ---------- Adresa /in-gradina/harta/ ---------- */

    public static function rewrite() {
        add_rewrite_rule('^' . GI_Puncte::SLUG . '/' . self::SLUG . '/?$', 'index.php?' . self::VAR . '=1', 'top');
    }

    public static function query_vars($vars) {
        $vars[] = self::VAR;
        return $vars;
    }

    public static function maybe_flush() {
        if (get_option('gi_harta_rewrite') !== GI_VERSION) {
            flush_rewrite_rules(false);
            update_option('gi_harta_rewrite', GI_VERSION, true);
        }
    }

    public static function is_page() {
        global $wp;
        return '' !== (string) get_query_var(self::VAR) && isset($wp->matched_rule) && 0 === strpos((string) $wp->matched_rule, '^' . GI_Puncte::SLUG . '/' . self::SLUG);
    }

    /** Un loc nu poate lua adresa „harta” (ar intra în conflict cu pagina hărții). */
    public static function reserved_slug($slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug) {
        if (GI_Puncte::TYPE === $post_type && self::SLUG === $slug) {
            return self::SLUG . '-2';
        }
        return $slug;
    }

    public static function route() {
        if (!self::is_page()) {
            return;
        }
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }

    public static function template($template) {
        return self::is_page() ? GI_PATH . 'templates/harta-page.php' : $template;
    }

    public static function document_title($title) {
        return self::is_page() ? 'Harta și ghidul Grădinii | ' . get_bloginfo('name') : $title;
    }

    public static function body_class($classes) {
        if (self::is_page()) {
            $classes[] = 'gi-one-page';
            $classes[] = 'gi-page-harta';
        }
        return $classes;
    }

    public static function url($aici = '') {
        $url = home_url('/' . GI_Puncte::SLUG . '/' . self::SLUG . '/');
        return '' !== $aici ? add_query_arg('aici', rawurlencode($aici), $url) : $url;
    }

    public static function assets() {
        $point = is_singular(GI_Puncte::TYPE);
        if (!$point && !self::is_page()) {
            return;
        }
        if (self::is_page()) {
            // Pagina hărții nu e un loc și nici o pagină obișnuită a site-ului: își cere singură aceleași stiluri (antet, subsol, pagină de conținut).
            wp_enqueue_style('gi-one-page', gi_asset_url('css/site.css'), array(), GI_VERSION);
            wp_enqueue_style('gi-pages', gi_asset_url('css/pages.css'), array('gi-one-page'), GI_VERSION);
            wp_enqueue_style('gi-content-page', gi_asset_url('css/content-page.css'), array('gi-pages'), GI_VERSION);
            wp_enqueue_style('gi-harta', gi_asset_url('css/harta.css'), array('gi-content-page'), GI_VERSION);
            wp_enqueue_script('gi-one-page', gi_asset_url('js/site.js'), array(), GI_VERSION, true);
            wp_enqueue_script('gi-harta', gi_asset_url('js/harta.js'), array(), GI_VERSION, true);
            return;
        }
        wp_enqueue_style('gi-harta', gi_asset_url('css/harta.css'), array(), GI_VERSION);
    }

    /* ---------- Planul ---------- */

    public static function settings() {
        $o = get_option(self::OPT, array());
        $o = is_array($o) ? $o : array();
        return array('img' => isset($o['img']) ? (int) $o['img'] : 0, 'alt' => isset($o['alt']) ? (string) $o['alt'] : '');
    }

    /** Planul, dacă e încărcat și fișierul există: array(url, w, h, alt) sau null. */
    public static function map() {
        $s = self::settings();
        if (!$s['img'] || 'attachment' !== get_post_type($s['img'])) {
            return null;
        }
        $src = wp_get_attachment_image_src($s['img'], 'full');
        if (!$src) {
            return null;
        }
        return array('url' => $src[0], 'w' => (int) $src[1], 'h' => (int) $src[2], 'alt' => '' !== $s['alt'] ? $s['alt'] : 'Planul Grădinii Ielelor');
    }

    public static function coords($post_id) {
        $x = get_post_meta($post_id, '_gi_x', true);
        $y = get_post_meta($post_id, '_gi_y', true);
        if ('' === $x || '' === $y || !is_numeric($x) || !is_numeric($y)) {
            return null;
        }
        return array(max(0, min(100, (float) $x)), max(0, min(100, (float) $y)));
    }

    /** Locurile publicate care au poziție pe plan. */
    public static function placed() {
        $out = array();
        $posts = get_posts(array('post_type' => GI_Puncte::TYPE, 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC'));
        foreach ($posts as $p) {
            $c = self::coords($p->ID);
            if ($c) {
                $out[] = array('post' => $p, 'x' => $c[0], 'y' => $c[1]);
            }
        }
        return $out;
    }

    private static function fmt($n) {
        return rtrim(rtrim(number_format((float) $n, 1, '.', ''), '0'), '.');
    }

    /**
     * Planul cu locurile marcate. $current: locul curent („Ești aici”); $mini: varianta mică de pe pagina unui loc (fără apăsări).
     */
    public static function render($current = 0, $mini = false) {
        $map = self::map();
        if (!$map) {
            return '';
        }
        $placed = self::placed();
        $html = '<div class="gi-harta' . ($mini ? ' gi-harta-mini' : '') . '" data-gi-harta><div class="gi-harta-cadru">';
        $html .= '<img src="' . esc_url($map['url']) . '" alt="' . esc_attr($map['alt']) . '"' . ($map['w'] && $map['h'] ? ' width="' . (int) $map['w'] . '" height="' . (int) $map['h'] . '"' : '') . ' decoding="async">';
        $html .= '<ul class="gi-harta-pini">';
        foreach ($placed as $row) {
            $p = $row['post'];
            $is_current = (int) $p->ID === (int) $current;
            $style = 'left:' . self::fmt($row['x']) . '%;top:' . self::fmt($row['y']) . '%';
            $label = get_the_title($p);
            $type = GI_Puncte::type_label($p->ID);
            $class = 'gi-pin' . ($is_current ? ' gi-pin-curent' : '') . ' gi-pin-' . esc_attr(GI_Puncte::type_group((string) get_post_meta($p->ID, '_gi_tip', true)));
            if ($mini && !$is_current) {
                $html .= '<li class="' . $class . '" style="' . $style . '"><span class="gi-pin-punct" aria-hidden="true"></span></li>';
                continue;
            }
            if ($mini) {
                $html .= '<li class="' . $class . '" style="' . $style . '"><span class="gi-pin-punct" aria-hidden="true"></span><span class="gi-pin-eticheta">Ești aici</span></li>';
                continue;
            }
            $html .= '<li class="' . $class . '" style="' . $style . '"><button type="button" class="gi-pin-buton" data-url="' . esc_url(get_permalink($p)) . '" data-nume="' . esc_attr($label) . '" data-tip="' . esc_attr($type) . '" aria-label="' . esc_attr($label . ($type ? ' (' . $type . ')' : '')) . '"><span class="gi-pin-punct" aria-hidden="true"></span></button></li>';
        }
        $html .= '</ul></div>';
        if (!$mini) {
            $html .= '<div class="gi-harta-fisa" data-gi-harta-fisa hidden><strong data-gi-harta-nume></strong><span data-gi-harta-tip></span><a class="button button-primary" data-gi-harta-link href="#">Deschide pagina</a></div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * „Ești aici”: planul mic cu locul acestei pagini marcat. Gol dacă planul nu e încărcat sau locul nu are poziție.
     */
    public static function here($post_id) {
        if (!self::map() || !self::coords($post_id)) {
            return '';
        }
        return '<section class="gi-aici" aria-labelledby="gi-aici-titlu"><h2 id="gi-aici-titlu">Ești aici</h2>'
            . self::render($post_id, true)
            . '<p class="gi-aici-link"><a class="gi-text-link" href="' . esc_url(self::url(get_post_field('post_name', $post_id))) . '">Vezi toată harta Grădinii</a></p></section>';
    }

    /* ---------- Formularul din Zona echipei ---------- */

    /** Alegerea poziției unui loc: apeși pe plan, apare un punct. Gol dacă planul nu e încărcat. */
    public static function editor($post) {
        $map = self::map();
        if (!$map) {
            return '<p class="gi-zona-mic">Ca să marchezi locul pe hartă, încarcă întâi planul Grădinii: <a class="gi-text-link" href="' . esc_url(GI_Zona::url('harta')) . '">Zona echipei → Harta</a>.</p>';
        }
        $c = $post ? self::coords($post->ID) : null;
        $x = $c ? self::fmt($c[0]) : '';
        $y = $c ? self::fmt($c[1]) : '';
        return '<div class="gi-harta-editor" data-gi-harta-editor><p class="gi-zona-mic gi-harta-ajutor"><strong>Unde e locul pe hartă?</strong> Apasă pe plan, în locul unde pui codul QR.</p>'
            . '<div class="gi-harta-cadru"><img src="' . esc_url($map['url']) . '" alt="' . esc_attr($map['alt']) . '" width="' . (int) $map['w'] . '" height="' . (int) $map['h'] . '">'
            . '<span class="gi-pin gi-pin-curent gi-pin-ales"' . ('' === $x ? ' hidden' : ' style="left:' . esc_attr($x) . '%;top:' . esc_attr($y) . '%"') . '><span class="gi-pin-punct" aria-hidden="true"></span></span></div>'
            . '<input type="hidden" name="poz_x" value="' . esc_attr($x) . '"><input type="hidden" name="poz_y" value="' . esc_attr($y) . '">'
            . '<p class="gi-zona-mic" data-gi-harta-stare aria-live="polite">' . ('' === $x ? 'Încă nu e marcat pe hartă.' : 'Marcat pe hartă.') . '</p>'
            . '<p><button type="button" class="button button-outline" data-gi-harta-scoate>Scoate de pe hartă</button></p></div>';
    }

    /** Salvează poziția dintr-un formular (poz_x, poz_y în procente); câmpuri goale = scoase de pe hartă. */
    public static function save_position($post_id, $x, $y) {
        if (is_numeric($x) && is_numeric($y) && (float) $x >= 0 && (float) $x <= 100 && (float) $y >= 0 && (float) $y <= 100) {
            update_post_meta($post_id, '_gi_x', round((float) $x, 1));
            update_post_meta($post_id, '_gi_y', round((float) $y, 1));
        } else {
            delete_post_meta($post_id, '_gi_x');
            delete_post_meta($post_id, '_gi_y');
        }
    }

    /** Pagina „Harta” din Zona echipei: încarcă, înlocuiește sau scoate planul. */
    public static function view_zona() {
        GI_Zona::back_link('copaci', 'Copaci și locuri');
        if (!current_user_can('gi_copaci')) {
            echo '<div class="gi-zona-card"><p>Contul tău nu are acces la hartă.</p></div>';
            return;
        }
        $map = self::map();
        $placed = count(self::placed());
        $all = (int) wp_count_posts(GI_Puncte::TYPE)->publish;
        $limit = size_format(min(8 * MB_IN_BYTES, wp_max_upload_size()));
        ?>
        <div class="gi-zona-card">
            <h2>Harta Grădinii</h2>
            <p>Încarci <strong>planul Grădinii</strong> (o poză sau un desen). Apoi, la fiecare loc din „Copaci și locuri”, apeși pe plan unde se află. Cine scanează codul QR al locului vede <strong>„Ești aici”</strong>, cu locul marcat pe plan.</p>
            <?php if ($map) : ?>
                <p class="gi-zona-mic"><strong><?php echo (int) $placed; ?></strong> din <strong><?php echo (int) $all; ?></strong> locuri publicate sunt marcate pe hartă. <a class="gi-text-link" href="<?php echo esc_url(self::url()); ?>" target="_blank" rel="noopener">Vezi harta publică</a></p>
                <div class="gi-harta-cadru gi-harta-previzualizare"><img src="<?php echo esc_url($map['url']); ?>" alt="<?php echo esc_attr($map['alt']); ?>" width="<?php echo (int) $map['w']; ?>" height="<?php echo (int) $map['h']; ?>"></div>
            <?php else : ?>
                <p class="gi-zona-mic">Planul nu e încărcat încă. Până îl încarci, hărțile nu apar nicăieri (restul paginilor merg ca până acum).</p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(GI_Zona::url('harta')); ?>" class="gi-zona-form" data-gi-o-data>
                <?php echo GI_Zona::nonce_field('harta'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <input type="hidden" name="gi_actiune" value="salveaza">
                <label for="gi-h-plan"><?php echo $map ? 'Alege alt plan, ca să-l schimbi' : 'Planul Grădinii'; ?></label>
                <input type="file" id="gi-h-plan" name="plan" accept="image/jpeg,image/png,image/webp">
                <p class="gi-zona-mic">JPG, PNG sau WebP, maximum <?php echo esc_html($limit); ?>. Un plan făcut din avion sau un desen clar, cu partea de sus a Grădinii sus pe poză. Dacă schimbi planul cu unul de altă mărime sau decupare, locurile marcate trebuie marcate din nou.</p>
                <label for="gi-h-alt">Ce arată planul (o frază scurtă, pentru cei care nu văd imaginea)</label>
                <input type="text" id="gi-h-alt" name="alt" maxlength="160" value="<?php echo esc_attr(self::settings()['alt']); ?>" placeholder="ex. Planul Grădinii, cu lacurile, zonele de campare și clădirile">
                <p><button type="submit" class="button button-primary">Salvează</button></p>
            </form>
            <?php if ($map) : ?>
                <form method="post" action="<?php echo esc_url(GI_Zona::url('harta')); ?>" class="gi-zona-form" data-gi-o-data>
                    <?php echo GI_Zona::nonce_field('harta'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <input type="hidden" name="gi_actiune" value="scoate">
                    <p><button type="submit" class="button button-outline" data-gi-confirma="Scoți planul? Hărțile dispar din pagini, dar pozițiile locurilor rămân păstrate.">Scoate planul</button></p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /** Încărcarea/scoaterea planului (POST din Zona). */
    public static function handle_post($action) {
        if (!current_user_can('gi_copaci')) {
            GI_Zona::deny('harta');
        }
        GI_Zona::check('harta', 'harta');
        if ('scoate' === $action) {
            $s = self::settings();
            if ($s['img'] && 'attachment' === get_post_type($s['img'])) {
                wp_delete_attachment($s['img'], true);
            }
            update_option(self::OPT, array('img' => 0, 'alt' => ''), false);
            GI_Zona::flash(array('ok' => 'Planul a fost scos.'));
            return;
        }
        $alt = mb_substr(sanitize_text_field((string) GI_Zona::posted('alt')), 0, 160);
        $s = self::settings();
        $problem = '';
        if (!empty($_FILES['plan']) && is_array($_FILES['plan']) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['plan']['error']) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (UPLOAD_ERR_OK !== (int) $_FILES['plan']['error'] || (int) $_FILES['plan']['size'] > min(8 * MB_IN_BYTES, wp_max_upload_size())) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $problem = 'Planul nu s-a putut încărca (maximum ' . size_format(min(8 * MB_IN_BYTES, wp_max_upload_size())) . ').';
            } else {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                $att = media_handle_upload('plan', 0, array('post_title' => 'Planul Grădinii Ielelor'), array('test_form' => false, 'mimes' => array('jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp')));
                if (is_wp_error($att)) {
                    $problem = 'Planul nu a fost acceptat: alege o poză JPG, PNG sau WebP.';
                } else {
                    if ($s['img'] && 'attachment' === get_post_type($s['img'])) {
                        wp_delete_attachment($s['img'], true); // planul vechi nu mai trebuie
                    }
                    $s['img'] = (int) $att;
                }
            }
        }
        update_option(self::OPT, array('img' => $s['img'], 'alt' => $alt), false);
        if ($s['img'] && 'attachment' === get_post_type($s['img'])) {
            update_post_meta($s['img'], '_wp_attachment_image_alt', $alt);
        }
        GI_Zona::flash(array('ok' => '' === $problem ? 'Salvat.' : '', 'errors' => '' !== $problem ? array($problem) : array()));
    }
}
