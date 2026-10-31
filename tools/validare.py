#!/usr/bin/env python3
"""
Validarea site-ului într-un WordPress local (vezi tools/local-wp/README.md).

Verifică: sintaxa PHP și JS a pluginului, fiecare pagină (HTTP 200, un singur H1, titlu și descriere),
fiecare imagine și link intern, ancorele (#...) pe pagina țintă, butoanele de rezervare
și jurnalul de erori PHP. Nu scrie nimic în WordPress.

Utilizare: python3 tools/validare.py [--baza http://127.0.0.1:9400] [--jurnal cale/gi-php-errors.log]
"""
import argparse
import html.parser
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

PLUGIN = Path(__file__).resolve().parents[1] / 'gradina-ielelor-integration'
PAGINI = ['/', '/bungalow/', '/glamping/', '/camping/', '/festivaluri-si-tabere/', '/evenimente-private/', '/despre-noi/', '/regulament-politici/', '/contact/']
# Butoane „Cere ofertă” care nu duc la modulul de rezervare: pagina pentru organizatori și Contact (Evenimente private).
CERERI_OFERTA = ('/festival/', '/cotatie/', '/contact/')
# Pagini care nu fac parte din plugin; pe site-ul real există, local pot lipsi.
EXTERNE_PLUGINULUI = ('/festival/', '/cotatie/', '/shop/', '/produs/')


class Pagina(html.parser.HTMLParser):
    def __init__(self):
        super().__init__()
        self.ids, self.linkuri, self.imagini, self.h1 = set(), [], [], 0
        self.titlu, self.descriere, self._in_titlu, self.rezervare = '', None, False, []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if a.get('id'):
            self.ids.add(a['id'])
        if tag == 'a' and a.get('href'):
            self.linkuri.append(a['href'])
            if 'data-gi-book' in a or 'header-cta' in (a.get('class') or '') or 'mobile-booking-cta' in (a.get('class') or ''):
                self.rezervare.append(a['href'])
        if tag == 'img' and a.get('src'):
            self.imagini.append(a['src'])
        if tag == 'h1':
            self.h1 += 1
        if tag == 'title':
            self._in_titlu = True
        if tag == 'meta' and a.get('name') == 'description':
            self.descriere = a.get('content', '')

    def handle_endtag(self, tag):
        if tag == 'title':
            self._in_titlu = False

    def handle_data(self, data):
        if self._in_titlu:
            self.titlu += data


