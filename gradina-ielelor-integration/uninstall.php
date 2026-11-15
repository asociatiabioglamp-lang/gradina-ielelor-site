<?php
// Șterge exclusiv opțiunile acestui plugin; nu atinge paginile, produsele sau comenzile.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('gi_integration_options');
delete_option('gi_puncte_rewrite');
delete_option('gi_galerie_aplicata');
delete_transient('gi_galerie_lipsa');

// Zona echipei: opțiunile, rolul „Vânzări (Grădina)” și drepturile adăugate la roluri. Conturile colegilor rămân (fără rol).
delete_option('gi_zona_rewrite');
delete_option('gi_zona_roluri');
delete_option('gi_zona_limite');
remove_role('gi_vanzari');
foreach (array('administrator', 'shop_manager', 'editor') as $gi_role_name) {
    $gi_role = get_role($gi_role_name);
    if ($gi_role) {
        foreach (array('gi_zona', 'gi_vouchere', 'gi_vouchere_toate', 'gi_copaci', 'gi_echipa') as $gi_cap) {
            $gi_role->remove_cap($gi_cap);
        }
    }
}
