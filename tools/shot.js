// Capturi full-page pe mobil (390px) și desktop (1440px): node shot.js <url> <out-prefix>
const { chromium } = require('playwright');
(async () => {
  const [url, out] = process.argv.slice(2);
  const browser = await chromium.launch();
  for (const [name, vp] of [['mobil', { width: 390, height: 844 }], ['desktop', { width: 1440, height: 900 }]]) {
    const page = await browser.newPage({ viewport: vp, deviceScaleFactor: name === 'mobil' ? 2 : 1 });
    await page.goto(url, { waitUntil: 'networkidle' });
    // Animațiile la derulare se declanșează doar la vizualizare; pentru captură le afișăm direct.
    await page.addStyleTag({ content: '.gi-reveal{animation:none!important;opacity:1!important;transform:none!important}' });
    // Derulăm toată pagina ca imaginile lazy să se încarce.
    await page.evaluate(async () => {
      for (let y = 0; y < document.documentElement.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 60)); }
      window.scrollTo(0, 0);
    });
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `${out}-${name}.png`, fullPage: true });
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    console.log(name, 'overflow-x:', overflow, 'height:', await page.evaluate(() => document.documentElement.scrollHeight));
    await page.close();
  }
  await browser.close();
})();
