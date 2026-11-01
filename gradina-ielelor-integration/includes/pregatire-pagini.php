<?php
/**
 * Unealta „Pregătește paginile” (Setări → Grădina Ielelor): în loc de zeci de clicuri în Pagini,
 * proprietarul apasă câte un buton, în ordine:
 *   1. creează paginile care lipsesc, ca CIORNE, cu șablonul și textul potrivit;
 *   2. pune șabloanele Grădinii pe paginile existente (inclusiv pagina principală);
 *   3. pune textul nou la Contact (cel vechi, Elementor, se păstrează și se poate recupera);
 *   4. publică paginile create de plugin.
 * „Revino” desface tot ce au făcut butoanele (șabloane, text Contact, publicare), fără să șteargă nimic.
 *
 * Nu șterge și nu înlocuiește niciun conținut existent, cu excepția pasului 3 (numai Contact, cu copie de siguranță).
 * Textele paginilor simple vin din content/pagini/*.html (copie a docs/pagini-continut.md, vezi tools/extrage-pagini.py).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Paginile pregătite, după adresă. `file` = text din content/pagini/, `excerpt` = „Rezumat” (apare sub titlu și în SEO).
 */
function gi_pagini_spec() {
    $spec = array();
    foreach (gi_offers() as $offer) {
        $spec[$offer['slug']] = array('title' => $offer['label'], 'template' => $offer['template'], 'file' => '', 'excerpt' => '');
    }
    $spec['despre-noi'] = array(
        'title' => 'Despre noi',
        'template' => 'gi-content.php',
        'file' => 'despre-noi.html',
        'excerpt' => 'Grădina Ielelor: șase hectare de natură la poalele Munților Făgăraș, la Sâmbăta de Sus. Un loc făcut cu grijă, pentru odihnă în natură.',
    );
    $spec['regulament-politici'] = array(
        'title' => 'Regulament & politici',
        'template' => 'gi-content.php',
        'file' => 'regulament-politici.html',
        'excerpt' => 'Regulile Grădinii Ielelor, pe scurt și pe înțeles: sosire și plecare, liniște, foc, animale, anulare cu voucher, termeni, date personale și cookie-uri.',
    );
    $spec['contact'] = array(
        'title' => 'Contact',
        'template' => 'gi-content.php',
        'file' => 'contact.html',
        'excerpt' => 'Ai o întrebare sau vrei să rezervi? Sună-ne la 0721 333 164 sau scrie-ne. Ne găsești la Sâmbăta de Sus, județul Brașov, sub Munții Făgăraș.',
    );
    return $spec;
}

/**
 * Pagina de nivel superior cu adresa dată, în orice stare (inclusiv coșul de gunoi), sau null.
 */
function gi_pagini_find($slug) {
    $found = get_posts(array(
        'post_type' => 'page',
        'name' => $slug,
        'post_parent' => 0,
        'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
        'numberposts' => 1,
        'suppress_filters' => true,
    ));
    if (!$found) {
        // WordPress redenumește pagina mutată în coș în „adresa__trashed”.
        $found = get_posts(array(
            'post_type' => 'page',
            'name' => $slug . '__trashed',
            'post_parent' => 0,
            'post_status' => 'trash',
            'numberposts' => 1,
            'suppress_filters' => true,
        ));
    }
    return $found ? $found[0] : null;
}

/**
 * Adresa e liberă pentru o pagină publicată? WordPress nu verifică unicitatea la ciorne, iar adresa poate fi ocupată
 * de altă pagină sau de un fișier din Media; atunci pagina ar fi publicată la „adresa-2”.
 */
function gi_pagini_adresa_libera($slug, $post_id = 0, $parent = 0) {
    return wp_unique_post_slug($slug, $post_id, 'publish', 'page', $parent) === $slug;
}

/**
 * Blocare scurtă: o dublă apăsare nu rulează două acțiuni deodată. Expiră după 60 de secunde.
 */
function gi_pagini_lock() {
    $acum = time();
    $t = (int) get_option('gi_pagini_lock');
    if ($t && ($acum - $t) < 60) {
        return false;
    }
    if ($t) {
        delete_option('gi_pagini_lock');
    }
    return add_option('gi_pagini_lock', $acum, '', 'no');
}

function gi_pagini_unlock() {
    delete_option('gi_pagini_lock');
}

