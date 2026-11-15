<?php
/**
 * Pagina /in-gradina/harta/: harta și ghidul Grădinii. Planul cu toate locurile marcate (dacă proprietara l-a încărcat), apoi lista paginilor:
 * informații (ce poți vizita, parteneri...), locuri (bucătărie, terasă, Wi-Fi, joacă...) și copaci și plante. Textele le schimbă echipa oricând.
 * Cine vine dintr-un cod QR (?aici=numele-locului) vede locul lui marcat pe plan.
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_aici = isset($_GET['aici']) ? sanitize_title(wp_unslash($_GET['aici'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$gi_curent = $gi_aici ? get_page_by_path($gi_aici, OBJECT, GI_Puncte::TYPE) : null;
$gi_curent_id = $gi_curent && 'publish' === $gi_curent->post_status ? (int) $gi_curent->ID : 0;
$gi_posts = get_posts(array('post_type' => GI_Puncte::TYPE, 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC'));
$gi_grupe = array('info' => array('Informații', array()), 'locuri' => array('Locuri și facilități', array()), 'natura' => array('Copaci și plante', array()));
foreach ($gi_posts as $gi_p) {
    $gi_grupe[GI_Puncte::type_group((string) get_post_meta($gi_p->ID, '_gi_tip', true))][1][] = $gi_p;
}
$gi_harta = GI_Harta::render($gi_curent_id, false);

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated gi-content-site gi-harta-site'));
gi_part('header');
?>

    <main id="continut">
        <section class="gi-content-hero" aria-labelledby="page-title">
            <?php echo gi_icon('mountain', 'gi-content-hero-mark'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="container gi-content-hero-inner">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Harta și ghidul Grădinii</span>
                </nav>
                <h1 id="page-title">Harta și ghidul Grădinii</h1>
                <p class="gi-content-lead">Ce găsești în Grădina Ielelor și unde. Apasă pe un loc ca să-l deschizi.</p>
            </div>
        </section>

        <div class="container">
            <div class="gi-harta-pagina">
                <?php if ('' !== $gi_harta) : ?>
                    <?php if ($gi_curent_id) : ?><p class="gi-aici-text"><strong>Ești aici:</strong> <?php echo esc_html(get_the_title($gi_curent_id)); ?></p><?php endif; ?>
                    <?php echo $gi_harta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>

                <?php foreach ($gi_grupe as $gi_g) : if (!$gi_g[1]) { continue; } ?>
                    <section class="gi-ghid-grupa" aria-labelledby="<?php echo esc_attr('ghid-' . sanitize_title($gi_g[0])); ?>">
                        <h2 id="<?php echo esc_attr('ghid-' . sanitize_title($gi_g[0])); ?>"><?php echo esc_html($gi_g[0]); ?></h2>
                        <ul class="gi-ghid-lista">
                            <?php foreach ($gi_g[1] as $gi_p) : ?>
                                <li>
                                    <a href="<?php echo esc_url(get_permalink($gi_p)); ?>">
                                        <span><?php echo esc_html(get_the_title($gi_p)); ?></span>
                                        <?php $gi_t = GI_Puncte::type_label($gi_p->ID); ?>
                                        <?php if ('' !== $gi_t) : ?><small><?php echo esc_html($gi_t); ?></small><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>

                <?php if (!$gi_posts) : ?>
                    <p>Ghidul se completează: în curând găsești aici ce poți vizita și unde.</p>
                <?php endif; ?>
            </div>

            <nav class="gi-content-end" aria-label="Mai departe">
                <a class="button button-primary" href="<?php echo esc_url(gi_home_booking_url('')); ?>">Rezervă-ți locul în Grădină</a>
            </nav>
        </div>
    </main>

<?php
gi_part('footer');
