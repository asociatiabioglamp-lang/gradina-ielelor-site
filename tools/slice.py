# Taie capturile full-page în bucăți vizibile: python3 slice.py <prefix>
import sys
from PIL import Image
prefix = sys.argv[1]
for name, step, scale in [('desktop', 1800, 1), ('mobil', 3000, 2)]:
    im = Image.open('%s-%s.png' % (prefix, name))
    w, h = im.size
    n = 0
    for y in range(0, h, step):
        part = im.crop((0, y, w, min(h, y + step)))
        if scale > 1:
            part = part.resize((part.width // scale, part.height // scale))
        part.save('%s-%s-%02d.png' % (prefix, name, n))
        n += 1
    print(name, w, h, n, 'bucăți')
