#!/usr/bin/env python3
"""Extrage textele paginilor simple din docs/pagini-continut.md în gradina-ielelor-integration/content/pagini/<slug>.html.

Sursa textelor rămâne documentul; fișierele din plugin sunt copia folosită de unealta „Pregătește paginile”
(Setări → Grădina Ielelor). Rulează din rădăcina depozitului: python3 tools/extrage-pagini.py
Scrie și rezumatele (câmpul „Rezumat”) pe ecran, ca să le verifici față de gi_pagini_spec()."""
import os, re, sys

DOC = 'docs/pagini-continut.md'
OUT = 'gradina-ielelor-integration/content/pagini'
lines = open(DOC, encoding='utf-8').read().split('\n')
sections = [i for i, l in enumerate(lines) if re.match(r'^## Pagina \d+:', l)]
os.makedirs(OUT, exist_ok=True)
for n, start in enumerate(sections):
    end = sections[n + 1] if n + 1 < len(sections) else len(lines)
    block = lines[start:end]
    slug = next((re.search(r'`([^`]+)`', l).group(1) for l in block if l.startswith('- **Slug:**')), None)
    excerpt = next((l.split(':**', 1)[1].strip() for l in block if l.startswith('- **Rezumat:**')), '')
    opens = [i for i, l in enumerate(block) if l.startswith('```html')]
    if not slug or not opens:
        sys.exit('Nu găsesc adresa sau blocul de cod la: ' + lines[start])
    body = []
    for l in block[opens[0] + 1:]:
        if l.startswith('```'):
            break
        body.append(l)
    text = '\n'.join(body).strip() + '\n'
    if 'gi-nota-editor' in text:
        sys.exit('Blocul paginii ' + slug + ' conține o notă pentru editor: nu se publică.')
    with open(os.path.join(OUT, slug + '.html'), 'w', encoding='utf-8') as f:
        f.write(text)
    print('%-22s %6d caractere | Rezumat: %s' % (slug, len(text), excerpt))
