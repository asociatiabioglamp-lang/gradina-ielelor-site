<?php
/**
 * Doar pentru testul local: activează pluginurile și creează paginile o singură dată.
 * Despre noi, Regulament & politici și Contact folosesc template-ul „Pagină simplă” (gi-content.php),
 * cu textul din docs/pagini-continut.md (marcaj de blocuri, exact ca în editorul WordPress).
 */
add_action('init', function () {
    if (get_option('gi_local_setup_done') === '14') {
        return;
    }
    $active = (array) get_option('active_plugins', array());
    foreach (array('woocommerce/woocommerce.php', 'gradina-ielelor-integration/gradina-ielelor-integration.php') as $plugin) {
        if (!in_array($plugin, $active, true)) {
            $active[] = $plugin;
        }
    }
    update_option('active_plugins', $active);
    update_option('permalink_structure', '/%postname%/');

    $pages = array(
        'acasa' => array('Acasă', 'gi-one-page.php'),
        'glamping' => array('Glamping', 'gi-glamping.php'),
        'bungalow' => array('Bungalow', 'gi-bungalow.php'),
        'camping' => array('Camping', 'gi-camping.php'),
        'festivaluri-si-tabere' => array('Festivaluri & tabere', 'gi-festivaluri.php'),
        'evenimente-private' => array('Evenimente private', 'gi-evenimente-private.php'),
        'despre-noi' => array('Despre noi', 'gi-content.php'),
        'regulament-politici' => array('Regulament & politici', 'gi-content.php'),
        'contact' => array('Contact', 'gi-content.php'),
    );
    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug);
        $id = $existing ? $existing->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page[0], 'post_name' => $slug, 'post_content' => 'Conținut de test.'));
        if ($page[1]) {
            update_post_meta($id, '_wp_page_template', $page[1]);
        }
        if ('acasa' === $slug) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $id);
        }
    }

    // „Festivaluri & evenimente” nu mai există (decizia din 31.10.2026): pagina veche de test trece pe ciornă,
    // iar /evenimente/ duce la Festivaluri & tabere prin redirecționarea pluginului.
    $old_events = get_page_by_path('evenimente');
    if ($old_events && 'gi-evenimente.php' === get_page_template_slug($old_events)) {
        wp_update_post(array('ID' => $old_events->ID, 'post_status' => 'draft'));
    }

    // Textul paginilor simple, salvat neschimbat (fără filtrul kses al vizitatorului neautentificat).
    kses_remove_filters();
    foreach (gi_local_content_pages() as $slug => $page) {
        $existing = get_page_by_path($slug);
        if ($existing) {
            wp_update_post(wp_slash(array('ID' => $existing->ID, 'post_content' => $page['content'], 'post_excerpt' => $page['excerpt'])));
        }
    }
    kses_init();

    flush_rewrite_rules();
    update_option('gi_local_setup_done', '14');
}, 99);

// Paginile legale ale site-ului actual (aceleași adrese ca pe live) și pagina de blog, pentru linkurile din subsol.
add_action('init', function () {
    if (get_option('gi_local_setup_legal') === '1') {
        return;
    }
    $parent = get_page_by_path('info-util');
    $parent_id = $parent ? $parent->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Info util', 'post_name' => 'info-util', 'post_content' => 'Conținut de test.'));
    foreach (array(
        'termeni-si-conditii' => 'Termeni și condiții',
        'politica-de-confidentialitate' => 'Politica de confidențialitate',
        'politica-cookies' => 'Politica cookies',
        'politica-de-anulare' => 'Politica de anulare',
    ) as $slug => $title) {
        if (!get_page_by_path('info-util/' . $slug)) {
            wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_parent' => $parent_id, 'post_content' => 'Conținut de test.'));
        }
    }
    $blog = get_page_by_path('blog');
    $blog_id = $blog ? $blog->ID : wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog', 'post_content' => ''));
    update_option('page_for_posts', $blog_id);
    flush_rewrite_rules();
    update_option('gi_local_setup_legal', '1');
}, 100);

/**
 * Copie a textului din docs/pagini-continut.md (generată din aceleași fișiere, verificate în editorul WordPress).
 */
