// Visual check of the two new features: the Leaflet route map and the audit log.
const { chromium } = require('playwright-core');
const path = require('path');
const fs = require('fs');

const BASE = 'http://127.0.0.1:8000';
const OUT = path.join(__dirname, 'ui-shots');

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    let browser;
    for (const channel of ['msedge', 'chrome']) {
        try { browser = await chromium.launch({ channel, headless: true }); break; } catch { }
    }
    if (!browser) { console.error('NO BROWSER'); process.exit(1); }

    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text().slice(0, 200)); });
    page.on('pageerror', (e) => errors.push('PAGEERROR: ' + String(e).slice(0, 200)));

    // Login (this also creates a 'login' audit entry)
    await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
    await page.fill('input[type=email]', 'admin@srmss.test');
    await page.fill('input[type=password]', 'password');
    await Promise.all([page.waitForURL('**/dashboard', { timeout: 20000 }), page.click('button[type=submit]')]);
    console.log('LOGGED IN');

    // Create a vehicle to generate an audit entry
    await page.goto(BASE + '/vehicles', { waitUntil: 'networkidle' });
    await page.click('text=+ Add Vehicle');
    await page.waitForSelector('text=Registration No');
    await page.fill('input[wire\\:model="registration_number"]', 'QA-' + Date.now().toString().slice(-4));
    await page.fill('input[wire\\:model="seating_capacity"]', '40');
    await page.click('button:has-text("Save")');
    await page.waitForTimeout(800);
    console.log('VEHICLE CREATED');

    // Route map (Leaflet) : open Map on the seeded R-138 (has coordinates)
    await page.goto(BASE + '/routes', { waitUntil: 'networkidle' });
    await page.click('tr:has-text("R-138") button:has-text("Map")');
    // Wait for Leaflet tiles to actually load from OpenStreetMap
    try {
        await page.waitForSelector('.leaflet-tile-loaded', { timeout: 15000 });
        console.log('LEAFLET TILES LOADED');
    } catch { console.log('LEAFLET TILES: not detected (network?)'); }
    await page.waitForTimeout(1200);
    await page.screenshot({ path: path.join(OUT, 'route-map-leaflet.png') });
    console.log('SHOT route-map-leaflet');

    // Audit log
    await page.goto(BASE + '/activity-log', { waitUntil: 'networkidle' });
    await page.waitForSelector('text=Activity Log');
    await page.waitForTimeout(400);
    await page.screenshot({ path: path.join(OUT, 'activity-log.png'), fullPage: true });
    console.log('SHOT activity-log');

    console.log('CONSOLE ERRORS: ' + (errors.length ? '\n' + errors.join('\n') : 'none'));
    await browser.close();
})().catch((e) => { console.error('FAILED: ' + e.message); process.exit(1); });
