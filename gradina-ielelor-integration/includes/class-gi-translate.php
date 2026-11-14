<?php
/**
 * Coșul, finalizarea, pagina de mulțumire și contul clientului în limba română.
 *
 * De ce: WooCommerce rulează pe site fără traducerea românească (etichete ca „Billing details”, „Place order”, „Thank you. Your order has been received.”).
 *
 * Cum funcționează, în siguranță:
 *  - înlocuiește numai textele din lista de mai jos, numai pe paginile publice și numai când textul NU a fost deja tradus
 *    (dacă se instalează mai târziu traducerea oficială, ea are prioritate);
 *  - nu atinge administrarea, AJAX-ul din administrare, REST, XML-RPC și emailurile către clienți (emailurile ar rămâne altfel pe jumătate în română);
 *  - ca să nu strice traducerea automată a vizitatorilor (GTranslate pornește de la româna din pagină), traduce numai când limba site-ului e româna.
 *
 * Filtre: gi_translate_active (true/false), gi_translate_woo_map și gi_translate_page_map (tablouri „text englezesc” => „text românesc”).
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Translate {
    /** @var bool */
    private static $email = false;

    public static function init() {
        add_filter('gettext', array(__CLASS__, 'gettext'), 20, 3);
        add_filter('gettext_with_context', array(__CLASS__, 'gettext_with_context'), 20, 4);
        // Cât timp se generează un email, nu traducem nimic: emailurile au propriul text.
        add_action('woocommerce_email_header', array(__CLASS__, 'email_started'), 0);
        // Emailurile „text simplu” nu au antet HTML: începutul tabelului comenzii are același rol.
        add_action('woocommerce_email_before_order_table', array(__CLASS__, 'email_started'), 0);
        // După trimiterea emailului, restul cererii (de ex. pagina de mulțumire) se traduce din nou.
        add_action('woocommerce_email_sent', array(__CLASS__, 'email_finished'), 99);
    }

    public static function email_started() {
        self::$email = true;
    }

    public static function email_finished() {
        self::$email = false;
    }

    private static function enabled() {
        static $locale_ok = null;
        if (self::$email || is_admin() || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || (defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }
        if (null === $locale_ok) {
            $locale_ok = (bool) apply_filters('gi_translate_active', 0 === strpos((string) get_locale(), 'ro'));
        }
        return $locale_ok;
    }

    private static function on_shop_page() {
        return (function_exists('is_cart') && is_cart()) || (function_exists('is_checkout') && is_checkout()) || (function_exists('is_account_page') && is_account_page());
    }

    public static function gettext($translation, $text, $domain) {
        return self::translate($translation, $text, $domain);
    }

    public static function gettext_with_context($translation, $text, $context, $domain) {
        return self::translate($translation, $text, $domain);
    }

    private static function translate($translation, $text, $domain) {
        if ($translation !== $text || !is_string($text) || '' === $text || !self::enabled()) {
            return $translation;
        }
        if ('woocommerce' === $domain) {
            $map = self::woo_map();
            if (isset($map[$text])) {
                return $map[$text];
            }
        }
        // Textele din antetul temei (pașii Coș → Finalizare → Comandă), numai pe paginile coșului, plății și contului.
        $page_map = self::page_map();
        if (isset($page_map[$text]) && self::on_shop_page()) {
            return $page_map[$text];
        }
        return $translation;
    }

    /**
     * Text englezesc (exact cum e în WooCommerce) => text românesc.
     *
     * @return array<string,string>
     */
    private static function woo_map() {
        static $map = null;
        if (null === $map) {
            $map = apply_filters('gi_translate_woo_map', array(
                // Coș
                'Cart' => 'Coș',
                'Cart totals' => 'Totalul rezervării',
                'Product' => 'Rezervare',
                'Price' => 'Preț',
                'Remove item' => 'Șterge',
                'Update cart' => 'Actualizează coșul',
                'Proceed to checkout' => 'Continuă spre plată',
                'View cart' => 'Vezi coșul',
                'Checkout' => 'Finalizare rezervare',
                'Your cart is currently empty.' => 'Coșul tău este gol.',
                'Return to shop' => 'Înapoi la rezervări',
                'No products in the cart.' => 'Nu ai nimic în coș.',
                // Reducere
                'Have a coupon?' => 'Ai un cod de reducere?',
                'Click here to enter your code' => 'Apasă aici ca să-l introduci',
                'If you have a coupon code, please apply it below.' => 'Dacă ai un cod de reducere, scrie-l mai jos.',
                'Coupon code' => 'Cod de reducere',
                'Apply coupon' => 'Aplică codul',
                'Coupon:' => 'Cod de reducere:',
                // Finalizare
                'Returning customer?' => 'Ai deja cont?',
                'Click here to login' => 'Apasă aici ca să te autentifici',
                'Billing details' => 'Date de facturare',
                'Additional information' => 'Informații suplimentare',
                'Your order' => 'Rezervarea ta',
                'Place order' => 'Finalizează rezervarea',
                'First name' => 'Prenume',
                'Last name' => 'Nume',
                'Company name' => 'Numele firmei',
                'Country / Region' => 'Țara',
                'Street address' => 'Adresa',
                'House number and street name' => 'Strada și numărul',
                'Apartment, suite, unit, etc.' => 'Bloc, scară, apartament etc.',
                'Postcode / ZIP' => 'Cod poștal',
                'Town / City' => 'Localitatea',
                'County' => 'Județ',
                'Phone' => 'Telefon',
                'Email address' => 'Adresa de email',
                'Create account password' => 'Alege o parolă pentru contul tău',
                'Password' => 'Parolă',
                'Order notes' => 'Observații',
                'Notes about your order, e.g. special notes for delivery.' => 'Observații despre rezervare, de exemplu ora sosirii sau alte cerințe.',
                'optional' => 'opțional',
                'terms and conditions' => 'termenii și condițiile',
                'I have read and agree to the website %s' => 'Am citit și sunt de acord cu %s site-ului',
                'Quantity' => 'Cantitate',
                'Create an account?' => 'Creezi un cont?',
                'Pay for order' => 'Plătește rezervarea',
                'Select a country / region&hellip;' => 'Alege țara&hellip;',
                'Select an option&hellip;' => 'Alege o opțiune&hellip;',
                '%s has been added to your cart.' => '%s a fost adăugat în coș.',
                'The following problems were found:' => 'Am găsit următoarele probleme:',
                'Invalid billing email address' => 'Adresa de email nu este validă.',
                '%s is not a valid email address.' => '%s nu este o adresă de email validă.',
                '%s is not a valid phone number.' => '%s nu este un număr de telefon valid.',
                '%s is not a valid postcode / ZIP.' => '%s nu este un cod poștal valid.',
                'Please enter an address to continue.' => 'Scrie o adresă ca să continui.',
                '%s is a required field.' => '%s este un câmp obligatoriu.',
                'Billing %s' => '%s (facturare)',
                'Please read and accept the terms and conditions to proceed with your order.' => 'Citește și acceptă termenii și condițiile ca să continui.',
                // Mulțumire
                'Thank you. Your order has been received.' => 'Mulțumim! Am primit rezervarea ta.',
                'Order number:' => 'Număr rezervare:',
                'Date:' => 'Data:',
                'Payment method:' => 'Metodă de plată:',
                'Order details' => 'Detaliile rezervării',
                'Billing address' => 'Adresa de facturare',
                'Order received' => 'Rezervare primită',
                // Cont
                'My account' => 'Contul meu',
                'Dashboard' => 'Panou',
                'Orders' => 'Rezervări',
                'Downloads' => 'Descărcări',
                'Addresses' => 'Adrese',
                'Account details' => 'Detaliile contului',
                'Log out' => 'Deconectare',
                'Login' => 'Autentificare',
                'Log in' => 'Autentifică-te',
                'Register' => 'Înregistrare',
                'Username or email address' => 'Utilizator sau email',
                'Username or email' => 'Utilizator sau email',
                'Remember me' => 'Ține-mă minte',
                'Lost your password?' => 'Ai uitat parola?',
                'Lost password' => 'Parolă uitată',
                'Reset password' => 'Resetează parola',
                'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.' => 'Ai uitat parola? Scrie utilizatorul sau emailul. Îți trimitem pe email un link ca să alegi o parolă nouă.',
            ));
            $map = is_array($map) ? $map : array();
        }
        return $map;
    }

    /**
     * Texte din tema site-ului (orice domeniu), numai pe paginile coșului, plății și contului.
     *
     * @return array<string,string>
     */
    private static function page_map() {
        static $map = null;
        if (null === $map) {
            $map = apply_filters('gi_translate_page_map', array(
                'Shopping cart' => 'Coș',
                'Checkout' => 'Finalizare',
                'Order complete' => 'Rezervare finalizată',
            ));
            $map = is_array($map) ? $map : array();
        }
        return $map;
    }
}