/**
 * Pagina principală statică (Setări → Citire), sau null dacă nu e setată.
 */
function gi_pagini_home() {
    if ('page' !== get_option('show_on_front')) {
        return null;
    }
    $id = (int) get_option('page_on_front');
    $page = $id ? get_post($id) : null;
    return ($page && 'page' === $page->post_type) ? $page : null;
}

function gi_pagini_content($file) {
    if (!$file) {
        return '';
    }
    $path = GI_PATH . 'content/pagini/' . basename($file);
    return is_readable($path) ? (string) file_get_contents($path) : '';
}

function gi_pagini_tpl($page) {
    $slug = get_page_template_slug($page);
    return $slug ? $slug : 'default';
}

function gi_pagini_set_template($post_id, $template) {
    update_post_meta($post_id, '_wp_page_template', $template);
}

/**
 * 1. Creează paginile care lipsesc, ca ciorne.
 */
function gi_pagini_creeaza() {
    $mesaje = array();
    kses_remove_filters(); // textul se salvează neschimbat (blocuri Detalii, tabele, clase)
    foreach (gi_pagini_spec() as $slug => $s) {
        $page = gi_pagini_find($slug);
        if ($page) {
            $mesaje[] = ('trash' === $page->post_status)
                ? '„' . $s['title'] . '” (/' . $slug . '/) e în coșul de gunoi: restaureaz-o din Pagini → Coș. Nu o creez a doua oară.'
                : '„' . $s['title'] . '” (/' . $slug . '/) există deja: nu o ating.';
            continue;
        }
        $content = gi_pagini_content($s['file']);
        if ($s['file'] && '' === $content) {
            $mesaje[] = 'EROARE: lipsește textul „' . $s['file'] . '” din plugin; „' . $s['title'] . '” nu s-a creat. Reinstalează arhiva completă.';
            continue;
        }
        if (!gi_pagini_adresa_libera($slug)) {
            $mesaje[] = 'EROARE: adresa /' . $slug . '/ e ocupată (de altă pagină sau de un fișier din Media): nu creez „' . $s['title'] . '”. Spune-i lui Claude.';
            continue;
        }
        $id = wp_insert_post(wp_slash(array(
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => $s['title'],
            'post_name' => $slug,
            'post_content' => $content,
            'post_excerpt' => $s['excerpt'],
        )), true);
        if (is_wp_error($id)) {
            $mesaje[] = 'EROARE la „' . $s['title'] . '”: ' . $id->get_error_message();
            continue;
        }
        gi_pagini_set_template($id, $s['template']);
        update_post_meta($id, '_gi_pagina_creata', '1');
        $mesaje[] = 'Creată ciornă: „' . $s['title'] . '” (/' . $slug . '/).';
    }
    kses_init();
    return $mesaje;
}

/**
 * 2. Pune șabloanele Grădinii pe paginile existente; șablonul vechi se reține pentru revenire. Pagina principală, la final.
 */
function gi_pagini_sabloane() {
    $mesaje = array();
    $tinte = array();
    foreach (gi_pagini_spec() as $slug => $s) {
        $tinte[] = array($s['title'] . ' (/' . $slug . '/)', gi_pagini_find($slug), $s['template']);
    }
    $tinte[] = array('Pagina principală', gi_pagini_home(), GI_TEMPLATE_SLUG);
    foreach ($tinte as $t) {
        list($nume, $page, $template) = $t;
        if (!$page) {
            $mesaje[] = $nume . ': nu există încă' . ('Pagina principală' === $nume ? ' (setează pagina statică în Setări → Citire)' : ' (apasă mai întâi pasul 1)') . '.';
            continue;
        }
        if ('trash' === $page->post_status) {
            $mesaje[] = $nume . ': e în coșul de gunoi; o las în pace.';
            continue;
        }
        $curent = gi_pagini_tpl($page);
        if ($curent === $template) {
            $mesaje[] = $nume . ': are deja șablonul potrivit.';
            continue;
        }
        if (!metadata_exists('post', $page->ID, '_gi_sablon_anterior')) {
            update_post_meta($page->ID, '_gi_sablon_anterior', $curent);
        }
        gi_pagini_set_template($page->ID, $template);
        $mesaje[] = $nume . ': șablonul a trecut pe cel nou (înainte: ' . $curent . ').';
    }
    return $mesaje;
}

