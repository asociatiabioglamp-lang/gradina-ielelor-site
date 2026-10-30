<?php
/**
 * Template Name: Grădina Ielelor — Glamping
 * Pagină dedicată ofertei Glamping. Rezervarea folosește exclusiv OVABRW, cu categoria Glamping preselectată.
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_glamping = gi_glamping_page();
$gi_experience = gi_experiences()['glamping'];
$gi_hero = $gi_experience['images'][0];
$gi_reviews = array_values(array_filter(gi_reviews(), function ($review) use ($gi_glamping) {
    return in_array($review['author'], $gi_glamping['review_authors'], true);
}));
$gi_related = array(
    'camping' => array('text' => 'Vii cu cortul sau cu rulota? Loc generos, lângă lac și munte.', 'image' => 'camping-day-960.webp'),
    'bungalow' => array('text' => 'Patru bungalow-uri cu baie proprie, pe malul lacului mare.', 'image' => 'bungalow-card-960.webp'),
    'evenimente' => array('text' => 'Festivaluri, petreceri și întâlniri pe un domeniu de 9 hectare.', 'image' => 'festival-scena-960.webp'),
);

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated'));
gi_part('header');
?>

    <main id="continut">
        <section class="gi-page-hero" aria-labelledby="glamping-title">
            <figure class="gi-page-hero-media">
                <img src="<?php echo esc_url(gi_image_url($gi_hero['src'])); ?>" alt="<?php echo esc_attr($gi_hero['alt']); ?>" width="1600" height="900" fetchpriority="high">
            </figure>
            <div class="gi-page-hero-overlay"></div>
            <div class="container gi-page-hero-content">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Glamping</span>
                </nav>
                <span class="eyebrow eyebrow-light"><?php echo gi_icon('mountain'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus · Brașov</span>
                <h1 id="glamping-title">Glamping în<br><em>Grădina Ielelor</em></h1>
                <p>Un cort cu pat adevărat, pe platformă de lemn, cu Munții Făgăraș la intrare. Nu ai nevoie de echipament ca să dormi la munte.</p>
                <div class="gi-page-hero-actions">
                    <a class="button button-accent" href="#rezervare">Verifică disponibilitatea <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="gi-hero-link" href="#galerie">Vezi pozele</a>
                </div>
            </div>
        </section>

        <div class="container">
            <ul class="gi-facts" aria-label="Pe scurt">
                <?php foreach ($gi_glamping['facts'] as $gi_fact) : ?>
                    <li><strong><?php echo esc_html($gi_fact['value']); ?></strong><span><?php echo esc_html($gi_fact['label']); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <section id="rezervare" class="gi-booking-card-section" aria-labelledby="booking-title">
            <div class="container">
                <div class="gi-booking-card">
                    <div class="gi-booking-card-intro">
                        <span class="eyebrow"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Rezervare</span>
                        <h2 id="booking-title">Când vii?</h2>
                        <p>Alege datele și vezi pe loc ce e liber și cât costă.</p>
                    </div>
                    <div class="booking-panel">
                        <?php gi_render_booking('glamping', false); ?>
                    </div>
                    <p class="gi-booking-alt">
                        <a href="<?php echo esc_url(gi_product_url('glamping')); ?>">Deschide calendarul glampingului <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                        <span>sau sună-ne la <a href="tel:+40721333164">0721 333 164</a></span>
                    </p>
                </div>
            </div>
        </section>

        <section class="gi-story gi-reveal" aria-labelledby="story-title">
            <div class="container gi-story-grid">
                <div class="gi-story-text">
                    <span class="eyebrow">Cum e să dormi aici</span>
                    <h2 id="story-title">Natura, cu confort.<br><em>Fără bagaj de expediție.</em></h2>
                    <p>Cortul stă pe o platformă de lemn și are pat cu lenjerie, covor și noptieră. La intrare te așteaptă o masă cu scaune, iar în față, muntele.</p>
                    <p>Ziua ai la dispoziție două lacuri, unul în care se înoată, și o terasă din lemn construită pe apă. Seara, focul se aprinde în vetrele amenajate, iar hamacele sunt la câțiva pași.</p>
                </div>
                <figure class="gi-story-media">
                    <img src="<?php echo esc_url(gi_image_url($gi_experience['images'][1]['src'])); ?>" alt="<?php echo esc_attr($gi_experience['images'][1]['alt']); ?>" width="640" height="800" loading="lazy" decoding="async">
                </figure>
            </div>
        </section>

        <section id="galerie" class="gi-gallery-section gi-reveal" aria-labelledby="gallery-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Galerie</span>
                    <h2 id="gallery-title">Vezi exact unde dormi</h2>
                </div>
                <?php gi_render_gallery($gi_experience['images'], 'Galerie foto glamping'); ?>
            </div>
        </section>

        <section class="gi-included gi-reveal" aria-labelledby="included-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Ce primești</span>
                    <h2 id="included-title">Tot ce-ți trebuie, nimic în plus</h2>
                </div>
                <div class="gi-included-grid">
                    <article class="gi-soft-card">
                        <h3><?php echo gi_icon('tent'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> În cortul tău</h3>
                        <ul class="gi-check-list">
                            <?php foreach ($gi_glamping['tent'] as $gi_item) : ?>
                                <li><?php echo gi_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($gi_item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                    <article class="gi-soft-card">
                        <h3><?php echo gi_icon('trees'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> În Grădină</h3>
                        <ul class="gi-check-list">
                            <?php foreach ($gi_glamping['garden'] as $gi_item) : ?>
                                <li><?php echo gi_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($gi_item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </div>
                <p class="gi-included-note">Băile sunt comune. Dacă vrei baie proprie, <a href="<?php echo esc_url(gi_offer_url('bungalow')); ?>">alege un bungalow</a>.</p>
            </div>
        </section>

        <section class="gi-prices gi-reveal" aria-labelledby="prices-title">
            <div class="container gi-prices-grid">
                <div>
                    <span class="eyebrow">Prețuri</span>
                    <h2 id="prices-title">Simplu, fără surprize</h2>
                    <p>Prețul final îl vezi când alegi datele, înainte să plătești.</p>
                    <a class="button button-primary" href="#rezervare">Alege datele <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                </div>
                <dl class="gi-price-list">
                    <?php foreach ($gi_glamping['prices'] as $gi_price) : ?>
                        <div><dt><?php echo esc_html($gi_price['label']); ?></dt><dd><?php echo esc_html($gi_price['value']); ?> <small>/ noapte</small></dd></div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>

        <section class="gi-notes gi-reveal" aria-labelledby="notes-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Bine de știut</span>
                    <h2 id="notes-title">Înainte să vii</h2>
                </div>
                <div class="gi-notes-grid">
                    <?php foreach ($gi_glamping['notes'] as $gi_note) : ?>
                        <article class="gi-note">
                            <?php echo gi_icon($gi_note['icon']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <h3><?php echo esc_html($gi_note['title']); ?></h3>
                            <p><?php echo esc_html($gi_note['text']); ?></p>
                        </article>
                    <?php endforeach; ?>
                    <article class="gi-note">
                        <?php echo gi_icon('file'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <h3>Regulament și anulare</h3>
                        <p>Regulile domeniului și politica de anulare sunt într-un singur loc. <a href="<?php echo esc_url(gi_clean_page_url('regulament-politici')); ?>">Citește-le aici</a>.</p>
                    </article>
                </div>
            </div>
        </section>

        <?php if ($gi_reviews) : ?>
            <section class="gi-page-reviews gi-reveal" aria-labelledby="reviews-title">
                <div class="container">
                    <div class="gi-section-head">
                        <span class="eyebrow">Ce spun oaspeții</span>
                        <h2 id="reviews-title">De la cei care au dormit aici</h2>
                    </div>
                    <div class="home-review-cards">
                        <?php foreach ($gi_reviews as $gi_review) : ?>
                            <blockquote><?php echo gi_icon('quote'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><p>„<?php echo esc_html($gi_review['text']); ?>”</p><footer><strong><?php echo esc_html($gi_review['author']); ?></strong><span><?php echo esc_html($gi_review['context']); ?> · ★★★★★</span></footer></blockquote>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="gi-location gi-reveal" aria-labelledby="location-title">
            <div class="container gi-location-grid">
                <div>
                    <span class="eyebrow"><?php echo gi_icon('map-pin'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Locație</span>
                    <h2 id="location-title">Cum ajungi</h2>
                    <p>Sâmbăta de Sus, județul Brașov, la baza Munților Făgăraș. Destul de departe de oraș cât să conteze, destul de aproape cât să ajungi ușor.</p>
                    <div class="gi-location-actions">
                        <a class="button button-outline" href="https://www.google.com/maps/search/?api=1&amp;query=Gradina+Ielelor+Sambata+de+Sus" target="_blank" rel="noopener noreferrer">Deschide în Google Maps <?php echo gi_icon('arrow-up-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                        <a class="gi-text-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 0721 333 164</a>
                    </div>
                </div>
                <ul class="gi-distances">
                    <?php foreach ($gi_glamping['distances'] as $gi_distance) : ?>
                        <li><span><?php echo esc_html($gi_distance['place']); ?></span><strong><?php echo esc_html($gi_distance['value']); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="gi-faq gi-reveal" aria-labelledby="faq-title">
            <div class="container gi-faq-grid">
                <div>
                    <span class="eyebrow">Întrebări</span>
                    <h2 id="faq-title">Ce ne întreabă lumea</h2>
                    <p>Nu găsești răspunsul? Scrie-ne la <a href="mailto:rezervari@gradina-ielelor.ro">rezervari@gradina-ielelor.ro</a>.</p>
                </div>
                <div class="gi-faq-list">
                    <?php foreach ($gi_glamping['faq'] as $gi_index => $gi_item) : ?>
                        <details<?php echo 0 === $gi_index ? ' open' : ''; ?>>
                            <summary><?php echo esc_html($gi_item['q']); ?><span aria-hidden="true"></span></summary>
                            <p><?php echo esc_html($gi_item['a']); ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="gi-related gi-reveal" aria-labelledby="related-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Mai multe feluri de a sta în Grădină</span>
                    <h2 id="related-title">Poate te tentează și</h2>
                </div>
                <div class="gi-related-grid">
                    <?php foreach ($gi_related as $gi_key => $gi_item) : ?>
                        <a class="gi-related-card" href="<?php echo esc_url(gi_offer_url($gi_key)); ?>">
                            <img src="<?php echo esc_url(gi_image_url($gi_item['image'])); ?>" alt="" width="960" height="720" loading="lazy" decoding="async">
                            <span class="gi-related-body">
                                <strong><?php echo esc_html(gi_offers()[$gi_key]['label']); ?></strong>
                                <span><?php echo esc_html($gi_item['text']); ?></span>
                            </span>
                            <?php echo gi_icon('arrow-up-right', 'gi-related-arrow'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="gi-closing" aria-labelledby="closing-title">
            <div class="container gi-closing-inner">
                <h2 id="closing-title">Gata de o noapte<br><em>sub cerul Făgărașului?</em></h2>
                <div class="gi-closing-actions">
                    <a class="button button-accent" href="#rezervare">Verifică disponibilitatea <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="gi-hero-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sună-ne</a>
                </div>
            </div>
        </section>
    </main>

<?php gi_part('footer', array('mobile_cta' => 'Verifică disponibilitatea')); ?>
