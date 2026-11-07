<?php
/**
 * Pagină dedicată unei oferte (Bungalow, Glamping, Camping & rulote, Festivaluri & tabere, Evenimente private).
 * Conținutul vine din gi_offer_page(); pagina nu conține formularul de rezervare: butoanele trimit
 * la modulul de rezervări de pe pagina principală, cu oferta preselectată.
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_key = gi_current_page_key();
$gi_page = gi_offer_page($gi_key);
if (!$gi_page) {
    // Template-ul a rămas pe o pagină fără conținut definit: „pagina nu există”, fără redirecționări în buclă.
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    $gi_404 = get_404_template();
    if ($gi_404) {
        include $gi_404;
    } else {
        wp_die('Pagina nu a fost găsită.', 'Pagina nu a fost găsită', array('response' => 404));
    }
    return;
}

$gi_book_url = gi_current_cta_url();
$gi_back_url = gi_home_booking_url($gi_page['booking_type']);
$gi_reviews = array();
if (!empty($gi_page['reviews']['authors'])) {
    $gi_reviews = array_values(array_filter(gi_reviews(), function ($review) use ($gi_page) {
        return in_array($review['author'], $gi_page['reviews']['authors'], true);
    }));
}
$gi_related = array_diff_key(gi_offer_cards(), array($gi_key => true));
// Sezonul și regula plății în locație vin din Setări → Grădina Ielelor, ca proprietarul să le poată schimba singur.
$gi_season = gi_season($gi_key);
if ($gi_season) {
    $gi_page['faq'][] = array('q' => empty($gi_page['prices']) ? 'Care e sezonul?' : 'Când e deschis?', 'a' => 'Sezonul pentru ' . strtolower(gi_offers()[$gi_key]['label']) . ': ' . $gi_season . '.');
}
$gi_payment_note = function_exists('gi_onsite_surcharge_notice') ? gi_onsite_surcharge_notice() : '';
// Al doilea buton de rezervare (ex. Camping & rulote: „Ai rulotă? Rezervă aici”, cu rulota preselectată).
$gi_alt = empty($gi_page['cta_alt']) ? null : array('label' => $gi_page['cta_alt']['label'], 'url' => !empty($gi_page['cta_alt']['url']) ? $gi_page['cta_alt']['url'] : gi_home_booking_url($gi_page['cta_alt']['booking_type']));

/**
 * Atributele srcset/sizes din variantele existente ale aceleiași fotografii (nume-640.webp, nume-960.webp ...).
 * Fără variante pe disc nu adaugă nimic; când apar fișierele mai mici, telefoanele le primesc automat.
 */
