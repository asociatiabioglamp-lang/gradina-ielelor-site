<?php
/**
 * Pagină simplă (ex. Despre noi, Regulament & politici, Contact): titlul și conținutul scrise în editorul
 * WordPress, între header-ul și footer-ul Grădinii. Proprietarul schimbă textul din editor; template-ul doar îl așază.
 *
 * - „Rezumatul” paginii (excerpt) apare sub titlu și devine descrierea SEO.
 * - Notele pentru editor nu se pun în pagină (docs/pagini-continut.md). Dacă totuși apare un bloc cu clasa
 *   `gi-nota-editor`, GI_Plugin::hide_editor_note() îl ascunde de vizitatori peste tot (pagină, rezumat, căutare, REST).
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_back_url = gi_home_booking_url('');
// Deschis din pagina de plată (link „Deschide regulamentul într-o pagină nouă”): înapoi la finalizarea aceleiași rezervări, nu la o rezervare nouă.
$gi_from_checkout = isset($_GET['din']) && 'plata' === sanitize_key(wp_unslash($_GET['din'])) && function_exists('wc_get_checkout_url'); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- doar alege un link.
if ($gi_from_checkout) {
    $gi_back_url = wc_get_checkout_url();
}

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated gi-content-site'));
gi_part('header');

while (have_posts()) :
    the_post();
    ?>

    <main id="continut">
        <section class="gi-content-hero" aria-labelledby="page-title">
            <?php echo gi_icon('mountain', 'gi-content-hero-mark'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="container gi-content-hero-inner">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page"><?php echo esc_html(get_the_title()); ?></span>
                </nav>
                <h1 id="page-title"><?php echo esc_html(get_the_title()); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="gi-content-lead"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <div class="container">
            <article class="gi-content-body gi-prose">
                <?php the_content(); ?>
            </article>

            <nav class="gi-content-end" aria-label="Mai departe">
                <?php if ($gi_from_checkout) : ?>
                    <p class="gi-content-end-note">Rezervarea ta e păstrată. Poți închide această filă și revii la plată, sau apasă butonul.</p>
                    <a class="button button-primary" href="<?php echo esc_url($gi_back_url); ?>" data-gi-return-checkout><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Înapoi la finalizarea rezervării</a>
                <?php else : ?>
                <a class="button button-primary" href="<?php echo esc_url($gi_back_url); ?>" data-gi-book><?php echo gi_icon('chevron-left'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Înapoi la rezervare</a>
                <?php endif; ?>
                <a class="gi-text-link" href="tel:+40721333164"><?php echo gi_icon('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> sau sună-ne la 0721 333 164</a>
            </nav>
        </div>
    </main>

    <?php
endwhile;

gi_part('footer');
