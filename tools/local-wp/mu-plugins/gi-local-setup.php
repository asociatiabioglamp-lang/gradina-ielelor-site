<?php
/**
 * Doar pentru testul local: activează pluginurile și creează paginile o singură dată.
 */
add_action('init', function () {
    if (get_option('gi_local_setup_done') === '2') {
        return;
    }
    $active = (array) get_option('active_plugins', array());
    foreach (array('woocommerce/woocommerce.php', 'gradina-ielelor-integration/gradina-ielelor-integration.php') as $plugin) {
        if (!in_array($plugin, $active, true)) {
            $active[] = $plugin;
        }
    }
    update_option('active_plugins', $active);
    update_option('permalink_structure', '/%postname%/');

    $pages = array(
        'acasa' => array('Acasă', 'gi-one-page.php'),
        'glamping' => array('Glamping', 'gi-glamping.php'),
        'despre-noi' => array('Despre noi', ''),
        'regulament-politici' => array('Regulament & politici', ''),
    );
    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug);
        $id = $existing ? $existing->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page[0], 'post_name' => $slug, 'post_content' => 'Conținut de test.'));
        if ($page[1]) {
            update_post_meta($id, '_wp_page_template', $page[1]);
        }
        if ('acasa' === $slug) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $id);
        }
    }
    flush_rewrite_rules();
    update_option('gi_local_setup_done', '2');
}, 99);
