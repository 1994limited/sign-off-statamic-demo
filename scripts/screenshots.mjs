// Screenshots of Saltmarsh Energy, as used in Sign-off's docs and
// Marketplace listing. Run against a fresh demo, after demo:setup:
//
//   cd scripts && npm install && npm run screenshots
//
// DEMO_URL     the demo's address (default http://localhost:8000)
// SCREENSHOTS  where to write them (default ./screenshots)
// CHROME       a Chrome or Chromium to drive (default: Google Chrome on macOS)
import { chromium } from 'playwright-core';
import { mkdirSync } from 'node:fs';

const base = (process.env.DEMO_URL || 'http://localhost:8000').replace(/\/$/, '');
const out = process.env.SCREENSHOTS || new URL('./screenshots', import.meta.url).pathname;
const chrome = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
mkdirSync(out, { recursive: true });

// Local demos often use a self-signed certificate (Herd, Valet).
const browser = await chromium.launch({ args: ['--ignore-certificate-errors'], executablePath: chrome });

async function settle(page) {
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(1500);
}

// A browser signed in to the Control Panel as one of the demo people.
async function as(email, colorScheme = 'light') {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2, colorScheme });
    const page = await context.newPage();
    await page.goto(`${base}/cp/auth/login`);
    await page.locator('input[type=email], input[name=email]').first().fill(email);
    await page.locator('input[type=password]').first().fill('password');
    await page.keyboard.press('Enter');
    await page.waitForURL((url) => !url.pathname.includes('/auth/'));
    return page;
}

// Open an entry from its collection's listing, by its title.
async function open(page, collection, title) {
    await page.goto(`${base}/cp/collections/${collection}/entries`);
    await page.getByRole('link', { name: title, exact: true }).first().click();
    await page.waitForURL(/\/entries\/[\w-]+$/);
    await settle(page);
}

async function compare(page) {
    await page.getByRole('button', { name: 'Compare changes' }).click();
    await page.waitForTimeout(1500);
}

// The site itself: the live price, while a cheaper one waits for sign-off.
const site = await browser.newPage({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
await site.goto(`${base}/`);
await settle(site);
await site.screenshot({ path: `${out}/site.png` });
await site.goto(`${base}/tariffs/fixed-green-12`);
await settle(site);
await site.screenshot({ path: `${out}/site-tariff.png` });

// Lee, in Legal: two things waiting, and the price cut compared.
const lee = await as('lee@example.com');
await lee.goto(`${base}/cp/sign-off`);
await settle(lee);
await lee.screenshot({ path: `${out}/review-page.png` });
await open(lee, 'tariffs', 'Fixed Green 12');
await lee.screenshot({ path: `${out}/entry-panel.png` });
await compare(lee);
await lee.screenshot({ path: `${out}/compare.png` });

// Alex, who writes: the dashboard, and the page that was sent back.
const alex = await as('alex@example.com');
await alex.goto(`${base}/cp/dashboard`);
await settle(alex);
await alex.screenshot({ path: `${out}/widget.png` });
await open(alex, 'pages', 'Switching to us');
await alex.screenshot({ path: `${out}/sent-back.png` });
await compare(alex);
await alex.getByRole('button', { name: /As submitted/ }).click();
await alex.waitForTimeout(1200);
await alex.screenshot({ path: `${out}/compare-previous.png` });

// Pat, the publisher: the last step of a new tariff.
const pat = await as('pat@example.com', 'dark');
await open(pat, 'tariffs', 'Night Saver');
await pat.screenshot({ path: `${out}/last-step-dark.png` });

// The admin: every round so far.
const admin = await as('admin@example.com');
await admin.goto(`${base}/cp/sign-off?tab=history`);
await settle(admin);
await admin.screenshot({ path: `${out}/history.png` });

await browser.close();
console.log(`Screenshots are in ${out}`);
