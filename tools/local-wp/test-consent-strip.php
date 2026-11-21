<?php
/**
 * Test pentru plasa de siguranță a cookie-urilor (GI_Consent::strip_trackers), fără WordPress: php tools/local-wp/test-consent-strip.php
 * Verifică: ce se scoate (coduri vechi, încărcarea Tag Manager / Facebook / TikTok), ce NU se scoate (scripturile asociației, JSON-LD, configurația bannerului),
 * cazuri-limită (comentarii, „</scripts”, atribute cu „>”, litere mari, etichete neînchise) și timpul pe pagini mari.
 */
define('ABSPATH', __DIR__ . '/');
$GLOBALS['gi_test_options'] = array('consent_enabled' => 1);
function gi_options() { return $GLOBALS['gi_test_options']; }
function apply_filters($tag, $value) { return $value; }
function add_action() {}
function add_shortcode() {}
require __DIR__ . '/../../gradina-ielelor-integration/includes/class-gi-consent.php';

$failures = 0;
function check($ok, $text) { global $failures; echo ($ok ? '[OK]     ' : '[EROARE] ') . $text . "\n"; if (!$ok) { $failures++; } }
function strip($body, $enabled = 1) { $GLOBALS['gi_test_options']['consent_enabled'] = $enabled; return GI_Consent::strip_trackers('<html><head>' . $body . '</head><body></body></html>'); }

$gtm = "<script data-cfasync=\"false\">(function(w,d,s,l,i){j.src='//www.googletagmanager.com/gtm.js?id='+i;})(window,document,'script','dataLayer','GTM-5XNZX5QT');</script>";
$ns = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5XNZX5QT" height="0" width="0"></iframe></noscript>';
$own = '<script id="gi-consent-js-before">window.GI_CONSENT={"gtm":"GTM-5XNZX5QT","attr":[]};</script>';
$ld = '<script type="application/ld+json">{"sameAs":["https://www.facebook.com/IELELEgradina"]}</script>';
$legit = '<script id="legit">window.__legit = true;</script>';

$o = strip($gtm . $ns . $own . $ld . $legit);
check(false === strpos($o, 'gtm.js?id=') && false === strpos($o, 'ns.html'), 'codul GTM4WP (script și iframe din noscript) se scoate cu bannerul pornit');
check(false !== strpos($o, 'GI_CONSENT') && false !== strpos($o, 'IELELEgradina') && false !== strpos($o, 'id="legit"'), 'configurația bannerului, JSON-LD cu link Facebook și scriptul asociației rămân');
$o = strip($gtm . $ns, 0);
check(false !== strpos($o, 'gtm.js?id=') && false !== strpos($o, 'ns.html'), 'cu bannerul oprit, codul Tag Manager din pagină rămâne (revenire)');
$o = strip("<script>fbq('init', '371463157145157');</script><script>ttq.load('D7P8DJBC77U3CRQJR7P0');</script><script async src=\"https://www.googletagmanager.com/gtag/js?id=G-T0Y2TVCYBD\"></script>" . $legit, 0);
check(false === strpos($o, '371463157145157') && false === strpos($o, 'D7P8DJBC77U3CRQJR7P0') && false === strpos($o, 'G-T0Y2TVCYBD') && false !== strpos($o, 'id="legit"'), 'ID-urile vechi se scot și cu bannerul oprit; scriptul asociației rămâne');

