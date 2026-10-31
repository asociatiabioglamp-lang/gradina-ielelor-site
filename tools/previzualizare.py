"""Copie statică, navigabilă, a site-ului nou din WordPress-ul local (pentru previzualizarea proprietarului).

Utilizare: python3 tools/previzualizare.py <folder-destinație>  (WordPress-ul local pornit pe http://127.0.0.1:9400).
Căutarea BRW nu pornește în copie; linkurile spre paginile care nu sunt în copie duc la site-ul live."""
import os, re, sys, posixpath, urllib.request, urllib.parse
BASE = 'http://127.0.0.1:9400'
LIVE = 'https://gradina-ielelor.ro'
OUT = sys.argv[1]
PAGES = {'/': 'index.html', '/bungalow/': 'bungalow/index.html', '/glamping/': 'glamping/index.html', '/camping/': 'camping/index.html',
         '/festivaluri-si-tabere/': 'festivaluri-si-tabere/index.html', '/evenimente-private/': 'evenimente-private/index.html',
         '/despre-noi/': 'despre-noi/index.html', '/regulament-politici/': 'regulament-politici/index.html', '/contact/': 'contact/index.html'}
done = {}

def get(url):
    with urllib.request.urlopen(urllib.request.Request(url, headers={'User-Agent': 'gi-previzualizare'}), timeout=120) as r:
        return r.read()

def save_asset(path):
    """Descarcă un fișier (fără ?ver) și întoarce calea lui locală relativă la OUT."""
    local = path.lstrip('/')
    if local in done:
        return local
    data = get(BASE + path)
    dest = os.path.join(OUT, local)
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    if path.endswith('.css'):
        css = data.decode('utf-8', 'replace')
        def fix(m):
            ref = m.group(2).strip()
            if ref.startswith(('data:', '#')):
                return m.group(0)
            absu = urllib.parse.urljoin(BASE + path, ref)
            u = urllib.parse.urlparse(absu)
            if u.netloc != urllib.parse.urlparse(BASE).netloc:
                return m.group(0)
            sub = save_asset(u.path)
            rel = posixpath.relpath(sub, posixpath.dirname(local))
            return 'url(' + m.group(1) + rel + (('#' + u.fragment) if u.fragment else '') + m.group(1) + ')'
        css = re.sub(r'url\((["\']?)([^)"\']+)\1\)', fix, css)
        data = css.encode('utf-8')
    with open(dest, 'wb') as f:
        f.write(data)
    done[local] = True
    return local

def rewrite(html, page_file):
    here = posixpath.dirname(page_file)
    def conv(url):
        u = urllib.parse.urlparse(url)
        frag = ('#' + u.fragment) if u.fragment else ''
        if u.path in PAGES and not u.query:
            return posixpath.relpath(PAGES[u.path], here or '.') + frag
        if re.match(r'^/(wp-content|wp-includes)/.+\.[a-z0-9]+$', u.path):
            return posixpath.relpath(save_asset(u.path), here or '.') + frag
        return LIVE + u.path + (('?' + u.query) if u.query else '') + frag
    def attr(m):
        name, q, val = m.group(1), m.group(2), m.group(3)
        if name == 'srcset':
            parts = []
            for item in val.split(','):
                bits = item.strip().split()
                if bits and bits[0].startswith(BASE):
                    bits[0] = conv(bits[0])
                parts.append(' '.join(bits))
            return f'{name}={q}{", ".join(parts)}{q}'
        if val.startswith(BASE):
            return f'{name}={q}{conv(val)}{q}'
        return m.group(0)
    html = re.sub(r'\b(src|href|srcset|action|content|data-src)=(["\'])(.*?)\2', attr, html)
    html = html.replace('http:\\/\\/127.0.0.1:9400', 'https:\\/\\/gradina-ielelor.ro').replace(BASE, LIVE)
    return html

NOTE = '''<script>document.addEventListener('submit',function(e){var f=e.target;if(!f.matches||!f.matches('form.ovabrw_search'))return;e.preventDefault();var n=f.querySelector('.gi-preview-note');if(!n){n=document.createElement('p');n.className='gi-preview-note';n.style.cssText='margin:12px 0 0;padding:10px 14px;border-radius:12px;background:#fff3e4;color:#22343f;font-size:14px';f.appendChild(n);}n.textContent='Aceasta e o copie de previzualizare: aici căutarea nu pornește. Pe site, butonul arată locurile libere și prețul.';},true);</script>'''
for path, page_file in PAGES.items():
    html = get(BASE + path).decode('utf-8')
    html = rewrite(html, page_file)
    html = html.replace('<head>', '<head>\n<meta name="robots" content="noindex, nofollow">', 1)
    html = html.replace('</body>', NOTE + '\n</body>', 1)
    dest = os.path.join(OUT, page_file)
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with open(dest, 'w', encoding='utf-8') as f:
        f.write(html)
    print('pagina', path, '->', page_file)
print('fișiere', len(done) + len(PAGES))