/**
 * 3. Textul nou la Contact. Textul vechi, rezumatul și modul Elementor se păstrează în câmpuri ale paginii (pentru „Revino”).
 */
function gi_pagini_contact() {
    $s = gi_pagini_spec();
    $s = $s['contact'];
    $page = gi_pagini_find('contact');
    if (!$page || 'trash' === $page->post_status) {
        return array('Contact: nu există (sau e în coș). Apasă mai întâi pasul 1.');
    }
    if (get_post_meta($page->ID, '_gi_pagina_creata', true)) {
        return array('Contact: a fost creată de plugin, cu textul nou. Nu e nimic de înlocuit.');
    }
    if (metadata_exists('post', $page->ID, '_gi_continut_anterior')) {
        return array('Contact: textul nou e deja pus.');
    }
    $content = gi_pagini_content($s['file']);
    if ('' === $content) {
        return array('EROARE: lipsește textul „' . $s['file'] . '” din plugin. Reinstalează arhiva completă.');
    }
    $sablon_vechi = gi_pagini_tpl($page);
    $sablon_salvat_acum = !metadata_exists('post', $page->ID, '_gi_sablon_anterior');
    update_post_meta($page->ID, '_gi_continut_anterior', wp_slash($page->post_content));
    update_post_meta($page->ID, '_gi_rezumat_anterior', wp_slash($page->post_excerpt));
    update_post_meta($page->ID, '_gi_elementor_anterior', wp_slash((string) get_post_meta($page->ID, '_elementor_edit_mode', true)));
    if ($sablon_salvat_acum) {
        update_post_meta($page->ID, '_gi_sablon_anterior', $sablon_vechi);
    }
    gi_pagini_set_template($page->ID, $s['template']); // întâi șablonul nostru: un șablon vechi care nu mai există ar bloca salvarea
    kses_remove_filters();
    $r = wp_update_post(wp_slash(array('ID' => $page->ID, 'post_content' => $content, 'post_excerpt' => $s['excerpt'])), true);
    kses_init();
    if (is_wp_error($r) || !$r) {
        gi_pagini_set_template($page->ID, $sablon_vechi);
        delete_post_meta($page->ID, '_gi_continut_anterior');
        delete_post_meta($page->ID, '_gi_rezumat_anterior');
        delete_post_meta($page->ID, '_gi_elementor_anterior');
        if ($sablon_salvat_acum) {
            delete_post_meta($page->ID, '_gi_sablon_anterior');
        }
        return array('EROARE la Contact: ' . (is_wp_error($r) ? $r->get_error_message() : 'salvarea a eșuat') . '. Nu am schimbat nimic; poți încerca din nou.');
    }
    delete_post_meta($page->ID, '_elementor_edit_mode'); // altfel Elementor ar afișa în continuare designul vechi, nu textul nou
    return array('Contact: textul nou e pus. Textul vechi (Elementor) rămâne păstrat și revine cu „Revino”.');
}

/**
 * 4. Publică paginile create de plugin.
 */
function gi_pagini_publica() {
    $mesaje = array();
    $pagini = get_posts(array('post_type' => 'page', 'post_status' => 'draft', 'meta_key' => '_gi_pagina_creata', 'meta_value' => '1', 'numberposts' => -1, 'suppress_filters' => true));
    if (!$pagini) {
        return array('Nu e nicio ciornă creată de plugin de publicat.');
    }
    foreach ($pagini as $page) {
        if (!gi_pagini_adresa_libera($page->post_name, $page->ID, (int) $page->post_parent)) {
            $mesaje[] = 'EROARE: adresa /' . $page->post_name . '/ e ocupată (de altă pagină sau de un fișier din Media): „' . $page->post_title . '” rămâne ciornă. Spune-i lui Claude.';
            continue;
        }
        $r = wp_update_post(array('ID' => $page->ID, 'post_status' => 'publish'), true);
        if (is_wp_error($r) || !$r) {
            $mesaje[] = 'EROARE la „' . $page->post_title . '”: ' . (is_wp_error($r) ? $r->get_error_message() : 'publicarea a eșuat');
            continue;
        }
        $acum = get_post($page->ID);
        $mesaje[] = 'Publicată: „' . $page->post_title . '” (/' . ($acum ? $acum->post_name : $page->post_name) . '/).';
    }
    return $mesaje;
}

/**
 * Revino: șabloanele vechi, textul vechi la Contact, paginile publicate de plugin trec pe ciornă. Nu șterge nimic.
 */
