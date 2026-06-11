// One-off UI verification: drives the running app with system Edge/Chrome
// (playwright-core, no browser download) and screenshots the main screens.
const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8123';
const OUT = path.join(__dirname, 'ui-shots');

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    let browser;
    for (const channel of ['msedge', 'chrome']) {
        try { browser = await chromium.launch({ channel, headless: true }); break; }
        catch (e) { console.log(`channel ${channel} failed: ${e.message.split('\n')[0]}`); }
    }
    if (!browser) { console.error('NO BROWSER'); process.exit(1); }

    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text().slice(0, 200)); });
    page.on('pageerror', (e) => errors.push('PAGEERROR: ' + String(e).slice(0, 200)));

    // Login
    await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
    await page.fill('input[type=email]', 'admin@srmss.test');
    await page.fill('input[type=password]', 'password');
    await Promise.all([
        page.waitForURL('**/dashboard', { timeout: 20000 }),
        page.click('button[type=submit]'),
    ]);
    console.log('LOGGED IN');

    const shots = [
        ['dashboard', '/dashboard', 'Depot Dashboard'],
        ['vehicles', '/vehicles', 'Vehicles'],
        ['drivers', '/drivers', 'Drivers'],
        ['routes', '/routes', 'Routes'],
        ['schedules', '/schedules', 'Schedules'],
        ['fuel-logs', '/fuel-logs', 'Fuel Logs'],
        ['maintenance', '/maintenance-logs', 'Maintenance Logs'],
        ['reports', '/reports', 'Reports'],
        ['users', '/users', 'Users'],
    ];
    for (const [name, url, expect] of shots) {
        await page.goto(BASE + url, { waitUntil: 'networkidle' });
        await page.waitForSelector(`text=${expect}`, { timeout: 15000 });
        await page.waitForTimeout(400);
        await page.screenshot({ path: path.join(OUT, name + '.png'), fullPage: true });
        console.log('SHOT ' + name);
    }

    // A modal, for form-layout check
    await page.goto(BASE + '/vehicles', { waitUntil: 'networkidle' });
    await page.click('text=+ Add Vehicle');
    await page.waitForSelector('text=Add Vehicle');
    await page.waitForTimeout(300);
    await page.screenshot({ path: path.join(OUT, 'vehicle-modal.png') });
    console.log('SHOT vehicle-modal');

    // Mobile viewport check
    const mctx = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const mpage = await mctx.newPage();
    await mpage.goto(BASE + '/login', { waitUntil: 'networkidle' });
    await mpage.fill('input[type=email]', 'admin@srmss.test');
    await mpage.fill('input[type=password]', 'password');
    await Promise.all([
        mpage.waitForURL('**/dashboard', { timeout: 20000 }),
        mpage.click('button[type=submit]'),
    ]);
    await mpage.goto(BASE + '/vehicles', { waitUntil: 'networkidle' });
    await mpage.waitForTimeout(400);
    await mpage.screenshot({ path: path.join(OUT, 'mobile-vehicles.png'), fullPage: true });
    await mpage.goto(BASE + '/dashboard', { waitUntil: 'networkidle' });
    await mpage.waitForTimeout(400);
    await mpage.screenshot({ path: path.join(OUT, 'mobile-dashboard.png'), fullPage: true });
    console.log('SHOT mobile');

    console.log('CONSOLE ERRORS: ' + (errors.length ? '\n' + errors.join('\n') : 'none'));
    await browser.close();
})().catch((e) => { console.error('FAILED: ' + e.message); process.exit(1); });
