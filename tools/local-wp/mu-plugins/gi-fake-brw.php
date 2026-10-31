<?php
/**
 * DOAR TEST LOCAL: imită markup-ul formularului de căutare OVABRW (pluginul comercial nu e instalat aici).
 */
add_action('init', function () {
    if (shortcode_exists('ovabrw_search')) {
        return;
    }
    add_shortcode('ovabrw_search', function ($atts) {
        // Aceeași structură ca formularul real de pe gradina-ielelor.ro (copiată la 31.10.2026), ca stilurile să fie testate corect.
        $class = isset($atts['class']) ? $atts['class'] : '';
        $id = wp_rand(1000, 9999);
        return '<script type="text/javascript">var ovabrwTaxDependCat = "no";</script><div class="ovabrw_wd_search">'
            . '<form action="' . esc_url(home_url('/')) . '" class="ovabrw_search form_ovabrw row ' . esc_attr($class) . ' ovabrw-modern-product" enctype="multipart/form-data" autocomplete="off">'
            . '<input type="hidden" class="" name="ovabrw_search_url" value="' . esc_url(home_url('/')) . '" placeholder="" />'
            . '<div class="wrap_content three-column">'
            . '<div class="s_field"><div class="content"><label>Tip Cazare</label><select name="cat" id="cat" class="postform "><option value="" selected="selected">Selecteză Tip Cazare</option><option class="level-0" value="camping">Camping Cort</option><option class="level-0" value="rulote">Rulote</option><option class="level-0" value="glamping">Glamping</option><option class="level-0" value="bungalow">Bungalow</option></select></div></div>'
            . '<div class="s_field"><div class="content"><label>Check-in</label><input type="text" id="ovabrw_pickup_' . $id . '" class="ovabrw_start_date ovabrw-input-required ovabrw-datepicker-start" name="pickup_date" value="" placeholder="d-m-Y" /></div></div>'
            . '<div class="s_field"><div class="content"><label>Check-out</label><input type="text" id="ovabrw_pickoff_' . $id . '" class="ovabrw_end_date ovabrw-input-required ovabrw-datepicker-end" name="dropoff_date" value="" placeholder="d-m-Y" /></div></div>'
            . '<input type="hidden" class="" name="order" value="DESC" placeholder="" /><input type="hidden" class="" name="orderby" value="date" placeholder="" /></div>'
            . '<div class="s_submit"><button class="ovabrw_btn_submit" type="submit">Caută</button></div>'
            . '<input type="hidden" class="" name="ovabrw_search" value="search_item" placeholder="" /></form></div>';
    });
}, 20);
