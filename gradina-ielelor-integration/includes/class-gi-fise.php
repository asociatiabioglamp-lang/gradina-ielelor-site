<?php
/**
 * Fișa de cazare: la recepție, pentru fiecare adult, din Zona echipei → Recepție → „Fișă de cazare”.
 *
 * Ce face:
 *  - datele titularului se iau din zona de jos a actului de identitate (citită în browser, assets/js/mrz.js: cifre de control, valabilitate, CNP numai dacă se
 *    potrivește cu actul), din rezervare (domiciliul, telefonul, datele șederii) sau se scriu de mână; actul NU se copiază și nu se păstrează imaginea lui;
 *  - fișa e în română și engleză, cu semnătura turistului și a recepționerului (desenate pe ecran) și confirmarea că turistul a fost informat despre prelucrarea
 *    datelor (textul se poate schimba din Setări; până îl dă juristul, rămâne textul provizoriu);
 *  - registrul fișelor îl vede numai administratorul (dreptul gi_fise_vezi): căutare, descărcare CSV, ștergere, termen de păstrare ales de proprietară;
 *    recepția doar înscrie fișe și vede fișa abia înscrisă (cel mult o oră), nu și registrul;
 *  - istoricul (Zona echipei → Istoric) păstrează cine a înscris, a șters sau a descărcat, fără nume, CNP sau alte date ale turiștilor.
 *
 * Datele stau într-un tabel al site-ului (gi_fise), care rămâne și dacă pluginul e șters (sunt acte ținute pe termen legal): se șterge din registru.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Fise {
    const DB_OPTION = 'gi_fise_db';
    const DB_VERSION = '1';
    const SETTINGS = 'gi_fisa_setari';
    const DEFAULT_GDPR = 'Turistul a fost informat despre prelucrarea datelor sale personale (Regulamentul UE 2016/679) și despre scopul colectării lor, conform notei de informare de la recepție.';

    /** @var array<string,string> Valorile trimise la ultima încercare, ca formularul să nu se golească după o greșeală. */
    private static $sticky = array();

    public static function init() {
        add_action('init', array(__CLASS__, 'maybe_install'), 5);
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'gi_fise';
    }

    public static function maybe_install() {
        if (get_option(self::DB_OPTION) === self::DB_VERSION) {
            return;
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table();
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  an smallint(5) unsigned NOT NULL,
  nr int(10) unsigned NOT NULL,
  serie varchar(40) NOT NULL,
  creat datetime NOT NULL,
  creat_de bigint(20) unsigned NOT NULL DEFAULT 0,
  receptioner varchar(120) NOT NULL DEFAULT '',
  rezervare varchar(20) NOT NULL DEFAULT '',
  comanda bigint(20) unsigned NOT NULL DEFAULT 0,
  adult_nr smallint(5) unsigned NOT NULL DEFAULT 1,
  nume varchar(120) NOT NULL,
  prenume varchar(120) NOT NULL,
  nastere date NOT NULL,
  sex char(1) NOT NULL DEFAULT '',
  loc_nastere varchar(160) NOT NULL DEFAULT '',
  cetatenie varchar(80) NOT NULL DEFAULT '',
  tip_act varchar(60) NOT NULL DEFAULT '',
  serie_act varchar(40) NOT NULL DEFAULT '',
  act_valabil date NULL,
  cnp varchar(13) NOT NULL DEFAULT '',
  domiciliu varchar(255) NOT NULL DEFAULT '',
  telefon varchar(40) NOT NULL DEFAULT '',
  sosire date NULL,
  plecare date NULL,
  gdpr_text text NULL,
  unitate text NULL,
  semn_turist mediumtext NULL,
  semn_receptie mediumtext NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY an_nr (an,nr),
  KEY comanda (comanda),
  KEY rezervare (rezervare),
  KEY creat (creat)
) {$charset};");
        if (self::exists()) {
            update_option(self::DB_OPTION, self::DB_VERSION, true);
        }
    }

    public static function exists() {
        global $wpdb;
        $table = self::table();
        return $table === $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
    }

    /* ---------- Setări ---------- */

    public static function settings() {
        $saved = get_option(self::SETTINGS, array());
        $saved = is_array($saved) ? $saved : array();
        $defaults = array('unitate' => 'Grădina Ielelor, Sâmbăta de Sus, Brașov', 'operator' => '', 'cui' => '', 'clasificare' => '', 'prefix' => 'GI', 'gdpr' => self::DEFAULT_GDPR, 'pastrare_luni' => 0);
        $out = array_merge($defaults, array_intersect_key($saved, $defaults));
        $out['pastrare_luni'] = max(0, min(240, (int) $out['pastrare_luni']));
        if ('' === trim((string) $out['gdpr'])) {
            $out['gdpr'] = self::DEFAULT_GDPR;
        }
        return $out;
    }

    private static function save_settings($in) {
        $clean = function ($v, $max) {
            return mb_substr(trim(preg_replace('/\s+/u', ' ', sanitize_text_field((string) $v))), 0, $max);
        };
        $old = self::settings();
        $new = array(
            'unitate' => $clean(isset($in['unitate']) ? $in['unitate'] : '', 160),
            'operator' => $clean(isset($in['operator']) ? $in['operator'] : '', 160),
            'cui' => $clean(isset($in['cui']) ? $in['cui'] : '', 30),
            'clasificare' => $clean(isset($in['clasificare']) ? $in['clasificare'] : '', 80),
            'prefix' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) (isset($in['prefix']) ? $in['prefix'] : 'GI'))),
            'gdpr' => mb_substr(trim(sanitize_textarea_field((string) (isset($in['gdpr']) ? $in['gdpr'] : ''))), 0, 800),
            'pastrare_luni' => max(0, min(240, absint(isset($in['pastrare_luni']) ? $in['pastrare_luni'] : 0))),
        );
        $new['prefix'] = '' === $new['prefix'] ? 'GI' : substr($new['prefix'], 0, 6);
        update_option(self::SETTINGS, $new, false);
        return array_keys(array_filter($new, function ($v, $k) use ($old) {
            return (string) $old[$k] !== (string) $v;
        }, ARRAY_FILTER_USE_BOTH));
    }

    private static function unit_missing($s) {
        $missing = array();
        foreach (array('operator' => 'operatorul (firma)', 'cui' => 'CUI', 'clasificare' => 'clasificarea unității') as $k => $label) {
            if ('' === trim((string) $s[$k])) {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    /* ---------- Validare și înscriere ---------- */

    private static function valid_date($v, $min = '1900-01-01', $max = null) {
        $v = trim((string) $v);
        if (1 !== preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return '';
        }
        if ($v < $min || (null !== $max && $v > $max)) {
            return '';
        }
        return $v;
    }

    /** Cifra de control a unui CNP (ponderile 279146358279). */
    public static function cnp_ok($cnp) {
        if (1 !== preg_match('/^\d{13}$/', (string) $cnp)) {
            return false;
        }
        $w = '279146358279';
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnp[$i] * (int) $w[$i];
        }
        $r = $sum % 11;
        return ($r === 10 ? 1 : $r) === (int) $cnp[12];
    }

    /** CNP potrivit cu data nașterii, sexul și secolul (aceeași regulă ca în mrz.js). */
    public static function cnp_matches($cnp, $dob, $sex) {
        if (!self::cnp_ok($cnp)) {
            return false;
        }
        $s = (int) $cnp[0];
        if ('' !== $dob && substr($cnp, 1, 6) !== substr($dob, 2, 2) . substr($dob, 5, 2) . substr($dob, 8, 2)) {
            return false;
        }
        if ('' !== $sex && $s >= 1 && $s <= 8 && (1 === $s % 2) !== ('M' === $sex)) {
            return false;
        }
        if ('' !== $dob && $s >= 1 && $s <= 6) {
            $y = (int) substr($dob, 0, 4);
            $century = $s <= 2 ? 1900 : ($s <= 4 ? 1800 : 2000);
            if ($y < $century || $y > $century + 99) {
                return false;
            }
        }
        return true;
    }

    /** Semnătură: imagine PNG (data URL) de mărime rezonabilă. Întoarce data URL-ul sau ''. */
    private static function valid_signature($v) {
        $v = trim((string) $v);
        if (1 !== preg_match('#^data:image/png;base64,([A-Za-z0-9+/]+={0,2})$#', $v, $m) || strlen($v) > 160000) {
            return '';
        }
        $bin = base64_decode($m[1], true);
        if (false === $bin || strlen($bin) < 150 || "\x89PNG\r\n\x1a\n" !== substr($bin, 0, 8)) {
            return '';
        }
        return $v;
    }

    /** Câte fișe are deja rezervarea (numai numărul, pentru „adultul k din N”). */
    public static function count_for($order) {
        global $wpdb;
        $table = self::table();
        $previous = $wpdb->suppress_errors(true);
        $n = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE comanda = %d", (int) $order->get_id())); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->suppress_errors($previous);
        return $n;
    }

    private static function act_key($v) {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $v));
    }

    private static function doc_exists_for($order, $doc) {
        global $wpdb;
        $table = self::table();
        $previous = $wpdb->suppress_errors(true);
        $rows = $wpdb->get_col($wpdb->prepare("SELECT serie_act FROM {$table} WHERE comanda = %d", (int) $order->get_id())); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->suppress_errors($previous);
        $want = self::act_key($doc);
        foreach ((array) $rows as $r) {
            if (self::act_key($r) === $want) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifică ce a trimis recepția. Întoarce array('errors' => [...], 'data' => [...]).
     *
     * @param array<string,string> $in
     */
    private static function validate($in, $order, $summary) {
        $errors = array();
        $g = function ($k) use ($in) {
            return isset($in[$k]) ? trim((string) $in[$k]) : '';
        };
        $text = function ($k, $label, $min, $max, $required = true) use ($g, &$errors) {
            $v = preg_replace('/\s+/u', ' ', $g($k));
            if ('' === $v) {
                if ($required) {
                    $errors[] = 'Completează: ' . $label . '.';
                }
                return '';
            }
            if (mb_strlen($v) < $min || mb_strlen($v) > $max) {
                $errors[] = $label . ': între ' . $min . ' și ' . $max . ' de caractere.';
            }
            return $v;
        };
        $today = gmdate('Y-m-d');
        $d = array();
        $d['nume'] = $text('f_nume', 'numele', 1, 80);
        $d['prenume'] = $text('f_prenume', 'prenumele', 1, 80);
        foreach (array('nume' => 'numele', 'prenume' => 'prenumele') as $k => $label) {
            if ('' !== $d[$k] && 1 !== preg_match("/^[\\p{L}\\p{M}' .-]+$/u", $d[$k])) {
                $errors[] = 'La ' . $label . ' folosește numai litere, spațiu, cratimă sau apostrof.';
            }
        }
        $d['nastere'] = self::valid_date($g('f_dob'), '1900-01-01', $today);
        if ('' === $d['nastere']) {
            $errors[] = 'Data nașterii lipsește sau nu e o dată valabilă (între 1900 și azi).';
        }
        $sex = strtoupper($g('f_sex'));
        $d['sex'] = in_array($sex, array('F', 'M'), true) ? $sex : '';
        $d['loc_nastere'] = $text('f_loc', 'locul nașterii', 2, 160, false);
        $d['cetatenie'] = $text('f_cet', 'cetățenia', 2, 80);
        $d['tip_act'] = $text('f_tip', 'tipul actului', 2, 60);
        $d['serie_act'] = $text('f_doc', 'seria și numărul actului', 3, 40);
        if ('' !== $d['serie_act'] && 1 !== preg_match('#^[A-Za-z0-9 /.-]+$#', $d['serie_act'])) {
            $errors[] = 'Seria și numărul actului: numai litere, cifre, spațiu, cratimă sau bară.';
        }
        $exp = $g('f_exp');
        $d['act_valabil'] = '' === $exp ? null : self::valid_date($exp, '1900-01-01', '2100-12-31');
        if ('' !== $exp && '' === $d['act_valabil']) {
            $errors[] = 'Data până la care e valabil actul nu e o dată valabilă.';
        }
        if (is_string($d['act_valabil']) && '' !== $d['act_valabil'] && $d['act_valabil'] < $today) {
            $errors[] = 'Actul a expirat la ' . self::fmt($d['act_valabil']) . '. Cere alt act.';
        }
        $cnp = preg_replace('/\s+/', '', $g('f_cnp'));
        if ('' !== $cnp) {
            if (1 !== preg_match('/^\d{13}$/', $cnp)) {
                $errors[] = 'CNP-ul are 13 cifre (lasă gol la turiștii străini).';
            } elseif (!self::cnp_ok($cnp)) {
                $errors[] = 'CNP-ul nu e valabil (cifra de control nu se potrivește). Verifică-l sau lasă-l gol.';
            } elseif ('' !== $d['nastere'] && !self::cnp_matches($cnp, $d['nastere'], $d['sex'])) {
                $errors[] = 'CNP-ul nu se potrivește cu data nașterii sau cu sexul de mai sus.';
            }
        }
        $d['cnp'] = $cnp;
        $d['domiciliu'] = $text('f_dom', 'domiciliul', 3, 255);
        $d['telefon'] = $text('f_tel', 'telefonul', 3, 40, false);
        if ('' !== $d['telefon'] && 1 !== preg_match('/^[0-9 +().\/-]+$/', $d['telefon'])) {
            $errors[] = 'Telefon: numai cifre, spațiu, +, cratimă sau paranteze.';
        }
        $d['sosire'] = self::valid_date($g('f_sosire'), '2000-01-01', '2100-12-31');
        $d['plecare'] = self::valid_date($g('f_plecare'), '2000-01-01', '2100-12-31');
        if ('' === $d['sosire'] || '' === $d['plecare']) {
            $errors[] = 'Datele de sosire și plecare lipsesc sau nu sunt valabile.';
        } elseif ($d['plecare'] <= $d['sosire']) {
            $errors[] = 'Plecarea trebuie să fie după sosire.';
        }
        $d['receptioner'] = $text('f_recep', 'numele recepționerului', 2, 120);
        $d['semn_turist'] = self::valid_signature($g('sigT'));
        $d['semn_receptie'] = self::valid_signature($g('sigR'));
        if ('' === $d['semn_turist'] || '' === $d['semn_receptie']) {
            $errors[] = 'Lipsește o semnătură: semnează turistul și recepționerul în câmpurile de jos.';
        }
        if ('1' !== $g('gdpr')) {
            $errors[] = 'Bifează că turistul a fost informat despre prelucrarea datelor personale.';
        }
        if ('' !== $d['serie_act'] && self::doc_exists_for($order, $d['serie_act'])) {
            $errors[] = 'Pentru această rezervare există deja o fișă cu același act (' . $d['serie_act'] . ').';
        }
        $have = self::count_for($order);
        if (null !== $summary['adulti'] && $summary['adulti'] > 0 && $have >= $summary['adulti'] && '1' !== $g('depaseste')) {
            $errors[] = 'Toți cei ' . $summary['adulti'] . ' adulți din rezervare au deja fișă. Dacă totuși mai adaugi una, bifează „Adaug încă o fișă”.';
        }
        return array('errors' => $errors, 'data' => $d);
    }

    /** Înscrie fișa; serie = prefix-an-număr. Întoarce id-ul sau 0. */
    private static function insert($d, $order, $label, $unit_snapshot) {
        global $wpdb;
        $table = self::table();
        $settings = self::settings();
        $an = (int) gmdate('Y');
        $code = GI_Rezervari::code_for_order($order, false);
        for ($try = 0; $try < 6; $try++) {
            $previous = $wpdb->suppress_errors(true);
            $nr = 1 + (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(MAX(nr), 0) FROM {$table} WHERE an = %d", $an)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $row = array(
                'an' => $an,
                'nr' => $nr,
                'serie' => $settings['prefix'] . '-' . $an . '-' . str_pad((string) $nr, 5, '0', STR_PAD_LEFT),
                'creat' => current_time('mysql', true),
                'creat_de' => get_current_user_id(),
                'receptioner' => $d['receptioner'],
                'rezervare' => '' !== $code ? $code : $label,
                'comanda' => (int) $order->get_id(),
                'adult_nr' => self::count_for($order) + 1,
                'nume' => $d['nume'],
                'prenume' => $d['prenume'],
                'nastere' => $d['nastere'],
                'sex' => $d['sex'],
                'loc_nastere' => $d['loc_nastere'],
                'cetatenie' => $d['cetatenie'],
                'tip_act' => $d['tip_act'],
                'serie_act' => $d['serie_act'],
                'act_valabil' => $d['act_valabil'],
                'cnp' => $d['cnp'],
                'domiciliu' => $d['domiciliu'],
                'telefon' => $d['telefon'],
                'sosire' => $d['sosire'],
                'plecare' => $d['plecare'],
                'gdpr_text' => $settings['gdpr'],
                'unitate' => wp_json_encode($unit_snapshot, JSON_UNESCAPED_UNICODE),
                'semn_turist' => $d['semn_turist'],
                'semn_receptie' => $d['semn_receptie'],
            );
            $ok = $wpdb->insert($table, $row);
            $wpdb->suppress_errors($previous);
            if ($ok) {
                return (int) $wpdb->insert_id;
            }
            if (false === stripos((string) $wpdb->last_error, 'duplicate')) {
                break;
            }
        }
        return 0;
    }

    /* ---------- Citire ---------- */

    public static function get($id) {
        global $wpdb;
        $table = self::table();
        $previous = $wpdb->suppress_errors(true);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->suppress_errors($previous);
        return $row ? $row : null;
    }

    /**
     * Registrul: fără imaginile semnăturilor (grele); căutare după serie, nume, rezervare, act sau CNP.
     *
     * @return array{rows: array<int,object>, total: int}
     */
    public static function search($q, $page, $per) {
        global $wpdb;
        $table = self::table();
        $where = '1=1';
        $args = array();
        $q = trim((string) $q);
        if ('' !== $q) {
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where = '(serie LIKE %s OR nume LIKE %s OR prenume LIKE %s OR CONCAT(nume, \' \', prenume) LIKE %s OR rezervare LIKE %s OR serie_act LIKE %s OR cnp LIKE %s OR comanda = %d)';
            $args = array($like, $like, $like, $like, $like, $like, $like, absint($q));
        }
        $previous = $wpdb->suppress_errors(true);
        $total = (int) $wpdb->get_var($args ? $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", $args) : "SELECT COUNT(*) FROM {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $args[] = (int) $per;
        $args[] = max(0, ((int) $page - 1) * (int) $per);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id, serie, creat, receptioner, rezervare, comanda, adult_nr, nume, prenume, tip_act, serie_act, sosire, plecare FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d", $args)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->suppress_errors($previous);
        return array('rows' => is_array($rows) ? $rows : array(), 'total' => $total);
    }

    private static function older_than_ids($months) {
        global $wpdb;
        $table = self::table();
        if ($months < 1) {
            return array();
        }
        $cutoff = gmdate('Y-m-d H:i:s', strtotime('-' . (int) $months . ' months', time()));
        $previous = $wpdb->suppress_errors(true);
        $ids = $wpdb->get_results($wpdb->prepare("SELECT id, serie FROM {$table} WHERE creat < %s ORDER BY id ASC", $cutoff)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->suppress_errors($previous);
        return is_array($ids) ? $ids : array();
    }

    private static function delete_rows($ids) {
        global $wpdb;
        $table = self::table();
        $n = 0;
        foreach (array_map('absint', (array) $ids) as $id) {
            if ($id && $wpdb->delete($table, array('id' => $id), array('%d'))) {
                $n++;
            }
        }
        return $n;
    }

    /* ---------- CSV ---------- */

    private static function csv_cell($v) {
        $v = (string) $v;
        if ('' !== $v && in_array($v[0], array('=', '+', '-', '@', "\t", "\r"), true)) {
            $v = "'" . $v; // un tabel (Excel) nu trebuie să ia textul drept formulă
        }
        return '"' . str_replace('"', '""', $v) . '"';
    }

    private static function stream_csv($q) {
        global $wpdb;
        $table = self::table();
        $head = array('Seria', 'Rezervare', 'Comanda', 'Adult nr.', 'Nume', 'Prenume', 'Data nașterii', 'Sex', 'Locul nașterii', 'Cetățenia', 'Tip act', 'Serie și număr act', 'Valabil până la', 'CNP', 'Domiciliu', 'Telefon', 'Sosire', 'Plecare', 'Înscrisă la (ora site-ului)', 'Recepționer');
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="registru-fise-' . gmdate('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF" . implode(';', array_map(array(__CLASS__, 'csv_cell'), $head)) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $count = 0;
        $page = 1;
        do {
            $res = self::search($q, $page, 200);
            $ids = array_map(function ($r) {
                return (int) $r->id;
            }, $res['rows']);
            if ($ids) {
                $previous = $wpdb->suppress_errors(true);
                $full = $wpdb->get_results("SELECT id, serie, rezervare, comanda, adult_nr, nume, prenume, nastere, sex, loc_nastere, cetatenie, tip_act, serie_act, act_valabil, cnp, domiciliu, telefon, sosire, plecare, creat, receptioner FROM {$table} WHERE id IN (" . implode(',', $ids) . ') ORDER BY id DESC'); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->suppress_errors($previous);
                foreach ((array) $full as $r) {
                    $local = get_date_from_gmt($r->creat, 'd.m.Y H:i');
                    $line = array($r->serie, $r->rezervare, $r->comanda, $r->adult_nr, $r->nume, $r->prenume, self::fmt($r->nastere), $r->sex, $r->loc_nastere, $r->cetatenie, $r->tip_act, $r->serie_act, self::fmt($r->act_valabil), $r->cnp, $r->domiciliu, $r->telefon, self::fmt($r->sosire), self::fmt($r->plecare), $local, $r->receptioner);
                    echo implode(';', array_map(array(__CLASS__, 'csv_cell'), $line)) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    $count++;
                }
            }
            $page++;
        } while (count($res['rows']) === 200 && $page < 500);
        return $count;
    }

    public static function fmt($iso) {
        return is_string($iso) && 1 === preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m) ? $m[3] . '.' . $m[2] . '.' . $m[1] : '';
    }

    /* ---------- Cârlige din Zona echipei ---------- */

    /** Înainte de afișare: descărcarea CSV (singurul răspuns care nu e o pagină). */
    public static function route($sec, $id) {
        if ('registru' !== $sec || !is_user_logged_in()) {
            return;
        }
        $export = isset($_GET['export']) ? sanitize_key(wp_unslash($_GET['export'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ('csv' !== $export) {
            return;
        }
        $nonce = isset($_GET['_gi_nonce']) ? sanitize_text_field(wp_unslash($_GET['_gi_nonce'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!current_user_can('gi_fise_vezi') || !wp_verify_nonce($nonce, 'gi_zona_export')) {
            wp_die('Linkul a expirat sau contul nu are voie. Deschide din nou Registrul și apasă „Descarcă CSV”.', '', array('response' => 403));
        }
        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $n = self::stream_csv($q);
        GI_Istoric::log('fisa_export', 'registru', array('fise' => $n, 'cautare' => '' !== $q));
        exit;
    }

    public static function handle_post($sec, $id, $action) {
        if ('fisa' === $sec && 'inscrie' === $action) {
            self::post_fisa();
        } elseif ('registru' === $sec && in_array($action, array('sterge', 'sterge_vechi', 'setari'), true)) {
            self::post_registry($action);
        }
    }

    private static function post_fisa() {
        if (!current_user_can('gi_receptie')) {
            GI_Zona::deny('receptie');
        }
        GI_Zona::check('fisa', 'receptie');
        $raw = array();
        foreach (array('f_nume', 'f_prenume', 'f_dob', 'f_sex', 'f_loc', 'f_cet', 'f_tip', 'f_doc', 'f_exp', 'f_cnp', 'f_dom', 'f_tel', 'f_sosire', 'f_plecare', 'f_recep', 'sigT', 'sigR', 'gdpr', 'depaseste', 'rez_cod', 'rez_comanda', 'rez_nume') as $k) {
            $raw[$k] = GI_Zona::posted($k);
        }
        $found = GI_Rezervari::resolve($raw['rez_cod'], $raw['rez_comanda'], $raw['rez_nume']);
        if ($found['empty'] || $found['error'] || !$found['order']) {
            GI_Zona::flash(array('errors' => array($found['error'] ? $found['error'] : 'Alege întâi rezervarea, din Recepție.')));
            GI_Zona::go('receptie');
        }
        $order = $found['order'];
        $errors = array();
        if (!GI_Rezervari::is_finalized($order)) {
            $errors[] = 'Rezervarea nu e finalizată (stare: ' . wc_get_order_status_name($order->get_status()) . '). Fișa se face numai pentru rezervări confirmate.';
        }
        if (!self::exists()) {
            $errors[] = 'Tabelul fișelor lipsește, deci nu se poate înscrie nimic. Spune-i lui Claude (probabil contul de bază de date nu are drept să creeze tabele).';
        }
        $summary = GI_Rezervari::summary($order);
        $checked = self::validate($raw, $order, $summary);
        $errors = array_merge($errors, $checked['errors']);
        if ($errors) {
            self::$sticky = $raw;
            GI_Zona::inline(array('errors' => $errors));
            return;
        }
        $settings = self::settings();
        $snapshot = array('unitate' => $settings['unitate'], 'operator' => $settings['operator'], 'cui' => $settings['cui'], 'clasificare' => $settings['clasificare']);
        $id = self::insert($checked['data'], $order, $found['label'], $snapshot);
        if (!$id) {
            self::$sticky = $raw;
            GI_Zona::inline(array('errors' => array('Nu s-a putut înscrie fișa (eroare la salvare). Încearcă din nou; dacă se repetă, spune-i lui Claude.')));
            return;
        }
        $row = self::get($id);
        GI_Istoric::log('fisa_creata', $row ? $row->serie : (string) $id, array('rezervare' => $row ? $row->rezervare : $found['label'], 'adult' => $row ? (int) $row->adult_nr : 0));
        GI_Zona::flash(array('ok' => 'Fișa a fost înscrisă: ' . ($row ? $row->serie : '#' . $id) . '. Printeaz-o și dă-o la semnat, dacă ai nevoie de o copie pe hârtie.'));
        GI_Zona::go('fisa', (string) $id);
    }

    private static function post_registry($action) {
        if (!current_user_can('gi_fise_vezi')) {
            GI_Zona::deny('acasa');
        }
        GI_Zona::check('registru', 'registru');
        if ('sterge' === $action) {
            $id = absint(GI_Zona::posted('fisa_id'));
            $row = $id ? self::get($id) : null;
            if (!$row) {
                GI_Zona::flash(array('errors' => array('Fișa nu mai există.')));
                return;
            }
            if (self::delete_rows(array($id))) {
                GI_Istoric::log('fisa_stearsa', $row->serie, array('motiv' => 'manual'));
                GI_Zona::flash(array('ok' => 'Fișa ' . $row->serie . ' a fost ștearsă.'));
            }
        } elseif ('sterge_vechi' === $action) {
            $months = self::settings()['pastrare_luni'];
            $old = self::older_than_ids($months);
            if ($months < 1 || !$old) {
                GI_Zona::flash(array('errors' => array($months < 1 ? 'Nu ai ales un termen de păstrare.' : 'Nu sunt fișe mai vechi decât termenul.')));
                return;
            }
            $n = self::delete_rows(array_map(function ($r) {
                return (int) $r->id;
            }, $old));
            GI_Istoric::log('fisa_stearsa_vechi', 'termen', array('fise' => $n, 'luni' => $months));
            GI_Zona::flash(array('ok' => $n . ' fișe mai vechi de ' . $months . ' luni au fost șterse.'));
        } elseif ('setari' === $action) {
            if (!current_user_can('gi_echipa')) {
                GI_Zona::deny('registru');
            }
            $in = array();
            foreach (array('unitate', 'operator', 'cui', 'clasificare', 'prefix', 'gdpr', 'pastrare_luni') as $k) {
                $in[$k] = GI_Zona::posted('s_' . $k);
            }
            $changed = self::save_settings($in);
            GI_Istoric::log('fisa_setari', 'setari', array('campuri' => $changed));
            GI_Zona::flash(array('ok' => $changed ? 'Setările fișei au fost salvate.' : 'Nimic de schimbat.'));
        }
    }

    /* ---------- Afișare ---------- */

    public static function view($sec, $id) {
        if ('fisa' === $sec) {
            self::view_fisa($id);
        } else {
            self::view_registry($id);
        }
    }

    private static function field($label, $name, $value, $extra = '', $note = '') {
        $type = isset($extra['type']) ? $extra['type'] : 'text';
        $attrs = '';
        foreach ((array) $extra as $k => $v) {
            if ('type' !== $k) {
                $attrs .= ' ' . esc_attr($k) . ('' === $v ? '' : '="' . esc_attr($v) . '"');
            }
        }
        return '<label for="gi-f-' . esc_attr($name) . '">' . esc_html($label) . ' <span class="gi-fisa-sursa" data-gi-sursa="' . esc_attr($name) . '">' . esc_html($note) . '</span></label>'
            . '<input type="' . esc_attr($type) . '" id="gi-f-' . esc_attr($name) . '" name="f_' . esc_attr($name) . '" value="' . esc_attr($value) . '" autocomplete="off"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private static function view_fisa($id) {
        GI_Zona::back_link('receptie', 'Recepție');
        if (!current_user_can('gi_receptie') || !class_exists('GI_Rezervari') || !class_exists('WooCommerce')) {
            echo '<div class="gi-zona-card"><p>Contul tău nu are acces la fișa de cazare.</p></div>';
            return;
        }
        if ('' !== $id && ctype_digit((string) $id)) {
            self::view_done((int) $id);
            return;
        }
        $cod = isset($_GET['cod']) ? sanitize_text_field(wp_unslash($_GET['cod'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $num = isset($_GET['comanda']) ? sanitize_text_field(wp_unslash($_GET['comanda'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $fam = isset($_GET['nume']) ? sanitize_text_field(wp_unslash($_GET['nume'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (self::$sticky) { // după o greșeală, rezervarea rămâne cea de dinainte
            $cod = self::$sticky['rez_cod'];
            $num = self::$sticky['rez_comanda'];
            $fam = self::$sticky['rez_nume'];
        }
        $found = GI_Rezervari::resolve($cod, $num, $fam);
        if ($found['empty'] || $found['error'] || !$found['order']) {
            echo '<div class="gi-zona-card"><p>' . esc_html($found['error'] ? $found['error'] : 'Alege întâi rezervarea, din Recepție.') . '</p><p><a class="button button-primary" href="' . esc_url(GI_Zona::url('receptie')) . '">Înapoi la Recepție</a></p></div>';
            return;
        }
        $order = $found['order'];
        $s = GI_Rezervari::summary($order);
        $settings = self::settings();
        $have = self::exists() ? self::count_for($order) : 0;
        $v = self::$sticky;
        $val = function ($k, $default = '') use ($v) {
            return isset($v[$k]) && '' !== $v[$k] ? $v[$k] : $default;
        };
        $user = wp_get_current_user();
        $adults = $s['adulti'];
        ?>
        <div class="gi-zona-card">
            <h2>Fișa de cazare</h2>
            <p class="gi-fisa-rez"><strong><?php echo esc_html($found['label']); ?></strong> · <?php echo esc_html($s['nume']); ?> · <?php echo esc_html(self::fmt($s['sosire'])); ?> → <?php echo esc_html(self::fmt($s['plecare'])); ?></p>
            <?php if (!$s['finalizata']) : ?>
                <div class="gi-zona-notice gi-zona-err" role="alert">Rezervarea nu e finalizată (stare: <?php echo esc_html($s['stare']); ?>). Fișa se face numai pentru rezervări confirmate.</div>
            <?php endif; ?>
            <p class="gi-fisa-adult"><strong>
                <?php
                if (null !== $adults && $adults > 0) {
                    echo $have >= $adults ? esc_html('Toți cei ' . $adults . ' adulți au deja fișă.') : esc_html('Adultul ' . ($have + 1) . ' din ' . $adults . '.');
                } else {
                    echo esc_html('Adultul ' . ($have + 1) . ' (numărul adulților nu apare în comandă).');
                }
                ?>
            </strong> Copiii nu au fișă.</p>
            <?php $missing = self::unit_missing($settings); if ($missing && current_user_can('gi_echipa')) : ?>
                <div class="gi-zona-notice gi-zona-err" role="status">Datele unității nu sunt complete pe fișă (lipsesc: <?php echo esc_html(implode(', ', $missing)); ?>). Completează-le în <a class="gi-text-link" href="<?php echo esc_url(GI_Zona::url('registru')); ?>">Registrul fișelor → Setări</a>.</div>
            <?php elseif ($missing) : ?>
                <p class="gi-zona-mic">Datele unității (firmă, CUI, clasificare) le completează proprietara; până atunci, fișa le arată goale.</p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo esc_url(GI_Zona::url('fisa')); ?>" class="gi-zona-card gi-zona-form gi-fisa-form" data-gi-fisa autocomplete="off">
            <?php echo GI_Zona::nonce_field('fisa'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <input type="hidden" name="gi_actiune" value="inscrie">
            <input type="hidden" name="rez_cod" value="<?php echo esc_attr($cod); ?>">
            <input type="hidden" name="rez_comanda" value="<?php echo esc_attr($num); ?>">
            <input type="hidden" name="rez_nume" value="<?php echo esc_attr($fam); ?>">

            <h2>Actul de identitate</h2>
            <p class="gi-zona-mic">Citești zona de jos a actului (rândurile cu „&lt;&lt;&lt;”) cu un cititor de documente, care scrie în câmp ca o tastatură, sau o scrii/lipești. Aplicația verifică cifrele de control și valabilitatea. Actul <strong>nu se copiază</strong>: se păstrează numai datele. Adresa nu se citește din act: vine din rezervare.</p>
            <label for="gi-f-mrz">Zona de jos a actului (2 sau 3 rânduri)</label>
            <textarea id="gi-f-mrz" spellcheck="false" autocomplete="off" autocapitalize="characters" rows="4" placeholder="IDROU...&#10;9003145F...&#10;POPESCU&lt;&lt;MARIA..."></textarea>
            <p class="gi-zona-butoane"><button type="button" class="button button-outline" data-gi-mrz-read>Citește actul</button></p>
            <ul class="gi-fisa-checks" data-gi-mrz-checks aria-live="polite"></ul>

            <h2>Datele turistului</h2>
            <?php
            echo self::field('Nume', 'nume', $val('f_nume'), array('required' => '', 'maxlength' => '80')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Prenume', 'prenume', $val('f_prenume'), array('required' => '', 'maxlength' => '80')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Data nașterii', 'dob', $val('f_dob'), array('type' => 'date', 'required' => '', 'max' => gmdate('Y-m-d'), 'min' => '1900-01-01')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
            <label for="gi-f-sex">Sex <span class="gi-fisa-sursa" data-gi-sursa="sex"></span></label>
            <select id="gi-f-sex" name="f_sex">
                <?php foreach (array('' => '—', 'F' => 'Feminin / Female', 'M' => 'Masculin / Male') as $k => $label) : ?>
                    <option value="<?php echo esc_attr($k); ?>" <?php selected($val('f_sex'), $k); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php
            echo self::field('Locul nașterii', 'loc', $val('f_loc'), array('maxlength' => '160', 'placeholder' => 'localitate, țară'), 'îl scrii tu'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Cetățenia', 'cet', $val('f_cet'), array('required' => '', 'maxlength' => '80')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Tipul actului', 'tip', $val('f_tip'), array('required' => '', 'maxlength' => '60')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Seria și numărul actului', 'doc', $val('f_doc'), array('required' => '', 'maxlength' => '40')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Valabil până la', 'exp', $val('f_exp'), array('type' => 'date', 'min' => gmdate('Y-m-d'))); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('CNP (numai români)', 'cnp', $val('f_cnp'), array('inputmode' => 'numeric', 'maxlength' => '13', 'pattern' => '[0-9]{13}'), 'din act, dacă apare'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Domiciliul', 'dom', $val('f_dom', $s['adresa']), array('required' => '', 'maxlength' => '255'), 'din rezervare, corectează dacă e nevoie'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Telefon', 'tel', $val('f_tel', $s['telefon']), array('type' => 'tel', 'maxlength' => '40'), 'din rezervare'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>

            <h2>Șederea</h2>
            <?php
            echo self::field('Sosire', 'sosire', $val('f_sosire', $s['sosire']), array('type' => 'date', 'required' => '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo self::field('Plecare', 'plecare', $val('f_plecare', $s['plecare']), array('type' => 'date', 'required' => '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>

            <h2>Semnături</h2>
            <p class="gi-zona-mic">Turistul semnează cu degetul în primul câmp, recepționerul în al doilea.</p>
            <div class="gi-fisa-sig">
                <p class="gi-fisa-sig-cap"><span>Semnătura turistului · Guest signature</span><button type="button" class="button button-outline" data-gi-clear="sigT">Șterge</button></p>
                <canvas id="gi-sigT" data-gi-pad="sigT" aria-label="Semnătura turistului" height="140"></canvas>
                <input type="hidden" name="sigT" value="<?php echo esc_attr($val('sigT')); ?>">
            </div>
            <div class="gi-fisa-sig">
                <p class="gi-fisa-sig-cap"><span>Semnătura recepționerului · Receptionist</span><button type="button" class="button button-outline" data-gi-clear="sigR">Șterge</button></p>
                <canvas id="gi-sigR" data-gi-pad="sigR" aria-label="Semnătura recepționerului" height="140"></canvas>
                <input type="hidden" name="sigR" value="<?php echo esc_attr($val('sigR')); ?>">
            </div>
            <?php echo self::field('Recepționer (numele)', 'recep', $val('f_recep', $user->display_name), array('required' => '', 'maxlength' => '120')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <label class="gi-zona-radio gi-fisa-gdpr"><input type="checkbox" name="gdpr" value="1" required <?php checked($val('gdpr'), '1'); ?>> <span><?php echo esc_html($settings['gdpr']); ?></span></label>
            <?php if (null !== $adults && $adults > 0 && $have >= $adults) : ?>
                <label class="gi-zona-radio"><input type="checkbox" name="depaseste" value="1" <?php checked($val('depaseste'), '1'); ?>> <span>Adaug încă o fișă (toți adulții din rezervare au deja)</span></label>
            <?php endif; ?>
            <p><button type="submit" class="button button-primary" data-gi-fisa-submit>Înscrie fișa</button></p>
        </form>
        <?php
    }

    /** Fișa abia înscrisă (recepția, o oră) sau deschisă din registru (administratorul). */
    private static function view_done($id) {
        $row = self::get($id);
        $own = $row && (int) $row->creat_de === get_current_user_id() && strtotime($row->creat . ' UTC') > time() - HOUR_IN_SECONDS;
        if (!$row || !(current_user_can('gi_fise_vezi') || $own)) {
            echo '<div class="gi-zona-card"><p>Fișa nu există sau nu ai voie să o vezi. Recepția vede fișa numai în ora de după înscriere.</p><p><a class="button button-primary" href="' . esc_url(GI_Zona::url('receptie')) . '">Înapoi la Recepție</a></p></div>';
            return;
        }
        self::render_sheet($row);
        $next = '';
        if ('' !== $row->rezervare && 1 === preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $row->rezervare)) {
            $next = add_query_arg(array('cod' => $row->rezervare), GI_Zona::url('fisa'));
        } else {
            $order = wc_get_order((int) $row->comanda);
            if ($order) {
                $next = add_query_arg(array('comanda' => $row->comanda, 'nume' => $order->get_billing_last_name()), GI_Zona::url('fisa'));
            }
        }
        ?>
        <p class="gi-zona-butoane no-print">
            <button type="button" class="button button-primary" onclick="window.print()">Printează fișa</button>
            <?php if ($next && current_user_can('gi_receptie')) : ?><a class="button button-outline" href="<?php echo esc_url($next); ?>">Următorul adult</a><?php endif; ?>
            <?php if (current_user_can('gi_fise_vezi')) : ?><a class="button button-outline" href="<?php echo esc_url(GI_Zona::url('registru')); ?>">Registrul fișelor</a><?php endif; ?>
            <a class="button button-outline" href="<?php echo esc_url(GI_Zona::url('receptie')); ?>">Recepție</a>
        </p>
        <?php
    }

    private static function pair($ro, $en, $value) {
        return '<tr><th scope="row">' . esc_html($ro) . '<small>' . esc_html($en) . '</small></th><td>' . ('' !== trim((string) $value) ? esc_html($value) : '—') . '</td></tr>';
    }

    private static function sig_img($data, $alt) {
        if (1 === preg_match('#^data:image/png;base64,[A-Za-z0-9+/]+={0,2}$#', (string) $data)) {
            return '<img src="' . esc_attr($data) . '" alt="' . esc_attr($alt) . '">';
        }
        return '<span class="gi-zona-mic">—</span>';
    }

    public static function render_sheet($row) {
        $u = json_decode((string) $row->unitate, true);
        $u = is_array($u) ? $u : array();
        $nights = ($row->sosire && $row->plecare) ? max(0, (int) round((strtotime($row->plecare . ' UTC') - strtotime($row->sosire . ' UTC')) / 86400)) : '';
        $sex = 'F' === $row->sex ? 'Feminin / Female' : ('M' === $row->sex ? 'Masculin / Male' : '');
        ?>
        <article class="gi-fisa-sheet" aria-label="Fișa de cazare <?php echo esc_attr($row->serie); ?>">
            <header class="gi-fisa-head">
                <div>
                    <h2>Fișă de cazare<small>Accommodation registration form</small></h2>
                    <p><?php echo esc_html(isset($u['unitate']) ? $u['unitate'] : ''); ?><?php echo !empty($u['operator']) ? ' · ' . esc_html($u['operator']) : ''; ?><?php echo !empty($u['cui']) ? ' · CUI ' . esc_html($u['cui']) : ''; ?><?php echo !empty($u['clasificare']) ? ' · ' . esc_html($u['clasificare']) : ''; ?></p>
                </div>
                <div class="gi-fisa-serie"><span>Seria · Serial no.</span><strong><?php echo esc_html($row->serie); ?></strong></div>
            </header>
            <table class="gi-fisa-tabel">
                <tbody>
                    <?php
                    echo self::pair('Nume', 'Surname', $row->nume); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Prenume', 'Given names', $row->prenume); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Data nașterii', 'Date of birth', self::fmt($row->nastere)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Sex', 'Sex', $sex); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Locul nașterii', 'Place of birth', $row->loc_nastere); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Cetățenia', 'Nationality', $row->cetatenie); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Act de identitate', 'ID document', trim($row->tip_act . ' ' . $row->serie_act)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Valabil până la', 'Valid until', self::fmt($row->act_valabil)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    if ('' !== $row->cnp) {
                        echo self::pair('CNP', 'Personal numeric code', $row->cnp); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                    echo self::pair('Domiciliul', 'Permanent address', $row->domiciliu); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Telefon', 'Phone', $row->telefon); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Sosire', 'Arrival', self::fmt($row->sosire)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Plecare', 'Departure', self::fmt($row->plecare) . ('' !== (string) $nights ? ' (' . $nights . ' ' . (1 === $nights ? 'noapte / night' : 'nopți / nights') . ')' : '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::pair('Rezervare', 'Booking ref.', $row->rezervare . ('' !== (string) $row->comanda && $row->comanda > 0 ? ' · #' . $row->comanda : '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </tbody>
            </table>
            <p class="gi-fisa-gdpr-text"><?php echo esc_html((string) $row->gdpr_text); ?></p>
            <div class="gi-fisa-semnaturi">
                <div><span>Semnătura turistului · Guest signature</span><?php echo self::sig_img($row->semn_turist, 'Semnătura turistului'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <div><span>Recepționer · Receptionist: <?php echo esc_html($row->receptioner); ?></span><?php echo self::sig_img($row->semn_receptie, 'Semnătura recepționerului'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            </div>
            <p class="gi-fisa-foot">Înscrisă la <?php echo esc_html(get_date_from_gmt($row->creat, 'd.m.Y H:i')); ?> · Adultul <?php echo (int) $row->adult_nr; ?> din rezervare</p>
        </article>
        <?php
    }

    private static function view_registry($id) {
        GI_Zona::back_link();
        if (!current_user_can('gi_fise_vezi')) {
            echo '<div class="gi-zona-card"><p>Registrul fișelor îl vede numai proprietara (administratorul).</p></div>';
            return;
        }
        if (!self::exists()) {
            echo '<div class="gi-zona-card"><p><strong>Tabelul fișelor lipsește.</strong> Spune-i lui Claude (probabil contul de bază de date nu are drept să creeze tabele).</p></div>';
            return;
        }
        if ('' !== $id && ctype_digit((string) $id)) {
            self::view_done((int) $id);
            return;
        }
        $settings = self::settings();
        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset($_GET['pagina']) ? max(1, absint(wp_unslash($_GET['pagina']))) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per = 50;
        $res = self::search($q, $page, $per);
        $pages = max(1, (int) ceil($res['total'] / $per));
        $old = self::older_than_ids($settings['pastrare_luni']);
        $link = function ($args) use ($q) {
            return add_query_arg(array_filter(array_merge(array('q' => $q), $args)), GI_Zona::url('registru'));
        };
        ?>
        <div class="gi-zona-card">
            <h2>Registrul fișelor de cazare</h2>
            <p class="gi-zona-mic">Îl vezi numai tu. Recepția înscrie fișe, dar nu vede registrul. <strong><?php echo (int) $res['total']; ?></strong> <?php echo '' !== $q ? 'fișe găsite' : 'fișe în total'; ?>.</p>
            <form method="get" action="<?php echo esc_url(GI_Zona::url('registru')); ?>" class="gi-zona-form">
                <label for="gi-reg-q">Caută (serie, nume, rezervare, act, CNP, număr comandă)</label>
                <input type="text" id="gi-reg-q" name="q" value="<?php echo esc_attr($q); ?>" autocomplete="off">
                <p class="gi-zona-butoane"><button type="submit" class="button button-primary">Caută</button>
                    <a class="button button-outline" href="<?php echo esc_url(add_query_arg(array('export' => 'csv', 'q' => $q, '_gi_nonce' => wp_create_nonce('gi_zona_export')), GI_Zona::url('registru'))); ?>">Descarcă CSV (Excel)</a></p>
            </form>
        </div>
        <?php if ($res['rows']) : ?>
            <div class="gi-zona-card">
                <div class="gi-zona-tabel">
                    <table>
                        <thead><tr><th>Seria</th><th>Titular</th><th>Act</th><th>Sosire</th><th>Plecare</th><th>Rezervare</th><th>Înscrisă</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($res['rows'] as $r) : ?>
                                <tr>
                                    <td><a class="gi-text-link" href="<?php echo esc_url(GI_Zona::url('registru', (string) $r->id)); ?>"><?php echo esc_html($r->serie); ?></a></td>
                                    <td><?php echo esc_html($r->nume . ' ' . $r->prenume); ?></td>
                                    <td><?php echo esc_html(trim($r->tip_act . ' ' . $r->serie_act)); ?></td>
                                    <td><?php echo esc_html(self::fmt($r->sosire)); ?></td>
                                    <td><?php echo esc_html(self::fmt($r->plecare)); ?></td>
                                    <td><?php echo esc_html($r->rezervare); ?></td>
                                    <td><?php echo esc_html(get_date_from_gmt($r->creat, 'd.m.Y H:i')); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo esc_url(GI_Zona::url('registru')); ?>" data-gi-o-data>
                                            <?php echo GI_Zona::nonce_field('registru'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            <input type="hidden" name="gi_actiune" value="sterge"><input type="hidden" name="fisa_id" value="<?php echo (int) $r->id; ?>">
                                            <button type="submit" class="button button-outline" data-gi-confirma="<?php echo esc_attr('Ștergi fișa ' . $r->serie . '? Nu se mai poate readuce.'); ?>">Șterge</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($pages > 1) : ?>
                    <p class="gi-zona-butoane">
                        <?php if ($page > 1) : ?><a class="button button-outline" href="<?php echo esc_url($link(array('pagina' => $page - 1))); ?>">← Mai noi</a><?php endif; ?>
                        <span class="gi-zona-mic">Pagina <?php echo (int) $page; ?> din <?php echo (int) $pages; ?></span>
                        <?php if ($page < $pages) : ?><a class="button button-outline" href="<?php echo esc_url($link(array('pagina' => $page + 1))); ?>">Mai vechi →</a><?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="gi-zona-card"><p><?php echo '' !== $q ? 'Nicio fișă pentru această căutare.' : 'Nicio fișă înscrisă încă.'; ?></p></div>
        <?php endif; ?>
        <?php if ($settings['pastrare_luni'] > 0) : ?>
            <div class="gi-zona-card">
                <h3>Termen de păstrare: <?php echo (int) $settings['pastrare_luni']; ?> luni</h3>
                <p><?php echo count($old) ? esc_html(count($old) . ' fișe au trecut de termen.') : 'Nicio fișă nu a trecut de termen.'; ?></p>
                <?php if ($old) : ?>
                    <form method="post" action="<?php echo esc_url(GI_Zona::url('registru')); ?>" data-gi-o-data>
                        <?php echo GI_Zona::nonce_field('registru'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <input type="hidden" name="gi_actiune" value="sterge_vechi">
                        <button type="submit" class="button button-outline" data-gi-confirma="<?php echo esc_attr('Ștergi ' . count($old) . ' fișe mai vechi de ' . $settings['pastrare_luni'] . ' luni? Nu se mai pot readuce. Descarcă întâi CSV dacă ai nevoie de ele.'); ?>">Șterge fișele care au trecut de termen</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (current_user_can('gi_echipa')) : ?>
            <form method="post" action="<?php echo esc_url(GI_Zona::url('registru')); ?>" class="gi-zona-card gi-zona-form">
                <?php echo GI_Zona::nonce_field('registru'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <input type="hidden" name="gi_actiune" value="setari">
                <h3>Setări fișă</h3>
                <p class="gi-zona-mic">Aceste date apar pe fiecare fișă nouă (fișele deja înscrise rămân cum au fost).</p>
                <label for="gi-s-unitate">Denumirea unității</label>
                <input type="text" id="gi-s-unitate" name="s_unitate" value="<?php echo esc_attr($settings['unitate']); ?>" maxlength="160">
                <label for="gi-s-operator">Operatorul (firma)</label>
                <input type="text" id="gi-s-operator" name="s_operator" value="<?php echo esc_attr($settings['operator']); ?>" maxlength="160">
                <label for="gi-s-cui">CUI</label>
                <input type="text" id="gi-s-cui" name="s_cui" value="<?php echo esc_attr($settings['cui']); ?>" maxlength="30">
                <label for="gi-s-clasificare">Clasificarea unității (ex. numărul de stele)</label>
                <input type="text" id="gi-s-clasificare" name="s_clasificare" value="<?php echo esc_attr($settings['clasificare']); ?>" maxlength="80">
                <label for="gi-s-prefix">Începutul seriei (litere sau cifre, până la 6)</label>
                <input type="text" id="gi-s-prefix" name="s_prefix" value="<?php echo esc_attr($settings['prefix']); ?>" maxlength="6">
                <label for="gi-s-gdpr">Textul bifei despre datele personale <span class="gi-zona-mic">(provizoriu, până îl dă juristul)</span></label>
                <textarea id="gi-s-gdpr" name="s_gdpr" rows="4" maxlength="800"><?php echo esc_textarea($settings['gdpr']); ?></textarea>
                <label for="gi-s-pastrare">Cât timp se păstrează fișele, în luni (0 = nehotărât; nu se șterge nimic singur)</label>
                <input type="number" id="gi-s-pastrare" name="s_pastrare_luni" value="<?php echo (int) $settings['pastrare_luni']; ?>" min="0" max="240">
                <p><button type="submit" class="button button-primary">Salvează setările</button></p>
            </form>
        <?php endif; ?>
        <?php
    }
}