function gi_pagini_revino() {
    $mesaje = array();
    $toate = array('publish', 'draft', 'pending', 'private', 'future', 'trash');
    $cu_sablon = get_posts(array('post_type' => 'page', 'post_status' => $toate, 'meta_key' => '_gi_sablon_anterior', 'numberposts' => -1, 'suppress_filters' => true));
    foreach ($cu_sablon as $page) {
        $inainte = get_post_meta($page->ID, '_gi_sablon_anterior', true);
        gi_pagini_set_template($page->ID, $inainte ? $inainte : 'default');
        delete_post_meta($page->ID, '_gi_sablon_anterior');
        $mesaje[] = '„' . $page->post_title . '”: șablonul vechi (' . ($inainte ? $inainte : 'default') . ') e înapoi.';
    }
    $cu_text = get_posts(array('post_type' => 'page', 'post_status' => $toate, 'meta_key' => '_gi_continut_anterior', 'numberposts' => -1, 'suppress_filters' => true));
    foreach ($cu_text as $page) {
        $vechi = get_post_meta($page->ID, '_gi_continut_anterior', true);
        $rezumat = get_post_meta($page->ID, '_gi_rezumat_anterior', true);
        $elementor = get_post_meta($page->ID, '_gi_elementor_anterior', true);
        update_post_meta($page->ID, '_gi_continut_nou', wp_slash($page->post_content)); // copie a textului de acum (cu editările tale), păstrată
        kses_remove_filters();
        $r = wp_update_post(wp_slash(array('ID' => $page->ID, 'post_content' => (string) $vechi, 'post_excerpt' => (string) $rezumat)), true);
        kses_init();
        if (is_wp_error($r) || !$r) {
            $mesaje[] = 'EROARE la „' . $page->post_title . '”: nu am putut readuce textul vechi. Nu am șters nimic.';
            continue;
        }
        if ('' !== (string) $elementor) {
            update_post_meta($page->ID, '_elementor_edit_mode', $elementor);
        }
        delete_post_meta($page->ID, '_gi_continut_anterior');
        delete_post_meta($page->ID, '_gi_rezumat_anterior');
        delete_post_meta($page->ID, '_gi_elementor_anterior');
        $mesaje[] = '„' . $page->post_title . '”: textul vechi e înapoi. Copia textului nou (cu editările tale) e păstrată în pagină; spune-i lui Claude dacă vrei s-o recuperezi.';
    }
    $publicate = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'meta_key' => '_gi_pagina_creata', 'meta_value' => '1', 'numberposts' => -1, 'suppress_filters' => true));
    foreach ($publicate as $page) {
        wp_update_post(array('ID' => $page->ID, 'post_status' => 'draft'));
        $mesaje[] = '„' . $page->post_title . '”: a trecut pe ciornă (nu e ștearsă).' . ('regulament-politici' === $page->post_name ? ' ATENȚIE: cât timp Regulamentul e ciornă, caseta cu regulamentul de la plată nu apare.' : '');
    }
    return $mesaje ? $mesaje : array('Nu era nimic de desfăcut.');
}

function gi_pagini_handler() {
    if (!current_user_can('manage_options')) {
        wp_die('Nu ai voie să faci asta.', '', array('response' => 403));
    }
    check_admin_referer('gi_pagini');
    $actiune = isset($_POST['gi_actiune']) ? sanitize_key(wp_unslash($_POST['gi_actiune'])) : '';
    if (!gi_pagini_lock()) {
        set_transient('gi_pagini_rezultat_' . get_current_user_id(), array('Altă acțiune rulează chiar acum. Așteaptă un minut și apasă din nou.'), 300);
        wp_safe_redirect(admin_url('options-general.php?page=gi-integration#gi-pagini'));
        exit;
    }
    switch ($actiune) {
        case 'creeaza':
            $mesaje = gi_pagini_creeaza();
            break;
        case 'sabloane':
            $mesaje = gi_pagini_sabloane();
            break;
        case 'contact':
            $mesaje = gi_pagini_contact();
            break;
        case 'publica':
            $mesaje = gi_pagini_publica();
            break;
        case 'revino':
            $mesaje = gi_pagini_revino();
            break;
        default:
            $mesaje = array('Acțiune necunoscută.');
    }
    gi_pagini_unlock();
    set_transient('gi_pagini_rezultat_' . get_current_user_id(), $mesaje, 300);
    wp_safe_redirect(admin_url('options-general.php?page=gi-integration#gi-pagini'));
    exit;
}
add_action('admin_post_gi_pagini', 'gi_pagini_handler');

