<?php
/**
 * Conținutul paginilor dedicate (Bungalow, Glamping, Camping, Rulote, Festivaluri & evenimente).
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
 * Opționale: cta_url (acțiune principală diferită de rezervare), calendar{title, text} (afișează gi_events_2027()).
 */
function gi_offer_page($key) {
    $pages = gi_offer_pages();
    return isset($pages[$key]) ? $pages[$key] : null;
}

function gi_offer_pages() {
    $experiences = gi_experiences();
    $pages = array();

    $pages['glamping'] = array(
        'title' => 'Glamping',
        'booking_type' => 'glamping',
        'cta' => 'Verifică disponibilitatea',
        'hero' => array(
            'image' => 'glamping-munte-1600.webp',
            'alt' => 'Corturile de glamping, cu Munții Făgăraș în fundal',
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
                'Ziua ai la dispoziție două lacuri, unul în care se înoată, și o terasă din lemn construită pe apă. Seara, focul se aprinde în vetrele amenajate, iar hamacele sunt la câțiva pași.',
            ),
            'image' => 'glamping-pajiste-960.webp',
            'alt' => 'Corturi de glamping pe pajiște, cu Munții Făgăraș sub nori în fundal',
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
                    'Opt dușuri cu apă caldă, non-stop',
                    'Douăsprezece toalete',
                    'Două bucătării comune',
                    'Două lacuri, unul în care se înoată',
                    'Terasă din lemn construită pe apă',
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
        ),
        'faq' => array(
            array('q' => 'Ce găsesc în cort?', 'a' => 'Un pat cu lenjerie, covor și noptieră. Cortul stă pe o platformă de lemn, iar la intrare ai o masă cu scaune. Nu e camping de supraviețuire.'),
            array('q' => 'Câte persoane încap într-un cort?', 'a' => 'Două persoane. Dacă mai vine cineva, poți adăuga un pat suplimentar când faci rezervarea.'),
            array('q' => 'Baia e în cort?', 'a' => 'Nu, băile sunt comune: opt dușuri cu apă caldă, disponibile oricând, și douăsprezece toalete. Dacă vrei baie proprie, alege un bungalow.'),
            array('q' => 'Am curent și internet?', 'a' => 'Da. Domeniul funcționează pe energie proprie, solară și GPL, așa că ai curent, apă caldă și internet. Lasă acasă doar aparatele electrice mari.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'De la 195 lei în timpul săptămânii și 235 lei vineri și sâmbătă. Patul suplimentar costă 55 lei pe noapte. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de o noapte', 'title_em' => 'sub cerul Făgărașului?', 'image' => 'hero-1280.webp'),
        'seo' => array(
            'title' => 'Glamping Brașov, sub Munții Făgăraș | Grădina Ielelor',
            'description' => 'Corturi de glamping cu pat adevărat, pe platformă de lemn, în Sâmbăta de Sus, la poalele Munților Făgăraș. Două lacuri, dușuri calde non-stop. Rezervă online.',
            'image' => 'glamping-munte-1600.webp',
        ),
    );

    $pages['bungalow'] = array(
        'title' => 'Bungalow',
        'booking_type' => 'bungalow',
        'cta' => 'Verifică disponibilitatea',
        'hero' => array(
            'image' => 'bungalow-detail-1600.webp',
            'alt' => 'Bungalow în Grădina Ielelor, cu terasă',
            'h1' => 'Bungalow în',
            'h1_em' => 'Grădina Ielelor',
            'lead' => 'Pereți, baie proprie și încălzire, pe malul lacului mare. Pentru cine vrea intimitate la poalele Munților Făgăraș.',
        ),
        'facts' => array(
            array('value' => '4', 'label' => 'bungalow-uri, fiecare cu baie proprie'),
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
            'image' => 'bungalow-terasa-lac-960.webp',
            'alt' => 'Vedere de pe terasa unui bungalow spre lac și Munții Făgăraș',
        ),
        // Fotografia cu muntele deschide galeria; pozele de interior rămân, pentru că informează.
        'gallery' => array('title' => 'Vezi unde te cazezi', 'images' => array_merge(array(
            array('src' => 'lac-ponton-1280.webp', 'alt' => 'Lacul cu pontonul din lemn, cu Munții Făgăraș în fundal', 'caption' => 'Lacul și pontonul'),
        ), $experiences['bungalow']['images'], array(
            array('src' => 'bungalow-baie-960.webp', 'alt' => 'Baia unui bungalow, cu duș, chiuvetă și toaletă', 'caption' => 'Baie proprie'),
        ))),
        'included' => array(
            'title' => 'Tot ce-ți trebuie, nimic în plus',
            'groups' => array(
                array('icon' => 'mountain', 'title' => 'În bungalow-ul tău', 'items' => array(
                    'Baie proprie, doar pentru tine',
                    'Încălzire',
                    'Dormitor și zonă de relaxare',
                    'Terasă proprie',
                    'Pe malul lacului mare',
                )),
                array('icon' => 'trees', 'title' => 'În Grădină', 'items' => array(
                    'Două bucătării comune',
                    'Două lacuri, unul în care se înoată',
                    'Ponton pe lacul mare',
                    'Terasă din lemn construită pe apă',
                    'Treizeci de hamace și vetre de foc',
                    'Pescuit sportiv',
                    'Internet și parcare proprie',
                    'Domeniu împrejmuit, pază 24 din 24',
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
            array('q' => 'Baia e doar a mea?', 'a' => 'Da. Fiecare dintre cele patru bungalow-uri are baia lui, așa că nu împarți dușul cu nimeni.'),
            array('q' => 'Pot să gătesc?', 'a' => 'Da, în cele două bucătării comune ale Grădinii. Sunt la dispoziția tuturor oaspeților.'),
            array('q' => 'Am curent și internet?', 'a' => 'Da. Domeniul funcționează pe energie proprie, solară și GPL, așa că ai curent, apă caldă și internet. Lasă acasă doar aparatele electrice mari.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'De la 350 lei în timpul săptămânii și 450 lei vineri și sâmbătă. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de o noapte', 'title_em' => 'pe malul lacului?', 'image' => 'home-hero-lake-centered-1600.webp'),
        'seo' => array(
            'title' => 'Bungalow pe malul lacului, Brașov | Grădina Ielelor',
            'description' => 'Patru bungalow-uri cu baie proprie și încălzire, pe malul lacului din Grădina Ielelor, la poalele Munților Făgăraș. Vezi pozele și verifică disponibilitatea.',
            'image' => 'bungalow-detail-1600.webp',
        ),
    );

    $pages['camping'] = array(
        'title' => 'Camping',
        'booking_type' => 'camping-cort',
        'cta' => 'Verifică disponibilitatea',
        'hero' => array(
            'image' => 'camping-munte-1600.webp',
            'alt' => 'Zona de camping văzută de sus: corturi, lacul și Munții Făgăraș în fundal',
            'h1' => 'Camping la',
            'h1_em' => 'poalele Făgărașului',
            'lead' => 'Vino cu cortul tău. Ai dușuri calde, bucătării comune și un lac în care se înoată, într-un domeniu privat și păzit.',
        ),
        'facts' => array(
            array('value' => '2', 'label' => 'lacuri, unul în care se înoată'),
            array('value' => '30', 'label' => 'de hamace, plus vetre de foc'),
            array('value' => '20 min', 'label' => 'până la Transfăgărășan'),
            array('value' => '80 lei', 'label' => 'pe noapte'),
        ),
        'story' => array(
            'eyebrow' => 'Cum e să campezi aici',
            'title' => 'Libertate în aer liber.',
            'title_em' => 'Cu tot ce ai nevoie aproape.',
            'paragraphs' => array(
                'Îți întinzi cortul pe un domeniu privat de șase hectare, împrejmuit și păzit permanent. Dușurile calde, bucătăriile și focul de seară sunt aproape.',
                'Lacul mare are 8.000 de metri pătrați și un metru adâncime, așa că se înoată în el. Lacul mic are o terasă din lemn construită direct pe apă, de 7 pe 12 metri, unde încape toată gașca.',
            ),
            'image' => 'camping-corturi-munte-960.webp',
            'alt' => 'Corturi în zona de camping, cu Munții Făgăraș în fundal',
        ),
        'gallery' => array('title' => 'Vezi unde îți pui cortul', 'images' => array(
            array('src' => 'camping-lac-1280.webp', 'alt' => 'Lacul și corturile de pe mal, cu Munții Făgăraș în fundal', 'caption' => 'Corturi lângă lac'),
            array('src' => 'camping-day-960.webp', 'alt' => 'Corturi și sălcii în campingul Grădina Ielelor, cu Munții Făgăraș în fundal', 'caption' => 'Camping în zi însorită'),
            array('src' => 'festival-lake-640.webp', 'alt' => 'Lacul cu pontonul din lemn, cu Munții Făgăraș în fundal', 'caption' => 'Acces la lac și ponton'),
            array('src' => 'home-hero-chill-640.webp', 'alt' => 'Foișor din lemn cu scaune-hamac și fotolii pentru relaxare', 'caption' => 'Loc de relaxare'),
            array('src' => 'camping-bucatarie-960.webp', 'alt' => 'Bucătărie comună în aer liber, sub un acoperiș din lemn', 'caption' => 'Bucătărie comună'),
            array('src' => 'camping-sanitare-960.webp', 'alt' => 'Grupul sanitar din camping, cu chiuvete și oglinzi', 'caption' => 'Grup sanitar'),
        )),
        'included' => array(
            'title' => 'Tot ce-ți trebuie, nimic în plus',
            'groups' => array(
                array('icon' => 'tent', 'title' => 'La cortul tău', 'items' => array(
                    'Spațiu generos pentru cort',
                    'Loc lângă lac',
                    'Acces la toate facilitățile',
                    'Internet și parcare proprie',
                    'Câinii sunt bineveniți',
                ), 'note' => array('text' => 'Vii cu rulota? ', 'link_text' => 'Vezi pagina Rulote', 'link_offer' => 'rulote')),
                array('icon' => 'trees', 'title' => 'În Grădină', 'items' => array(
                    'Opt dușuri cu apă caldă, non-stop',
                    'Douăsprezece toalete',
                    'Două bucătării comune',
                    'Două lacuri, unul în care se înoată',
                    'Terasă din lemn construită pe apă',
                    'Treizeci de hamace și vetre de foc',
                    'Pescuit sportiv și ponton',
                    'Domeniu împrejmuit, pază 24 din 24',
                ), 'note' => array('text' => 'Nu ai cort? Avem corturi cu pat, gata pregătite: ', 'link_text' => 'alege glampingul', 'link_offer' => 'glamping')),
            ),
        ),
        'reviews' => array('title' => 'De la cei care au campat aici', 'authors' => array('Rebeca Teban', 'Mihaela Mocanu')),
        'prices' => array(
            'title' => 'Simplu, fără surprize',
            'text' => 'Prețul final îl vezi când alegi datele, înainte să plătești.',
            'unit' => '/ noapte',
            'rows' => array(
                array('label' => 'Camping cu cortul tău', 'value' => '80 lei'),
            ),
        ),
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoarele din camping și îmbuteliată, la recepție.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Ai curent, apă caldă și internet. Te rugăm doar să lași acasă electrocasnicele mari.'),
            array('icon' => 'flame', 'title' => 'Focul, doar în vetrele amenajate', 'text' => 'Avem vetre de foc pregătite pe domeniu. Seara, acolo se adună toată lumea.'),
        ),
        'faq' => array(
            array('q' => 'Ce găsesc la camping?', 'a' => 'Spațiu pentru cortul tău, opt dușuri cu apă caldă non-stop, douăsprezece toalete și două bucătării comune. Plus două lacuri, treizeci de hamace și vetre de foc.'),
            array('q' => 'Se poate înota?', 'a' => 'Da, în lacul mare. Are 8.000 de metri pătrați și un metru adâncime pe toată suprafața, fără zone adânci.'),
            array('q' => 'Am curent și internet?', 'a' => 'Da. Domeniul funcționează pe energie proprie, solară și GPL, așa că ai curent, apă caldă și internet. Lasă acasă doar aparatele electrice mari.'),
            array('q' => 'Ce pot face prin zonă?', 'a' => 'Transfăgărășanul e la circa 20 de minute, iar Mănăstirea Brâncoveanu e chiar în Sâmbăta de Sus. Prin satele de sub munte găsești drumuri de țară bune de bicicletă și producători locali.'),
            array('q' => 'Trebuie să rezerv din timp?', 'a' => 'Îți recomandăm, mai ales pentru weekendurile de vară și cât timp e deschis Transfăgărășanul.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'Tariful de referință este 80 lei pe noapte. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de o noapte', 'title_em' => 'la cort, sub Făgăraș?', 'image' => 'camping-lac-munte-1280.webp'),
        'seo' => array(
            'title' => 'Camping Făgăraș, aproape de Transfăgărășan | Grădina Ielelor',
            'description' => 'Vino cu cortul în Grădina Ielelor, Sâmbăta de Sus (Brașov): două lacuri, dușuri calde non-stop, bucătării comune și vetre de foc. La 20 min de Transfăgărășan.',
            'image' => 'camping-munte-1600.webp',
        ),
    );

    $pages['rulote'] = array(
        'title' => 'Rulote',
        'booking_type' => 'camping-rulota',
        'cta' => 'Verifică disponibilitatea',
        'hero' => array(
            'image' => 'rulote-hero-1600.webp',
            'alt' => 'Autorulote parcate lângă lac, cu Munții Făgăraș în fundal',
            'h1' => 'Cu rulota în',
            'h1_em' => 'Grădina Ielelor',
            'lead' => 'Rulotă sau autorulotă? Ai racorduri și servicii pe loc, lângă lac, la poalele Munților Făgăraș.',
        ),
        'facts' => array(
            array('value' => '8', 'label' => 'dușuri cu apă caldă, non-stop'),
            array('value' => '2', 'label' => 'bucătării comune'),
            array('value' => '20 min', 'label' => 'până la Transfăgărășan'),
            array('value' => '80 lei', 'label' => 'pe noapte'),
        ),
        'story' => array(
            'eyebrow' => 'Cum e să stai aici',
            'title' => 'Vii cu casa ta.',
            'title_em' => 'Restul îl găsești aici.',
            'paragraphs' => array(
                'Parchezi rulota sau autorulota în zona de camping, aproape de lac. Ai racorduri și servicii pe loc, plus internet.',
                'Când cobori, ai toată Grădina: dușuri calde non-stop, două bucătării comune, hamace și vetre de foc. Iar lacul mare, de 8.000 de metri pătrați și un metru adâncime, te așteaptă la o baie.',
            ),
            'image' => 'rulote-lac-munte-960.webp',
            'alt' => 'Autorulote parcate lângă lac, cu Munții Făgăraș în fundal',
        ),
        'gallery' => array('title' => 'Vezi locul înainte să vii', 'images' => array(
            array('src' => 'domeniu-aerian-1280.webp', 'alt' => 'Domeniul Grădina Ielelor văzut de sus, cu rulote și Munții Făgăraș în fundal', 'caption' => 'Domeniul, văzut de sus'),
            array('src' => 'rulote-aerian-960.webp', 'alt' => 'Rulote și autorulote parcate pe domeniu, văzute de sus, cu munții în fundal', 'caption' => 'Rulote pe domeniu, văzute de sus'),
            array('src' => 'festival-lake-640.webp', 'alt' => 'Lacul cu pontonul din lemn, cu Munții Făgăraș în fundal', 'caption' => 'Acces la lac și ponton'),
            array('src' => 'rulote-zona-960.webp', 'alt' => 'Vedere aeriană cu oaspeți, corturi și rulote în Grădina Ielelor', 'caption' => 'Corturi și rulote, lângă lacuri'),
            array('src' => 'camping-bucatarie-960.webp', 'alt' => 'Bucătărie comună în aer liber, sub un acoperiș din lemn', 'caption' => 'Bucătărie comună'),
            array('src' => 'camping-sanitare-960.webp', 'alt' => 'Grupul sanitar din camping, cu chiuvete și oglinzi', 'caption' => 'Grup sanitar'),
        )),
        'included' => array(
            'title' => 'Tot ce-ți trebuie, nimic în plus',
            'groups' => array(
                array('icon' => 'map-pin', 'title' => 'La locul tău', 'items' => array(
                    'Loc pentru rulotă sau autorulotă',
                    'Racorduri și servicii pe loc',
                    'Aproape de lac',
                    'Internet în zona de camping',
                    'Câinii sunt bineveniți',
                ), 'note' => array('text' => 'Vii cu cortul? ', 'link_text' => 'Vezi pagina Camping', 'link_offer' => 'camping')),
                array('icon' => 'trees', 'title' => 'În Grădină', 'items' => array(
                    'Opt dușuri cu apă caldă, non-stop',
                    'Douăsprezece toalete',
                    'Două bucătării comune',
                    'Două lacuri, unul în care se înoată',
                    'Terasă din lemn construită pe apă',
                    'Treizeci de hamace și vetre de foc',
                    'Pescuit sportiv și ponton',
                    'Domeniu împrejmuit, pază 24 din 24',
                ), 'note' => array('text' => 'Vrei o noapte cu baie proprie? ', 'link_text' => 'Alege un bungalow', 'link_offer' => 'bungalow')),
            ),
        ),
        'reviews' => array('title' => 'De la cei care au stat aici', 'authors' => array('Mihaela Mocanu', 'Rebeca Teban')),
        'prices' => array(
            'title' => 'Simplu, fără surprize',
            'text' => 'Prețul final îl vezi când alegi datele, înainte să plătești.',
            'unit' => '/ noapte',
            'rows' => array(
                array('label' => 'Rulotă sau autorulotă', 'value' => '80 lei'),
            ),
        ),
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoarele din camping și îmbuteliată, la recepție.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Ai curent, apă caldă și internet. Te rugăm doar să lași acasă electrocasnicele mari.'),
            array('icon' => 'calendar', 'title' => 'Vara, rezervă din timp', 'text' => 'Mai ales pentru weekendurile de vară și cât timp e deschis Transfăgărășanul.'),
        ),
        'faq' => array(
            array('q' => 'Primiți și autorulote?', 'a' => 'Da, și rulote, și autorulote. Ai racorduri și servicii pe loc, în zona de camping. Dacă ai întrebări despre rulota ta, sună-ne la 0721 333 164.'),
            array('q' => 'Unde fac duș?', 'a' => 'În băile comune: opt dușuri cu apă caldă, disponibile oricând, și douăsprezece toalete.'),
            array('q' => 'Pot să gătesc și afară din rulotă?', 'a' => 'Da. Ai două bucătării comune, iar seara focul se aprinde în vetrele amenajate.'),
            array('q' => 'Se poate înota?', 'a' => 'Da, în lacul mare. Are 8.000 de metri pătrați și un metru adâncime pe toată suprafața, fără zone adânci.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'Tariful de referință este 80 lei pe noapte. Prețul final îl vezi când alegi datele.'),
        ),
        'closing' => array('title' => 'Gata de drum', 'title_em' => 'spre poalele Făgărașului?', 'image' => 'home-hero-lake-centered-1280.webp'),
        'seo' => array(
            'title' => 'Camping rulote și autorulote, Brașov | Grădina Ielelor',
            'description' => 'Loc pentru rulotă sau autorulotă în Grădina Ielelor, Sâmbăta de Sus: racorduri și servicii pe loc, dușuri calde non-stop, bucătării comune și lac, sub Făgăraș.',
            'image' => 'rulote-hero-1600.webp',
        ),
    );

    $pages['evenimente'] = array(
        'title' => 'Festivaluri & evenimente',
        'booking_type' => '',
        'cta' => 'Cere ofertă',
        'cta_url' => home_url('/festival/'),
        'hero' => array(
            'image' => 'evenimente-munte-1600.webp',
            'alt' => 'Familii la un eveniment în aer liber în Grădina Ielelor, cu Munții Făgăraș în fundal',
            'h1' => 'Festivaluri și evenimente',
            'h1_em' => 'în Grădina Ielelor',
            'lead' => 'Nouă hectare la poalele Munților Făgăraș, cu scenă permanentă, două lacuri, cazare și parcare. Pentru festivaluri, petreceri și evenimente de familie.',
        ),
        'facts' => array(
            array('value' => '5.000', 'label' => 'persoane, cu aviz ISU'),
            array('value' => '6 × 7 m', 'label' => 'scenă permanentă'),
            array('value' => '58', 'label' => 'unități de cazare pe domeniu'),
            array('value' => 'cca. 1.000', 'label' => 'locuri de parcare'),
        ),
        'story' => array(
            'eyebrow' => 'Ce găzduim',
            'title' => 'Momente trăite',
            'title_em' => 'împreună.',
            'paragraphs' => array(
                'Grădina primește festivaluri de muzică cu camping, petreceri, evenimente de familie și evenimente private. Publicul doarme pe domeniu, iar după ultimul set focul, lacul și hamacele preiau seara.',
                'Pentru nunți și botezuri avem pavilionul de 225 mp de pe platou, cu terasă acoperită și loc pentru până la 150 de invitați. Pavilionul este disponibil din mai 2027.',
            ),
            'image' => 'festival-domeniu-960.webp',
            'alt' => 'Domeniul Grădina Ielelor văzut de sus, cu corturi, lacul și Munții Făgăraș în fundal',
        ),
        'calendar' => array(
            'title' => 'Ce pregătim în 2027',
            'text' => 'Festivaluri, petreceri și întâlniri pentru familii. Datele exacte și biletele le anunțăm pe măsură ce fiecare eveniment se confirmă.',
        ),
        'gallery' => array('title' => 'Vezi domeniul', 'images' => array_merge(gi_event_images(), array(
            array('src' => 'evenimente-apus-1280.webp', 'alt' => 'Apus de soare reflectat în lacul din Grădina Ielelor', 'caption' => 'Lacul, la apus'),
        ))),
        'included' => array(
            'title' => 'Totul pe același teren',
            'groups' => array(
                array('icon' => 'music', 'title' => 'Spațiile de program', 'items' => array(
                    'Scenă permanentă de 6 × 7 m, înaltă de 5 m',
                    'Platou înălțat de 3.000 mp',
                    'Pavilion de 225 mp cu terasă acoperită, disponibil din mai 2027',
                    'Lac de 8.000 mp cu ponton, în care se înoată',
                    'Lac de 2.000 mp cu terasă din lemn pe apă',
                )),
                array('icon' => 'trees', 'title' => 'Pentru organizatori', 'items' => array(
                    'Aviz ISU pentru 5.000 de persoane',
                    '6 ha împrejmuite, pază 24 din 24',
                    'Trei zone separate, pe același domeniu',
                    'Parcare de 3 ha, cca. 1.000 de locuri',
                    'La festivaluri, încă o parcare, de 9 ha',
                    '58 de unități de cazare, plus camping și rulote',
                    'Bucătărie licențiată pe domeniu',
                    'Opt dușuri calde non-stop, douăsprezece toalete',
                    'Cea mai apropiată casă, la circa 2 km',
                ), 'note' => array('text' => 'Cazare pentru artiști sau invitați? ', 'link_text' => 'Vezi bungalow-urile', 'link_offer' => 'bungalow')),
            ),
        ),
        'reviews' => array('title' => 'Ce spun oaspeții', 'authors' => array()),
        'prices' => null,
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E doar pentru igienă. Apa de băut se asigură separat, prin dozatoare și îmbuteliată.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Domeniul are curent pentru consumul lui. Pentru scenă, sunet și lumini, necesarul se stabilește din timp, împreună cu organizatorul.'),
            array('icon' => 'music', 'title' => 'Producția rămâne a organizatorului', 'text' => 'Sunetul, luminile, structurile temporare și toaletele suplimentare nu sunt incluse. Le planificăm împreună, din timp.'),
        ),
        'faq' => array(
            array('q' => 'Ce fel de evenimente găzduiți?', 'a' => 'Festivaluri de muzică cu camping, petreceri, evenimente de familie și evenimente private. Din mai 2027, odată cu pavilionul, și nunți sau botezuri.'),
            array('q' => 'Câți oameni încap?', 'a' => 'Domeniul are aviz ISU pentru 5.000 de persoane. Parcarea, de circa 1.000 de locuri, ajunge pentru aproximativ 3.000 de participanți; peste, se planifică teren suplimentar sau navetă.'),
            array('q' => 'Unde dorm participanții?', 'a' => 'Pe domeniu sunt 58 de unități: 4 bungalow-uri cu baie proprie, 4 corturi glamping cu pat și 50 cu saltele. Se adaugă zona de camping și rulote, iar la evenimentele mari ajută și pensiunile din satele din jur.'),
            array('q' => 'Ce asigurați voi și ce aduce organizatorul?', 'a' => 'Noi punem la dispoziție locul: scena, platoul, lacurile, grupurile sanitare, parcarea, cazarea și paza. Organizatorul aduce producția: sunet, lumini, generatoare, program, bilete și avizele evenimentului.'),
            array('q' => 'Pot organiza o nuntă sau un botez?', 'a' => 'Da, din mai 2027, când pavilionul de 225 mp este disponibil. Încap până la 150 de invitați, iar bucătăria licențiată e chiar pe domeniu.'),
            array('q' => 'Primiți și tabere sau grupuri?', 'a' => 'Da. Taberele de copii și grupurile găsesc aici un perimetru închis, pază 24 din 24 și cazare pe domeniu. Cere o ofertă și stabilim totul împreună.'),
        ),
        'closing' => array('title' => 'Ai un eveniment în minte?', 'title_em' => 'Hai să vorbim.', 'image' => 'intrare-munte-1280.webp'),
        'seo' => array(
            'title' => 'Locație evenimente în aer liber, Brașov | Grădina Ielelor',
            'description' => 'Festivaluri, petreceri și evenimente de familie în Grădina Ielelor, Sâmbăta de Sus: 9 ha, aviz ISU pentru 5.000 de persoane, scenă permanentă, lacuri și cazare.',
            'image' => 'evenimente-munte-1600.webp',
        ),
    );

    return apply_filters('gi_offer_pages', $pages);
}
