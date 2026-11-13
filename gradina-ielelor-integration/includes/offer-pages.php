<?php
/**
 * Conținutul paginilor dedicate (Bungalow, Glamping, Camping & rulote, Festivaluri & tabere, Evenimente private).
 * Fapte din prezentările proprietarului, textele pregătite pentru WordPress și catalogul public;
 * tarifele sunt referință, prețul final vine din sistemul de rezervări.
 * Nu se adaugă fapte neconfirmate (vezi docs/decizii.md și CLAUDE.md).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Distanțele, comune tuturor paginilor.
 */
function gi_distances() {
    return array(
        array('place' => 'Transfăgărășan', 'value' => 'cca. 20 min'),
        array('place' => 'Făgăraș', 'value' => '30 km'),
        array('place' => 'Brașov', 'value' => 'cca. 60 km'),
        array('place' => 'Sibiu', 'value' => 'cca. 70 km'),
    );
}

/**
 * Datele unei pagini dedicate sau null dacă cheia nu există.
 *
 * Structură: title, booking_type ('' = fără preselectare), cta, hero{image, alt, h1, h1_em, lead},
 * facts[], story{eyebrow, title, title_em, paragraphs[], image, alt}, gallery{title, images[]},
 * included{title, groups[{icon, title, items[], note?{text, link_text, link_offer}}]},
 * reviews{title, authors[]}, prices{title, text, unit, rows[]} sau null, notes[], faq[],
 * closing{title, title_em, image}, seo{title, description, image}.
 * Opționale: cta_url (acțiune principală diferită de rezervare), phone (true = telefonul lângă butonul din hero),
 * calendar{title, text} (afișează gi_events_2027()), cta_alt{label, booking_type | url} (al doilea buton: rezervare sau o adresă).
 */
function gi_offer_page($key) {
    $pages = gi_offer_pages();
    return isset($pages[$key]) ? $pages[$key] : null;
}

/**
 * Spațiile comune sunt ale tuturor oaspeților, la orice ofertă (glamping, camping și rulote, bungalow, festivaluri, evenimente).
 * Pozele se repetă pe fiecare pagină de ofertă, după pozele specifice locului. Sunt din Drive, folderul „GI”.
 */
function gi_common_facilities() {
    $data = array(
        'title' => 'Spațiile comune, ale tuturor',
        'intro' => 'Oricare ai alege, ai acces la ele: băile, bucătăriile, vetrele de foc, locul de joacă, terasele și lacul.',
        'images' => array(
            array('src' => 'spatii-lac-ponton-960.webp', 'alt' => 'Lacul mare cu un ponton de lemn, oglindind munții și cerul', 'caption' => 'Lacul mare și pontonul'),
            array('src' => 'spatii-pasarela-lac-960.webp', 'alt' => 'Pasarelă de lemn peste lac, cu stâlpi de lemn și frânghii', 'caption' => 'Pasarela peste lac'),
            array('src' => 'terasa-lemn-interior-960.webp', 'alt' => 'Terasa acoperită din lemn, cu fotolii-puf și scaune suspendate', 'caption' => 'Terasa de pe lac, pe dinăuntru'),
            array('src' => 'spatii-terasa-metal-960.webp', 'alt' => 'Terasă acoperită cu structură metalică, cu mese și scaune, pe o punte de lemn', 'caption' => 'Terasa metalică'),
            array('src' => 'spatii-joaca-nisip-960.webp', 'alt' => 'Loc de joacă pentru copii, cu o cutie cu nisip și leagăne, la apus', 'caption' => 'Locul de joacă, cu nisip și leagăne'),
            array('src' => 'spatii-joaca-copii-960.webp', 'alt' => 'Copii care aleargă pe pajiște, lângă leagăne și fotolii-puf, sub sălcii', 'caption' => 'Locul de joacă, pe pajiște'),
            array('src' => 'vatra-seara-960.webp', 'alt' => 'Vetre de foc cu bănci și mese, sub ghirlande de lumini, la apus', 'caption' => 'Vetrele de foc, seara'),
            array('src' => 'vatra-ziua-960.webp', 'alt' => 'Vatră de foc din pietre, cu bănci din lemn, sub un cer luminos', 'caption' => 'Vatră de foc, ziua'),
            array('src' => 'bucatarie-comuna-960.webp', 'alt' => 'Bucătărie în aer liber, sub o pergolă de lemn, cu tejghea și taburete din buturugi', 'caption' => 'Bucătăria din exterior'),
            array('src' => 'spatii-bucatarie-interior-960.webp', 'alt' => 'Bucătărie interioară, cu aragaz, blat de lucru și masă cu bănci', 'caption' => 'Bucătăria din interior'),
            array('src' => 'spatii-bai-exterior-960.webp', 'alt' => 'Clădirea băilor, cu chiuvete în aer liber pe un perete alb', 'caption' => 'Băile'),
            array('src' => 'spatii-dusuri-960.webp', 'alt' => 'Cabine de duș cu perdele albe și pereți placați cu piatră', 'caption' => 'Dușurile'),
        ),
    );

    return apply_filters('gi_common_facilities', $data);
}