// cazuri-limită
$o = strip('<!-- vechi: <script async src="https://www.googletagmanager.com/gtag/js?id=G-T0Y2TVCYBD"> --><div id="d">x</div>' . $legit);
check(false !== strpos($o, '<div id="d">x</div>') && false !== strpos($o, 'id="legit"'), 'un <script> neînchis dintr-un comentariu nu înghite ce urmează');
$o = strip('<script>var a="</scripts>";var b="googletagmanager.com/gtm.js";</script>' . $legit);
check(false === strpos($o, 'gtm.js') && false !== strpos($o, 'id="legit"'), '„</scripts” dintr-un script nu îl închide (ca în browser): scriptul cu cod de încărcare se scoate întreg');
$o = strip('<!--><script>window.__dupa_comentariu_gol=1;</script><script async src="https://www.googletagmanager.com/gtm.js?id=GTM-5XNZX5QT"></script>');
check(false !== strpos($o, '__dupa_comentariu_gol') && false === strpos($o, 'gtm.js'), '<!--> este un comentariu gol: ce urmează se tratează normal');
$o = strip('<img alt="a>b" src="https://www.facebook.com/tr?id=1&ev=PageView"><img src="/poza.jpg">');
check(false === strpos($o, 'facebook.com/tr') && false !== strpos($o, '/poza.jpg'), 'imaginea Facebook cu atribut care conține „>” se scoate, altă imagine rămâne');
$o = strip('<SCRIPT SRC="HTTPS://WWW.GOOGLETAGMANAGER.COM/GTM.JS?ID=GTM-5XNZX5QT"></SCRIPT><NOSCRIPT><IFRAME SRC="https://www.googletagmanager.com/ns.html?id=X"></IFRAME></NOSCRIPT>');
check(false === stripos($o, 'googletagmanager'), 'litere mari: se scot');
$o = strip('<script async src="https://www.googletagmanager.com/gtm.js?id=GTM-5XNZX5QT"><div id="rest">pagina continuă</div>');
check(false !== strpos($o, 'googletagmanager') && false !== strpos($o, 'id="rest"'), 'script care nu se închide nicăieri: se lasă neatins (nu se șterge restul paginii)');
$o = strip('<script async src="https://www.googletagmanager.com/gtm.js?id=GTM-5XNZX5QT">' . $legit);
check(false === strpos($o, 'gtm.js'), 'script fără </script> urmat de alt script: pentru browser, al doilea e corpul primului (nu rulează), deci se scot împreună');
$o = strip('<link rel="preload" href="https://connect.facebook.net/en_US/fbevents.js" as="script"><link rel="stylesheet" href="/a.css">');
check(false === strpos($o, 'connect.facebook.net') && false !== strpos($o, '/a.css'), '<link> spre Facebook se scoate, celelalte rămân');
$o = GI_Consent::strip_trackers('{"json":"googletagmanager.com/gtm.js"}');
check($o === '{"json":"googletagmanager.com/gtm.js"}', 'un răspuns care nu e o pagină HTML (fără <html) rămâne neatins');

// pagini mari
$big = '<script id="mare">/*' . str_repeat('x', 12000000) . ' G-T0Y2TVCYBD */</script>' . $legit;
$t = microtime(true);
$o = strip($big);
$ms = (microtime(true) - $t) * 1000;
check(false === strpos($o, 'id="mare"') && false !== strpos($o, 'id="legit"') && $ms < 1500, 'script de 12 MB cu ID vechi: scos fără limită PCRE (' . round($ms) . ' ms)');
$many = str_repeat('<!-- c -->', 100000) . $gtm;
$t = microtime(true);
$o = strip($many);
$ms = (microtime(true) - $t) * 1000;
check(false === strpos($o, 'gtm.js') && $ms < 1500, '100.000 de comentarii (1 MB): timp liniar (' . round($ms) . ' ms)');
$tiny = str_repeat('<script>var a=1;</script>', 40000) . $gtm;
$t = microtime(true);
$o = strip($tiny);
$ms = (microtime(true) - $t) * 1000;
check(false === strpos($o, 'gtm.js') && substr_count($o, '<script>var a=1;</script>') === 40000 && $ms < 1500, '40.000 de scripturi mici: timp liniar, niciunul pierdut (' . round($ms) . ' ms)');

echo $failures === 0 ? "\nTOATE VERIFICĂRILE AU TRECUT\n" : "\n$failures VERIFICĂRI AU EȘUAT\n";
exit($failures === 0 ? 0 : 1);
