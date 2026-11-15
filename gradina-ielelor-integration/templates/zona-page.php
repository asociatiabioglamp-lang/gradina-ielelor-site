<?php
/**
 * „Zona echipei”: partea privată a site-ului (utilizator + parolă), cu intrare din căsuța „Administrare” din subsol.
 * Pagina e virtuală (adresa /zona-echipei/...), nu se află în lista de pagini din WordPress și nu apare în Google.
 * Tot ce se afișează aici vine din GI_Zona::render(): formularul de intrare sau ecranul care se potrivește drepturilor contului.
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_zona_title = GI_Zona::page_title();
$gi_zona_login = !is_user_logged_in();
$gi_zona_lead = $gi_zona_login ? 'Pentru echipa Grădinii: vouchere, copaci și locuri, conturi pentru colegi.' : 'Instrumentele echipei, într-un singur loc.';

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated gi-content-site gi-zona-site'));
gi_part('header');
?>

    <main id="continut">
        <section class="gi-content-hero" aria-labelledby="page-title">
            <?php echo gi_icon('lock', 'gi-content-hero-mark'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="container gi-content-hero-inner">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <?php if ('acasa' !== GI_Zona::section() && !$gi_zona_login) : ?>
                        <a href="<?php echo esc_url(GI_Zona::url()); ?>">Zona echipei</a>
                        <span aria-hidden="true">/</span>
                        <span aria-current="page"><?php echo esc_html($gi_zona_title); ?></span>
                    <?php else : ?>
                        <span aria-current="page">Zona echipei</span>
                    <?php endif; ?>
                </nav>
                <h1 id="page-title"><?php echo esc_html($gi_zona_login ? 'Zona echipei' : $gi_zona_title); ?></h1>
                <p class="gi-content-lead"><?php echo esc_html($gi_zona_lead); ?></p>
            </div>
        </section>

        <div class="container">
            <div class="gi-zona">
                <?php GI_Zona::render(); ?>
            </div>
        </div>
    </main>

<?php
gi_part('footer');
