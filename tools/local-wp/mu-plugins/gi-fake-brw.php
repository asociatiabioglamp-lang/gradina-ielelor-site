<?php
/**
 * DOAR TEST LOCAL: imită markup-ul formularului de căutare OVABRW (pluginul comercial nu e instalat aici).
 */
add_action('init', function () {
    if (shortcode_exists('ovabrw_search')) {
        return;
    }
    add_shortcode('ovabrw_search', function ($atts) {
        $class = isset($atts['class']) ? $atts['class'] : '';
        return '<form class="ovabrw_search ' . esc_attr($class) . '" action="/shop/" method="get">'
            . '<div class="ovabrw_search_wrap"><div class="s_field"><label>Tip cazare</label><select name="cat"><option value="">Alege</option><option value="camping">Camping</option><option value="rulote">Rulote</option><option value="glamping">Glamping</option><option value="bungalow">Bungalow</option></select></div>'
            . '<div class="s_field"><label>Check-in</label><input type="text" name="ovabrw_pickup_date" placeholder="zz.ll.aaaa"></div>'
            . '<div class="s_field"><label>Check-out</label><input type="text" name="ovabrw_pickoff_date" placeholder="zz.ll.aaaa"></div>'
            . '<input type="hidden" name="ovabrw_search_url" value="/shop/"><button type="submit">Caută</button></div></form>';
    });
}, 20);
