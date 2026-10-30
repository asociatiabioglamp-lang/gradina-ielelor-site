<?php
/**
 * Template Name: Grădina Ielelor — One Page + BRW
 * Randare server-side; modulul de rezervare este exclusiv OVABRW.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php gi_part('document-start', array('shell_class' => 'site-shell home-page gi-site')); ?>
<?php gi_part('header'); ?>

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
                <p>Camping, glamping, bungalow și evenimente la poalele Munților Făgăraș.</p>
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
                    <p>Alege experiența și datele. Vezi pe loc ce e liber și cât costă, înainte să plătești.</p>
                </div>
                <div class="booking-panel">
                    <?php gi_render_booking('', false); ?>
                </div>
                <p class="booking-integration-note">Tarifele de pe site sunt orientative. Prețul final și condițiile le vezi la rezervare, înainte de plată.</p>
            </div>
        </section>

        <section id="experiente" class="gi-offers" aria-labelledby="offers-title">
            <div class="container">
                <div class="gi-offers-head">
                    <span class="eyebrow">Aceeași Grădină. 1000 de feluri de a o trăi.</span>
                    <h2 id="offers-title">Alege-ți <em>experiența</em></h2>
                </div>
                <div class="gi-offers-grid">
                    <?php foreach (gi_offer_cards() as $gi_key => $gi_card) : ?>
                        <a class="gi-offer-card gi-offer-card-<?php echo esc_attr($gi_key); ?>" href="<?php echo esc_url(gi_offer_url($gi_key)); ?>">
                            <img src="<?php echo esc_url(gi_image_url($gi_card['image'])); ?>" alt="<?php echo esc_attr($gi_card['alt']); ?>" width="960" height="720" loading="lazy" decoding="async">
                            <span class="gi-offer-card-body">
                                <strong><?php echo esc_html(gi_offers()[$gi_key]['label']); ?></strong>
                                <span><?php echo esc_html($gi_card['text']); ?></span>
                                <small><?php echo esc_html($gi_card['price']); ?></small>
                            </span>
                            <span class="gi-offer-card-cta"><?php echo gi_offer_has_page($gi_key) ? 'Descoperă' : 'Vezi detalii'; ?> <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php foreach (gi_experiences() as $id => $experience) : ?>
            <?php
            // Oferta cu pagină dedicată nu se mai încarcă aici; cardul de mai sus duce la pagina ei.
            if (gi_offer_has_page($id)) {
                continue;
            }
            ?>
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
                                <span><?php echo esc_html($experience['price_note']); ?><br>Tarif orientativ · prețul final îl vezi la rezervare</span>
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
                            <?php if (gi_offer_has_page($id)) : ?>
                                <a class="button button-accent" href="<?php echo esc_url(gi_offer_url($id)); ?>">Descoperă pagina <?php echo esc_html(gi_offers()[$id]['label']); ?><?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php endif; ?>
                            <a class="button button-primary" href="<?php echo esc_url(gi_booking_url($experience['booking_type'])); ?>"><?php echo esc_html($experience['booking_label']); ?><?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php if (!empty($experience['secondary_type'])) : ?>
                                <a class="button button-outline" href="<?php echo esc_url(gi_booking_url($experience['secondary_type'])); ?>"><?php echo esc_html($experience['secondary_label']); ?><?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if (!gi_offer_has_page('evenimente')) : ?>
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
        <?php endif; ?>

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

<?php gi_part('footer', array('mobile_cta' => 'Rezervă cazare')); ?>
