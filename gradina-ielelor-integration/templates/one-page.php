<?php
/**
 * Template Name: Grădina Ielelor — One Page + BRW
 * Randare server-side; modulul de rezervare este exclusiv OVABRW.
 */
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#173f35">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<svg class="gi-svg-sprite" aria-hidden="true" focusable="false">
    <symbol id="gi-icon-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
    <symbol id="gi-icon-arrow-up-right" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10"/></symbol>
    <symbol id="gi-icon-calendar" viewBox="0 0 24 24"><path d="M6 2v4M18 2v4M3 9h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></symbol>
    <symbol id="gi-icon-mountain" viewBox="0 0 24 24"><path d="m3 20 7-13 4 7 2-3 5 9H3Z"/></symbol>
    <symbol id="gi-icon-music" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13M9 9l12-2"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></symbol>
    <symbol id="gi-icon-tent" viewBox="0 0 24 24"><path d="M3 20 12 3l9 17H3ZM12 3l3 17M9 20l3-8 3 8"/></symbol>
    <symbol id="gi-icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
    <symbol id="gi-icon-waves" viewBox="0 0 24 24"><path d="M2 6c3-3 5 3 8 0s5 3 8 0 4 0 4 0M2 12c3-3 5 3 8 0s5 3 8 0 4 0 4 0M2 18c3-3 5 3 8 0s5 3 8 0 4 0 4 0"/></symbol>
    <symbol id="gi-icon-trees" viewBox="0 0 24 24"><path d="m8 3-5 8h3l-4 6h6v4h2v-4h5l-4-6h3L8 3ZM18 7l-3 5h2l-3 5h4v4h2v-4h3l-3-5h2l-4-5Z"/></symbol>
    <symbol id="gi-icon-map-pin" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
    <symbol id="gi-icon-phone" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></symbol>
    <symbol id="gi-icon-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></symbol>
    <symbol id="gi-icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="gi-icon-x" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></symbol>
    <symbol id="gi-icon-chevron-left" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
    <symbol id="gi-icon-chevron-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
    <symbol id="gi-icon-quote" viewBox="0 0 24 24"><path d="M10 11H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3a3 3 0 0 1 3 3v5a6 6 0 0 1-6 6M21 11h-5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3a3 3 0 0 1 3 3v5a6 6 0 0 1-6 6"/></symbol>
</svg>

