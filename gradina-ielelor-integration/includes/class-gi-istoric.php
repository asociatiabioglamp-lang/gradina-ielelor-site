<?php
/**
 * Istoricul Zonei echipei: cine a creat sau a schimbat ce și când (vouchere generate și șterse, locuri cu cod QR, conturile colegilor,
 * limitele de reducere, parolele schimbate). Fiecare eveniment e o linie într-un tabel al site-ului; rămâne și după ce un voucher sau un
 * loc e șters. Nu se scriu niciodată parole sau chei în istoric.
 *
 * Se vede în Zona echipei → Istoric: vouchere (cine are drept la toate voucherele), locuri (cine administrează copacii), conturi (administratorul).
 * Tabelul se păstrează și dacă pluginul e șters (e istoric); se poate șterge din baza de date.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Istoric {
    const DB_OPTION = 'gi_istoric_db';
    const DB_VERSION = '1';

    public static function init() {
        add_action('init', array(__CLASS__, 'maybe_install'), 5);
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'gi_istoric';
    }

    /**
     * Creează tabelul la prima rulare (și după o versiune care îl schimbă). Același tabel nu se creează de două ori.
     */
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
  creat datetime NOT NULL,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  utilizator varchar(190) NOT NULL DEFAULT '',
  actiune varchar(40) NOT NULL,
  obiect varchar(190) NOT NULL DEFAULT '',
  detalii longtext NULL,
  PRIMARY KEY  (id),
  KEY actiune (actiune),
  KEY user_id (user_id),
  KEY creat (creat)
) {$charset};");
        update_option(self::DB_OPTION, self::DB_VERSION, true);
    }

    /**
     * Scrie un eveniment. Nu oprește niciodată acțiunea care l-a cerut: dacă tabelul lipsește, se renunță în tăcere.
     *
     * @param string $action  ex. voucher_creat, voucher_sters, loc_creat, cont_creat
     * @param string $object  la ce se referă (cod voucher, id loc, nume utilizator)
     * @param array  $details date suplimentare (fără parole)
     * @param int|null $user_id cine a făcut acțiunea (implicit utilizatorul curent)
     */
    public static function log($action, $object = '', $details = array(), $user_id = null) {
        global $wpdb;
        $uid = null === $user_id ? get_current_user_id() : (int) $user_id;
        $user = $uid ? get_userdata($uid) : false;
        $label = $user ? $user->display_name . ' (' . $user->user_login . ')' : 'sistem';
        $previous = $wpdb->suppress_errors(true);
        $done = $wpdb->insert(
            self::table(),
            array(
                'creat' => current_time('mysql'),
                'user_id' => $uid,
                'utilizator' => mb_substr($label, 0, 190),
                'actiune' => substr(sanitize_key($action), 0, 40),
                'obiect' => mb_substr((string) $object, 0, 190),
                'detalii' => wp_json_encode($details, JSON_UNESCAPED_UNICODE),
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
        $wpdb->suppress_errors($previous);
        return (bool) $done;
    }

    /**
     * Cele mai noi evenimente, cu începutul numelui acțiunii în lista dată (ex. array('voucher_', 'loc_')).
     * Întoarce cel mult $limit + 1 rânduri: al doilea în plus arată că mai sunt unele mai vechi.
     *
     * @param string[] $prefixes
     * @return array<int,object>
     */
    public static function recent($prefixes, $limit = 100, $offset = 0) {
        global $wpdb;
        $where = array();
        $args = array();
        foreach ((array) $prefixes as $prefix) {
            $where[] = 'actiune LIKE %s';
            $args[] = $wpdb->esc_like((string) $prefix) . '%';
        }
        if (!$where) {
            return array();
        }
        $args[] = (int) $limit + 1;
        $args[] = max(0, (int) $offset);
        $table = self::table();
        $previous = $wpdb->suppress_errors(true);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id, creat, user_id, utilizator, actiune, obiect, detalii FROM {$table} WHERE (" . implode(' OR ', $where) . ') ORDER BY id DESC LIMIT %d OFFSET %d', $args)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->suppress_errors($previous);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Textul pe înțeles al unui eveniment: array('text' => ..., 'coduri' => [...]) (codurile apar numai la generarea de vouchere).
     */
    public static function describe($row) {
        $d = json_decode((string) $row->detalii, true);
        $d = is_array($d) ? $d : array();
        $v = function ($key, $fallback = '') use ($d) {
            return isset($d[$key]) && is_scalar($d[$key]) ? (string) $d[$key] : $fallback;
        };
        $nice = function ($n) {
            return rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
        };
        $out = array('text' => $row->actiune, 'coduri' => array());
        switch ($row->actiune) {
            case 'voucher_creat':
                $value = 'procent' === $v('tip') ? $nice($v('valoare')) . '%' : $nice($v('valoare')) . ' lei';
                $out['text'] = 'A generat ' . (int) $v('numar') . ' vouchere de ' . $value . ' (începutul codului ' . $v('prefix') . ')' . ('' !== $v('nota') ? ', nota: „' . $v('nota') . '”' : '') . '. Valabile până la ' . $v('expira') . '.';
                $out['coduri'] = isset($d['coduri']) && is_array($d['coduri']) ? array_map('strval', $d['coduri']) : array();
                break;
            case 'voucher_sters':
                $out['text'] = 'A șters voucherul ' . $row->obiect . ' (' . $v('valoare') . '), nefolosit' . ('' !== $v('creat_la') ? ', creat la ' . $v('creat_la') : '') . '.';
                break;
            case 'loc_creat':
                $out['text'] = 'A adăugat locul „' . $v('titlu') . '” (' . ('publish' === $v('status') ? 'publicat' : 'ciornă') . ').';
                break;
            case 'loc_modificat':
                $out['text'] = 'A modificat locul „' . $v('titlu') . '” (' . ('publish' === $v('status') ? 'publicat' : 'ciornă') . ').';
                break;
            case 'cont_creat':
                $out['text'] = 'A creat contul colegului ' . $v('nume') . ' (' . $v('utilizator') . ').';
                break;
            case 'cont_reactivat':
                $out['text'] = 'A reactivat contul lui ' . $v('nume') . ' (' . $v('utilizator') . '), cu o parolă nouă.';
                break;
            case 'cont_rol':
                $out['text'] = 'A dat acces la Zonă contului de client ' . $v('nume') . ' (' . $v('utilizator') . ').';
                break;
            case 'cont_parola':
                $out['text'] = 'A dat o parolă nouă lui ' . $v('nume') . ' (' . $v('utilizator') . ').';
                break;
            case 'cont_scos':
                $out['text'] = 'A scos accesul lui ' . $v('nume') . ' (' . $v('utilizator') . ').';
                break;
            case 'cont_limite':
                $out['text'] = 'A schimbat limitele pentru vânzări: ' . $nice($v('procent')) . '% / ' . $nice($v('suma')) . ' lei / ' . (int) $v('numar') . ' coduri.';
                break;
        }
        return $out;
    }
}
