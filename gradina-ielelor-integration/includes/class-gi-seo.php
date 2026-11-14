<?php
if (!defined('ABSPATH')) {
    exit;
}

final class GI_SEO {
    public static function init() {
        add_filter('pre_get_document_title', array(__CLASS__, 'document_title'), 20);
        add_action('wp_head', array(__CLASS__, 'fallback_metadata'), 2);

        // Integrare cu pluginul SEO existent pe staging; nu produce taguri duplicate.
        add_filter('wpseo_title', array(__CLASS__, 'seo_title'));
        add_filter('wpseo_metadesc', array(__CLASS__, 'seo_description'));
        add_filter('wpseo_canonical', array(__CLASS__, 'canonical'));
        add_filter('wpseo_opengraph_title', array(__CLASS__, 'og_title'));
        add_filter('wpseo_opengraph_desc', array(__CLASS__, 'og_description'));
        add_filter('wpseo_opengraph_url', array(__CLASS__, 'canonical'));
        add_filter('wpseo_opengraph_image', array(__CLASS__, 'og_image'));

        // Reparații pentru site-ul migrat: adresa „dev24” rămasă în Yoast, harta site-ului, numele site-ului din titluri, schema.
        add_filter('wpseo_canonical', array(__CLASS__, 'fix_stale_host'), 99);
        add_filter('wpseo_opengraph_url', array(__CLASS__, 'fix_stale_host'), 99);
        add_filter('wpseo_schema_graph', array(__CLASS__, 'fix_stale_host'), 99);
        add_filter('wpseo_exclude_from_sitemap_by_post_ids', array(__CLASS__, 'sitemap_exclude_ids'));
        add_filter('wpseo_sitemap_entry', array(__CLASS__, 'sitemap_entry'), 10, 3);
        add_filter('wpseo_schema_organization', array(__CLASS__, 'organization_schema'));
        add_filter('wpseo_title', array(__CLASS__, 'short_site_name'), 99);
        add_filter('wpseo_opengraph_title', array(__CLASS__, 'short_site_name'), 99);
        add_filter('pre_get_document_title', array(__CLASS__, 'short_site_name'), 99);

        add_action('add_meta_boxes_page', array(__CLASS__, 'meta_box'));
        add_action('save_post_page', array(__CLASS__, 'save_meta'));
    }

    public static function document_title($title) {
        if (!gi_is_site_page()) {
            return $title;
        }
        // Yoast / Rank Math trimit aici titlul lor; titlul setat de mână în ele are prioritate.
        return self::title_value($title);
    }

    public static function seo_title($title) {
        return gi_is_site_page() ? self::title_value($title) : $title;
    }

    public static function seo_description($description) {
        return gi_is_site_page() ? self::description_value($description) : $description;
    }

    public static function og_title($title) {
        if (!gi_is_site_page()) {
            return $title;
        }
        $custom = self::custom('title');
        if ($custom) {
            return $custom;
        }
        $social = get_post_meta(get_queried_object_id(), '_yoast_wpseo_opengraph-title', true);
        return $social ? $title : self::title_value($title);
    }

    public static function og_description($description) {
        if (!gi_is_site_page()) {
            return $description;
        }
        $custom = self::custom('description');
        if ($custom) {
            return $custom;
        }
        $social = get_post_meta(get_queried_object_id(), '_yoast_wpseo_opengraph-description', true);
        return $social ? $description : self::description_value($description);
    }

    public static function canonical($url) {
        if (!gi_is_site_page()) {
            return $url;
        }
        $yoast = get_post_meta(get_queried_object_id(), '_yoast_wpseo_canonical', true);
        return $yoast ? $url : self::canonical_url();
    }

    public static function og_image($image) {
        if (!gi_is_site_page()) {
            return $image;
        }
        if (has_post_thumbnail(get_queried_object_id())) {
            $featured = get_the_post_thumbnail_url(get_queried_object_id(), 'full');
            return $featured ? $featured : $image;
        }
        $social = get_post_meta(get_queried_object_id(), '_yoast_wpseo_opengraph-image', true);
        $defaults = gi_page_seo_defaults(gi_current_page_key());
        return $social ? $image : gi_image_url($defaults['image']);
    }

    public static function fallback_metadata() {
        if (!gi_is_site_page() || defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) {
            return;
        }

        $title = self::title_value('');
        $description = self::description_value('');
        $canonical = self::canonical_url();
        $image = self::og_image('');
        // Un singur canonical: cel de mai jos, nu și cel din nucleul WordPress.
        remove_action('wp_head', 'rel_canonical');
        ?>
        <meta name="description" content="<?php echo esc_attr($description); ?>">
        <link rel="canonical" href="<?php echo esc_url($canonical); ?>">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="<?php echo esc_attr(get_locale()); ?>">
        <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
        <meta property="og:title" content="<?php echo esc_attr($title); ?>">
        <meta property="og:description" content="<?php echo esc_attr($description); ?>">
        <meta property="og:url" content="<?php echo esc_url($canonical); ?>">
        <meta property="og:image" content="<?php echo esc_url($image); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <?php
    }

