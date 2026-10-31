<?php
/**
 * Template Name: Grădina Ielelor — Pagina principală (rezervare)
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
            <div class="home-hero-slices" role="group" aria-label="Lacul, munții și focul de seară în Grădina Ielelor">
                <figure class="home-hero-slice home-hero-slice-lake">
                    <picture>
                        <source media="(max-width: 640px)" srcset="<?php echo esc_url(gi_image_url('home-hero-lake-centered-mobile-640.webp')); ?> 640w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-mobile-960.webp')); ?> 960w" sizes="100vw">
                        <img src="<?php echo esc_url(gi_image_url('home-hero-lake-centered-1600.webp')); ?>" srcset="<?php echo esc_url(gi_image_url('home-hero-lake-centered-640.webp')); ?> 640w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-960.webp')); ?> 960w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-1280.webp')); ?> 1280w, <?php echo esc_url(gi_image_url('home-hero-lake-centered-1600.webp')); ?> 1600w" sizes="100vw" alt="Lacul din Grădina Ielelor într-o zi însorită, cu Munții Făgăraș în fundal" width="1600" height="900" fetchpriority="high">
                    </picture>
                </figure>
                <figure class="home-hero-slice home-hero-slice-shore">
                    <img src="<?php echo esc_url(gi_image_url('lac-ponton-1280.webp')); ?>" alt="Lacul și corturile de pe mal, cu Munții Făgăraș în fundal" width="1280" height="720" fetchpriority="low" decoding="async">
                </figure>
                <figure class="home-hero-slice home-hero-slice-foc">
                    <?php // Fotografia e încadrată pe lățimea primei pagini: focul și oamenii cad în triunghiul de jos. ?>
                    <picture>
                        <source media="(max-width: 700px)" srcset="<?php echo esc_url(gi_image_url('home-hero-foc-mobil-1280.webp')); ?> 1280w" sizes="100vw">
                        <img src="<?php echo esc_url(gi_image_url('home-hero-foc-1920.webp')); ?>" srcset="<?php echo esc_url(gi_image_url('home-hero-foc-1280.webp')); ?> 1280w, <?php echo esc_url(gi_image_url('home-hero-foc-1920.webp')); ?> 1920w" sizes="100vw" alt="Oaspeți în jurul focului, seara, cu tobe, în Grădina Ielelor" width="1920" height="753" fetchpriority="low" decoding="async">
                    </picture>
                </figure>
            </div>
            <div class="home-hero-overlay"></div>
            <div class="container home-hero-content">
                <span class="eyebrow eyebrow-light"><?php echo gi_icon('mountain'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus · Brașov</span>
                <h1 id="home-hero-title">Unde natura<br><em>devine acasă.</em></h1>
                <p>Camping, glamping, bungalow și evenimente la poalele Munților Făgăraș.</p>
                <div class="home-hero-actions">
                    <a class="button button-accent" href="#rezervare">Rezervă-ți escapada <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="home-hero-events-link" href="<?php echo esc_url(gi_offer_url('festivaluri')); ?>"><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Festivaluri &amp; tabere</a>
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
                <p class="booking-integration-note">Tarifele de pe site sunt orientative. Prețul final și condițiile le vezi la rezervare, înainte de plată.<?php $gi_payment_note = function_exists('gi_onsite_surcharge_notice') ? gi_onsite_surcharge_notice() : ''; echo $gi_payment_note ? ' ' . esc_html($gi_payment_note) : ''; ?></p>
            </div>
        </section>

        <?php
        // Secțiunile detaliate care rămân pe prima pagină: o ofertă fără pagină dedicată trimite la ele
        // (ex. Festivaluri & tabere și Evenimente private → #evenimente, până când amândouă au pagina lor).
        $gi_home_sections = array();
        foreach (gi_offers() as $gi_offer_key => $gi_offer) {
            if (!gi_offer_has_page($gi_offer_key)) {
                $gi_home_sections[$gi_offer['anchor']] = true;
            }
        }
        $gi_card_ids = array();
        ?>
        <section id="experiente" class="gi-offers" aria-labelledby="offers-title">
            <div class="container">
                <div class="gi-offers-head">
                    <span class="eyebrow">Aceeași Grădină. 1000 de feluri de a o trăi.</span>
                    <h2 id="offers-title">Alege-ți <em>experiența</em></h2>
                </div>
                <div class="gi-offers-grid">
                    <?php foreach (gi_offer_cards() as $gi_key => $gi_card) : ?>
                        <?php
                        // Adresele vechi (/#glamping, /#evenimente) ajung la card când secțiunea cu același nume nu mai e pe pagină;
                        // un singur card primește numele (la evenimente, primul: Festivaluri & tabere).
                        $gi_card_anchor = gi_offers()[$gi_key]['anchor'];
                        $gi_card_id = (empty($gi_home_sections[$gi_card_anchor]) && empty($gi_card_ids[$gi_card_anchor])) ? $gi_card_anchor : '';
                        if ($gi_card_id) {
                            $gi_card_ids[$gi_card_id] = true;
                        }
                        ?>
                        <a<?php echo $gi_card_id ? ' id="' . esc_attr($gi_card_id) . '"' : ''; ?> class="gi-offer-card gi-offer-card-<?php echo esc_attr($gi_key); ?>" href="<?php echo esc_url(gi_offer_url($gi_key)); ?>">
                            <?php // Numele ofertei e deja textul linkului: fotografia e decorativă aici (alt gol, fără dublare). ?>
                            <img src="<?php echo esc_url(gi_image_url($gi_card['image'])); ?>" alt="" width="960" height="720" loading="lazy" decoding="async">
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

        <section class="gi-home-info" aria-label="Informații utile">
            <div class="container gi-home-info-grid">
                <a class="gi-home-info-card gi-home-info-festival" href="<?php echo esc_url(home_url('/festival/')); ?>">
                    <?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <strong>Organizezi un festival?</strong>
                    <span>Aviz ISU pentru 5.000 de persoane, scenă permanentă, camping și parcare proprie.</span>
                    <em>Cere o ofertă de organizare <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></em>
                </a>
                <a class="gi-home-info-card" href="<?php echo esc_url(gi_clean_page_url('despre-noi')); ?>">
                    <?php echo gi_icon('trees'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <strong>Despre noi</strong>
                    <span>Cine suntem și cum arată locul: șase hectare la poalele Făgărașului.</span>
                    <em>Citește povestea <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></em>
                </a>
                <a class="gi-home-info-card" href="<?php echo esc_url(gi_clean_page_url('regulament-politici')); ?>">
                    <?php echo gi_icon('file'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <strong>Regulament &amp; politici</strong>
                    <span>Regulile domeniului și politica de anulare, pe scurt și clar.</span>
                    <em>Vezi regulile <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></em>
                </a>
            </div>
        </section>

        <?php foreach (gi_experiences() as $id => $experience) : ?>
            <?php
            if (empty($gi_home_sections[$id])) {
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

        <?php if (!empty($gi_home_sections['evenimente'])) : ?>
        <section id="evenimente" class="home-events-detail" aria-labelledby="home-events-title">
            <div class="container">
                <div class="home-experience-intro">
                    <div>
                        <span class="eyebrow"><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Festivaluri, tabere &amp; evenimente private</span>
                        <h2 id="home-events-title">Momente trăite<br><em>împreună.</em></h2>
                    </div>
                    <div class="home-experience-summary">
                        <p>Festivaluri, petreceri, nunți și întâlniri corporate pe un domeniu natural de 9 ha, cu scenă permanentă, camping, cazare, lacuri și parcare.</p>
                        <div class="home-event-quick-facts"><span><strong>5.000</strong> persoane · aviz ISU</span><span><strong>6 × 7 m</strong> scenă permanentă</span><span><strong>cca. 1.000</strong> locuri de parcare</span></div>
                    </div>
                </div>

                <?php gi_render_gallery(gi_event_images(), 'Galerie festivaluri și evenimente'); ?>

                <div class="home-event-zones">
                    <article><?php echo gi_icon('waves'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><h3>Camping &amp; lacul mic</h3><p>Bucătării comune, hamace, vetre de foc, Wi-Fi și terasă acoperită pe apă.</p></div></article>
                    <article><?php echo gi_icon('trees'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><h3>Platoul &amp; pavilionul</h3><p>Pavilion de 225 mp, terasă de 50 mp, bucătărie autorizată și grup sanitar.</p></div></article>
                    <article><?php echo gi_icon('music'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><h3>Lacul mare &amp; scena</h3><p>Scenă permanentă, lac de 8.000 mp, ponton, bungalow-uri și bloc sanitar.</p></div></article>
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
                    <span class="eyebrow">Recenzii</span>
                    <h2 id="reviews-title">Ce spun oaspeții</h2>
                    <p>Câteva dintre recenziile primite de la oaspeții noștri, alese de noi.</p>
                </div>
                <div class="home-review-cards">
                    <?php foreach (gi_reviews() as $review) : ?>
                        <blockquote><?php echo gi_icon('quote'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><p>„<?php echo esc_html($review['text']); ?>”</p><footer><strong><?php echo esc_html($review['author']); ?></strong><span><?php echo esc_html($review['context']); ?> · ★★★★★</span></footer></blockquote>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php
        $gi_posts = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3, 'no_found_rows' => true));
        if ($gi_posts) :
            ?>
            <section id="blog" class="gi-home-blog" aria-labelledby="blog-title">
                <div class="container">
                    <div class="gi-offers-head">
                        <span class="eyebrow">Blog</span>
                        <h2 id="blog-title">Povești din <em>Grădină</em></h2>
                    </div>
                    <div class="gi-home-blog-grid">
                        <?php foreach ($gi_posts as $gi_post) : ?>
                            <a class="gi-home-blog-card" href="<?php echo esc_url(get_permalink($gi_post)); ?>">
                                <small><?php echo esc_html(get_the_date('', $gi_post)); ?></small>
                                <strong><?php echo esc_html(get_the_title($gi_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($gi_post), 22)); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

<?php gi_part('footer', array('mobile_cta' => 'Rezervă cazare')); ?>