def cere(url):
    try:
        with urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': 'gi-validare'}), timeout=60) as r:
            return r.status, r.geturl(), r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, url, ''


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--baza', default='http://127.0.0.1:9400')
    ap.add_argument('--jurnal', default='')
    arg = ap.parse_args()
    baza = arg.baza.rstrip('/')
    gazda = urllib.parse.urlparse(baza).netloc
    erori, avertismente, ok = [], [], []

    # 1. Sintaxă
    for f in sorted(PLUGIN.rglob('*.php')):
        r = subprocess.run(['php', '-l', str(f)], capture_output=True, text=True)
        (ok if r.returncode == 0 else erori).append(f'php -l {f.relative_to(PLUGIN)}' + ('' if r.returncode == 0 else ': ' + r.stdout.strip()))
    for f in sorted(PLUGIN.rglob('*.js')):
        r = subprocess.run(['node', '--check', str(f)], capture_output=True, text=True)
        (ok if r.returncode == 0 else erori).append(f'node --check {f.relative_to(PLUGIN)}' + ('' if r.returncode == 0 else ': ' + r.stderr.strip()))

    # 2. Pagini
    verificate, pagini = {}, {}
    for cale in PAGINI:
        status, final, corp = cere(baza + cale)
        if status != 200 or urllib.parse.urlparse(final).path != cale:
            erori.append(f'{cale}: HTTP {status}, ajunge la {final}')
            continue
        p = Pagina()
        p.feed(corp)
        pagini[cale] = p
        if p.h1 != 1:
            erori.append(f'{cale}: {p.h1} titluri H1 (trebuie 1)')
        if not p.titlu.strip():
            erori.append(f'{cale}: fără <title>')
        if p.descriere is not None and not (70 <= len(p.descriere) <= 170):
            avertismente.append(f'{cale}: descrierea are {len(p.descriere)} caractere (ideal 120-160)')
        if 'FOTO PROVIZORIU' in corp:
            avertismente.append(f'{cale}: text de imagine provizorie în pagină')
        ok.append(f'{cale}: 200, 1 H1, titlu „{p.titlu.strip()[:70]}”')

    # 3. Imagini și linkuri interne, ancore
    for cale, p in pagini.items():
        for src in set(p.imagini):
            url = urllib.parse.urljoin(baza + cale, src)
            if urllib.parse.urlparse(url).netloc != gazda or url in verificate:
                continue
            verificate[url] = cere(url)[0]
            if verificate[url] != 200:
                erori.append(f'{cale}: imagine {src} -> HTTP {verificate[url]}')
        for href in set(p.linkuri):
            if href.startswith(('mailto:', 'tel:')):
                continue
            url = urllib.parse.urljoin(baza + cale, href)
            u = urllib.parse.urlparse(url)
            if u.netloc != gazda:
                continue
            tinta = u.path or '/'
            if u.fragment:
                ancora = u.fragment
                pg = pagini.get(tinta)
                if pg is not None and ancora not in pg.ids and not re.fullmatch(r'rezervare-[a-z-]+', ancora):
                    erori.append(f'{cale}: link {href} -> ancora #{ancora} nu există pe {tinta}')
            if tinta.startswith(EXTERNE_PLUGINULUI):
                continue
            cheie = u._replace(fragment='').geturl()
            if cheie not in verificate:
                status, final, _ = cere(cheie)
                verificate[cheie] = status if urllib.parse.urlparse(final).path == tinta else f'{status} (redirecționat la {urllib.parse.urlparse(final).path})'
            if verificate[cheie] != 200:
                erori.append(f'{cale}: link {href} -> {verificate[cheie]}')

    # 4. Butoanele de rezervare de pe paginile dedicate duc la modulul de pe pagina principală
    acasa = pagini.get('/')
    if acasa is not None and 'rezervare' not in acasa.ids:
        erori.append('/: lipsește secțiunea #rezervare')
    for cale, p in pagini.items():
        if cale == '/':
            continue
        for href in set(p.rezervare):
            u = urllib.parse.urlparse(urllib.parse.urljoin(baza + cale, href))
            if u.path in CERERI_OFERTA:
                continue
            if u.path != '/' or not u.fragment.startswith('rezervare'):
                erori.append(f'{cale}: buton de rezervare spre {href} (trebuie /#rezervare-<tip>)')
        ok.append(f'{cale}: rezervare -> ' + ', '.join(sorted({urllib.parse.urlparse(urllib.parse.urljoin(baza + cale, h)).path + "#" + urllib.parse.urlparse(h).fragment for h in p.rezervare})))

    # 5. Jurnal PHP
    if arg.jurnal and Path(arg.jurnal).exists():
        linii = [l for l in Path(arg.jurnal).read_text(errors='replace').splitlines() if 'gradina-ielelor-integration' in l]
        (erori if linii else ok).append(f'jurnal PHP: {len(linii)} mesaje din plugin' + (': ' + linii[0][:200] if linii else ''))

    for t, lista in (('OK', ok), ('ATENȚIE', avertismente), ('EROARE', erori)):
        for x in lista:
            print(f'[{t}] {x}')
    print(f'\nRezumat: {len(ok)} OK, {len(avertismente)} atenționări, {len(erori)} erori; {len(verificate)} URL-uri verificate.')
    sys.exit(1 if erori else 0)


if __name__ == '__main__':
    main()