    public static function meta_box() {
        add_meta_box('gi-seo-panel', 'Grădina Ielelor — SEO server-side', array(__CLASS__, 'render_meta_box'), 'page', 'normal', 'default');
    }

    public static function render_meta_box($post) {
        wp_nonce_field('gi_seo_save', 'gi_seo_nonce');
        $title = get_post_meta($post->ID, '_gi_seo_title', true);
        $description = get_post_meta($post->ID, '_gi_seo_description', true);
        ?>
        <p><label for="gi-seo-title"><strong>Titlu SEO</strong></label></p>
        <input class="widefat" id="gi-seo-title" name="gi_seo_title" type="text" maxlength="180" value="<?php echo esc_attr($title); ?>">
        <p><label for="gi-seo-description"><strong>Meta descriere</strong></label></p>
        <textarea class="widefat" id="gi-seo-description" name="gi_seo_description" rows="3" maxlength="320"><?php echo esc_textarea($description); ?></textarea>
        <p class="description">Dacă câmpurile sunt goale, se folosesc valorile implicite ale template-ului, apoi excerptul și configurația pluginului SEO. Imaginea reprezentativă devine OG image; altfel se folosește imaginea hero.</p>
        <?php
    }

    public static function save_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['gi_seo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gi_seo_nonce'])), 'gi_seo_save')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $title = isset($_POST['gi_seo_title']) ? sanitize_text_field(wp_unslash($_POST['gi_seo_title'])) : '';
        $description = isset($_POST['gi_seo_description']) ? sanitize_text_field(wp_unslash($_POST['gi_seo_description'])) : '';
        self::store($post_id, '_gi_seo_title', $title);
        self::store($post_id, '_gi_seo_description', $description);
    }

    private static function store($post_id, $key, $value) {
        if ('' === $value) {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }

    private static function custom($field) {
        return trim((string) get_post_meta(get_queried_object_id(), '_gi_seo_' . $field, true));
    }

    private static function title_value($generated) {
        $custom = self::custom('title');
        if ($custom) {
            return $custom;
        }
        $id = get_queried_object_id();
        $seo_meta = get_post_meta($id, '_yoast_wpseo_title', true);
        $rank_math = get_post_meta($id, 'rank_math_title', true);
        if (($seo_meta || $rank_math) && $generated && !self::is_legacy_text($generated)) {
            return $generated;
        }
        $defaults = gi_page_seo_defaults(gi_current_page_key());
        if (!is_front_page() && !gi_is_dedicated_page()) {
            $page_title = get_the_title($id);
            if ($page_title) {
                return $page_title . ' | ' . get_bloginfo('name');
            }
        }
        return $defaults['title'];
    }

    private static function description_value($generated) {
        $custom = self::custom('description');
        if ($custom) {
            return $custom;
        }
        $id = get_queried_object_id();
        $seo_meta = get_post_meta($id, '_yoast_wpseo_metadesc', true);
        $rank_math = get_post_meta($id, 'rank_math_description', true);
        if (($seo_meta || $rank_math) && $generated && !self::is_legacy_text($generated)) {
            return $generated;
        }
        if (has_excerpt($id)) {
            return trim(wp_strip_all_tags(get_the_excerpt($id)));
        }
        $defaults = gi_page_seo_defaults(gi_current_page_key());
        return $defaults['description'];
    }

    private static function canonical_url() {
        return get_permalink(get_queried_object_id());
    }

    /**
     * Titlurile și descrierile vechi din Yoast de la Glamping și Camping (greșeala „Sambata se Sus”, „Facilitati All Inclusive”)
     * nu mai au prioritate față de cele din plugin. Un text nou scris în Yoast (fără aceste greșeli) are în continuare prioritate.
     */
    private static function is_legacy_text($text) {
        return is_string($text) && (bool) preg_match('/Sambata\s+se\s+Sus|Facilitati\s+All\s+Inclusive/i', $text);
    }

    /**
     * Adrese de lucru rămase în Yoast după mutarea site-ului (ex. dev24.gradina-ielelor.ro). Filtru: gi_seo_stale_hosts.
     *
     * @return string[]
     */
    private static function stale_hosts() {
        $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $stale = '' !== $host ? array('dev24.' . $host) : array();
        $stale = apply_filters('gi_seo_stale_hosts', $stale);
        return is_array($stale) ? array_values(array_filter(array_map('strtolower', array_filter($stale, 'is_string')))) : array();
    }

    /**
     * Înlocuiește adresa de lucru veche cu adresa reală, în adrese (canonical, og:url) și în datele structurate (arbori de tablouri).
     */
    public static function fix_stale_host($value) {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::fix_stale_host($item);
            }
            return $value;
        }
        if (!is_string($value) || false === stripos($value, 'dev')) {
            return $value;
        }
        $real = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $port = wp_parse_url(home_url('/'), PHP_URL_PORT);
        $scheme = (string) wp_parse_url(home_url('/'), PHP_URL_SCHEME);
        if ('' === $real) {
            return $value;
        }
        $origin = ('' !== $scheme ? $scheme : 'https') . '://' . $real . ($port ? ':' . $port : '');
        foreach (self::stale_hosts() as $stale) {
            $value = preg_replace('#(?:https?:)?//' . preg_quote($stale, '#') . '(?::\d+)?#i', $origin, $value);
        }
        return $value;
    }

    /**
     * Paginile vechi care doar redirecționează (din redirects.php, cât timp redirecționarea e activă) și paginile
     * Coș, Finalizare și Contul meu nu aparțin hărții site-ului pentru Google. Filtru: gi_seo_sitemap_exclude_ids.
     *
     * @return int[]
     */
    private static function excluded_post_ids() {
        static $ids = null;
        if (null !== $ids) {
            return $ids;
        }
        $ids = array();
        if (function_exists('gi_redirect_normalized_map') && function_exists('gi_redirect_target_url')) {
            foreach (gi_redirect_normalized_map() as $old => $new) {
                $slug = trim((string) $old, '/');
                if ('' === $slug || '' === gi_redirect_target_url($new)) {
                    continue;
                }
                $page = get_page_by_path($slug);
                if ($page) {
                    $ids[] = (int) $page->ID;
                }
            }
        }
        if (function_exists('wc_get_page_id')) {
            foreach (array('cart', 'checkout', 'myaccount') as $woo_page) {
                $page_id = (int) wc_get_page_id($woo_page);
                if ($page_id > 0) {
                    $ids[] = $page_id;
                }
            }
        }
        $extra = apply_filters('gi_seo_sitemap_exclude_ids', array());
        if (is_array($extra)) {
            $ids = array_merge($ids, array_map('intval', $extra));
        }
        $ids = array_values(array_unique(array_filter($ids)));
        return $ids;
    }

    public static function sitemap_exclude_ids($ids) {
        $ids = is_array($ids) ? $ids : array();
        return array_values(array_unique(array_merge($ids, self::excluded_post_ids())));
    }

    /**
     * A doua plasă pentru aceeași regulă: unele versiuni de Yoast sar peste intrare dacă filtrul întoarce fals.
     */
    public static function sitemap_entry($url, $type = '', $object = null) {
        if ('post' === $type && is_object($object) && isset($object->ID) && in_array((int) $object->ID, self::excluded_post_ids(), true)) {
            return false;
        }
        return $url;
    }

    /**
     * „Organizație” din datele structurate Yoast devine și „Camping”, cu telefon, email și localitate, numai din ce e deja public pe site.
     * Valorile completate deja în Yoast nu se schimbă.
     */
    public static function organization_schema($data) {
        if (!is_array($data)) {
            return $data;
        }
        $types = isset($data['@type']) ? (array) $data['@type'] : array('Organization');
        if (!in_array('Campground', $types, true)) {
            $types[] = 'Campground';
        }
        $data['@type'] = array_values($types);
        if (empty($data['telephone'])) {
            $data['telephone'] = '+40721333164';
        }
        if (empty($data['email'])) {
            $data['email'] = 'rezervari@gradina-ielelor.ro';
        }
        if (empty($data['address'])) {
            $data['address'] = array(
                '@type' => 'PostalAddress',
                'addressLocality' => 'Sâmbăta de Sus',
                'addressRegion' => 'Brașov',
                'addressCountry' => 'RO',
            );
        }
        return $data;
    }

    /**
     * Numele site-ului din titluri („Gradina Ielelor - Camping and Glamping in Romania”) e prea lung și în engleză: devine „Grădina Ielelor”.
     * Titlul paginii principale din Yoast, care nu conține numele site-ului, rămâne cum e.
     */
    public static function short_site_name($title) {
        if (!is_string($title) || '' === $title) {
            return $title;
        }
        $name = (string) get_bloginfo('name');
        if ('' !== $name && 'Grădina Ielelor' !== $name && false !== strpos($title, $name)) {
            $title = str_replace($name, 'Grădina Ielelor', $title);
        }
        return $title;
    }
}
