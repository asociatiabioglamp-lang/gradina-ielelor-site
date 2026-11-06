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
                array('src' => 'camping-day-960.webp', 'alt' => 'Corturi și sălcii în campingul Grădina Ielelor, cu Munții Făgăraș în fundal', 'caption' => 'Camping în zi însorită'),
                array('src' => 'festival-lake-640.webp', 'alt' => 'Lacul cu pontonul din lemn, cu Munții Făgăraș în fundal', 'caption' => 'Acces la lac și ponton'),
                array('src' => 'camping-night-960.webp', 'alt' => 'Vetre de foc cu bănci și ghirlande de lumini, la apus, în Grădina Ielelor', 'caption' => 'Seara, la vatra de foc'),
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
                array('src' => 'glamping-platforme-apus-960.webp', 'alt' => 'Corturi de glamping pe platforme de lemn, la apus, cu cerul colorat în fundal', 'caption' => 'Corturile stau pe platforme de lemn'),
                array('src' => 'glamping-platforme-camp-960.webp', 'alt' => 'Corturi de glamping pe platforme de lemn, într-o zi senină, cu un copac în prim-plan', 'caption' => 'Corturile, într-o zi senină'),
                array('src' => 'glamping-platforma-intrare-960.webp', 'alt' => 'Intrarea unui cort de glamping, pe platformă de lemn, cu două scaune, o măsuță și un covor', 'caption' => 'Masă și scaune la intrare'),
                array('src' => 'glamping-interior-pat-720.webp', 'alt' => 'Interiorul unui cort de glamping, cu pat dublu, perne galbene, noptieră și covoare', 'caption' => 'Pat adevărat, cu lenjerie'),
                array('src' => 'glamping-interior-paturi-720.webp', 'alt' => 'Interiorul unui cort de glamping, cu două paturi, comodă, noptieră și covoare', 'caption' => 'Interior cu paturi adevărate'),
                array('src' => 'glamping-interior-comoda-720.webp', 'alt' => 'Interiorul unui cort de glamping, cu pat, comodă, covor și un puf', 'caption' => 'Mobilier în interior'),
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
                array('src' => 'bungalow-hero-cer-1600.webp', 'alt' => 'Bungalow cu terasă, sub un cer albastru, în Grădina Ielelor', 'caption' => 'Bungalow cu terasă'),
                array('src' => 'bungalow-dormitor-alb-960.webp', 'alt' => 'Dormitor cu două paturi albe într-un bungalow, cu jaluzele de lemn', 'caption' => 'Dormitorul'),
                array('src' => 'bungalow-relaxare-720.webp', 'alt' => 'Zona de relaxare dintr-un bungalow, cu canapea, covor și paravan de lemn', 'caption' => 'Zona de relaxare'),
                array('src' => 'bungalow-lac-baldachin-1280.webp', 'alt' => 'Lacul cu reflexii, pontonul și Munții Făgăraș, văzute de lângă baldachinul de pe mal', 'caption' => 'Lacul, la câțiva pași'),
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
        array('src' => 'intrare-munte-960.webp', 'alt' => 'Arcadă din lemn cu numele Grădinii, loc de fotografie, cu domeniul și Munții Făgăraș în fundal', 'caption' => 'Locul de fotografie de la intrare'),
        array('src' => 'festival-scena-960.webp', 'alt' => 'Spectacol de dansuri populare pe scenă, cu copii în public, la un eveniment în Grădina Ielelor', 'caption' => 'Festivaluri în mijlocul naturii'),
        array('src' => 'evenimente-foc-960.webp', 'alt' => 'Oaspeți în jurul focului de tabără, seara, în Grădina Ielelor', 'caption' => 'Seara, în jurul focului'),
        array('src' => 'festival-pavilion-640.webp', 'alt' => 'Oaspeți sub o terasă acoperită din lemn, la căderea serii', 'caption' => 'Petreceri și întâlniri'),
        array('src' => 'private-events-card-960.webp', 'alt' => 'Terasă acoperită din lemn, cu mese și scaune, pentru evenimente private', 'caption' => 'Evenimente private'),
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
        'bungalow' => array('text' => 'Baie proprie, terasă și liniște, pe malul lacului mare.', 'price' => 'de la 350 lei / noapte', 'image' => 'bungalow-munte-card-960.webp', 'alt' => 'Bungalow în Grădina Ielelor'),
        'glamping' => array('text' => 'Corturi cu pat, pe platformă de lemn, cu muntele la intrare.', 'price' => 'de la 195 lei / noapte', 'image' => 'glamping-platforme-camp-960.webp', 'alt' => 'Corturi de glamping pe platforme de lemn, într-o zi senină'),
        'camping' => array('text' => 'Cu cortul, cu rulota sau cu autorulota: loc lângă lac, dușuri calde și bucătării comune.', 'price' => '80 lei / noapte', 'image' => 'camping-card-960.webp', 'alt' => 'Corturi în campingul Grădina Ielelor, cu Munții Făgăraș în fundal'),
        'festivaluri' => array('text' => 'Festivaluri, tabere de copii, boot camp și retreaturi, pe un domeniu păzit.', 'price' => 'Ofertă la cerere', 'image' => 'evenimente-card-960.webp', 'alt' => 'Oaspeți la un eveniment în aer liber, cu Munții Făgăraș în fundal'),
        'private' => array('text' => 'Nunți, botezuri, majorate și petreceri de familie, în aer liber, cu munții în față.', 'price' => 'Ofertă la cerere', 'image' => 'intrare-munte-960.webp', 'alt' => 'Arcada din lemn cu numele Grădinii, cu Munții Făgăraș în fundal'),
    );
}
