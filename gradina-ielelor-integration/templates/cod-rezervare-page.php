<?php
/**
 * Pagina /cod-rezervare/<cod>/: codul QR al unei rezervări, mare, de arătat la recepție (pe telefon) sau de printat.
 * Conține numai codul rezervării (fără nume, fără date, fără sumele comenzii). Nu apare în Google.
 */
if (!defined('ABSPATH')) {
    exit;
}

$gi_cod = GI_Rezervari::page_code();

gi_part('document-start', array('shell_class' => 'site-shell gi-site gi-dedicated gi-content-site gi-zona-site gi-cod-site'));
gi_part('header');
?>

    <main id="continut">
        <section class="gi-content-hero" aria-labelledby="page-title">
            <?php echo gi_icon('calendar', 'gi-content-hero-mark'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="container gi-content-hero-inner">
                <nav class="gi-breadcrumb" aria-label="Unde te afli">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Codul rezervării</span>
                </nav>
                <h1 id="page-title">Codul rezervării</h1>
                <p class="gi-content-lead">Arată-l la recepție, la sosire. Conține numai numărul rezervării.</p>
            </div>
        </section>

        <div class="container">
            <div class="gi-zona">
                <div class="gi-zona-card gi-cod-card">
                    <p class="gi-cod-text"><?php echo esc_html($gi_cod); ?></p>
                    <div class="gi-qr-box gi-cod-qr-mare" data-qr-url="<?php echo esc_attr(GI_Rezervari::payload($gi_cod)); ?>" role="img" aria-label="<?php echo esc_attr('Cod QR al rezervării ' . $gi_cod); ?>"></div>
                    <p class="gi-zona-mic">Ține telefonul cu ecranul luminos. Dacă nu se citește, recepția poate scrie codul de mai sus.</p>
                    <p><button type="button" class="button button-outline" onclick="window.print()">Printează</button></p>
                </div>
            </div>
        </div>
    </main>

<?php
gi_part('footer');
