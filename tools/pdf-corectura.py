"""Înlocuiește texte dintr-un PDF cu text real, în același font, aceeași mărime și culoare.
- PDF-uri LibreOffice (Carlito/Caladea): fonturile instalate pe sistem.
- PDF-uri PowerPoint (Calibri): fontul încorporat în document; o cifră care lipsește din fontul italic se ia din Calibri normal, înclinat.
Utilizare: python3 -I pdf-edit2.py in.pdf out.pdf edits.json
edits.json: [{"page":1,"old":"text vechi (începutul sau tot)","new":"text nou","bbox":[x0,y0,x1,y1] (opțional)}]"""
import sys, json, fitz
SYS = {'Carlito-Regular': '/usr/share/fonts/truetype/crosextra/Carlito-Regular.ttf', 'Carlito-Bold': '/usr/share/fonts/truetype/crosextra/Carlito-Bold.ttf',
       'Carlito-Italic': '/usr/share/fonts/truetype/crosextra/Carlito-Italic.ttf', 'Caladea-Regular': '/usr/share/fonts/truetype/crosextra/Caladea-Regular.ttf',
       'Caladea-Bold': '/usr/share/fonts/truetype/crosextra/Caladea-Bold.ttf'}
src, dst, cfg = sys.argv[1:4]
d = fitz.open(src)
embedded = {}
for p in d:
    for f in p.get_fonts(full=True):
        base = f[3].split('+')[-1]
        if base in embedded: continue
        try:
            _, _, _, buf = d.extract_font(f[0])
        except Exception:
            continue
        if buf and len(buf) > 50000: embedded[base] = buf   # fonturile complete încorporate (PowerPoint)
def font_for(base):
    if base in SYS: return fitz.Font(fontfile=SYS[base]), dict(fontfile=SYS[base])
    return fitz.Font(fontbuffer=embedded[base]), dict(fontbuffer=embedded[base])
for e in json.load(open(cfg)):
    page = d[e['page'] - 1]
    cand = []
    for b in page.get_text('dict')['blocks']:
        for l in b.get('lines', []):
            for s in l['spans']:
                if not s['text'].strip().startswith(e['old'].strip()): continue
                if 'bbox' in e and not fitz.Rect(s['bbox']).intersects(fitz.Rect(e['bbox'])): continue
                cand.append(s)
    assert len(cand) == 1, f'pagina {e["page"]}: {len(cand)} potriviri pentru „{e["old"]}”'
    s = cand[0]; x0, y0, x1, y1 = s['bbox']
    pix = page.get_pixmap(clip=fitz.Rect(x0 - 3, y0 - 3, x0 - 1, y0 - 1), dpi=72)
    bg = tuple(c / 255 for c in pix.pixel(0, 0)[:3])
    page.add_redact_annot(fitz.Rect(x0 - 1, y0 - 1, x1 + 1, y1 + 1), fill=bg)
    page.apply_redactions(images=fitz.PDF_REDACT_IMAGE_NONE, graphics=fitz.PDF_REDACT_LINE_ART_NONE)
    base = s['font'].split('+')[-1]; size = s['size']; col = s['color']
    rgb = ((col >> 16) & 255) / 255, ((col >> 8) & 255) / 255, (col & 255) / 255
    fo, fkw = font_for(base)
    x, y = s['origin']
    # segmentăm textul: caracterele care lipsesc din font se desenează din varianta normală, înclinată
    runs, cur, cur_ok = [], '', True
    for ch in e['new']:
        ok = fo.has_glyph(ord(ch)) or ch == ' '
        if cur and ok != cur_ok: runs.append((cur, cur_ok)); cur = ''
        cur += ch; cur_ok = ok
    if cur: runs.append((cur, cur_ok))
    for i, (t, ok) in enumerate(runs):
        if ok:
            tw = fitz.TextWriter(page.rect, color=rgb)
            tw.append(fitz.Point(x, y), t, font=fo, fontsize=size)
            tw.write_text(page)
            x += fo.text_length(t, fontsize=size)
        else:
            # glifă lipsă din fontul încorporat: o luăm din Carlito (compatibil cu Calibri), în aceeași variantă
            fb = fitz.Font(fontfile=SYS['Carlito-Italic' if base.endswith('Italic') else 'Carlito-Bold' if base.endswith('Bold') else 'Carlito-Regular'])
            tw = fitz.TextWriter(page.rect, color=rgb)
            tw.append(fitz.Point(x, y), t, font=fb, fontsize=size)
            tw.write_text(page)
            x += fb.text_length(t, fontsize=size)
    print(f'pagina {e["page"]}: „{s["text"][:40]}…” -> „{e["new"][:50]}” ({base} {size:.1f}pt; lățime nouă {x - s["origin"][0]:.0f} față de {x1 - x0:.0f}; pagina are {page.rect.width:.0f})')
d.save(dst, garbage=3, deflate=True)
