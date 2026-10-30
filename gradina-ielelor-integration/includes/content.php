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
 * Cardurile celor cinci categorii (pagina principală și „Poate te tentează și” de pe paginile dedicate).
 * Fiecare card deschide pagina dedicată; până la publicarea ei, duce la secțiunea detaliată de pe pagina principală.
 */
function gi_offer_cards() {
    return array(
        'bungalow' => array('text' => 'Baie proprie, terasă și liniște, pe malul lacului mare.', 'price' => 'de la 350 lei / noapte', 'image' => 'bungalow-card-960.webp', 'alt' => 'Bungalow în Grădina Ielelor'),
        'glamping' => array('text' => 'Corturi cu pat, pe platformă de lemn, cu muntele la intrare.', 'price' => 'de la 195 lei / noapte', 'image' => 'super-glamping-1600.webp', 'alt' => 'Corturi de glamping cu Munții Făgăraș în fundal'),
        'camping' => array('text' => 'Vino cu cortul tău: loc lângă lac, bucătării comune și dușuri calde.', 'price' => '80 lei / noapte', 'image' => 'camping-day-960.webp', 'alt' => 'Corturi și spații verzi în campingul Grădina Ielelor'),
        'rulote' => array('text' => 'Rulotă sau autorulotă? Racorduri și servicii pe loc, lângă lac.', 'price' => '80 lei / noapte', 'image' => 'home-hero-sunny-960.webp', 'alt' => 'Rulote la lac cu munții în fundal'),
        'evenimente' => array('text' => 'Festivaluri, petreceri și evenimente de familie pe un domeniu de 9 hectare.', 'price' => 'Ofertă la cerere', 'image' => 'festival-scena-960.webp', 'alt' => 'Festival și camping pe domeniul Grădina Ielelor'),
    );
}
