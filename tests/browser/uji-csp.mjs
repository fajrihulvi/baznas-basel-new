// Buka halaman lewat Apache (header CSP asli), catat pelanggaran CSP & error konsol, uji interaksi JS utama.
import puppeteer from 'puppeteer-core';

const base = process.argv[2] ?? 'http://localhost:8090';
const halaman = ['/', '/berita', '/zakat', '/zakat/zakat-penghasilan', '/berita/baznas-bangka-selatan-gelar-pembinaan-mualaf-tahun-2026-bersama-ipari-dan-kemenag', '/program/basel-peduli', '/halaman/kurban','/program', '/bayar-zakat', '/kalkulator-zakat', '/konfirmasi',
  '/rekening', '/regulasi', '/laporan', '/video', '/galeri', '/amil', '/testimoni', '/cari?q=zakat', '/tentang', '/admin/masuk', '/tidak-ada-uji-csp'];

const browser = await puppeteer.launch({
  executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new', args: ['--no-first-run', '--no-default-browser-check'],
});
let masalah = 0;
for (const p of halaman) {
  const page = await browser.newPage();
  await page.setViewport({ width: 360, height: 780, isMobile: true, hasTouch: true });
  const catatan = [];
  page.on('console', (m) => { if (m.type() === 'error' || /Content.Security.Policy/i.test(m.text())) catatan.push('konsol: ' + m.text()); });
  page.on('pageerror', (e) => catatan.push('JS: ' + e.message));
  await page.evaluateOnNewDocument(() => {
    document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP-VIOLATION ${e.violatedDirective} ${e.blockedURI}`));
  });
  const r = await page.goto(base + p, { waitUntil: 'networkidle0', timeout: 60000 });
  const csp = r.headers()['content-security-policy'] ? 'CSP' : 'tanpa-CSP';
  // Gulir ke bawah agar carousel/lazy load ikut berjalan
  await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 120)); } });
  await new Promise((r) => setTimeout(r, 600));
  // 404 sengaja: abaikan error konsol "Failed to load resource" untuk halaman itu sendiri
  const relevan = catatan.filter((c) => !(p.startsWith('/tidak-ada') && /404/.test(c)));
  masalah += relevan.length;
  console.log(`${relevan.length ? 'MASALAH' : 'OK     '} ${String(r.status()).padEnd(4)} ${csp.padEnd(9)} ${p}`);
  relevan.forEach((c) => console.log('        ' + c));
  await page.close();
}

// Interaksi: menu sheet, tab zakat, kalkulator
const page = await browser.newPage();
await page.setViewport({ width: 360, height: 780, isMobile: true, hasTouch: true });
const err = [];
page.on('console', (m) => { if (m.type() === 'error') err.push(m.text()); });
page.on('pageerror', (e) => err.push(e.message));
await page.goto(base + '/', { waitUntil: 'networkidle0' });
const menu = await page.$('[data-menu-buka], [aria-controls="menu-sheet"], button[aria-haspopup="dialog"]');
if (menu) { await menu.click(); await new Promise((r) => setTimeout(r, 400)); }
const terbuka = await page.evaluate(() => !!document.querySelector('dialog[open]'));
console.log(`${terbuka ? 'OK     ' : 'MASALAH'} menu sheet terbuka di 360px`);
if (!terbuka) masalah++;
await page.goto(base + '/kalkulator-zakat', { waitUntil: 'networkidle0' });
const input = await page.$('input[inputmode="numeric"]');
if (input) { await input.type('100000000'); await new Promise((r) => setTimeout(r, 400)); }
const teks = await page.evaluate(() => document.body.innerText);
const hitung = /Rp2\.500\.000/.test(teks);
console.log(`${hitung ? 'OK     ' : 'CEK    '} kalkulator menghitung (Rp100.000.000 → Rp2.500.000)`);
console.log(`${err.length ? 'MASALAH' : 'OK     '} interaksi tanpa error JS ${err.join(' | ')}`);
masalah += err.length;

// Admin (login akun uji dari env UJI_USER/UJI_PASS): editor, daftar, status
if (process.env.UJI_USER) {
  const a = await browser.newPage();
  await a.setViewport({ width: 1280, height: 900 });
  const ae = [];
  a.on('console', (m) => { if (m.type() === 'error' || /Content.Security.Policy/i.test(m.text())) ae.push(m.text()); });
  a.on('pageerror', (e) => ae.push(e.message));
  await a.evaluateOnNewDocument(() => document.addEventListener('securitypolicyviolation', (e) => console.error(`CSP-VIOLATION ${e.violatedDirective} ${e.blockedURI}`)));
  await a.goto(base + '/admin/masuk', { waitUntil: 'networkidle0' });
  await a.type('input[name=username]', process.env.UJI_USER);
  await a.type('input[name=password]', process.env.UJI_PASS);
  await Promise.all([a.waitForNavigation({ waitUntil: 'networkidle0' }), a.click('main form button[type=submit]')]);
  for (const p of ['/admin', '/admin/post', '/admin/post/baru', '/admin/banner/baru', '/admin/status', '/admin/tautan-rusak', '/admin/redirect/baru?dari=/x', '/admin/pembayaran', '/admin/setting']) {
    const n = ae.length;
    const r = await a.goto(base + p, { waitUntil: 'networkidle0' });
    if (p === '/admin/post/baru') {
      const ed = await a.$('[contenteditable=true]');
      if (ed) { await ed.click(); await a.keyboard.type('Uji editor'); }
      const tb = await a.$$('form button[type=button]');
      for (const b of tb.slice(0, 3)) await b.click();
      console.log(`        editor: ${ed ? 'ada' : 'TIDAK ADA'}, ${Math.min(3, tb.length)} dari ${tb.length} tombol toolbar diklik`);
    }
    const baru = ae.slice(n);
    masalah += baru.length;
    console.log(`${baru.length ? 'MASALAH' : 'OK     '} ${String(r.status()).padEnd(4)} ${p}`);
    baru.forEach((c) => console.log('        ' + c));
  }
  await a.close();
}

await browser.close();
console.log(`\n${masalah === 0 ? 'TANPA PELANGGARAN CSP / ERROR JS' : masalah + ' masalah'}`);
process.exit(masalah === 0 ? 0 : 1);