function gi_pagini_buton($actiune, $eticheta, $tip = 'secondary') {
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin:0 8px 8px 0;">
        <input type="hidden" name="action" value="gi_pagini">
        <input type="hidden" name="gi_actiune" value="<?php echo esc_attr($actiune); ?>">
        <?php wp_nonce_field('gi_pagini'); ?>
        <?php submit_button($eticheta, $tip, '', false); ?>
    </form>
    <?php
}

/**
 * Secțiunea din Setări → Grădina Ielelor.
 */
function gi_pagini_render() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $cheie = 'gi_pagini_rezultat_' . get_current_user_id();
    $rezultat = get_transient($cheie);
    if ($rezultat) {
        delete_transient($cheie);
    }
    $stari = array('publish' => 'publicată', 'draft' => 'ciornă', 'pending' => 'în așteptare', 'private' => 'privată', 'future' => 'programată', 'trash' => 'în coșul de gunoi');
    $rows = array();
    foreach (gi_pagini_spec() as $slug => $s) {
        $rows[] = array('/' . $slug . '/', gi_pagini_find($slug), $s['template']);
    }
    $rows[] = array('Pagina principală', gi_pagini_home(), GI_TEMPLATE_SLUG);
    $contact = gi_pagini_find('contact');
    ?>
    <h2 id="gi-pagini">Pregătește paginile</h2>
    <p>Apeși butoanele <strong>în ordine</strong>. Nu șterg și nu înlocuiesc nimic din ce ai deja (cu excepția textului de la Contact, care se păstrează și revine cu „Revino”). Paginile noi se creează ca <strong>ciorne</strong>: se văd numai de tine până la pasul 4.</p>
    <?php if ($rezultat) : ?>
        <div class="notice notice-info inline" style="padding:8px 12px;">
            <p><strong>Ce s-a făcut:</strong></p>
            <ul style="list-style:disc;margin-left:20px;">
                <?php foreach ((array) $rezultat as $m) : ?>
                    <li><?php echo esc_html($m); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <table class="widefat striped" style="max-width:900px;margin-bottom:16px;">
        <thead><tr><th>Pagina</th><th>Stare</th><th>Șablon</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r) : ?>
            <?php
            list($nume, $page, $template) = $r;
            if (!$page) {
                $stare = '✗ lipsește';
                $sablon = '—';
            } else {
                $stare = isset($stari[$page->post_status]) ? $stari[$page->post_status] : $page->post_status;
                $stare .= get_post_meta($page->ID, '_gi_pagina_creata', true) ? ' (creată de plugin)' : '';
                $sablon = (gi_pagini_tpl($page) === $template) ? '✓ potrivit' : 'altul (' . gi_pagini_tpl($page) . ')';
            }
            ?>
            <tr><td><?php echo esc_html($nume); ?></td><td><?php echo esc_html($stare); ?></td><td><?php echo esc_html($sablon); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    gi_pagini_buton('creeaza', '1. Creează paginile care lipsesc (ciorne)', 'primary');
    gi_pagini_buton('sabloane', '2. Pune șabloanele pe paginile existente');
    if ($contact && 'trash' !== $contact->post_status && !get_post_meta($contact->ID, '_gi_pagina_creata', true) && !metadata_exists('post', $contact->ID, '_gi_continut_anterior')) {
        gi_pagini_buton('contact', '3. Pune textul nou la Contact');
    }
    gi_pagini_buton('publica', '4. Publică paginile create');
    ?>
    <p class="description">Ordinea la lansare: 1, 2, 3, apoi (după ce te uiți pe ciorne, cu „Previzualizează”) 4. După pasul 2, pagina principală și paginile ofertelor arată deja designul nou, pentru toți vizitatorii.</p>
    <p>
        <?php gi_pagini_buton('revino', 'Revino: șabloanele vechi, textul vechi la Contact, paginile create trec pe ciornă'); ?>
        <br><span class="description">Atenție: „Revino” trece pe ciornă și pagina Regulament &amp; politici. Cât timp e ciornă, caseta cu regulamentul de la plată nu apare.</span>
    </p>
    <?php
}