<div class="site-shell home-page gi-site">
    <a class="skip-link" href="#continut">Sari la conținut</a>

    <header class="site-header shared-header header-overlay">
        <div class="container header-inner">
            <?php gi_brand(true); ?>
            <nav class="desktop-nav" aria-label="Navigație principală">
                <a href="<?php echo esc_url(gi_clean_page_url('despre-noi')); ?>">Despre noi</a>
                <a href="#camping">Camping</a>
                <a href="#glamping">Glamping</a>
                <a href="#bungalow">Bungalow</a>
                <a href="#evenimente">Evenimente</a>
                <a href="<?php echo esc_url(gi_clean_page_url('regulament-politici')); ?>">Regulament &amp; politici</a>
            </nav>
            <a class="button button-small header-cta" href="#rezervare">Rezervă <?php echo gi_icon('arrow-up-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="gi-mobile-navigation" aria-label="Deschide meniul" data-menu-toggle>
                <span data-menu-open><?php echo gi_icon('menu'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span data-menu-close hidden><?php echo gi_icon('x'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </button>
        </div>
        <nav id="gi-mobile-navigation" class="mobile-nav" aria-label="Navigație mobilă" hidden>
            <a href="<?php echo esc_url(gi_clean_page_url('despre-noi')); ?>">Despre noi</a>
            <a href="#camping">Camping</a>
            <a href="#glamping">Glamping</a>
            <a href="#bungalow">Bungalow</a>
            <a href="#evenimente">Evenimente</a>
            <a href="<?php echo esc_url(gi_clean_page_url('regulament-politici')); ?>">Regulament &amp; politici</a>
            <a href="#rezervare">Rezervă cazare</a>
        </nav>
    </header>

    <main id="continut">
        <section class="home-hero" aria-labelledby="home-hero-title">
            <div class="home-hero-slices" aria-label="Lac, relaxare și evenimente în Grădina Ielelor">
                <figure class="home-hero-slice home-hero-slice-lake">
                    <picture>
                        <source media="(max-width: 640px)" srcset="<?php echo esc_url(gi_image_url('home-hero-lake-centered-mobile-640.webp')); ?> 640w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-mobile-960.webp')); ?> 960w" sizes="100vw">
                        <img src="<?php echo esc_url(gi_image_url('home-hero-lake-centered-1600.webp')); ?>" srcset="<?php echo esc_url(gi_image_url('home-hero-lake-centered-640.webp')); ?> 640w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-960.webp')); ?> 960w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-1280.webp')); ?> 1280w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-1600.webp')); ?> 1600w" sizes="100vw" alt="Lacul din Grădina Ielelor într-o zi însorită, cu Munții Făgăraș în fundal" width="1600" height="900" fetchpriority="high">
                    </picture>
                </figure>
                <figure class="home-hero-slice home-hero-slice-festival">
                    <img src="<?php echo esc_url(gi_image_url('home-hero-festival-640.webp')); ?>" alt="Vedere aeriană cu oaspeți, corturi și rulote în Grădina Ielelor" width="640" height="480">
                </figure>
                <figure class="home-hero-slice home-hero-slice-chill">
                    <img src="<?php echo esc_url(gi_image_url('home-hero-chill-640.webp')); ?>" alt="Terasă cu hamace și fotolii pentru relaxare" width="640" height="480">
                </figure>
            </div>
            <div class="home-hero-overlay"></div>
            <div class="container home-hero-content">
                <span class="eyebrow eyebrow-light"><?php echo gi_icon('mountain'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus · Brașov</span>
                <h1 id="home-hero-title">Unde natura<br><em>devine acasă.</em></h1>
                <p>Camping, glamping, bungalow și evenimente la poalele Munților Făgăraș. Toate fotografiile și detaliile, într-o singură pagină.</p>
                <div class="home-hero-actions">
                    <a class="button button-accent" href="#rezervare">Rezervă-ți escapada <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="home-hero-events-link" href="#evenimente"><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Festivaluri &amp; petreceri</a>
                </div>
            </div>
        </section>

        <section id="rezervare" class="home-booking" aria-labelledby="booking-title">
            <div class="container home-booking-shell">
                <div class="booking-heading">
                    <div>
                        <span class="eyebrow"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Escapada ta începe aici</span>
                        <h2 id="booking-title">Când ne vedem în Grădină?</h2>
                    </div>
                    <p>Alege experiența și datele dorite. Verificarea disponibilității și continuarea rezervării folosesc motorul real BRW + WooCommerce.</p>
                </div>
                <div class="booking-panel">
                    <?php gi_render_booking(); ?>
                </div>
                <p class="booking-integration-note">Modul real de rezervări. Conținutul și tarifele de referință de mai jos sunt exclusiv informative; totalul și condțiile se confirmă în fluxul BRW/WooCommerce.</p>
            </div>
        </section>

        <nav class="container home-quick-nav" aria-label="Acces rapid la experiențe">
            <a href="#camping"><?php echo gi_icon('tent'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Camping</a>
            <a href="#glamping"><?php echo gi_icon('mountain'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Glamping</a>
            <a href="#bungalow">Bungalow</a>
            <a href="#evenimente"><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Evenimente</a>
        </nav>

        <div class="container home-one-page-heading">
            <span class="eyebrow">Aceeași Grădină. 1000 de feluri de a o trăi.</span>
            <h2>Tot ce vrei să vezi.<br><em>Fără pagini în plus.</em></h2>
            <p>Derulează prin experiențe, deschide fotografiile și rezervă direct atunci când ai găsit varianta potrivită.</p>
        </div>

        <?php foreach (gi_experiences() as $id => $experience) : ?>
            <section id="<?php echo esc_attr($id); ?>" class="home-experience-detail home-experience-<?php echo esc_attr($id); ?>" aria-labelledby="<?php echo esc_attr($id); ?>-title">
                <div class="container">
                    <div class="home-experience-intro">
                        <div>
                            <span class="eyebrow"><?php echo esc_html($experience['eyebrow']); ?></span>
                            <h2 id="<?php echo esc_attr($id); ?>-title"><?php echo esc_html($experience['title']); ?><br><em><?php echo esc_html($experience['emphasis']); ?></em></h2>
                        </div>
                        <div class="home-experience-summary">
                            <p><?php echo esc_html($experience['description']); ?></p>
                            <div class="home-inline-price">
                                <strong><?php echo esc_html($experience['price']); ?></strong>
                                <span><?php echo esc_html($experience['price_note']); ?><br>Conținut static de prezentare · tariful final se confirmă în BRW</span>
                            </div>
                        </div>
                    </div>

                    <?php gi_render_gallery($experience['images'], 'Galerie foto ' . $id); ?>

                    <div class="home-experience-bottom">
                        <div class="home-facilities" aria-label="Facilități <?php echo esc_attr($id); ?>">
                            <?php foreach ($experience['facilities'] as $facility) : ?>
                                <span><?php echo gi_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($facility); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="home-experience-actions">
                            <a class="button button-primary" href="<?php echo esc_url(gi_booking_url($experience['booking_type'])); ?>"><?php echo esc_html($experience['booking_label']); ?><?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php if (!empty($experience['secondary_type'])) : ?>
                                <a class="button button-outline" href="<?php echo esc_url(gi_booking_url($experience['secondary_type'])); ?>"><?php echo esc_html($experience['secondary_label']); ?><?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <section id="evenimente" class="home-events-detail" aria-labelledby="home-events-title">
            <div class="container">
                <div class="home-experience-intro">
                    <div>
                        <span class="eyebrow"><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Festivaluri &amp; evenimente</span>
                        <h2 id="home-events-title">Momente trăite<br><em>împreună.</em></h2>
                    </div>
                    <div class="home-experience-summary">
                        <p>Festivaluri, petreceri, nunți și întâlniri corporate pe un domeniu natural de 9 ha, cu scenă permanentă, camping, cazare, lacuri și parcare.</p>
                        <div class="home-event-quick-facts"><span><strong>5.000</strong> persoane · aviz ISU</span><span><strong>6 × 7 m</strong> scenă permanentă</span><span><strong>cca. 1.000</strong> locuri de parcare</span></div>
                    </div>
                </div>

                <?php gi_render_gallery(gi_event_images(), 'Galerie festivaluri și evenimente'); ?>

                <div class="home-event-zones">
                    <article><?php echo gi_icon('waves'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><h3>Camping &amp; lacul mic</h3><p>Bucătării comune, hamace, vetre de foc, Wi-Fi și terasă pe apă.</p></div></article>
                    <article><?php echo gi_icon('trees'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><h3>Platoul &amp; pavilionul</h3><p>Pavilion de 225 mp, terasă de 50 mp, bucătărie licențiată și grup sanitar.</p></div></article>
                    <article><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><h3>Lacul mare &amp; scena</h3><p>Scenă permanentă, lac de 8.000 mp, ponton, bungalowuri și bloc sanitar.</p></div></article>
                </div>

                <details class="home-event-details">
                    <summary>Detalii tehnice pentru organizatori<span>+</span></summary>
                    <p>Curentul pentru infrastructura locației este asigurat de domeniu; curentul pentru scenă, sunet, lumini și food trucks se asigură de organizator sau contra cost. Sonorizarea, luminile, structurile temporare și toaletele suplimentare nu sunt incluse standard. Configurația finală se stabilește împreună cu organizatorul.</p>
                </details>

                <div class="home-event-contact">
                    <span><?php echo gi_icon('map-pin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus, Brașov</span>
                    <a href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> +40 721 333 164</a>
                    <a class="button button-primary" href="<?php echo esc_url(gi_booking_url()); ?>">Rezervă cazare <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="button button-accent" href="mailto:rezervari@gradina-ielelor.ro?subject=Cerere%20ofert%C4%83%20eveniment">Cere ofertă <?php echo gi_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                </div>
            </div>

            <section id="calendar-2027" class="events-calendar calendar-compact" aria-labelledby="calendar-title">
                <div class="container calendar-layout">
                    <div class="calendar-intro">
                        <span class="eyebrow"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Calendar evenimente</span>
                        <div class="calendar-year">2027</div>
                        <h2 id="calendar-title">Ne vedem în Grădină.</h2>
                        <p>Festivaluri, petreceri și întâlniri pentru familii. Datele exacte și biletele vor fi publicate pe măsură ce fiecare eveniment este confirmat.</p>
                        <img class="calendar-watermark" src="<?php echo esc_url(gi_asset_url('logo-gradina-ielelor-white.png')); ?>" alt="" width="150" height="70" loading="lazy">
                        <div class="calendar-filters" aria-label="Filtrează evenimentele">
                            <button type="button" class="active" data-event-filter="toate" aria-pressed="true">Toate</button>
                            <button type="button" data-event-filter="festival" aria-pressed="false">Festivaluri</button>
                            <button type="button" data-event-filter="petrecere" aria-pressed="false">Petreceri</button>
                            <button type="button" data-event-filter="familie" aria-pressed="false">Pentru familii</button>
                        </div>
                    </div>
                    <div class="calendar-list" aria-live="polite">
                        <?php foreach (gi_events_2027() as $index => $event) : ?>
                            <article class="calendar-event" data-event-category="<?php echo esc_attr($event['category']); ?>">
                                <div class="calendar-event-icon" aria-hidden="true"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></div>
                                <div><span class="calendar-event-category"><?php echo esc_html($event['category_label']); ?></span><h3><?php echo esc_html($event['title']); ?></h3><small><?php echo esc_html($event['period']); ?></small></div>
                            </article>
                        <?php endforeach; ?>
                        <p class="calendar-disclaimer">Calendar orientativ, bazat pe proiectele anunțate public. Nu reprezintă confirmarea datei sau a biletelor disponibile.</p>
                    </div>
                </div>
            </section>
            <div class="container home-accommodation-note"><?php echo gi_icon('tent'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Pentru festivaluri și evenimente, oaspeții pot alege camping, glamping sau bungalow direct din modulul real de rezervare.</span></div>
        </section>

        <section id="recenzii" class="home-reviews" aria-labelledby="reviews-title">
            <div class="container">
                <div class="reviews-summary">
                    <span class="eyebrow">Recenzii ale oaspeților</span>
                    <div class="reviews-score"><strong>5,0</strong><span>/ 5</span></div>
                    <div class="stars" aria-label="5 din 5 stele">★★★★★</div>
                    <p id="reviews-title">Media recenziilor de 5 stele selectate mai jos, publicate pe site-ul Grădina Ielelor.</p>
                    <a href="https://gradina-ielelor.ro/" target="_blank" rel="noopener noreferrer" class="text-link">Vezi sursa recenziilor <?php echo gi_icon('arrow-up-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                </div>
                <div class="home-review-cards">
                    <?php foreach (gi_reviews() as $review) : ?>
                        <blockquote><?php echo gi_icon('quote'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><p>„<?php echo esc_html($review['text']); ?>”</p><footer><strong><?php echo esc_html($review['author']); ?></strong><span><?php echo esc_html($review['context']); ?> · ★★★★★</span></footer></blockquote>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer shared-footer">
        <div class="container footer-grid">
            <div>
                <?php gi_brand(true, 'footer-official-brand'); ?>
                <p>Camping, glamping, bungalow și evenimente în natură, la Sâmbăta de Sus, Brașov, la poalele Munților Făgăraș.</p>
            </div>
            <div><h3>Descoperă</h3><a href="#camping">Camping cort &amp; rulotă</a><a href="#glamping">Glamping</a><a href="#bungalow">Bungalow</a><a href="#evenimente">Festivaluri &amp; petreceri</a></div>
            <div><h3>Informații</h3><a href="<?php echo esc_url(gi_clean_page_url('despre-noi')); ?>">Despre noi</a><a href="<?php echo esc_url(gi_clean_page_url('regulament-politici')); ?>">Regulament &amp; politici</a><a href="#calendar-2027">Calendar evenimente 2027</a><a href="#recenzii">Recenzii ale oaspeților</a></div>
            <div><h3>Contact</h3><a href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> +40 721 333 164</a><a href="mailto:office@gradina-ielelor.ro"><?php echo gi_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> office@gradina-ielelor.ro</a><a href="https://www.google.com/maps/search/?api=1&amp;query=Gradina+Ielelor+Sambata+de+Sus" target="_blank" rel="noopener noreferrer"><?php echo gi_icon('map-pin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus, Brașov</a></div>
        </div>
        <div class="container footer-bottom"><span>Rezervările, disponibilitatea și plățile sunt procesate prin WooCommerce și BRW.</span><span>© <?php echo esc_html(wp_date('Y')); ?> Grădina Ielelor</span></div>
    </footer>

    <a class="mobile-booking-cta" href="#rezervare"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Rezervă cazare</a>
</div>

<div id="gi-lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Galerie foto" hidden>
    <button class="lightbox-close" type="button" aria-label="Închide galeria" data-lightbox-close><?php echo gi_icon('x'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    <button class="lightbox-arrow previous" type="button" aria-label="Imaginea anterioară" data-lightbox-previous><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
    <figure><img src="" alt="" data-lightbox-image><figcaption><span data-lightbox-caption></span><span data-lightbox-count></span></figcaption></figure>
    <button class="lightbox-arrow next" type="button" aria-label="Imaginea următoare" data-lightbox-next><?php echo gi_icon('chevron-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
</div>

<?php wp_footer(); ?>
</body>
</html>
