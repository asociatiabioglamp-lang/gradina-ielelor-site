#!/usr/bin/env python3
"""Schiță simplificată a Grădinii Ielelor, desenată după planul PUZ (GI_SCHITA.pdf), cu numere pe fiecare clădire și zonă.
Rulează: python3 make_schita.py   → schita-numerotata.png, schita-date.json (numere, coduri PUZ, poziții în procente)."""
import json, math, os
import cv2, numpy as np
from PIL import Image, ImageDraw, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
im = cv2.imread(os.environ.get('GI_PUZ_IMG') or os.path.join(HERE, 'img-000.png'))
H, W = im.shape[:2]
rgb = im[:, :, ::-1].astype(int)

def near(c, t):
    return (np.abs(rgb - np.array(c)).sum(2) < t)

canal = near((166, 223, 243), 40).astype(np.uint8)
road = near((214, 216, 205), 22).astype(np.uint8)
boundary = near((21, 23, 158), 70).astype(np.uint8)
red = ((rgb[:, :, 0] > 190) & (rgb[:, :, 1] < 90) & (rgb[:, :, 2] < 90)).astype(np.uint8)
kids = near((88, 181, 126), 55).astype(np.uint8)
orange = ((rgb[:, :, 0] > 235) & (rgb[:, :, 1] > 140) & (rgb[:, :, 1] < 200) & (rgb[:, :, 2] < 80)).astype(np.uint8)
cyan = near((105, 215, 245), 45).astype(np.uint8)
lgreen = ((rgb[:, :, 1] > 170) & (rgb[:, :, 0] < 150) & (rgb[:, :, 2] < 120)).astype(np.uint8)

def comps(mask, min_area, k=1):
    m = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, np.ones((k, k), np.uint8)) if k > 1 else mask
    n, lab, stats, cent = cv2.connectedComponentsWithStats(m, 8)
    return [(i, stats[i], cent[i]) for i in range(1, n) if stats[i][4] >= min_area], lab

