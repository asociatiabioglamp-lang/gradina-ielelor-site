<?php
/**
 * Pagina unui loc din Grădină (copac, plantă, loc de văzut): deschisă de vizitator după ce scanează codul QR din teren.
 * Titlul, poza, rezumatul și textul se scriu în administrare (Grădina → Copaci și locuri); template-ul doar le așază.
 * Adresa paginii (/in-gradina/<nume>/) e cea din codul printat: nu se schimbă după ce codul e în teren.
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_back_url = gi_home_booking_url('');
$gi_from_checkout = gi_from_checkout();
if ($gi_from_checkout) {
    $gi_back_url = wc_get_checkout_url();
}

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated gi-content-site gi-punct-site'));
gi_part('header');

while (have_posts()) :
    the_post();
    $gi_id = get_the_ID();
    $gi_tip = GI_Puncte::type_label($gi_id);
    $gi_stiintific = (string) get_post_meta($gi_id, '_gi_stiintific', true);
    $gi_others = GI_Puncte::others($gi_id, 6);
    ?>

    <main id="continut">
        <section class="gi-content-hero" aria-labelledby="page-title">
            <?php echo gi_icon('mountain', 'gi-content-hero-mark'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="container gi-content-hero-inner">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <span>În Grădină</span>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page"><?php echo esc_html(get_the_title()); ?></span>
                </nav>
                <?php if ('' !== $gi_tip) : ?><p class="eyebrow"><?php echo esc_html($gi_tip); ?></p><?php endif; ?>
                <h1 id="page-title"><?php echo esc_html(get_the_title()); ?></h1>
                <?php if ('' !== $gi_stiintific) : ?><p class="gi-punct-stiintific"><em><?php echo esc_html($gi_stiintific); ?></em></p><?php endif; ?>
                <?php if (has_excerpt()) : ?><p class="gi-content-lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
            </div>
        </section>

        <div class="container">
            <article class="gi-content-body gi-prose gi-punct-body">
                <?php if (has_post_thumbnail()) : ?>
                    <figure class="gi-punct-foto">
                        <?php the_post_thumbnail('large', array('loading' => 'eager', 'decoding' => 'async', 'alt' => (string) get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true))); // textul alternativ îl scrie proprietara la poză; fără el, poza e decorativă ?>
                    </figure>
                <?php endif; ?>
                <?php the_content(); ?>
            </article>

            <?php if ($gi_others) : ?>
                <section class="gi-punct-altele" aria-labelledby="altele-titlu">
                    <h2 id="altele-titlu">Mai multe de văzut în Grădină</h2>
                    <ul class="gi-punct-lista">
                        <?php foreach ($gi_others as $gi_o) : ?>
                            <li>
                                <a href="<?php echo esc_url(get_permalink($gi_o)); ?>">
                                    <?php if (has_post_thumbnail($gi_o)) : ?>
                                        <?php echo get_the_post_thumbnail($gi_o, 'medium_large', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php endif; ?>
                                    <span><?php echo esc_html(get_the_title($gi_o)); ?></span>
                                    <?php $gi_ot = GI_Puncte::type_label($gi_o->ID); ?>
                                    <?php if ('' !== $gi_ot) : ?><small><?php echo esc_html($gi_ot); ?></small><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <nav class="gi-content-end" aria-label="Mai departe">
                <?php if ($gi_from_checkout) : ?>
                    <p class="gi-content-end-note">Rezervarea ta e păstrată. Poți închide această filă și revii la plată, sau apasă butonul.</p>
                    <a class="button button-primary" href="<?php echo esc_url($gi_back_url); ?>" data-gi-return-checkout><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Înapoi la plată</a>
                <?php else : ?>
                    <a class="button button-primary" href="<?php echo esc_url($gi_back_url); ?>" data-gi-book><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Rezervă-ți locul în Grădină</a>
                <?php endif; ?>
                <a class="gi-text-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> sau sună-ne la 0721 333 164</a>
            </nav>
        </div>
    </main>

    <?php
endwhile;

gi_part('footer');
