// Server-local Chromium screenshot engine for the two approved SPAD artwork images.
// Reads a self-contained HTML document from stdin and writes only PNG bytes to stdout.
// Never navigates to remote URLs or changes website files.
'use strict';
const fs = require('fs');
const {chromium} = require(process.env.SPAD_WA_PLAYWRIGHT);
(async () => {
  let html = '';
  process.stdin.setEncoding('utf8');
  for await (const chunk of process.stdin) {
    html += chunk;
    if (html.length > 9_000_000) throw Error('Input too large');
  }
  if (!html.includes('class="wa-poster"')) throw Error('Invalid poster input');
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu'],
  });
  try {
    const page = await browser.newPage({
      viewport:{width:1122,height:1402},
      deviceScaleFactor:1,
      locale:'fa-IR',
    });
    await page.route(/^https?:\/\//, route => route.abort());
    await page.setContent(html, {waitUntil:'load', timeout:18000});
    await page.evaluate(async () => { await document.fonts.ready; });
    const png = await page.screenshot({type:'png', fullPage:false, timeout:18000});
    if (png.length < 40000) throw Error('Unexpected poster PNG size');
    process.stdout.write(png);
    await page.close();
  } finally {
    await browser.close();
  }
})().catch(e => {
  console.error('SPAD_WA_RENDER_ERROR='+String(e?.message||e).slice(0,180));
  process.exitCode=1;
});