$gi_srcset = function ($file, $sizes) {
    if (!preg_match('/^(.+)-\d+\.webp$/', $file, $gi_m)) {
        return '';
    }
    $set = array();
    foreach (array(480, 640, 960, 1280, 1600, 1920) as $w) {
        $name = $gi_m[1] . '-' . $w . '.webp';
        $path = GI_PATH . 'assets/images/' . $name;
        if (file_exists($path)) {
            $size = @getimagesize($path);
            $set[] = esc_url(gi_image_url($name)) . ' ' . ($size ? (int) $size[0] : $w) . 'w';
        }
    }
    return count($set) > 1 ? ' srcset="' . implode(', ', $set) . '" sizes="' . esc_attr($sizes) . '"' : '';
};

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated'));
gi_part('header');
?>

    <main id="continut">
        <section class="gi-page-hero" aria-labelledby="page-title">
            <figure class="gi-page-hero-media<?php echo !empty($gi_page['hero']['image2']) ? ' gi-page-hero-media--duo' : ''; ?>">
                <img src="<?php echo esc_url(gi_image_url($gi_page['hero']['image'])); ?>" alt="<?php echo esc_attr($gi_page['hero']['alt']); ?>"<?php echo $gi_srcset($gi_page['hero']['image'], '100vw'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- atribute escapate în $gi_srcset. ?> width="1600" height="900" fetchpriority="high">
                <?php if (!empty($gi_page['hero']['image2'])) : ?>
                    <img class="gi-hero-second" src="<?php echo esc_url(gi_image_url($gi_page['hero']['image2'])); ?>" alt="<?php echo esc_attr(!empty($gi_page['hero']['alt2']) ? $gi_page['hero']['alt2'] : ''); ?>" width="1280" height="720" loading="lazy" decoding="async">
                <?php endif; ?>
            </figure>
            <div class="gi-page-hero-overlay"></div>
            <div class="container gi-page-hero-content">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page"><?php echo esc_html($gi_page['title']); ?></span>
                </nav>
                <span class="eyebrow eyebrow-light"><?php echo gi_icon('mountain'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sâmbăta de Sus · Brașov</span>
                <h1 id="page-title"><?php echo esc_html($gi_page['hero']['h1']); ?><br><em><?php echo esc_html($gi_page['hero']['h1_em']); ?></em></h1>
                <p><?php echo esc_html($gi_page['hero']['lead']); ?></p>
                <div class="gi-page-hero-actions">
                    <a class="button button-accent" href="<?php echo esc_url($gi_book_url); ?>" data-gi-book><?php echo esc_html($gi_page['cta']); ?> <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <?php if ($gi_alt) : ?><a class="gi-hero-link" href="<?php echo esc_url($gi_alt['url']); ?>" data-gi-book><?php echo esc_html($gi_alt['label']); ?></a><?php endif; ?>
                    <?php // Paginile cu cerere de ofertă (festivaluri, tabere, evenimente private): telefonul, la vedere lângă buton. ?>
                    <?php if (!empty($gi_page['phone'])) : ?><a class="gi-hero-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 0721 333 164</a><?php endif; ?>
                    <a class="gi-hero-link" href="#galerie">Vezi pozele</a>
                </div>
            </div>
        </section>

        <div class="container">
            <ul class="gi-facts" aria-label="Pe scurt">
                <?php foreach ($gi_page['facts'] as $gi_fact) : ?>
                    <li><strong><?php echo esc_html($gi_fact['value']); ?></strong><span><?php echo esc_html($gi_fact['label']); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <section class="gi-story gi-reveal" aria-labelledby="story-title">
            <div class="container gi-story-grid">
                <div class="gi-story-text">
                    <span class="eyebrow"><?php echo esc_html($gi_page['story']['eyebrow']); ?></span>
                    <h2 id="story-title"><?php echo esc_html($gi_page['story']['title']); ?><br><em><?php echo esc_html($gi_page['story']['title_em']); ?></em></h2>
                    <?php foreach ($gi_page['story']['paragraphs'] as $gi_paragraph) : ?>
                        <p><?php echo esc_html($gi_paragraph); ?></p>
                    <?php endforeach; ?>
                </div>
                <figure class="gi-story-media">
                    <img src="<?php echo esc_url(gi_image_url($gi_page['story']['image'])); ?>" alt="<?php echo esc_attr($gi_page['story']['alt']); ?>"<?php echo $gi_srcset($gi_page['story']['image'], '(max-width: 900px) calc(100vw - 32px), 45vw'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> width="640" height="800" loading="lazy" decoding="async">
                </figure>
            </div>
        </section>

        <?php if (!empty($gi_page['calendar'])) : ?>
            <section id="calendar" class="gi-calendar gi-reveal" aria-labelledby="calendar-title">
                <div class="container">
                    <div class="gi-section-head">
                        <span class="eyebrow"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Calendar</span>
                        <h2 id="calendar-title"><?php echo esc_html($gi_page['calendar']['title']); ?></h2>
                        <?php if (!empty($gi_page['calendar']['text'])) : ?>
                            <p><?php echo esc_html($gi_page['calendar']['text']); ?></p>
                        <?php endif; ?>
                    </div>
                    <ul class="gi-calendar-list">
                        <?php foreach (gi_events_2027() as $gi_event) : ?>
                            <li><span class="gi-calendar-tag"><?php echo esc_html($gi_event['category_label']); ?></span><strong><?php echo esc_html($gi_event['title']); ?></strong><small><?php echo esc_html($gi_event['period']); ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <section id="galerie" class="gi-gallery-section gi-reveal" aria-labelledby="gallery-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Galerie</span>
                    <h2 id="gallery-title"><?php echo esc_html($gi_page['gallery']['title']); ?></h2>
                </div>
                <?php gi_render_gallery($gi_page['gallery']['images'], 'Galerie foto ' . $gi_page['title']); ?>
            </div>
        </section>

        <?php $gi_common = gi_common_facilities(); ?>
        <?php if (!empty($gi_common['images'])) : ?>
        <section id="spatii-comune" class="gi-gallery-section gi-common gi-reveal" aria-labelledby="common-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Pentru toți oaspeții</span>
                    <h2 id="common-title"><?php echo esc_html($gi_common['title']); ?></h2>
                    <p class="gi-section-lead"><?php echo esc_html(!empty($gi_page['common_intro']) ? $gi_page['common_intro'] : $gi_common['intro']); ?></p>
                </div>
                <?php gi_render_gallery($gi_common['images'], 'Spațiile comune ale Grădinii'); ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="gi-included gi-reveal" aria-labelledby="included-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Ce primești</span>
                    <h2 id="included-title"><?php echo esc_html($gi_page['included']['title']); ?></h2>
                </div>
                <div class="gi-included-grid<?php echo count($gi_page['included']['groups']) > 2 ? ' gi-included-grid-many' : ''; ?>">
                    <?php foreach ($gi_page['included']['groups'] as $gi_index => $gi_group) : ?>
                        <div class="gi-included-group <?php echo 0 === $gi_index ? 'gi-included-tent' : 'gi-included-garden'; ?>">
                            <h3><?php echo gi_icon($gi_group['icon']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html($gi_group['title']); ?></h3>
                            <ul class="gi-check-list">
                                <?php foreach ($gi_group['items'] as $gi_item) : ?>
                                    <li><?php echo gi_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($gi_item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!empty($gi_group['note'])) : ?>
                                <p class="gi-included-note"><?php echo esc_html($gi_group['note']['text']); ?><a href="<?php echo esc_url(gi_offer_url($gi_group['note']['link_offer'])); ?>"><?php echo esc_html($gi_group['note']['link_text']); ?></a>.</p>
                            <?php endif; ?>
                            <?php if (!empty($gi_group['action'])) : ?>
                                <p class="gi-included-action"><a class="button button-small" href="<?php echo esc_url($gi_group['action']['url']); ?>"><?php echo esc_html($gi_group['action']['label']); ?></a></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($gi_reviews) : ?>
            <section class="gi-page-reviews gi-reveal" aria-labelledby="reviews-title">
                <div class="container">
                    <div class="gi-section-head">
                        <span class="eyebrow">Ce spun oaspeții</span>
                        <h2 id="reviews-title"><?php echo esc_html($gi_page['reviews']['title']); ?></h2>
                    </div>
                    <?php // Pe telefon rândul se derulează orizontal: îl facem accesibil și de la tastatură. ?>
                    <div class="home-review-cards gi-swipe" role="group" aria-label="Recenzii" tabindex="0">
                        <?php foreach ($gi_reviews as $gi_review) : ?>
                            <blockquote><?php echo gi_icon('quote'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><p>„<?php echo esc_html($gi_review['text']); ?>”</p><footer><strong><?php echo esc_html($gi_review['author']); ?></strong><span><?php echo esc_html($gi_review['context']); ?> · ★★★★★</span></footer></blockquote>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($gi_page['prices'])) : ?>
            <section class="gi-prices gi-reveal" aria-labelledby="prices-title">
                <div class="container">
                    <div class="gi-prices-band">
                        <div class="gi-prices-intro">
                            <span class="eyebrow"><?php echo gi_icon('calendar'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Prețuri</span>
                            <h2 id="prices-title"><?php echo esc_html($gi_page['prices']['title']); ?></h2>
                            <p><?php echo esc_html($gi_page['prices']['text']); ?></p>
                            <?php if ($gi_season) : ?>
                                <p class="gi-prices-season"><strong>Sezon:</strong> <?php echo esc_html($gi_season); ?></p>
                            <?php endif; ?>
                            <?php if ($gi_payment_note) : ?>
                                <p class="gi-prices-note"><?php echo esc_html($gi_payment_note); ?></p>
                            <?php endif; ?>
                        </div>
                        <dl class="gi-price-list">
                            <?php foreach ($gi_page['prices']['rows'] as $gi_price) : ?>
                                <div><dt><?php echo esc_html($gi_price['label']); ?></dt><dd><?php echo esc_html($gi_price['value']); ?> <small><?php echo esc_html($gi_page['prices']['unit']); ?></small></dd></div>
                            <?php endforeach; ?>
                        </dl>
                        <div class="gi-prices-actions">
                            <a class="button button-accent" href="<?php echo esc_url($gi_book_url); ?>" data-gi-book><?php echo esc_html($gi_page['cta']); ?> <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                            <?php if ($gi_alt) : ?><a class="gi-hero-link" href="<?php echo esc_url($gi_alt['url']); ?>" data-gi-book><?php echo esc_html($gi_alt['label']); ?></a><?php endif; ?>
                            <a class="gi-hero-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> sau sună-ne la 0721 333 164</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="gi-notes gi-reveal" aria-labelledby="notes-title">
            <div class="container">
                <div class="gi-section-head">
                    <span class="eyebrow">Bine de știut</span>
                    <h2 id="notes-title">Înainte să vii</h2>
                </div>
                <div class="gi-notes-grid gi-swipe">
                    <?php foreach ($gi_page['notes'] as $gi_note) : ?>
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
                <ul class="gi-distances" aria-label="Distanțe">
                    <?php foreach (gi_distances() as $gi_distance) : ?>
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
                    <p>Nu găsești răspunsul? Scrie-ne la <a href="mailto:rezervari@gradina-ielelor.ro">rezervari@gradina-ielelor.ro</a> sau la <a href="mailto:office@gradina-ielelor.ro">office@gradina-ielelor.ro</a>.</p>
                </div>
                <div class="gi-faq-list">
                    <?php foreach ($gi_page['faq'] as $gi_index => $gi_item) : ?>
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
                    <h2 id="related-title">Poate te tentează și acestea</h2>
                </div>
                <div class="gi-related-grid gi-swipe">
                    <?php foreach ($gi_related as $gi_related_key => $gi_item) : ?>
                        <a class="gi-related-card" href="<?php echo esc_url(gi_offer_url($gi_related_key)); ?>">
                            <span class="gi-related-media"><img src="<?php echo esc_url(gi_image_url($gi_item['image'])); ?>" alt=""<?php echo $gi_srcset($gi_item['image'], '(max-width: 700px) 80vw, (max-width: 1240px) 33vw, 400px'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> width="960" height="720" loading="lazy" decoding="async"></span>
                            <span class="gi-related-body">
                                <strong><?php echo esc_html(gi_offers()[$gi_related_key]['label']); ?></strong>
                                <span><?php echo esc_html($gi_item['text']); ?></span>
                            </span>
                            <?php echo gi_icon('arrow-up-right', 'gi-related-arrow'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="gi-closing" aria-labelledby="closing-title">
            <img class="gi-closing-media" src="<?php echo esc_url(gi_image_url($gi_page['closing']['image'])); ?>" alt=""<?php echo $gi_srcset($gi_page['closing']['image'], '100vw'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> width="1280" height="720" loading="lazy" decoding="async">
            <div class="container gi-closing-inner">
                <h2 id="closing-title"><?php echo esc_html($gi_page['closing']['title']); ?><br><em><?php echo esc_html($gi_page['closing']['title_em']); ?></em></h2>
                <div class="gi-closing-actions">
                    <a class="button button-accent" href="<?php echo esc_url($gi_book_url); ?>" data-gi-book><?php echo esc_html($gi_page['cta']); ?> <?php echo gi_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                    <a class="gi-hero-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Sună-ne</a>
                </div>
                <a class="gi-back-link" href="<?php echo esc_url($gi_back_url); ?>"><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Înapoi la rezervare</a>
            </div>
        </section>
    </main>

<?php gi_part('footer', array('mobile_cta' => $gi_page['cta'])); ?>