function gi_offer_pages() {
    $experiences = gi_experiences();
    $pages = array();

    $pages['glamping'] = array(
        'common_intro' => 'Dormi în glamping, iar spațiile comune sunt și ale tale: băile, bucătăriile, vetrele de foc, locul de joacă, terasele și lacul.',
        'title' => 'Glamping',
        'booking_type' => 'glamping',
        'cta' => 'Verifică disponibilitatea',
        'hero' => array(
            'image' => 'glamping-hero-platforme-1280.webp',
            'alt' => 'Corturi de glamping pe platforme de lemn, într-o zi senină',
            'h1' => 'Glamping în',
            'h1_em' => 'Grădina Ielelor',
            'lead' => 'Confort și intimitate în natură, la poalele Munților Făgăraș. Nu ai nevoie de echipament ca să dormi la munte.',
        ),
        'facts' => array(
            array('value' => '4', 'label' => 'corturi cu pat'),
            array('value' => '2 + 1', 'label' => 'persoane, cu pat suplimentar'),
            array('value' => '20 min', 'label' => 'până la Transfăgărășan'),
            array('value' => 'de la 195 lei', 'label' => 'pe noapte'),
        ),
        'story' => array(
            'eyebrow' => 'Cum e să dormi aici',
            'title' => 'Natura, cu confort.',
            'title_em' => 'Fără bagaj de expediție.',
            'paragraphs' => array(
                'Te culci comod, te trezești cu muntele în față și ai timp doar pentru tine.',
                'Ziua ai la dispoziție două lacuri, unul în care se înoată, și o terasă acoperită din lemn, construită pe apă. Seara, focul se aprinde în vetrele amenajate, iar hamacele sunt la câțiva pași.',
            ),
            'image' => 'glamping-platforma-scaune-960.webp',
            'alt' => 'Cort de glamping pe platformă de lemn, cu două scaune, o măsuță și un covor la intrare',
        ),
        'gallery' => array('title' => 'Vezi exact unde dormi', 'images' => $experiences['glamping']['images']),
        'included' => array(
            'title' => 'Tot ce-ți trebuie, nimic în plus',
            'groups' => array(
                array('icon' => 'tent', 'title' => 'În cortul tău', 'items' => array(
                    'Pat adevărat, cu lenjerie',
                    'Covor și noptieră',
                    'Platformă de lemn, ridicată de la sol',
                    'Masă și scaune la intrare',
                    'Pat suplimentar, la cerere',
                )),
                array('icon' => 'trees', 'title' => 'În Grădină', 'items' => array(
                    'Opt dușuri publice, cu apă caldă non-stop',
                    'Douăsprezece toalete publice',
                    'Două bucătării comune',
                    'Două lacuri, unul în care se înoată',
                    'Terasă acoperită din lemn, construită pe apă',
                    'Treizeci de hamace și vetre de foc',
                    'Internet și parcare proprie',
                    'Domeniu împrejmuit, pază 24 din 24',
                ), 'note' => array('text' => 'Băile sunt comune. Dacă vrei baie proprie, ', 'link_text' => 'alege un bungalow', 'link_offer' => 'bungalow')),
            ),
        ),
        'reviews' => array('title' => 'De la cei care au dormit aici', 'authors' => array('Ada-Casandra Ștefan', 'Mihaela Mocanu')),
        'prices' => array(
            'title' => 'Simplu, fără surprize',
            'text' => 'Prețul final îl vezi când alegi datele, înainte să plătești.',
            'unit' => '/ noapte',
            'rows' => array(
                array('label' => 'Nopți în timpul săptămânii', 'value' => 'de la 195 lei'),
                array('label' => 'Vineri și sâmbătă', 'value' => '235 lei'),
                array('label' => 'Pat suplimentar', 'value' => '55 lei'),
            ),
        ),
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoare și îmbuteliată.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Ai curent, apă caldă și internet. Te rugăm doar să lași acasă electrocasnicele mari.'),
            array('icon' => 'flame', 'title' => 'Focul, doar în vetrele amenajate', 'text' => 'Avem vetre de foc pregătite pe domeniu. Seara, acolo se adună toată lumea.'),
            array('icon' => 'calendar', 'title' => 'Sunt patru glamping-uri permanente', 'text' => 'Rezervă din timp, mai ales pentru weekendurile de vară. Pentru grupuri și evenimente, numărul se poate extinde la cerere.'),
        ),
        'faq' => array(
            array('q' => 'Câte glamping-uri aveți?', 'a' => 'Patru glamping-uri permanente, pe platformă, cu pat și mobilier în interior. La cerere și la evenimente putem extinde până la 50 de glamping-uri.'),
            array('q' => 'Ce găsesc în cort?', 'a' => 'Un pat cu lenjerie, covor și noptieră. Cortul stă pe o platformă de lemn, iar la intrare ai o masă cu scaune. Nu e camping de supraviețuire.'),
            array('q' => 'Câte persoane încap într-un cort?', 'a' => 'Două persoane. Dacă mai vine cineva, poți adăuga un pat suplimentar când faci rezervarea.'),
            array('q' => 'Baia e în cort?', 'a' => 'Nu, băile sunt comune: opt dușuri publice cu apă caldă, disponibile oricând, și douăsprezece toalete publice. Baie proprie, cu toaletă și duș, au doar bungalow-urile. Dacă vrei baie proprie, alege un bungalow.'),
            array('q' => 'Am curent și internet?', 'a' => 'Da. Domeniul funcționează pe energie proprie, solară și GPL, așa că ai curent, apă caldă și internet. Lasă acasă doar aparatele electrice mari.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'De la 195 lei în timpul săptămânii și 235 lei vineri și sâmbătă. Patul suplimentar costă 55 lei pe noapte. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de o noapte', 'title_em' => 'sub cerul Făgărașului?', 'image' => 'closing-glamping-platforme-1280.webp'),
        'seo' => array(
            'title' => 'Glamping Brașov, sub Munții Făgăraș | Grădina Ielelor',
            'description' => 'Corturi de glamping cu pat adevărat, pe platformă de lemn, în Sâmbăta de Sus, la poalele Munților Făgăraș. Două lacuri, dușuri calde non-stop. Rezervă online.',
            'image' => 'glamping-hero-platforme-1280.webp',
        ),
    );

    $pages['bungalow'] = array(
        'common_intro' => 'Bungalow-ul are baie și toaletă proprii. Spațiile comune sunt și ale tale, la fel ca ale tuturor oaspeților.',
        'title' => 'Bungalow',
        'booking_type' => 'bungalow',
        'cta' => 'Verifică disponibilitatea',
        'hero' => array(
            'image' => 'bungalow-hero-cer-1600.webp',
            'alt' => 'Bungalow cu terasă, sub un cer albastru, în Grădina Ielelor',
            'h1' => 'Bungalow în',
            'h1_em' => 'Grădina Ielelor',
            'lead' => 'Pereți, baie proprie și încălzire, pe malul lacului mare. Pentru cine vrea intimitate la poalele Munților Făgăraș.',
        ),
        'facts' => array(
            array('value' => '4', 'label' => 'bungalow-uri, fiecare cu baie și duș propriu'),
            array('value' => '8.000 mp', 'label' => 'lacul mare, în care se înoată'),
            array('value' => '20 min', 'label' => 'până la Transfăgărășan'),
            array('value' => 'de la 350 lei', 'label' => 'pe noapte'),
        ),
        'story' => array(
            'eyebrow' => 'Cum e să stai aici',
            'title' => 'Un spațiu doar al tău.',
            'title_em' => 'Natura rămâne la ușă.',
            'paragraphs' => array(
                'Ai dormitor, zonă de relaxare, baie proprie și încălzire. Ieși pe terasă și ești deja în natură.',
                'Bungalow-urile stau pe malul lacului mare, care are 8.000 de metri pătrați și un metru adâncime. Se înoată în el. Cea mai apropiată casă e la circa doi kilometri, așa că seara se aude apa și atât.',
            ),
            'image' => 'bungalow-lac-baldachin-1280.webp',
            'alt' => 'Lacul cu reflexii, pontonul și Munții Făgăraș, văzute de lângă baldachinul de pe mal',
        ),
        // Fotografiile alese de proprietară (3.11.2026, din Drive, folderul „GI”): bungalow-ul sub cer senin, malul lacului, interiorul.
        'gallery' => array('title' => 'Vezi unde te cazezi', 'images' => array(
            array('src' => 'bungalow-malul-lacului-960.webp', 'alt' => 'Malul lacului, cu bungalow-uri și un baldachin alb, sub cer și munți', 'caption' => 'Bungalow-urile, pe malul lacului'),
            array('src' => 'bungalow-dormitor-alb-960.webp', 'alt' => 'Dormitor cu două paturi albe într-un bungalow, cu jaluzele de lemn', 'caption' => 'Dormitorul'),
            array('src' => 'bungalow-relaxare-720.webp', 'alt' => 'Zona de relaxare dintr-un bungalow, cu canapea, covor și paravan de lemn', 'caption' => 'Zona de relaxare'),
            array('src' => 'bungalow-baie-720.webp', 'alt' => 'Baia unui bungalow, cu chiuvetă pe suport de lemn și oglindă', 'caption' => 'Baie proprie'),
            array('src' => 'evenimente-lac-sup-960.webp', 'alt' => 'Plăci SUP pe lac, cu Munții Făgăraș în fundal, sub cer albastru', 'caption' => 'Pe lac, cu plăci SUP'),
        )),
        'included' => array(
            'title' => 'Tot ce-ți trebuie, nimic în plus',
            'groups' => array(
                array('icon' => 'mountain', 'title' => 'În bungalow-ul tău', 'items' => array(
                    'Baie proprie, doar pentru tine',
                    'Duș propriu',
                    'Calorifere',
                    'Dormitor și zonă de relaxare',
                    'Terasă proprie, pe malul lacului',
                )),
                array('icon' => 'trees', 'title' => 'În Grădină', 'items' => array(
                    'Zonă privată pentru bungalow-uri, cu acces privat la lac',
                    'Două bucătării comune, dotate și utilate',
                    'Ponton pe lacul mare, terasă pe lacul mic',
                    'Două lacuri, unul în care se înoată',
                    'Terasă acoperită din lemn, construită pe apă',
                    'Treizeci de hamace și vetre de foc, în zona special amenajată',
                    'Pescuit sportiv',
                    'Wi-Fi gratuit, inclusiv în zona bungalow-urilor',
                    'Parcare proprie',
                    'Domeniu împrejmuit, cu iluminat pe perimetru noaptea și pază 24 din 24',
                ), 'note' => array('text' => 'Vrei să dormi în cort, dar pe un pat adevărat? ', 'link_text' => 'Alege glampingul', 'link_offer' => 'glamping')),
            ),
        ),
        'reviews' => array('title' => 'Ce spun oaspeții', 'authors' => array()),
        'prices' => array(
            'title' => 'Simplu, fără surprize',
            'text' => 'Prețul final îl vezi când alegi datele, înainte să plătești.',
            'unit' => '/ noapte',
            'rows' => array(
                array('label' => 'Nopți în timpul săptămânii', 'value' => 'de la 350 lei'),
                array('label' => 'Vineri și sâmbătă', 'value' => '450 lei'),
            ),
        ),
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoare și îmbuteliată.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Ai curent, apă caldă și internet. Te rugăm doar să lași acasă electrocasnicele mari.'),
            array('icon' => 'calendar', 'title' => 'Sunt doar patru bungalow-uri', 'text' => 'Rezervă din timp, mai ales pentru weekendurile de vară și cât timp e deschis Transfăgărășanul.'),
        ),
        'faq' => array(
            array('q' => 'Ce găsesc în bungalow?', 'a' => 'Un dormitor, o zonă de relaxare și baie proprie. Ai încălzire și terasă, iar bungalow-ul stă pe malul lacului mare.'),
            array('q' => 'Baia e doar a mea?', 'a' => 'Da. Fiecare dintre cele patru bungalow-uri are baia și dușul lui, așa că nu împarți nimic cu nimeni.'),
            array('q' => 'Am acces la lac?', 'a' => 'Da. Bungalow-urile au o zonă privată în Grădină, cu acces privat la lac, plus terasa proprie pe malul lacului.'),
            array('q' => 'Pot să gătesc?', 'a' => 'Da, în cele două bucătării comune ale Grădinii. Sunt la dispoziția tuturor oaspeților.'),
            array('q' => 'Am curent și internet?', 'a' => 'Da. Domeniul funcționează pe energie proprie, solară și GPL, așa că ai curent, apă caldă și internet. Lasă acasă doar aparatele electrice mari.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'De la 350 lei în timpul săptămânii și 450 lei vineri și sâmbătă. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de o noapte', 'title_em' => 'pe malul lacului?', 'image' => 'closing-bungalow-sori-1280.webp'),
        'seo' => array(
            'title' => 'Bungalow pe malul lacului, Brașov | Grădina Ielelor',
            'description' => 'Patru bungalow-uri cu baie proprie și încălzire, pe malul lacului din Grădina Ielelor, la poalele Munților Făgăraș. Vezi pozele și verifică disponibilitatea.',
            'image' => 'bungalow-hero-cer-1600.webp',
        ),
    );

    $pages['camping'] = array(
        'common_intro' => 'Cortul sau rulota sunt locul tău. Spațiile comune sunt ale tuturor, deci și ale tale.',
        'title' => 'Camping & rulote',
        'booking_type' => 'camping-cort',
        'header_type' => '', // butonul din header și bara mobilă: formularul fără tip (cort SAU rulotă)
        'cta' => 'Verifică disponibilitatea',
        // Al doilea buton: același modul de rezervare, cu „Rulote” preselectat.
        'cta_alt' => array('label' => 'Ai rulotă? Rezervă aici', 'booking_type' => 'camping-rulota'),
        'hero' => array(
            'image' => 'camping-hero-corturi-1600.webp',
            'alt' => 'Corturi pe iarbă, sub sălcii, într-o zi senină, cu Munții Făgăraș în fundal',
            'image2' => 'camping-hero-lac-1280.webp',
            'alt2' => 'Lacul cu plăci SUP, cu Munții Făgăraș în fundal, sub cer albastru',
            'h1' => 'Camping cu cortul',
            'h1_em' => 'sau cu rulota',
            'lead' => 'Vino cu cortul, cu rulota sau cu autorulota. Ai dușuri calde, bucătării comune și un lac în care se înoată, într-un domeniu privat și păzit, la poalele Făgărașului.',
        ),
        'facts' => array(
            array('value' => '2', 'label' => 'lacuri, unul în care se înoată'),
            array('value' => '8', 'label' => 'dușuri publice, cu apă caldă non-stop'),
            array('value' => '20 min', 'label' => 'până la Transfăgărășan'),
            array('value' => '80 lei', 'label' => 'pe noapte, cort sau rulotă'),
        ),
        'story' => array(
            'eyebrow' => 'Cum e să stai aici',
            'title' => 'Libertate în aer liber.',
            'title_em' => 'Cu tot ce ai nevoie aproape.',
            'paragraphs' => array(
                'Îți întinzi cortul pe un domeniu privat de șase hectare, împrejmuit și păzit permanent. Dușurile calde, bucătăriile și focul de seară sunt aproape.',
                'Vii cu rulota sau cu autorulota? O parchezi în zona de camping, aproape de lac. Locul nu are racorduri. Îți încarci telefonul și laptopul în spațiile comune, iar stația de golire e în zona 1.',
                'Lacul mare are 8.000 de metri pătrați și un metru adâncime, așa că se înoată în el, iar pe apă faci și activități nautice. Pescuiești pe ambele lacuri. Lacul mic are, în mijloc, o terasă acoperită din lemn, de 7 pe 12 metri, unde încape toată gașca.',
            ),
            'image' => 'camping-story-cort-960.webp',
            'alt' => 'Corturi pe iarbă, într-o zi senină, cu un cort mare albastru în prim-plan',
        ),
        'gallery' => array('title' => 'Vezi unde îți pui cortul sau rulota', 'images' => array(
            array('src' => 'camping-cort-rand-960.webp', 'alt' => 'Corturi pe un rând, pe iarbă, într-o zi senină', 'caption' => 'Corturi pe iarbă, la soare'),
            array('src' => 'rulote-lac-munte-960.webp', 'alt' => 'Două rulote parcate lângă lac, cu munții în fundal', 'caption' => 'Rulote, lângă lac'),
            array('src' => 'camping-pajiste-960.webp', 'alt' => 'Pajiște cu sălcii și corturi, într-o zi senină, cu munții în fundal', 'caption' => 'Pajiștea cu sălcii'),
            array('src' => 'camping-ponton-960.webp', 'alt' => 'Lacul cu un ponton de lemn, oameni pe SUP-uri și Munții Făgăraș sub cer albastru', 'caption' => 'Lacul și pontonul'),
            array('src' => 'camping-lac-munte-1280.webp', 'alt' => 'Corturi pe malul lacului, cu Munții Făgăraș în fundal', 'caption' => 'Lacul, cu munții în față'),
        )),
        'included' => array(
            'title' => 'Tot ce-ți trebuie, nimic în plus',
            'groups' => array(
                array('icon' => 'tent', 'title' => 'La locul tău', 'items' => array(
                    'Spațiu generos pentru cort',
                    'Loc pentru rulotă sau autorulotă, fără racorduri',
                    'Stație de golire pentru rulote, în zona 1',
                    'Aproape de lac',
                    'Parcare proprie',
                    'Câinii mici (până la 15 kg) sunt bineveniți',
                ), 'note' => array('text' => 'Nu ai cort? Avem corturi cu pat, gata pregătite: ', 'link_text' => 'alege glampingul', 'link_offer' => 'glamping')),
                array('icon' => 'trees', 'title' => 'În Grădină', 'items' => array(
                    'Opt dușuri publice, cu apă caldă non-stop',
                    'Douăsprezece toalete publice',
                    'Două bucătării comune',
                    'Două lacuri, unul în care se înoată',
                    'Terasă acoperită din lemn, construită pe apă',
                    'Treizeci de hamace și vetre de foc',
                    'Pescuit sportiv și ponton',
                    'Curent pentru telefon și laptop, în spațiile comune',
                    'Wi-Fi în zona teraselor și în zonele marcate',
                    'Domeniu împrejmuit, pază 24 din 24',
                ), 'note' => array('text' => 'Vrei o noapte cu baie proprie? ', 'link_text' => 'Alege un bungalow', 'link_offer' => 'bungalow')),
            ),
        ),
        'reviews' => array('title' => 'De la cei care au stat aici', 'authors' => array('Rebeca Teban', 'Mihaela Mocanu')),
        'prices' => array(
            'title' => 'Simplu, fără surprize',
            'text' => 'Prețul final îl vezi când alegi datele, înainte să plătești.',
            'unit' => '/ noapte',
            'rows' => array(
                array('label' => 'Loc de cort', 'value' => '80 lei'),
                array('label' => 'Loc de rulotă sau autorulotă', 'value' => '80 lei'),
            ),
        ),
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoarele din camping și îmbuteliată, la recepție.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'La locul rulotei nu e curent. Telefonul și laptopul și le încarci în spațiile comune. Wi-Fi ai în zona teraselor și în zonele marcate, iar la dușuri, apă caldă non-stop.'),
            array('icon' => 'flame', 'title' => 'Focul, doar în vetrele amenajate', 'text' => 'Avem vetre de foc pregătite pe domeniu. Seara, acolo se adună toată lumea.'),
        ),
        'faq' => array(
            array('q' => 'Ce găsesc la camping?', 'a' => 'Spațiu pentru cortul tău, opt dușuri publice cu apă caldă non-stop, douăsprezece toalete publice și două bucătării comune. Plus două lacuri, treizeci de hamace și vetre de foc.'),
            array('q' => 'Primiți și rulote sau autorulote?', 'a' => 'Da, și rulote, și autorulote, în zona de camping. Locul rulotei nu are racorduri: fără curent, apă sau canalizare. Stația de golire e doar în zona 1. Telefonul și laptopul le încarci în spațiile comune. Dacă ai întrebări despre rulota ta, sună-ne la 0721 333 164.'),
            array('q' => 'Se poate înota?', 'a' => 'Da, în lacul mare. Are 8.000 de metri pătrați și un metru adâncime pe toată suprafața, fără zone adânci.'),
            array('q' => 'Am curent și internet?', 'a' => 'La locul rulotei nu e curent. În spațiile comune îți poți încărca telefonul și laptopul. Wi-Fi ai în zona teraselor și în zonele marcate. La dușuri e apă caldă non-stop. Domeniul funcționează pe energie proprie, solară și GPL, așa că lasă acasă electrocasnicele mari.'),
            array('q' => 'Ce pot face prin zonă?', 'a' => 'Transfăgărășanul e la circa 20 de minute, iar Mănăstirea Brâncoveanu e chiar în Sâmbăta de Sus. Prin satele de sub munte găsești drumuri de țară bune de bicicletă și producători locali.'),
            array('q' => 'Trebuie să rezerv din timp?', 'a' => 'Îți recomandăm, mai ales pentru weekendurile de vară și cât timp e deschis Transfăgărășanul.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'Tariful de referință este 80 lei pe noapte, pentru cort sau pentru rulotă. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de o noapte', 'title_em' => 'la cort sau în rulotă, sub Făgăraș?', 'image' => 'closing-camping-munti-1600.webp'),
        'seo' => array(
            'title' => 'Camping cu cortul și rulota, sub Munții Făgăraș | Grădina Ielelor',
            'description' => 'Camping cu cortul, rulota sau autorulota la Sâmbăta de Sus (Brașov): două lacuri, dușuri calde non-stop, bucătării comune, vetre de foc. La 20 min de Transfăgărășan.',
            'image' => 'camping-hero-corturi-1600.webp',
        ),
    );

    // Festivaluri & tabere (decizia din 31.10.2026). Surse: prezentările Festivaluri, Festival familie, Tabere
    // și fișa tehnică (docs/prezentari.md), pagina /festival/ de pe site-ul actual și docs/decizii.md.
    $pages['festivaluri'] = array(
        'common_intro' => 'Și la festivaluri și tabere, spațiile comune sunt la îndemâna tuturor: băile, bucătăriile, vetrele de foc, locul de joacă, terasele și lacul.',
        'title' => 'Festivaluri & tabere',
        'booking_type' => '',
        'cta' => 'Cere ofertă',
        // Modulul de ofertare pentru festivaluri (adresa există pe site-ul actual, nu face parte din plugin) și,
        // lângă el, pagina pentru organizatori cu fișa tehnică (/festival/).
        'cta_url' => home_url('/cotatie/?tip=festival'),
        'cta_alt' => array('label' => 'Pagina pentru organizatori', 'url' => home_url('/festival/')),
        'phone' => true,
        'hero' => array(
            'image' => 'evenimente-munte-1600.webp',
            'alt' => 'Oameni la un eveniment în aer liber în Grădina Ielelor, cu Munții Făgăraș în fundal',
            'h1' => 'Festivaluri și tabere',
            'h1_em' => 'sub Munții Făgăraș',
            'lead' => 'Un domeniu privat cu scenă permanentă, două lacuri, cazare pe loc și pază zi și noapte. Pentru festivaluri cu public cazat pe domeniu, tabere de copii, boot camp, retreaturi și ieșiri cu echipa.',
        ),
        'facts' => array(
            array('value' => '5.000', 'label' => 'persoane, cu aviz ISU'),
            array('value' => '6 × 7 m', 'label' => 'scenă permanentă'),
            array('value' => '58', 'label' => 'unități de cazare pe domeniu'),
            array('value' => '6 ha', 'label' => 'împrejmuite și păzite'),
        ),
        'story' => array(
            'eyebrow' => 'Ce găzduim',
            'title' => 'Construit de la zero',
            'title_em' => 'pentru grupuri mari.',
            'paragraphs' => array(
                'Găzduim festivaluri de muzică cu camping, rock, stand-up și folk, dar și festivaluri pentru familii. Publicul doarme pe domeniu, iar după ultimul set focul, lacul și hamacele preiau seara.',
                'La taberele de copii, siguranța vine prima. Cele șase hectare sunt împrejmuite, cu o singură intrare și pază pe toată durata șederii. Parcarea este în interiorul Grădinii, iar la tabere organizatorul decide cum intră mașinile. Lacul mare are un metru adâncime peste tot, fără zone adânci.',
                'Copiii au loc de joacă și de învățat: platoul de 3.000 mp pentru jocuri și festivitatea de deschidere, scena pentru spectacolul de final, lacurile pentru activități pe apă supravegheate și vetrele de foc pentru serile de povești.',
                'Mai primim ieșiri cu echipa, retreaturi, cursuri și un boot camp pentru tineri. Poți rezerva o zonă sau toată Grădina.',
            ),
            'image' => 'festival-tabara-aeriana-960.webp',
            'alt' => 'Domeniul Grădina Ielelor văzut de sus, cu corturi, lacul și Munții Făgăraș în fundal',
        ),
        'calendar' => array(
            'title' => 'Ce pregătim în 2027',
            'text' => 'Festivaluri, petreceri și întâlniri pentru familii. Datele exacte le anunțăm pe măsură ce se confirmă. Pregătești o tabără sau un festival? Rezervă-ți perioada din timp.',
        ),
        'gallery' => array('title' => 'Vezi domeniul', 'images' => array(
            array('src' => 'festival-scena-960.webp', 'alt' => 'Spectacol de dansuri populare pe scenă, cu copii în public, la un eveniment în Grădina Ielelor', 'caption' => 'Spectacol pe scenă'),
            array('src' => 'festival-aerian-albastru-1280.webp', 'alt' => 'Domeniul văzut de sus, cu tabăra de rulote, lacurile și câmpurile, sub cer albastru', 'caption' => 'Domeniul, văzut de sus'),
            array('src' => 'vatra-oameni-foc-1600.webp', 'alt' => 'Oameni cântând la tobe în jurul unei vetre de foc, noaptea, în Grădina Ielelor', 'caption' => 'Seara, în jurul focului'),
            array('src' => 'festival-aerian-rulote-1280.webp', 'alt' => 'Tabără de rulote pe domeniu, văzută de sus, într-un festival', 'caption' => 'Tabără de rulote, la un festival'),
            array('src' => 'camping-lac-munte-1280.webp', 'alt' => 'Corturi pe malul lacului, cu Munții Făgăraș în fundal', 'caption' => 'Corturi pe malul lacului'),
            array('src' => 'festival-glamping-sol-1280.webp', 'alt' => 'Corturi de glamping pe iarbă, sub cer albastru, cu munții în depărtare', 'caption' => 'Corturi de glamping'),
            array('src' => 'festival-aerian-lac-1280.webp', 'alt' => 'Lacurile și tabăra de rulote ale domeniului, văzute de sus, sub cer albastru', 'caption' => 'Lacurile, văzute de sus'),
            array('src' => 'home-cer-corturi-1280.webp', 'alt' => 'Corturi de glamping pe iarbă, în număr mare, cu Munții Făgăraș în fundal', 'caption' => 'Corturi de glamping, la festivaluri'),
        )),
        'included' => array(
            'title' => 'Totul pe același teren',
            'groups' => array(
                array('icon' => 'music', 'title' => 'Pentru festivaluri', 'items' => array(
                    'Aviz ISU pentru 5.000 de persoane',
                    'Teren de 9 ha în regim de festival',
                    'Scenă permanentă și acoperită, de 6 × 7 m, înaltă de 5 m',
                    'Platou înălțat de 3.000 mp, pentru a doua scenă sau un târg',
                    'Pavilion de 225 mp cu terasă acoperită, gata la 1 mai 2027',
                    'Drum de acces pentru camioane, până la scenă',
                    'Bucătărie autorizată și loc pentru food trucks',
                    'Parcare în interiorul Grădinii, pe 60.000 mp. La evenimentele mari, la cerere, se adaugă încă 30.000 mp în vecinătate, la un partener: în total 9 ha, cu loc pentru cca. 1.000 de mașini',
                    'Cea mai apropiată casă, la circa 2 km',
                ), 'action' => array('label' => 'Cere ofertă pentru festival', 'url' => home_url('/cotatie/?tip=festival')), 'note' => array('text' => 'Cazare pentru artiști și echipă? ', 'link_text' => 'Vezi bungalow-urile', 'link_offer' => 'bungalow')),
                array('icon' => 'tent', 'title' => 'Pentru tabere', 'items' => array(
                    'Șase hectare împrejmuite, cu o singură intrare',
                    'Pază 24 din 24, pe toată durata șederii',
                    'Lac de 1 m adâncime peste tot, fără zone adânci',
                    'Trei zone separate, pentru grupe diferite',
                    '4 bungalow-uri, cu baie proprie (toaletă și duș), 4 corturi cu pat și 50 cu saltele (lenjerie la cerere)',
                    'Opt dușuri publice cu apă caldă non-stop și douăsprezece toalete publice, repartizate pe zone',
                    'Bucătărie autorizată și două bucătării comune',
                    'Internet și iluminat în zonele comune',
                ), 'action' => array('label' => 'Cere ofertă pentru tabără', 'url' => home_url('/cotatie/?tip=tabara')), 'note' => array('text' => 'Vrei o nuntă, un botez sau un majorat? ', 'link_text' => 'Vezi evenimentele private', 'link_offer' => 'private')),
                array('icon' => 'flame', 'title' => 'Ieșiri cu echipa și retreaturi', 'items' => array(
                    'Ieșire cu echipa: 8–30 de oameni, o zi, două sau trei',
                    'Programul îl ținem noi, cu obiectivul stabilit dinainte',
                    'Fără concursuri și punctaje: ceva util sau plăcut, făcut împreună',
                    'Retreaturi și cursuri, pentru profesori de yoga, terapeuți, ONG-uri și școli',
                    'De la 10 oameni la câteva sute',
                    'La retreaturi, programul, înscrierile și prețul rămân ale tale',
                    'Noi pregătim corturile, mesele, curățenia și gunoiul',
                    'Poți rezerva o zonă sau toată Grădina',
                    'Un om de contact, prezent tot timpul',
                ), 'action' => array('label' => 'Cere ofertă pentru echipa ta', 'url' => home_url('/cotatie/?tip=retreat'))),
                array('icon' => 'trees', 'title' => 'Boot camp pentru tineri', 'items' => array(
                    'Cinci zile în care tinerii învață pe un teren adevărat: pământ, apă, energie, construcție naturală',
                    'La final rămâne ceva construit, nu doar o diplomă',
                    'Sprijinul tău devine locuri pentru tineri care altfel nu ar ajunge aici',
                    'Sponsorii primesc un raport de impact după fiecare ediție, cu cifre și fotografii',
                    'Materiale foto și video, pe care le poți folosi',
                    'Numele tău pe materialele ediției',
                    'O zi de voluntariat pentru echipa ta, în timpul bootcampului',
                    'Prima opțiune pe ediția următoare',
                ), 'action' => array('label' => 'Cere ofertă pentru sponsorizare sau grup', 'url' => home_url('/cotatie/?tip=retreat'))),
            ),
        ),
        'reviews' => array('title' => 'Ce spun oaspeții', 'authors' => array()),
        'prices' => null,
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E doar pentru igienă. Apa de băut se asigură separat, prin dozatoare și îmbuteliată.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Domeniul are curent pentru consumul lui. Pentru scenă, sunet și lumini, necesarul se stabilește din timp, împreună cu organizatorul.'),
            array('icon' => 'music', 'title' => 'Programul și producția rămân ale tale', 'text' => 'Sunetul, luminile, structurile temporare și toaletele suplimentare nu sunt incluse. La tabere, programul, personalul și masa sunt ale organizatorului.'),
        ),
        'faq' => array(
            array('q' => 'Ce fel de festivaluri găzduiți?', 'a' => 'Festivaluri de muzică cu camping, festivaluri rock, seri de stand-up și muzică folk și festivaluri pentru familii. Unele au avut deja loc aici, altele sunt în calendarul 2027.'),
            array('q' => 'Câți oameni încap?', 'a' => 'Domeniul are aviz ISU pentru 5.000 de persoane. Mașinile parchează în interiorul Grădinii, pe 60.000 de metri pătrați. La evenimentele mari, la cerere, se adaugă încă 30.000 de metri pătrați, într-o parcare din vecinătate, a unui partener: în total 9 hectare, cu loc pentru cam o mie de mașini.'),
            array('q' => 'Câte băi sunt și unde?', 'a' => 'Sunt opt dușuri publice cu apă caldă și douăsprezece toalete, repartizate pe cele trei zone. În Zona 1 (camping și lacul mic): 4 dușuri și 4 toalete. În Zona 2 (platoul și pavilionul): 4 toalete. În Zona 3 (lacul mare și scena): 4 dușuri și 4 toalete. Fiecare dintre cele 4 bungalow-uri are în plus baie proprie.'),
            array('q' => 'Unde dorm participanții?', 'a' => 'Pe domeniu sunt 58 de unități: 4 bungalow-uri cu baie proprie, 4 corturi glamping cu pat și 50 cu saltele, cu lenjerie la cerere. Se adaugă zona de camping și rulote, iar la evenimentele mari ajută și pensiunile din satele din jur. Cazarea de pe domeniu o poți include în biletul festivalului.'),
            array('q' => 'Ce asigurați voi și ce aduce organizatorul?', 'a' => 'Noi punem la dispoziție locul: scena, platoul, lacurile, grupurile sanitare, parcarea, cazarea și paza. Organizatorul aduce producția: sunet, lumini, generatoare, program, bilete și avizele evenimentului.'),
            array('q' => 'Ce asigurați la o tabără de copii?', 'a' => 'Noi asigurăm cazarea, spațiile comune, utilitățile, paza și accesul la bucătării. Tu vii cu programul, cu oamenii care au grijă de copii, cu asistența medicală, transportul, asigurările și avizele taberei. Masa o poți găti în bucătăria autorizată sau o aduce un furnizor ales împreună.'),
            array('q' => 'Primiți și ieșiri cu echipa, retreaturi sau cursuri?', 'a' => 'Da. Pentru echipe de 8–30 de oameni facem ieșiri de o zi, două sau trei, cu programul ținut de noi. La retreaturi și cursuri vii cu programul tău, iar noi ținem locul, mesele și curățenia. Poți rezerva o zonă sau toată Grădina. Copiii sunt bineveniți, fără limită de vârstă, iar câinii mici (până la 15 kg) sunt primiți. Pentru nunți, botezuri și majorate avem pagina Evenimente private.'),
            array('q' => 'Ce este bootcampul pentru tineri?', 'a' => 'Cinci zile în care tinerii învață pe un teren adevărat: pământ, apă, energie, construcție naturală, și cum se transformă asta într-un proiect care stă în picioare. Sponsorizarea se traduce direct în locuri pentru tineri care altfel nu ar ajunge aici. Dacă vrei să susții o ediție, scrie-ne.'),
            array('q' => 'Cum putem colabora?', 'a' => 'La festivaluri, închiriezi tot domeniul pe durata evenimentului, cu zilele de montaj și demontaj. La tabere, plătești pe copil și pe noapte, închiriezi tot domeniul sau doar o zonă, ori împărțim încasările. Variantele se pot combina, iar tariful se negociază, în funcție de configurarea evenimentului.'),
            array('q' => 'Când să vă scriu?', 'a' => 'Cât mai devreme: îți recomandăm cel puțin șase luni înainte, mai ales pentru weekendurile de vară. Cel mai bine e să vii întâi în vizită. Ne facem timp oricând, și în afara sezonului.'),
        ),
        'closing' => array('title' => 'Ai un festival, o tabără sau un retreat în minte?', 'title_em' => 'Hai să vorbim.', 'image' => 'festival-standuri-1280.webp'),
        'seo' => array(
            'title' => 'Festivaluri, tabere și retreaturi sub Munții Făgăraș | Grădina Ielelor',
            'description' => 'Festivaluri, tabere de copii, retreaturi și boot camp la Sâmbăta de Sus, Brașov: aviz ISU pentru 5.000 de persoane, scenă permanentă, două lacuri, cazare pe domeniu.',
            'image' => 'evenimente-munte-1600.webp',
        ),
    );

    // Evenimente private (decizia din 31.10.2026). Surse: prezentarea „Boot camp” (formulele I și II, docs/prezentari.md),
    // pagina veche /gradina-ielelor-totul-despre-ea/evenimente/ (nunți, botezuri, majorate, seminarii, teambuilding) și docs/decizii.md.
    $pages['private'] = array(
        'common_intro' => 'Și la evenimente, spațiile comune fac parte din loc: băile, bucătăriile, vetrele de foc, locul de joacă, terasele și lacul.',
        'title' => 'Evenimente private',
        'booking_type' => '',
        'cta' => 'Cere ofertă',
        // Nunți, botezuri și majorate au configuratorul lor de ofertă (modulul /cotatie/, tip nuntă); Contact rămâne alături.
        'cta_url' => home_url('/cotatie/?tip=nunta'),
        'cta_alt' => array('label' => 'Sau scrie-ne', 'url' => gi_clean_page_url('contact')),
        'phone' => true,
        'hero' => array(
            'image' => 'evenimente-hero-baldachin-1600.webp',
            'alt' => 'Lacul cu un pat baldachin alb pe mal, sub cer albastru, cu corturi în depărtare',
            'h1' => 'Evenimente private',
            'h1_em' => 'în Grădina Ielelor',
            'lead' => 'Un lac, munții în față și o zonă doar pentru ai tăi. Pentru nunți, botezuri, majorate și petreceri de familie, în aer liber.',
        ),
        'facts' => array(
            array('value' => '58', 'label' => 'unități de cazare pe domeniu'),
            array('value' => '8 + 12', 'label' => 'dușuri publice cu apă caldă și toalete publice'),
            array('value' => '60.000 mp', 'label' => 'de parcare, în interiorul Grădinii'),
            array('value' => 'cca. 60 km', 'label' => 'de Brașov'),
        ),
        'story' => array(
            'eyebrow' => 'Cum e la noi',
            'title' => 'Un loc al vostru,',
            'title_em' => 'sub Munții Făgăraș.',
            'paragraphs' => array(
                'Un domeniu privat, cu un lac, Munții Făgăraș în față și pază zi și noapte. Aici îți poți face nunta, botezul sau majoratul în aer liber, cu oamenii tăi aproape.',
                'Evenimentele se țin în aer liber, în pavilioane. Le punem noi la dispoziție sau le aduci tu, după caz și preferință. Poți rezerva o zonă sau toată Grădina.',
                'Invitații pot dormi pe domeniu: avem 58 de unități de cazare, între bungalow-uri, corturi de glamping și corturi cu saltele. Seara, lacul și focul fac restul. Spune-ne ce ai în minte și vedem împreună cum ți se potrivește locul.',
            ),
            'image' => 'evenimente-story-lac-960.webp',
            'alt' => 'Lacul, cu o salcie pe mal, sub cer albastru, cu Munții Făgăraș în depărtare',
        ),
        'gallery' => array('title' => 'Vezi locul', 'images' => array(
            array('src' => 'evenimente-lac-sup-960.webp', 'alt' => 'Lacul, cu plăci SUP în prim-plan și Munții Făgăraș în fundal, sub cer albastru', 'caption' => 'Lacul, cu plăci SUP'),
            array('src' => 'bungalow-malul-lacului-960.webp', 'alt' => 'Malul lacului, cu bungalow-uri și un baldachin alb, sub cer și munți', 'caption' => 'Cazare: bungalow-uri pe malul lacului'),
            array('src' => 'bungalow-dormitor-alb-960.webp', 'alt' => 'Dormitor cu două paturi albe într-un bungalow, cu jaluzele de lemn', 'caption' => 'Cazare: dormitor de bungalow'),
            array('src' => 'glamping-interior-pat-720.webp', 'alt' => 'Interiorul unui cort de glamping, cu pat dublu, perne galbene, noptieră și covoare', 'caption' => 'Cazare: cort de glamping cu pat'),
            array('src' => 'evenimente-vedere-lac-1280.webp', 'alt' => 'Lacul cu un baldachin alb pe mal, corturi pe malul opus și Munții Făgăraș, sub cer albastru', 'caption' => 'Lacul, cu baldachinul de pe mal'),
        )),
        'included' => array(
            'title' => 'Ce găsești la noi',
            'groups' => array(
                array('icon' => 'music', 'title' => 'La eveniment', 'items' => array(
                    'În aer liber, în pavilioane puse la dispoziție de noi sau aduse de tine',
                    'Poți rezerva o zonă sau toată Grădina, doar pentru tine și invitații tăi',
                    'Vetre de foc și hamace',
                    'Pavilion de 225 mp, gata la 1 mai 2027',
                    'Loc de fotografie la arcada Grădinii',
                )),
                array('icon' => 'tent', 'title' => 'Pentru invitați', 'items' => array(
                    '58 de unități de cazare pe domeniu',
                    '4 bungalow-uri, fiecare cu baie proprie (toaletă și duș)',
                    '4 corturi de glamping cu pat și 50 cu saltele (lenjerie la cerere)',
                    'Opt dușuri publice, cu apă caldă non-stop',
                    'Douăsprezece toalete publice',
                    'Parcare în interiorul Grădinii, pe 60.000 mp',
                    'Domeniu împrejmuit, pază 24 din 24',
                    'Două lacuri, cu ponton din lemn',
                ), 'note' => array('text' => 'Pregătești o ieșire cu echipa, un retreat sau o tabără? ', 'link_text' => 'Vezi Festivaluri & tabere', 'link_offer' => 'festivaluri')),
            ),
        ),
        'reviews' => array('title' => 'Ce spun oaspeții', 'authors' => array()),
        'prices' => null,
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoare și îmbuteliată.'),
            array('icon' => 'calendar', 'title' => 'Spune-ne trei lucruri', 'text' => 'Câți invitați veți fi, ce perioadă ai în minte și ce fel de eveniment faci. Revenim cu un răspuns.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Ai curent, apă caldă și internet. Te rugăm doar să lași acasă electrocasnicele mari.'),
        ),
        'faq' => array(
            array('q' => 'Ce fel de evenimente private găzduiți?', 'a' => 'Nunți, botezuri, majorate și petreceri de familie. Se țin în aer liber, în pavilioane. Spune-ne ce ai în minte și vedem împreună dacă locul ți se potrivește.'),
            array('q' => 'Cine asigură pavilioanele?', 'a' => 'Le punem noi la dispoziție sau le aduci tu, după caz și preferință. Stabilim împreună ce ți se potrivește.'),
            array('q' => 'Suntem singuri pe domeniu?', 'a' => 'Poți rezerva o zonă sau toată Grădina. Detaliile le stabilim împreună, după ce ne spui câți sunteți.'),
            array('q' => 'Unde dorm invitații?', 'a' => 'Pe domeniu sunt 58 de unități: 4 bungalow-uri cu baie proprie, 4 corturi glamping cu pat și 50 cu saltele, cu lenjerie la cerere. Pentru invitați în plus, ajută pensiunile din satele din jur.'),
            array('q' => 'Și dacă plouă?', 'a' => 'Evenimentele se țin în pavilioane, puse la dispoziție de noi sau aduse de tine. Din 1 mai 2027 se adaugă pavilionul nostru de 225 mp, cu loc pentru 150 de persoane.'),
            array('q' => 'Cum decurge?', 'a' => 'Ne scrii câți invitați veți fi, ce perioadă ai în minte și ce fel de eveniment faci. Revenim cu un răspuns și vedem împreună ce ți se potrivește.'),
            array('q' => 'Pot veni copii și câini?', 'a' => 'Da, copiii sunt bineveniți și chiar îi încurajăm: nu există limită de vârstă. Câinii mici, până la 15 kg, sunt primiți, cu regulile din Regulament & politici.'),
            array('q' => 'Pot veni cu echipa de la firmă sau cu un grup de retreat?', 'a' => 'Da. Ieșirile cu echipa, retreaturile și cursurile sunt pe pagina Festivaluri & tabere.'),
        ),
        'closing' => array('title' => 'Ai un eveniment în minte?', 'title_em' => 'Spune-ne câți, când și de ce.', 'image' => 'closing-evenimente-lac-1280.webp'),
        'seo' => array(
            'title' => 'Nunți, botezuri și majorate în aer liber | Grădina Ielelor',
            'description' => 'Nunți, botezuri și majorate în aer liber la Sâmbăta de Sus, Brașov: un lac, Munții Făgăraș în față, o zonă sau toată Grădina doar pentru tine, cazare pe domeniu.',
            'image' => 'evenimente-hero-baldachin-1600.webp',
        ),
    );

    return apply_filters('gi_offer_pages', $pages);
}