# ---- conturul parcelei: cea mai mare componentă a liniei albastre închise (fără legendă) ----
bclose = cv2.morphologyEx(boundary, cv2.MORPH_CLOSE, np.ones((25, 25), np.uint8))
cs, _ = cv2.findContours(bclose, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
cs = [c for c in cs if cv2.boundingRect(c)[0] > 100 and cv2.contourArea(c) > 1_000_000]
parcel_c = max(cs, key=cv2.contourArea)
parcel = np.zeros((H, W), np.uint8)
cv2.drawContours(parcel, [parcel_c], -1, 1, -1)
x0, y0, bw, bh = cv2.boundingRect(parcel_c)
PAD = 140
cx0, cy0 = max(0, x0 - PAD), max(0, y0 - PAD)
cx1, cy1 = min(W, x0 + bw + PAD), min(H, y0 + bh + PAD)
CW, CH = cx1 - cx0, cy1 - cy0
print('crop', cx0, cy0, CW, CH)

def clip(mask):
    return mask[cy0:cy1, cx0:cx1]

parcel_k = clip(parcel)
legend_zone = np.zeros((H, W), np.uint8)  # legenda și titlul (stânga-sus) nu intră în desen
legend_zone[:1650, :1180] = 1
legend_zone = clip(legend_zone)

def clean(mask, min_area, close=1):
    m = clip(mask) * (1 - legend_zone)
    if close > 1:
        m = cv2.morphologyEx(m, cv2.MORPH_CLOSE, np.ones((close, close), np.uint8))
    n, lab, st, _ = cv2.connectedComponentsWithStats(m, 8)
    out = np.zeros_like(m)
    for i in range(1, n):
        if st[i][4] >= min_area:
            out[lab == i] = 1
    return out

canal_k = clean(canal, 15000, 9)
road_k = clean(road, 1500, 13) * cv2.dilate(parcel_k, np.ones((61, 61), np.uint8))  # numai pe parcelă (ștampilele și liniile din afară dispar)
# restul liniilor gri de dimensiuni mici (cote) se pierd la min_area

# ---- clădiri: componentele roșii (hașură + contur), unite prin dilatare ----
red_k = clip(red) * (1 - legend_zone)
red_d = cv2.dilate(red_k, np.ones((23, 23), np.uint8))
n, lab, st, cen = cv2.connectedComponentsWithStats(red_d, 8)
buildings = []
parcel_shift = parcel_c - np.array([[cx0, cy0]])
for i in range(1, n):
    x, y, w, h, a = st[i]
    if a < 2500 or w < 25 or h < 25:
        continue
    pts = cv2.findNonZero((lab == i).astype(np.uint8))
    hull = cv2.convexHull(pts)
    hull = cv2.approxPolyDP(hull, 6, True)
    M = cv2.moments(hull)
    c = (M['m10'] / M['m00'], M['m01'] / M['m00']) if M['m00'] else (x + w / 2, y + h / 2)
    buildings.append({'hull': hull[:, 0, :], 'c': c, 'bbox': (int(x), int(y), int(w), int(h)), 'area': float(a)})
# intrarea (triunghiul roșu): mic, în colțul de nord-est, lângă drumul public; restul componentelor din afara parcelei (legendă, ștampile, linii) se aruncă
entrance = None
bl = []
for b in buildings:
    x, y, w, h = b['bbox']
    if w < 190 and h < 190 and b['c'][0] > CW * 0.8 and b['c'][1] < CH * 0.06:
        entrance = b
        continue
    inside = cv2.pointPolygonTest(parcel_shift.astype(np.float32), (float(b['c'][0]), float(b['c'][1])), False) >= 0
    if inside:
        bl.append(b)
buildings = bl
print('clădiri', len(buildings), 'intrare', bool(entrance))

# ---- zone: joacă (verde închis), panouri (dungi verzi), parcare (poligon măsurat), platforme deșeuri, put, giratoriu ----
def largest_in_parcel(mask, min_area):
    m = clean(mask, min_area, 5)
    n, lab, st, cen = cv2.connectedComponentsWithStats(m, 8)
    res = []
    for i in range(1, n):
        cxy = (int(cen[i][0]), int(cen[i][1]))
        if parcel_k[min(CH - 1, cxy[1]), min(CW - 1, cxy[0])]:
            res.append((i, st[i], cen[i], lab))
    return res

kids_c = largest_in_parcel(kids, 20000)
zones = []
for i, s, c, lab in kids_c:
    cnt, _ = cv2.findContours((lab == i).astype(np.uint8), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    hull = cv2.approxPolyDP(cv2.convexHull(max(cnt, key=cv2.contourArea)), 6, True)[:, 0, :]
    zones.append({'kind': 'joaca', 'hull': hull, 'c': tuple(c)})

# zona cu panouri fotovoltaice (dreptunghi cu dungi verzi, lângă clădirile de glamping): poligon măsurat pe planul original
solar_full = np.array([[2578, 1157], [2705, 1158], [2678, 1290], [2578, 1290]])
solar = solar_full - np.array([cx0, cy0])
zones.append({'kind': 'panouri', 'hull': solar, 'c': tuple(solar.mean(0))})

# parcarea de la intrare: poligon măsurat pe planul original (colț sus-stânga al zonei: x=2400, y=300)
park_full = np.array([[2668, 450], [2870, 538], [2866, 670], [2622, 562]])
park = (park_full - np.array([cx0, cy0]))
zones.append({'kind': 'parcare', 'hull': park, 'c': tuple(park.mean(0))})

# platformele de deșeuri (pătrate portocalii), puțul (cerc cyan), giratoriul (cerc verde deschis, jos-stânga)
org = clean(orange, 1200, 7)
n, lab, st, cen = cv2.connectedComponentsWithStats(org, 8)
for i in range(1, n):
    x, y, w, h, a = st[i]
    cnt, _ = cv2.findContours((lab == i).astype(np.uint8), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    hull = cv2.approxPolyDP(cv2.convexHull(max(cnt, key=cv2.contourArea)), 5, True)[:, 0, :]
    zones.append({'kind': 'deseuri', 'hull': hull, 'c': tuple(cen[i])})
cy_ = clean(cyan, 600, 5)
n, lab, st, cen = cv2.connectedComponentsWithStats(cy_, 8)
for i in range(1, n):
    x, y, w, h, a = st[i]
    if 40 < w < 140 and 40 < h < 140 and parcel_k[min(CH - 1, int(cen[i][1])), min(CW - 1, int(cen[i][0]))] == 0:
        pass
    if 40 < w < 140 and 40 < h < 140:
        zones.append({'kind': 'put', 'hull': np.array([[x, y], [x + w, y], [x + w, y + h], [x, y + h]]), 'c': tuple(cen[i]), 'r': max(w, h) / 2})
        break
lg = clip(lgreen) * (1 - legend_zone)
rb = lg  # giratoriu: verde deschis jos-stânga
n, lab, st, cen = cv2.connectedComponentsWithStats(cv2.dilate(rb, np.ones((15, 15), np.uint8)), 8)
for i in range(1, n):
    x, y, w, h, a = st[i]
    if 80 < w < 200 and 80 < h < 200 and cen[i][1] > CH * 0.75:
        zones.append({'kind': 'giratoriu', 'hull': np.array([[x, y], [x + w, y], [x + w, y + h], [x, y + h]]), 'c': tuple(cen[i]), 'r': max(w, h) / 2})
        break

# ---- coduri PUZ (indicativ): poziții aproximative pe planul original (în px din imaginea redusă la 1414 lățime) ----
S = 3175 / 1414
puz_pos = {
    'A1': (1240, 372), 'A5': (1055, 480), 'A6': (1165, 480), 'A7': (1100, 547), 'A8': (1010, 590), 'A9': (1125, 590), 'A2': (1160, 730), 'A3': (1152, 765),
    'A10': (1105, 915), 'A11': (917, 853), 'B1': (715, 1100), 'B2': (880, 1190), 'C1': (460, 1355), 'C2': (425, 1410), 'C3': (350, 1515), 'C4': (370, 1600),
    'C5': (305, 1590), 'C6': (772, 1378), 'C7': (690, 1530), 'C8': (767, 1536), 'C9': (605, 1680),
}
puz_name = {'A1': 'Stație de epurare', 'A2': 'Toaletă', 'A3': 'Platformă panouri solare', 'A5': 'Glamping', 'A6': 'Glamping', 'A7': 'Glamping', 'A8': 'Glamping',
            'A9': 'Glamping', 'A10': 'Foișor', 'A11': 'Toaletă', 'B1': 'Restaurant', 'B2': 'Restaurant', 'C1': 'Bungalow', 'C2': 'Bungalow', 'C3': 'Bungalow',
            'C4': 'Bungalow', 'C5': 'Bungalow', 'C6': 'Platformă panouri solare', 'C7': 'Toaletă', 'C8': 'Foișor', 'C9': 'Spațiu tehnic și administrativ'}
for code, (px, py) in puz_pos.items():
    gx, gy = px * S - cx0, py * S - cy0
    best = min(buildings, key=lambda b: (b['c'][0] - gx) ** 2 + (b['c'][1] - gy) ** 2)
    d = math.hypot(best['c'][0] - gx, best['c'][1] - gy)
    if d < 260 and 'puz' not in best:
        best['puz'] = code
    elif d < 260:
        best.setdefault('puz_alt', []).append(code)
split_out = []
for b in buildings:
    if b.get('puz_alt'):
        codes = [b['puz']] + b['puz_alt']
        x, y, w, h = b['bbox']
        sub = red_k[y:y + h, x:x + w]
        ys, xs = np.nonzero(sub)
        pts = np.column_stack([xs + x, ys + y]).astype(np.float32)
        k = len(codes)
        _, labk, cenk = cv2.kmeans(pts, k, None, (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 50, 0.5), 5, cv2.KMEANS_PP_CENTERS)
        used = set()
        for ci in range(k):
            cp = pts[labk.ravel() == ci].astype(np.int32)
            hull = cv2.approxPolyDP(cv2.convexHull(cp), 6, True)[:, 0, :]
            M = cv2.moments(hull.reshape(-1, 1, 2))
            c = (M['m10'] / M['m00'], M['m01'] / M['m00']) if M['m00'] else tuple(cenk[ci])
            best = min((cd for cd in codes if cd not in used), key=lambda cd: (puz_pos[cd][0] * S - cx0 - c[0]) ** 2 + (puz_pos[cd][1] * S - cy0 - c[1]) ** 2)
            used.add(best)
            split_out.append({'hull': hull, 'c': c, 'bbox': b['bbox'], 'area': float(len(cp)), 'puz': best})
    else:
        split_out.append(b)
buildings = split_out
print('fără cod PUZ:', [round(b['c'][0]) for b in buildings if 'puz' not in b])

# ---- numerotare: de la nord la sud (sus → jos), apoi de la vest la est ----
items = []
for b in buildings:
    items.append({'kind': 'cladire', 'hull': b['hull'], 'c': b['c'], 'puz': b.get('puz', ''), 'puz_alt': b.get('puz_alt', [])})
for z in zones:
    items.append({'kind': z['kind'], 'hull': z['hull'], 'c': z['c'], 'puz': '', 'r': z.get('r', 0)})
if entrance:
    items.append({'kind': 'intrare', 'hull': entrance['hull'], 'c': entrance['c'], 'puz': ''})
items.sort(key=lambda t: (round(t['c'][1] / 70), t['c'][0]))
for i, t in enumerate(items, 1):
    t['nr'] = i

# ---- desen (supraeșantionare x2, apoi micșorare) ----
OUT_W = 1500
scale = OUT_W / CW
SS = 2
sc = scale * SS
Wd, Hd = int(CW * sc), int(CH * sc)
img = Image.new('RGB', (Wd, Hd), (244, 241, 229))
d = ImageDraw.Draw(img)

def P(pts):
    return [(float(x) * sc, float(y) * sc) for x, y in pts]

def poly_from_mask(mask, eps=3, min_area=800):
    cnt, _ = cv2.findContours(mask.astype(np.uint8), cv2.RETR_CCOMP, cv2.CHAIN_APPROX_SIMPLE)
    out = []
    for c in cnt:
        if cv2.contourArea(c) >= min_area:
            out.append(cv2.approxPolyDP(c, eps, True)[:, 0, :])
    return out

GRASS = (228, 238, 214)
d.polygon(P(cv2.approxPolyDP(parcel_c - np.array([[cx0, cy0]]), 4, True)[:, 0, :]), fill=GRASS)
for pc in poly_from_mask(canal_k, 4, 6000):
    d.polygon(P(pc), fill=(150, 205, 234))
for pc in poly_from_mask(road_k, 3, 300):
    d.polygon(P(pc), fill=(207, 200, 186))
# conturul Grădinii
d.line(P(list(cv2.approxPolyDP(parcel_c - np.array([[cx0, cy0]]), 4, True)[:, 0, :]) + [cv2.approxPolyDP(parcel_c - np.array([[cx0, cy0]]), 4, True)[0, 0, :]]), fill=(31, 85, 99), width=int(5 * sc / 1.0 * 0.7))

COL = {'cladire': (217, 138, 74), 'joaca': (93, 180, 126), 'panouri': (176, 210, 120), 'parcare': (170, 170, 178), 'deseuri': (240, 190, 90), 'put': (60, 170, 225), 'giratoriu': (140, 200, 120), 'intrare': (200, 50, 45)}
for t in items:
    hull = t['hull']
    col = COL[t['kind']]
    if t['kind'] in ('put', 'giratoriu'):
        cx, cy = t['c']
        r = t.get('r', 30)
        d.ellipse([(cx - r) * sc, (cy - r) * sc, (cx + r) * sc, (cy + r) * sc], fill=col, outline=(60, 60, 60), width=int(2 * sc))
    else:
        d.polygon(P(hull), fill=col, outline=(70, 50, 30))
        d.line(P(list(hull) + [hull[0]]), fill=(70, 50, 30), width=max(2, int(3 * sc / 1.5)))

# ---- numere: cercuri albe, cu mici deplasări ca să nu se suprapună ----
R = 30
pos = [[t['c'][0], t['c'][1]] for t in items]
for _ in range(120):
    moved = False
    for i in range(len(pos)):
        for j in range(i + 1, len(pos)):
            dx, dy = pos[j][0] - pos[i][0], pos[j][1] - pos[i][1]
            dist = math.hypot(dx, dy) or 0.01
            if dist < 2 * R + 6:
                push = (2 * R + 6 - dist) / 2
                ux, uy = dx / dist, dy / dist
                pos[i][0] -= ux * push; pos[i][1] -= uy * push
                pos[j][0] += ux * push; pos[j][1] += uy * push
                moved = True
    if not moved:
        break
fnt = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', int(R * 1.15 * sc))
for t, (px, py) in zip(items, pos):
    r = R * sc
    cx, cy = px * sc, py * sc
    if math.hypot(px - t['c'][0], py - t['c'][1]) > 6:  # linie spre locul real, când numărul a fost mutat
        d.line([(t['c'][0] * sc, t['c'][1] * sc), (cx, cy)], fill=(40, 40, 40), width=int(2 * sc))
    d.ellipse([cx - r, cy - r, cx + r, cy + r], fill=(255, 255, 255), outline=(30, 30, 30), width=int(3 * sc))
    txt = str(t['nr'])
    tw = d.textlength(txt, font=fnt)
    d.text((cx - tw / 2, cy - fnt.size * 0.62), txt, font=fnt, fill=(20, 20, 20))
    t['badge'] = (px, py)

# ---- antet cu sigla și numele, săgeata nord, mențiunea de ciornă ----
LOGO = os.environ.get('GI_LOGO') or os.path.join(HERE, '..', '..', 'gradina-ielelor-integration', 'assets', 'logo-gradina-ielelor.png')
lg_img = Image.open(LOGO).convert('RGBA')
lw = int(Wd * 0.2)
lg_img = lg_img.resize((lw, int(lw * lg_img.height / lg_img.width)), Image.LANCZOS)
img.paste(lg_img, (int(Wd * 0.035), int(Hd * 0.015)), lg_img)
f1 = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf', int(Wd * 0.03))
f2 = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', int(Wd * 0.016))
tx = int(Wd * 0.035) + lw + int(Wd * 0.02)
d.text((tx, int(Hd * 0.018)), 'Grădina Ielelor', font=f1, fill=(31, 85, 99))
d.text((tx, int(Hd * 0.018) + int(Wd * 0.04)), 'Planul Grădinii · Sâmbăta de Sus, Brașov', font=f2, fill=(70, 80, 85))
d.text((tx, int(Hd * 0.018) + int(Wd * 0.062)), 'CIORNĂ: numerele se înlocuiesc cu denumirile locurilor', font=f2, fill=(190, 60, 40))
# săgeata nord
ax, ay = int(Wd * 0.93), int(Hd * 0.045)
d.polygon([(ax, ay - 50 * SS), (ax - 22 * SS, ay + 25 * SS), (ax, ay + 10 * SS), (ax + 22 * SS, ay + 25 * SS)], fill=(31, 85, 99))
d.text((ax - 12 * SS, ay + 32 * SS), 'N', font=ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 30 * SS), fill=(31, 85, 99))

img = img.resize((OUT_W, int(Hd / SS)), Image.LANCZOS)
out_png = os.path.join(HERE, 'schita-numerotata.png')
img.save(out_png, optimize=True)
print('salvat', out_png, img.size)

# ---- date pentru tabel și pentru hartă (poziții în procente din imaginea finală) ----
FW, FH = img.size
kinds = {'cladire': 'clădire', 'joaca': 'zonă verde de joacă / agrement copii', 'panouri': 'zonă cu panouri fotovoltaice', 'parcare': 'zonă cu locuri înclinate (parcare?)',
         'deseuri': 'platformă colectare deșeuri', 'put': 'put forat', 'giratoriu': 'giratoriu (cerc verde)', 'intrare': 'acces din drumul public'}
rows = []
for t in items:
    px, py = t['badge']
    rows.append({'nr': t['nr'], 'tip': kinds[t['kind']], 'cod_puz': t['puz'], 'nume_puz': puz_name.get(t['puz'], ''), 'x_pct': round(px * scale / FW * 100, 1) if False else round(px / CW * 100, 1), 'y_pct': round(py / CH * 100, 1)})
json.dump({'latime': FW, 'inaltime': FH, 'randuri': rows}, open(os.path.join(HERE, 'schita-date.json'), 'w'), ensure_ascii=False, indent=1)
for r in rows:
    print(r['nr'], r['tip'], r['cod_puz'], r['nume_puz'], r['x_pct'], r['y_pct'])
