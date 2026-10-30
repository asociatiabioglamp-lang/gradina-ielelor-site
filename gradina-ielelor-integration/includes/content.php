<?php
/**
 * Conținut static de prezentare preluat din prototipul validat.
 * Nu este sursă pentru disponibilitate, preț final, comandă sau plată.
 */

if (!defined('ABSPATH')) {
    exit;
}

function gi_experiences() {
    return array(
        'camping' => array(
            'eyebrow' => 'Camping · cort & rulotă',
            'title' => 'Libertate în aer liber.',
            'emphasis' => 'Cu tot ce ai nevoie aproape.',
            'description' => 'Spații generoase pentru cort sau rulotă, facilități comune și acces la lac, terase și zonele de relaxare din Grădină.',
            'price' => '80 lei',
            'price_note' => '/ noapte · tarif public de referință',
            'booking_type' => 'camping-cort',
            'booking_label' => 'Rezervă pentru cort',
            'secondary_type' => 'camping-rulota',
            'secondary_label' => 'Rezervă pentru rulotă',
            'facilities' => array('Băi și dușuri comune', 'Loc de joacă', 'Lac pentru pescuit', 'Două bucătării dotate', 'Hamace și vetre de foc', 'Apă curentă și Wi-Fi', 'Două terase', 'Parcare gratuită'),
            'images' => array(
                array('src' => 'camping-day-960.webp', 'alt' => 'Corturi și spații verzi în campingul Grădina Ielelor', 'caption' => 'Camping în zi însorită'),
                array('src' => 'home-hero-sunny-960.webp', 'alt' => 'Rulote la lac cu munții în fundal', 'caption' => 'Rulote lângă lac'),
                array('src' => 'camping-night-960.webp', 'alt' => 'Corturi luminate seara în Grădina Ielelor', 'caption' => 'Seri liniștite la cort'),
                array('src' => 'festival-lake-640.webp', 'alt' => 'Lac și ponton în zona de camping', 'caption' => 'Acces la lac și ponton'),
            ),
        ),
        'glamping' => array(
            'eyebrow' => 'Glamping · confort în natură',
            'title' => 'Natura, cu confort.',
            'emphasis' => 'Muntele la intrarea în cort.',
            'description' => 'Corturi spațioase și atent pregătite pentru două persoane, cu opțiune de pat suplimentar și acces la toate facilitățile comune.',
            'price' => 'de la 195 lei',
            'price_note' => '/ noapte · 235 lei vineri și sâmbătă',
            'booking_type' => 'glamping',
            'booking_label' => 'Rezervă glamping',
            'facilities' => array('4 unități de glamping', '2 + 1 oaspeți', 'Băi și dușuri comune', 'Loc de joacă și lac', 'Două bucătării dotate', 'Hamace și vetre de foc', 'Wi-Fi și două terase', 'Parcare gratuită'),
            'images' => array(
                array('src' => 'super-glamping-1600.webp', 'alt' => 'Unități de glamping cu Munții Făgăraș în fundal', 'caption' => 'Glamping cu vedere la munte'),
                array('src' => 'glamping-4-640.webp', 'alt' => 'Interior luminos al unei unități de glamping', 'caption' => 'Interior cald și luminos'),
                array('src' => 'glamping-5-640.webp', 'alt' => 'Cort de glamping pe platformă din lemn', 'caption' => 'Platformă proprie'),
                array('src' => 'glamping-6-1280.webp', 'alt' => 'Detalii din interiorul cortului de glamping', 'caption' => 'Detalii pentru relaxare'),
                array('src' => 'glamping-3-640.webp', 'alt' => 'Cort de glamping în mijlocul vegetației', 'caption' => 'Natura chiar la intrare'),
                array('src' => 'hero-1280.webp', 'alt' => 'Unități de glamping la apus', 'caption' => 'Apus în Grădina Ielelor'),
            ),
        ),
        'bungalow' => array(
            'eyebrow' => 'Bungalow · intimitate la lac',
            'title' => 'Un spațiu doar al tău.',
            'emphasis' => 'Natura rămâne la ușă.',
            'description' => 'Cazare cu baie proprie, dormitor, zonă de relaxare și terasă, cu acces la spațiile verzi și facilitățile Grădinii.',
            'price' => 'de la 350 lei',
            'price_note' => '/ noapte · 450 lei vineri și sâmbătă',
            'booking_type' => 'bungalow',
            'booking_label' => 'Rezervă bungalow',
            'facilities' => array('Baie proprie', 'Terasă și dormitor', 'Loc de joacă și lac', 'Două bucătării dotate', 'Hamace și vetre de foc', 'Apă curentă și Wi-Fi', 'Două terase', 'Parcare gratuită'),
            'images' => array(
                array('src' => 'bungalow-detail-1600.webp', 'alt' => 'Bungalow Grădina Ielelor cu terasă', 'caption' => 'Bungalow cu terasă'),
                array('src' => 'bungalow-interior-960.webp', 'alt' => 'Interiorul bungalow-ului cu dormitor și zonă de relaxare', 'caption' => 'Interior și zonă de relaxare'),
                array('src' => 'bungalow-card-960.webp', 'alt' => 'Bungalow în Grădina Ielelor', 'caption' => 'Intimitate în natură'),
                array('src' => 'spiral-bungalow-640.webp', 'alt' => 'Bungalow și peisajul din Grădina Ielelor', 'caption' => 'Natura la ușă'),
            ),
        ),
    );
}

