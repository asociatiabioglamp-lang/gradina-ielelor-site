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
        if (($seo_meta || $rank_math) && $generated) {
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
        if (($seo_meta || $rank_math) && $generated) {
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
}