function gi_local_content_pages() {
    return array(
        'despre-noi' => array(
            'excerpt' => 'Grădina Ielelor: șase hectare de natură la poalele Munților Făgăraș, la Sâmbăta de Sus. Un loc făcut cu grijă, pentru odihnă în natură.',
            'content' => <<<'GI_DESPRE_NOI'
<!-- wp:paragraph {"className":"gi-lead"} -->
<p class="gi-lead">Un cort sub clar de lună. O grădină cu trandafiri. O vatră de foc, ca pe vremuri. O livadă bună de plimbat și un lac pe malul căruia merge o carte bună. Pe scurt: odihnă, natură, liniște. Pe scurt, Grădina Ielelor.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cine suntem</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Suntem gazdele Grădinii. Am construit-o de la primul gând până la ultima cheie. N-a fost ușor, n-a fost repede și n-a fost fără obstacole. Tocmai de aceea ținem atât de mult la ea.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Ne-ar plăcea, măcar o zi, să fim în locul tău: să auzim prima dată de Grădina Ielelor și să venim s-o vedem cu ochii noștri. Nu putem, gazde fiind. Putem, în schimb, să fim gazde bune. Asta îți promitem.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Ce este, de fapt, Grădina</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Un domeniu privat de șase hectare la Sâmbăta de Sus, în județul Brașov, cu Munții Făgăraș în față, împrejmuit și păzit zi și noapte. Mașinile parchează în interior, pe 60.000 de metri pătrați. La festivaluri, domeniul trece în regim de festival, pe nouă hectare. La evenimentele mari, la cerere, parcarea se extinde cu încă 30.000 de metri pătrați, în vecinătate: în total, cam o mie de mașini.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Grădina are trei zone, fiecare cu felul ei de a fi:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><strong>Campingul și lacul mic.</strong> Locuri pentru cort și rulotă, un lac în formă de potcoavă, cu o terasă acoperită din lemn, pe apă, două bucătării comune, hamace și vetre de foc.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Platoul și pavilionul.</strong> Un platou înălțat, cu leagănul infinity și pavilionul de evenimente, care va fi gata la 1 mai 2027.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Lacul mare și scena.</strong> Lacul de 8.000 de metri pătrați, cu ponton, cele patru bungalow-uri de pe mal și scena unde se țin festivalurile.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cum poți sta la noi</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Vii cu <a href="/camping/">cortul tău</a> sau cu <a href="/camping/">rulota</a>. Sau dormi într-un <a href="/glamping/">cort de glamping</a> cu pat, pe platformă de lemn, ori într-un <a href="/bungalow/">bungalow</a> cu baie proprie. Iar dacă pregătești un festival, o tabără sau un eveniment privat, avem loc și pentru asta: <a href="/festivaluri-si-tabere/">festivaluri și tabere</a> sau <a href="/evenimente-private/">evenimente private</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Energie din soare, apă din puțul nostru</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Grădina nu e legată la rețeaua de curent. Energia vine din panouri solare și din GPL. Apa vine din puțul propriu, iar apele uzate trec prin stația de epurare a domeniului.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Pentru tine, asta înseamnă așa: ai curent, apă caldă și internet, dar lasă acasă electrocasnicele mari. Apa de la robinet e pentru duș, spălat și toalete, nu pentru băut. Apa de băut o găsești la dozatoare și îmbuteliată.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Ce găsești aici</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Două lacuri, un ponton și o terasă pe apă.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Opt dușuri publice cu apă caldă, non-stop, și douăsprezece toalete publice. Fiecare dintre cele patru bungalow-uri are baia lui proprie, cu toaletă și duș.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Două bucătării comune.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Treizeci de hamace și vetre de foc.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Un loc de joacă pentru copii, lângă terasa de pe lac.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Livezi, zmeură și mure. Le poți gusta; doar nu le culege la pungă.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Animalele de companie mici sunt binevenite, după <a href="/regulament-politici/">câteva reguli simple</a>.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cum trece timpul aici</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Aici timpul trece altfel. Te întinzi în hamac și te uiți la stele. Stai de vorbă cu vecinul, din ușa cortului. Prinzi răsăritul pe malul lacului. Lucruri mici, care la oraș nu prea se întâmplă.</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>Ce preț poate avea clipa în care te trezești dimineața în parfumul cimbrișorului sau al unei grădini cu trandafiri?</p>
<!-- /wp:paragraph --><cite>Gazdele Grădinii Ielelor</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Prin împrejurimi</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Când vrei să ieși din Grădină, ai multe de văzut aproape:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Mănăstirea Brâncoveanu și Castelul Brâncoveanu, chiar la Sâmbăta de Sus.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Herghelia de la Sâmbăta de Sus, cu cai lipițani.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Cetatea Făgărașului, la 30 de kilometri.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Transfăgărășanul, la cam 20 de minute.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Trasee de drumeție în Munții Făgăraș.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Cel mai bine e să vii să vezi. Te așteptăm.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/#experiente">Alege cum vrei să stai</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/contact/">Scrie-ne</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
GI_DESPRE_NOI
        ),
        'regulament-politici' => array(
            'excerpt' => 'Regulile Grădinii Ielelor, pe scurt și pe înțeles: sosire și plecare, liniște, foc, animale, anulare cu voucher, termeni, date personale și cookie-uri.',
            'content' => <<<'GI_REGULAMENT'
<!-- wp:heading -->
<h2 class="wp-block-heading">Pe scurt</h2>
<!-- /wp:heading -->

<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>Subiect</th><th>Regula</th></tr></thead><tbody><tr><td>Sosire la cazare</td><td>după ora 14:00</td></tr><tr><td>Sosire la camping</td><td>după ora 12:00</td></tr><tr><td>Plecare</td><td>până la ora 12:00</td></tr><tr><td>Ore de odihnă</td><td>24:00 – 08:00</td></tr><tr><td>Animale de companie</td><td>da, până la 15 kg, în lesă</td></tr><tr><td>Anulare</td><td>voucher cu toată suma plătită</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Regulile, pe teme</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Apasă pe o temă ca să vezi detaliile.</p>
<!-- /wp:paragraph -->

<!-- wp:details {"showContent":true} -->
<details class="wp-block-details" open><summary>Rezervare și anulare</summary><!-- wp:paragraph -->
<p>Rezervi și plătești online, direct pe site-ul nostru, cu cardul, inclusiv cu cardurile de vacanță Edenred, Pluxee și Up. Rezervi un tip de loc, nu un loc anume.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tarifele de pe site sunt pentru plata online, cu rezervarea garantată. Dacă alegi plata în locație, tariful e cu 10% mai mare, iar rezervarea nu e garantată.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"gi-caseta"} -->
<div class="wp-block-group gi-caseta"><!-- wp:paragraph -->
<p><strong>Dacă anulezi, banii nu se returnează, dar nici nu se pierd.</strong> Primești un voucher cu toată suma plătită, fără nicio reținere.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Voucherul e valabil până la 31 decembrie a anului următor celui în care ai anulat. De exemplu, dacă anulezi în 2027, îl poți folosi până la 31 decembrie 2028.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Îl folosești pentru o altă rezervare la noi.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Îl poți folosi și parțial: suma nefolosită rămâne pe voucher, până la aceeași dată.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p>Anularea se face pentru toată perioada rezervată, nu pentru o parte din ea. Ca să anulezi, scrie-ne la <a href="mailto:rezervari@gradina-ielelor.ro">rezervari@gradina-ielelor.ro</a> sau sună-ne la <a href="tel:+40721333164">0721 333 164</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Dacă pleci mai devreme decât ai rezervat, nopțile rămase se plătesc integral. Dacă vrei să mai rămâi, te ajutăm cu drag dacă avem loc, dar nu putem garanta prelungirea.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Sosire și plecare</summary><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Cazare (glamping, bungalow): sosirea după ora 14:00.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Camping (cort, rulotă): sosirea după ora 12:00.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Plecarea: până la ora 12:00.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>La sosire te înregistrăm pe baza actului de identitate. Dacă se schimbă numărul de persoane față de rezervare, spune-ne la recepție, ca să trecem schimbarea.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Locurile de camping sunt numerotate. Dacă vrei alt loc, vorbește cu noi la recepție. Pentru un cort în plus sau orice altă schimbare față de ce am stabilit, cere-ne voie înainte.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Un cort sau o rulotă lăsate în camping fără oameni se plătesc ca un loc ocupat de doi adulți.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Liniște și muzică</summary><!-- wp:paragraph -->
<p>Grădina e un loc liniștit, toată ziua. Orele de odihnă sunt între 24:00 și 08:00.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>În spațiile comune asculți muzică doar la căști. Acolo se aude doar muzica pusă de noi.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>La locul tău poți asculta muzică încet, să nu se audă la vecini, până la ora 22:00.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Boxele date tare și orice zgomot care îi deranjează pe ceilalți nu sunt permise.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Foc, grătar și curent</summary><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Focul se face doar în vetrele amenajate și doar cu lemne. Lemnele le primești de la noi, contra cost. Nu se taie și nu se adună lemn din Grădină.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Grătarul e permis până la ora 22:00.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Poți închiria grătar, perie sau topor. Pentru ele lași o garanție de 50 de lei.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Pentru gătit folosești bucătăriile comune și locurile amenajate.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Mucurile de țigară, stinse, merg doar în scrumiere.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Prizele sunt pentru încărcat telefonul, laptopul și alte aparate mici. Aparatele electrice mari rămân acasă.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Tablourile electrice le folosește doar personalul nostru.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Animale de companie</summary><!-- wp:paragraph -->
<p>Animalele de companie sunt binevenite în camping, cu câteva condiții:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>au cel mult 15 kg; câinii sunt acceptați doar de talie mică;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>nu sunt agresive și nu deranjează; rasele considerate agresive nu sunt acceptate;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>stau în lesă și sub ochii tăi;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>strângi după ele.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Animalele nu intră în spațiile comune: terase, băi și toalete, locul de joacă. Ai la tine certificatul de vaccinare și de sănătate al animalului, plus o copie, ca să ni-l arăți dacă ți-l cerem.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Mașini și parcare</summary><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>În Grădină se merge cu cel mult 5 km/h. Cine depășește viteza își încheie sejurul și părăsește Grădina.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>La fiecare loc de cazare sau de camping intră o singură mașină. Rulotele și autorulotele stau pe parcela lor.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Mașina o folosești doar ca să intri și să ieși, nu ca să te plimbi prin Grădină.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Mașinile intră și ies între 8:00 și 23:00. Dacă vii mai târziu, parchezi în afara Grădinii.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>O mașină parcată în alt loc din camping se plătește ca o parcelă de cort pentru două persoane.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Putem refuza accesul unui șofer bănuit că a consumat alcool sau alte substanțe.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Vizitatori</summary><!-- wp:paragraph -->
<p>Prietenii care vin în vizită au nevoie de acordul nostru. O vizită de până la 6 ore se plătește ca o zi. Peste 6 ore, până la 24 de ore, se plătește ca o noapte de cazare.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Copii, lacuri și siguranță</summary><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Copiii sub 14 ani folosesc facilitățile și participă la activități doar sub supravegherea și pe răspunderea părinților. Asta e valabil și la locul de joacă.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>În lacul mare se poate înota: are un metru adâncime peste tot, fără zone adânci. Lacul mic e rezervat pescuitului.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Pescuitul e doar sportiv, pe principiul „prinde și eliberează”. Peștele din lacuri nu se consumă.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Ai grijă la suprafețele care pot aluneca: băi, dușuri, malurile lacurilor.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Pentru lucrurile de valoare avem seif. Nu răspundem pentru lucrurile lăsate nesupravegheate în corturi sau în spațiile comune.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Grădina e natură adevărată: insecte, plante, vreme schimbătoare. Plimbările și sportul le faci pe propria răspundere.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Nu răspundem pentru efectele calamităților naturale sau ale lucrărilor care pot bloca drumul ori alimentarea cu apă și curent.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Natura Grădinii</summary><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Nu rupe plante, flori sau arbuști și nu tăia copaci.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Fructele din livadă se gustă, nu se culeg.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Gunoiul îl sortezi în coșurile noastre sau în saci, pe care îi ridicăm noi.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Nu spăla vase, haine sau mașini la robinetele pentru udat pomii. Apa murdară strică pomii.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Toaleta chimică și apele gri de la rulotă se golesc doar în locul amenajat.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Nu te cățăra în copaci.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Ce nu e permis</summary><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>armele, spray-urile lacrimogene, drogurile și orice substanțe interzise;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>agresivitatea, verbală sau fizică, față de ceilalți oaspeți;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>ura și discriminarea de orice fel;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>jocurile cu obiecte ascuțite, contondente sau inflamabile;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>focul în afara vetrelor;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>folosirea prizelor, instalațiilor și echipamentelor rezervate personalului, fără acordul nostru;</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>ținuta care contravine bunelor moravuri.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Stricăciunile se plătesc. Dacă cineva încalcă regulile, putem încheia sejurul și îi putem cere să plece, fără un al doilea avertisment și fără restituirea sumelor plătite. Putem cere și despăgubiri.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Termeni și condiții de folosire a site-ului</summary><!-- wp:paragraph -->
<p>Folosind site-ul gradina-ielelor.ro, accepți acești termeni. Pe scurt:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Textele, fotografiile și celelalte materiale de pe site ne aparțin nouă sau partenerilor noștri. Le poți vedea, tipări și trimite mai departe doar în scop personal, necomercial. Pentru orice altă folosire îți trebuie acordul nostru scris.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Facem tot ce putem ca informațiile să fie corecte și la zi, dar se pot strecura greșeli. Tarifele de pe site sunt orientative: prețul final îl vezi la rezervare, înainte de plată.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Nu putem garanta că site-ul merge mereu fără întreruperi. Putem schimba sau opri temporar unele servicii.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Nu e permis să încerci să modifici conținutul site-ului, să-i afectezi funcționarea sau să folosești adresele de email de pe el pentru mesaje nesolicitate.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Pentru site-urile la care trimit linkurile noastre nu răspundem; acolo se aplică regulile lor.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Putem actualiza acești termeni. Versiunea în vigoare este mereu cea de pe această pagină.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p><strong>Reclamații.</strong> Ne poți trimite o reclamație pe email, la <a href="mailto:office@gradina-ielelor.ro">office@gradina-ielelor.ro</a>, în cel mult 7 zile de la întâmplarea reclamată. Spune-ne cine ești, ce s-a întâmplat și, dacă e cazul, linkul la care te referi. Îți răspundem în cel mult 30 de zile lucrătoare. Dacă lipsesc informații, te anunțăm, iar termenul se prelungește corespunzător.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Legea aplicabilă.</strong> Se aplică legea română. Eventualele litigii se soluționează de instanțele competente din România.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Datele tale personale</summary><!-- wp:paragraph -->
<p>Avem grijă de datele tale și le folosim doar pentru ce e nevoie.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><strong>Ce date:</strong> nume și prenume, CNP, adresă, email și telefon.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>De ce:</strong> ca să-ți gestionăm rezervarea și plata, să-ți emitem factura și să-ți răspundem la mesaje și reclamații. Dacă ești de acord, îți trimitem și noutăți despre Grădină și evenimente. Folosim și statistici, ca să ne îmbunătățim serviciile.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Cine le vede:</strong> doar oamenii noștri și furnizorii care ne ajută: email, rezervări și plăți online, facturare și contabilitate, servicii juridice, curierat, SMS. Autorităților le comunicăm date doar când legea o cere.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Cât timp:</strong> cât durează relația noastră și apoi o perioadă rezonabilă, cel puțin cât termenul general de prescripție.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Drepturile tale:</strong> să afli ce date avem despre tine, să le corectezi, să ceri ștergerea sau restricționarea lor, să le primești într-un format pe care îl poți duce în altă parte, să te opui prelucrării, să nu fii supus unei decizii luate doar automat și să-ți retragi oricând acordul. Poți depune plângere la Autoritatea Națională de Supraveghere a Prelucrării Datelor cu Caracter Personal (ANSPDCP).</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Pentru orice cerere despre datele tale, scrie-ne la <a href="mailto:office@gradina-ielelor.ro">office@gradina-ielelor.ro</a> sau prin poștă, la sediul firmei. Răspunsul e gratuit. Doar cererile vădit nefondate sau repetate pot fi taxate ori refuzate.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Cookie-uri</summary><!-- wp:paragraph -->
<p>Cookie-urile sunt fișiere mici pe care site-ul le salvează în browserul tău. Unele sunt necesare ca site-ul să funcționeze, de exemplu pentru rezervare. Altele ne ajută să numărăm vizitele, să ținem minte preferințele tale sau să-ți arătăm reclame potrivite.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><strong>Esențiale:</strong> fără ele site-ul nu merge corect, așa că nu pot fi oprite.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>De trafic:</strong> ne arată câți vizitatori avem și cum folosesc site-ul.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>De funcționalitate:</strong> țin minte preferințe, de exemplu limba.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>De marketing:</strong> ne ajută să-ți arătăm reclame potrivite, uneori prin parteneri precum Google sau Facebook.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Le poți șterge sau bloca din setările browserului. Dacă le blochezi, unele părți ale site-ului pot să nu mai meargă.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Cine suntem</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Site-ul gradina-ielelor.ro este administrat de AGRICOLA MEDICINALE 2002 SRL, cu sediul în Sâmbăta de Sus nr. 588, comuna Sâmbăta de Sus, județul Brașov. CUI 33073262, nr. de înregistrare J08/641/2014. Email <a href="mailto:office@gradina-ielelor.ro">office@gradina-ielelor.ro</a>, telefon <a href="tel:+40721333164">0721 333 164</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Protecția consumatorului</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><a href="https://anpc.ro/">ANPC, Autoritatea Națională pentru Protecția Consumatorilor</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="https://anpc.ro/categorie/1271/sal">ANPC, Soluționarea alternativă a litigiilor (SAL)</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="https://ec.europa.eu/consumers/odr/main/index.cfm?event=main.home2.show&amp;lng=RO">Comisia Europeană, Soluționarea online a litigiilor (SOL / ODR)</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Ai o nelămurire? Scrie-ne la <a href="mailto:office@gradina-ielelor.ro">office@gradina-ielelor.ro</a> sau sună-ne la <a href="tel:+40721333164">0721 333 164</a>. Preferăm oricând să lămurim lucrurile direct, între noi.</p>
<!-- /wp:paragraph -->
GI_REGULAMENT
        ),
        'contact' => array(
            'excerpt' => 'Ai o întrebare sau vrei să rezervi? Sună-ne la 0721 333 164 sau scrie-ne. Ne găsești la Sâmbăta de Sus, județul Brașov, sub Munții Făgăraș.',
            'content' => <<<'GI_CONTACT'
<!-- wp:heading -->
<h2 class="wp-block-heading">Sună-ne</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Cel mai repede ne găsești la telefon: <a href="tel:+40721333164"><strong>0721 333 164</strong></a>.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="tel:+40721333164">Sună acum</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Scrie-ne</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><strong>Rezervări și întrebări despre sejur:</strong> <a href="mailto:rezervari@gradina-ielelor.ro">rezervari@gradina-ielelor.ro</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Pentru orice altceva:</strong> <a href="mailto:office@gradina-ielelor.ro">office@gradina-ielelor.ro</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Ne găsești și pe <a href="https://www.facebook.com/IELELEgradina">Facebook</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Unde suntem</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Grădina Ielelor<br>Sâmbăta de Sus, județul Brașov<br>la poalele Munților Făgăraș</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.google.com/maps/search/?api=1&amp;query=Gradina+Ielelor+Sambata+de+Sus" target="_blank" rel="noreferrer noopener">Deschide în Google Maps</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Cât faci până la noi</h3>
<!-- /wp:heading -->

<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>De la Grădină până la</th><th>Distanța</th></tr></thead><tbody><tr><td>Transfăgărășan</td><td>cca. 20 min</td></tr><tr><td>Făgăraș</td><td>30 km</td></tr><tr><td>Brașov</td><td>cca. 60 km</td></tr><tr><td>Sibiu</td><td>cca. 70 km</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Când ajungi</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Cazarea te așteaptă de la ora 14:00, iar locul de camping de la ora 12:00. Plecarea e până la ora 12:00. Restul regulilor, pe scurt, le găsești în <a href="/regulament-politici/">Regulament și politici</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Organizezi un festival, o tabără sau un eveniment privat?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Pentru festivaluri, tabere, retreaturi și ieșiri cu echipa avem o pagină dedicată organizatorilor, cu fișa tehnică a domeniului și cererea de ofertă. Pentru nunți, botezuri, majorate sau alte petreceri de familie, sună-ne sau scrie-ne la adresele de mai sus: câți invitați veți fi, ce perioadă ai în minte și ce fel de eveniment faci.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/festival/">Vezi pagina pentru organizatori</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph -->
<p>Datele firmei sunt în pagina <a href="/regulament-politici/">Regulament și politici</a>.</p>
<!-- /wp:paragraph -->
GI_CONTACT
        ),
    );
}
