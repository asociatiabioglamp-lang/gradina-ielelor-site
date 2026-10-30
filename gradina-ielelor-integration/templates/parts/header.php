<?php
/**
 * Header comun: logo, cele patru oferte, informații și CTA de rezervare.
 * Ofertele trimit la pagina dedicată când există, altfel la secțiunea de pe pagina principală.
 */
if (!defined('ABSPATH')) {
    exit;
}
$gi_current = gi_current_page_key();
$gi_links = array();
foreach (gi_offers() as $gi_key => $gi_offer) {
    $gi_links[] = array('label' => $gi_offer['label'], 'url' => gi_offer_url($gi_key), 'current' => $gi_key === $gi_current);
}
$gi_info_links = array(
    array('label' => 'Despre noi', 'url' => gi_clean_page_url('despre-noi'), 'current' => false),
    array('label' => 'Regulament & politici', 'url' => gi_clean_page_url('regulament-politici'), 'current' => false),
);
?>
    <header class="site-header shared-header header-overlay">
        <div class="container header-inner">
            <?php gi_brand(true); ?>
            <nav class="desktop-nav" aria-label="Navigație principală">
                <?php foreach (array_merge($gi_links, $gi_info_links) as $gi_link) : ?>
                    <a href="<?php echo esc_url($gi_link['url']); ?>"<?php echo $gi_link['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($gi_link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="button button-small header-cta" href="#rezervare">Rezervă <?php echo gi_icon('arrow-up-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="gi-mobile-navigation" aria-label="Deschide meniul" data-menu-toggle>
                <span data-menu-open><?php echo gi_icon('menu'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span data-menu-close hidden><?php echo gi_icon('x'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </button>
        </div>
        <nav id="gi-mobile-navigation" class="mobile-nav" aria-label="Navigație mobilă" hidden>
            <?php foreach (array_merge($gi_links, $gi_info_links) as $gi_link) : ?>
                <a href="<?php echo esc_url($gi_link['url']); ?>"<?php echo $gi_link['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($gi_link['label']); ?></a>
            <?php endforeach; ?>
            <a href="#rezervare">Rezervă cazare</a>
        </nav>
    </header>