function gi_events_2027() {
    return array(
        array('title' => 'Open Party — deschiderea sezonului', 'period' => 'Primăvara 2027 · data exactă se confirmă', 'category' => 'petrecere', 'category_label' => 'Petrecere'),
        array('title' => '3SÓ Festival', 'period' => '2027 · perioada se confirmă', 'category' => 'festival', 'category_label' => 'Festival'),
        array('title' => 'Festival de folk', 'period' => '2027 · proiect în pregătire', 'category' => 'festival', 'category_label' => 'Festival'),
        array('title' => 'Festival de stand-up', 'period' => '2027 · proiect în pregătire', 'category' => 'festival', 'category_label' => 'Festival'),
        array('title' => 'Festival de rock', 'period' => '2027 · proiect în pregătire', 'category' => 'festival', 'category_label' => 'Festival'),
        array('title' => 'KAYA Family Festival', 'period' => '2027 · perioada se confirmă', 'category' => 'familie', 'category_label' => 'Pentru familii'),
    );
}

function gi_event_images() {
    return array(
        array('src' => 'festival-scena-960.webp', 'alt' => 'Festival și camping pe domeniul Grădina Ielelor', 'caption' => 'Festivaluri în mijlocul naturii'),
        array('src' => 'festival-family-640.webp', 'alt' => 'Familii și copii la un eveniment în Grădina Ielelor', 'caption' => 'Experiențe pentru familii'),
        array('src' => 'festival-pavilion-640.webp', 'alt' => 'Muzică și invitați la pavilionul Grădina Ielelor', 'caption' => 'Petreceri și întâlniri'),
        array('src' => 'private-events-card-960.webp', 'alt' => 'Terasă din lemn pentru evenimente private', 'caption' => 'Evenimente private la lac'),
    );
}

function gi_reviews() {
    return array(
        array('text' => 'Un camping superb! Nu ne-a lipsit nimic: chicinetă echipată, băi și dușuri curate cu apă caldă, loc de grătar și de foc. Și un view minunat.', 'author' => 'Rebeca Teban', 'context' => 'Experiență camping'),
        array('text' => 'Locația este extraordinară, glampingul este spațios și curat. Este o bucurie să te trezești cu o asemenea priveliște. Recomand cu căldură familiilor cu copii.', 'author' => 'Ada-Casandra Ștefan', 'context' => 'Experiență glamping'),
        array('text' => 'Loc de campare idilic. Foarte curat, amenajat cu atenție la detalii, cu vedere la Munții Făgăraș. Spațiul este generos, cu locuri pentru rulote, corturi și glamping.', 'author' => 'Mihaela Mocanu', 'context' => 'Experiență în Grădina Ielelor'),
    );
}

/**
 * Conținutul paginii dedicate Glamping.
 * Fapte din prezentările proprietarului și din catalogul public; tarifele sunt referință, nu calcul.
 * Nepublicat până la confirmare: animale de companie, ore check-in/check-out, număr minim de nopți.
 */
