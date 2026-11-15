<?php
/**
 * Scriere simplă pentru textul paginilor din „Copaci și locuri” (fără HTML): proprietara scrie ca într-un mesaj, cu câteva semne la început de rând.
 *
 *   # Titlu mare            → titlu (h2)           ## Subtitlu → h3        ### Mai mic → h4
 *   - element de listă      → listă cu buline (și „•” sau „*” la început de rând)      1. pas → listă numerotată
 *   **îngroșat**            *înclinat*             [text](https://adresa)  → link (și [0721 333 164](tel:0721333164), [scrie-ne](mailto:adresa@exemplu.ro))
 *   > citat                 --- (singur pe rând) → linie despre
 *   rând gol                → paragraf nou;  rânduri lipite → același paragraf, cu rând nou
 *
 * Se aplică numai textelor fără HTML (cele scrise în Zona echipei). Un text cu etichete HTML (din editorul WordPress) rămâne cum e.
 * Ce scrie proprietara rămâne în baza de date exact cum a scris; transformarea se face la afișare. Rezultatul trece prin wp_kses_post.
 */
if (!defined('ABSPATH')) {
    exit;
}

final class GI_Text {
    /** Există etichete de bloc HTML în text? Atunci nu-l atingem. */
    public static function has_html($raw) {
        // O etichetă întreagă (<b>, </p>, <a href="...">, <br/>) sau un comentariu HTML; un „<” singur („preț 5 < 10”) nu e HTML.
        return 1 === preg_match('~<\s*/?[a-z][a-z0-9-]*(\s[^<>]*)?/?>|<!--~i', $raw);
    }

    /** Text simplu curat: fără etichete HTML și fără caractere nepermise, dar cu „%”, „&”, „>” și ghilimele neatinse (spre deosebire de sanitize_textarea_field, care scoate „%20”). */
    private static function clean_plain($raw) {
        $t = wp_check_invalid_utf8((string) $raw);
        $t = wp_pre_kses_less_than($t);
        $t = wp_strip_all_tags($t, false);
        return str_replace("\0", '', $t);
    }

    /** Semnele din interiorul unui rând: link-uri, îngroșat, înclinat. Link-urile se pun deoparte întâi, ca semnele * din adresă să nu strice eticheta. */
    private static function inline($line) {
        $s = esc_html($line);
        $links = array();
        // [text](adresa): numai http, https, tel și mailto
        $s = preg_replace_callback('~\[([^\]\n]{1,200})\]\(((?:https?://|tel:|mailto:)[^\s)]{1,300})\)~u', function ($m) use (&$links) {
            $href = html_entity_decode($m[2], ENT_QUOTES);
            $external = 0 === strpos($href, 'http');
            $links[] = '<a href="' . esc_url($href, array('http', 'https', 'tel', 'mailto')) . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
            return "\x01" . (count($links) - 1) . "\x02";
        }, $s);
        $s = preg_replace('~\*\*(.+?)\*\*~us', '<strong>$1</strong>', $s);
        $s = preg_replace('~(?<![\*\w])\*(?!\s)([^*\n]+?)(?<!\s)\*(?![\*\w])~u', '<em>$1</em>', $s);
        // textul din interiorul unui link poate avea și el îngroșat sau înclinat; adresa nu
        return preg_replace_callback('~\x01(\d+)\x02~', function ($m) use ($links) {
            return isset($links[(int) $m[1]]) ? $links[(int) $m[1]] : '';
        }, $s);
    }

    /**
     * Ce se păstrează în baza de date: un text cu HTML (din editorul WordPress) trece prin wp_kses_post, ca până acum; un text scris simplu rămâne cum a fost scris
     * (cu >, & și ghilimele), fără etichete (acestea se scot). La afișare se transformă și se curăță din nou.
     */
    public static function clean_for_save($raw) {
        $raw = (string) $raw;
        return self::has_html($raw) ? wp_kses_post($raw) : self::clean_plain($raw);
    }

    /** Textul pentru caseta din formular: un text simplu salvat de o versiune mai veche (cu &amp; și &gt;) se arată cum a fost scris. */
    public static function for_edit($content) {
        $content = (string) $content;
        return self::has_html($content) ? $content : html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    }

    public static function to_html($raw) {
        $raw = (string) $raw;
        if ('' === trim($raw) || self::has_html($raw)) {
            return $raw;
        }
        $raw = html_entity_decode($raw, ENT_QUOTES, 'UTF-8'); // texte simple păstrate de o versiune mai veche, cu entități
        $raw = str_replace(array("\r\n", "\r"), "\n", $raw);
        $blocks = preg_split("~\n\s*\n~", trim($raw));
        $out = array();
        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map('rtrim', explode("\n", $block)), function ($l) {
                return '' !== trim($l);
            }));
            $i = 0;
            $n = count($lines);
            $para = array();
            $flush = function () use (&$para, &$out) {
                if ($para) {
                    $out[] = '<p>' . implode('<br>', $para) . '</p>';
                    $para = array();
                }
            };
            while ($i < $n) {
                $line = trim($lines[$i]);
                if (1 === preg_match('~^(#{1,3})\s+(.+)$~u', $line, $m)) {
                    $flush();
                    $level = strlen($m[1]) + 1; // # → h2, ## → h3, ### → h4
                    $out[] = '<h' . $level . '>' . self::inline($m[2]) . '</h' . $level . '>';
                    $i++;
                } elseif (1 === preg_match('~^(?:[-•*])\s+(.+)$~u', $line)) {
                    $flush();
                    $items = array();
                    while ($i < $n && 1 === preg_match('~^(?:[-•*])\s+(.+)$~u', trim($lines[$i]), $m)) {
                        $items[] = '<li>' . self::inline($m[1]) . '</li>';
                        $i++;
                    }
                    $out[] = '<ul>' . implode('', $items) . '</ul>';
                } elseif (1 === preg_match('~^\d{1,2}[.)]\s+(.+)$~u', $line)) {
                    $flush();
                    $items = array();
                    while ($i < $n && 1 === preg_match('~^\d{1,2}[.)]\s+(.+)$~u', trim($lines[$i]), $m)) {
                        $items[] = '<li>' . self::inline($m[1]) . '</li>';
                        $i++;
                    }
                    $out[] = '<ol>' . implode('', $items) . '</ol>';
                } elseif (1 === preg_match('~^>\s?(.*)$~u', $line, $m)) {
                    $flush();
                    $quote = array();
                    while ($i < $n && 1 === preg_match('~^>\s?(.*)$~u', trim($lines[$i]), $m)) {
                        $quote[] = self::inline($m[1]);
                        $i++;
                    }
                    $out[] = '<blockquote><p>' . implode('<br>', $quote) . '</p></blockquote>';
                } elseif (1 === preg_match('~^-{3,}$~', $line)) {
                    $flush();
                    $out[] = '<hr>';
                    $i++;
                } else {
                    $para[] = self::inline($line);
                    $i++;
                }
            }
            $flush();
        }
        return wp_kses_post(implode("\n", $out));
    }
}
