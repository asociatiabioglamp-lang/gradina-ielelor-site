<?php
/**
 * Footer comun, CTA-ul mobil de rezervare, lightbox-ul galeriilor și închiderea documentului.
 * $args['mobile_cta'] — textul butonului fix de pe mobil; $args['mobile_cta_url'] — destinația lui (implicit: modulul de rezervări).
 */
if (!defined('ABSPATH')) {
    exit;
}
$gi_mobile_cta = isset($args['mobile_cta']) ? $args['mobile_cta'] : 'Rezervă cazare';
$gi_mobile_cta_url = isset($args['mobile_cta_url']) ? $args['mobile_cta_url'] : gi_current_cta_url();
?>
    <footer class="site-footer shared-footer">
        <div class="container footer-grid">
            <div>
                <?php gi_brand(true, 'footer-official-brand'); ?>
                <p>Camping, glamping, bungalow și evenimente în natură, la Sâmbăta de Sus, Brașov, la poalele Munților Făgăraș.</p>
            </div>
            <div><h3>Descoperă</h3><?php foreach (gi_offers() as $gi_key => $gi_offer) : ?><a href="<?php echo esc_url(gi_offer_url($gi_key)); ?>"<?php echo gi_current_page_key() === $gi_key ? ' aria-current="page"' : ''; ?>><?php echo esc_html($gi_offer['label']); ?></a><?php endforeach; ?></div>
            <div><h3>Informații</h3><a href="<?php echo esc_url(gi_clean_page_url('despre-noi')); ?>">Despre noi</a><a href="<?php echo esc_url(gi_clean_page_url('regulament-politici')); ?>">Regulament &amp; politici</a><a href="<?php echo esc_url(gi_clean_page_url('contact')); ?>">Contact</a><a href="<?php echo esc_url(gi_blog_url()); ?>">Blog</a><a href="<?php echo esc_url(gi_offer_has_page('evenimente') ? gi_offer_url('evenimente') . '#calendar' : gi_home_section_url('calendar-2027')); ?>">Calendar evenimente 2027</a><a href="<?php echo esc_url(gi_home_section_url('recenzii')); ?>">Recenzii ale oaspeților</a></div>
            <div><h3>Contact</h3><a href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> +40 721 333 164</a><a href="mailto:office@gradina-ielelor.ro"><?php echo gi_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> office@gradina-ielelor.ro</a><a href="https://www.google.com/maps/search/?api=1&amp;query=Gradina+Ielelor+Sambata+de+Sus" target="_blank" rel="noopener noreferrer"><?php echo gi_icon('map-pin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus, Brașov</a></div>
        </div>
        <nav class="container footer-legal" aria-label="Informații legale">
            <?php foreach (gi_legal_links() as $gi_label => $gi_url) : ?>
                <?php $gi_external = 0 !== strpos($gi_url, home_url('/')); ?>
                <a href="<?php echo esc_url($gi_url); ?>"<?php echo $gi_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html($gi_label); ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="container footer-bottom"><span>Rezervările și plățile se fac online, direct pe site.</span><span>© <?php echo esc_html(wp_date('Y')); ?> Grădina Ielelor</span></div>
    </footer>

    <nav class="gi-mobile-booking" aria-label="Rezervare rapidă">
        <a class="mobile-booking-cta" href="<?php echo esc_url($gi_mobile_cta_url); ?>"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html($gi_mobile_cta); ?></a>
    </nav>
</div>

<div id="gi-lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Galerie foto" hidden>
    <button class="lightbox-close" type="button" aria-label="Închide galeria" data-lightbox-close><?php echo gi_icon('x'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    <button class="lightbox-arrow previous" type="button" aria-label="Imaginea anterioară" data-lightbox-previous><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    <figure><img src="" alt="" data-lightbox-image><figcaption><span data-lightbox-caption></span><span data-lightbox-count></span></figcaption></figure>
    <button class="lightbox-arrow next" type="button" aria-label="Imaginea următoare" data-lightbox-next><?php echo gi_icon('chevron-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
</div>

<?php
/*
 * Pe paginile dedicate, butonul „Rezervă” duce mereu la aceeași pagină (modulul de rezervări de pe prima pagină).
 * Pe telefon nu există „hover”, deci regula WordPress (la hover) nu pornește: descărcăm documentul HTML al acelei
 * pagini în clipa atingerii (prefetch „conservative”, fără scripturi). Nu „immediate”: fiecare vizită ar cere
 * încă o dată pagina principală, cu încărcare dublă pe server și vizite fantomă în statisticile de pe server.
 * Doar unde WordPress are regulile active (nu pentru utilizatorii autentificați).
 */
if (gi_is_dedicated_page() && function_exists('wp_get_speculation_rules_configuration') && wp_get_speculation_rules_configuration()) :
    ?>
<script type="speculationrules">{"prefetch":[{"source":"document","where":{"selector_matches":"[data-gi-book]"},"eagerness":"conservative"}]}</script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