function gi_glamping_page() {
    return array(
        'facts' => array(
            array('value' => '4', 'label' => 'corturi cu pat'),
            array('value' => '2 + 1', 'label' => 'persoane, cu pat suplimentar'),
            array('value' => 'Platformă', 'label' => 'de lemn, cu masă la intrare'),
            array('value' => '195 lei', 'label' => 'de la, pe noapte'),
        ),
        'tent' => array(
            'Pat adevărat, cu lenjerie',
            'Covor și noptieră',
            'Platformă de lemn, ridicată de la sol',
            'Masă și scaune la intrare',
            'Pat suplimentar, la cerere',
        ),
        'garden' => array(
            'Opt dușuri cu apă caldă, non-stop',
            'Douăsprezece toalete',
            'Două bucătării comune',
            'Două lacuri, unul în care se înoată',
            'Terasă din lemn construită pe apă',
            'Treizeci de hamace și vetre de foc',
            'Internet și parcare proprie',
            'Domeniu împrejmuit, pază 24 din 24',
        ),
        'prices' => array(
            array('label' => 'Nopți în timpul săptămânii', 'value' => 'de la 195 lei'),
            array('label' => 'Vineri și sâmbătă', 'value' => '235 lei'),
            array('label' => 'Pat suplimentar', 'value' => '55 lei'),
        ),
        'notes' => array(
            array('icon' => 'droplet', 'title' => 'Apa de la robinet nu e potabilă', 'text' => 'E pentru duș, spălat și toalete. Apa de băut o găsești la dozatoare și îmbuteliată.'),
            array('icon' => 'sun', 'title' => 'Energie proprie, din soare și GPL', 'text' => 'Ai curent, apă caldă și internet. Te rugăm doar să lași acasă electrocasnicele mari.'),
            array('icon' => 'flame', 'title' => 'Focul, doar în vetrele amenajate', 'text' => 'Avem vetre de foc pregătite pe domeniu. Seara, acolo se adună toată lumea.'),
        ),
        'distances' => array(
            array('place' => 'Transfăgărășan', 'value' => 'cca. 20 min'),
            array('place' => 'Făgăraș', 'value' => '30 km'),
            array('place' => 'Brașov', 'value' => 'cca. 60 km'),
            array('place' => 'Sibiu', 'value' => 'cca. 70 km'),
        ),
        'faq' => array(
            array('q' => 'Ce găsesc în cort?', 'a' => 'Un pat cu lenjerie, covor și noptieră. Cortul stă pe o platformă de lemn, iar la intrare ai o masă cu scaune. Nu e camping de supraviețuire.'),
            array('q' => 'Câte persoane încap într-un cort?', 'a' => 'Două persoane. Dacă mai vine cineva, poți adăuga un pat suplimentar când faci rezervarea.'),
            array('q' => 'Baia e în cort?', 'a' => 'Nu, băile sunt comune: opt dușuri cu apă caldă, disponibile oricând, și douăsprezece toalete. Dacă vrei baie proprie, alege un bungalow.'),
            array('q' => 'Am curent și internet?', 'a' => 'Da. Domeniul funcționează pe energie proprie, solară și GPL, așa că ai curent, apă caldă și internet. Lasă acasă doar aparatele electrice mari.'),
            array('q' => 'Cât costă o noapte?', 'a' => 'De la 195 lei în timpul săptămânii și 235 lei vineri și sâmbătă. Patul suplimentar costă 55 lei pe noapte. Prețul final îl vezi când alegi datele.'),
        ),
        'review_authors' => array('Ada-Casandra Ștefan', 'Mihaela Mocanu'),
    );
}

/**
 * Cardurile celor patru oferte de pe pagina principală (hub de rezervare).
 * Fiecare card deschide pagina dedicată; până la publicarea ei, duce la secțiunea detaliată de mai jos.
 */
function gi_offer_cards() {
    return array(
        'glamping' => array('text' => 'Corturi cu pat, pe platformă de lemn, cu muntele la intrare.', 'price' => 'de la 195 lei / noapte', 'image' => 'super-glamping-1600.webp', 'alt' => 'Corturi de glamping cu Munții Făgăraș în fundal'),
        'camping' => array('text' => 'Loc pentru cort sau rulotă, lângă lac, cu bucătării și dușuri calde.', 'price' => '80 lei / noapte', 'image' => 'camping-day-960.webp', 'alt' => 'Corturi și spații verzi în campingul Grădina Ielelor'),
        'bungalow' => array('text' => 'Baie proprie, terasă și liniște, pe malul lacului mare.', 'price' => 'de la 350 lei / noapte', 'image' => 'bungalow-card-960.webp', 'alt' => 'Bungalow în Grădina Ielelor'),
        'evenimente' => array('text' => 'Festivaluri, petreceri și evenimente private pe un domeniu de 9 hectare.', 'price' => 'Ofertă la cerere', 'image' => 'festival-scena-960.webp', 'alt' => 'Festival și camping pe domeniul Grădina Ielelor'),
    );
}
