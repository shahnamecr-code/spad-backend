'use strict';

/**
 * PM2-owned CLI-only refresh worker for approved SPAD PNG previews.
 *
 * The hosting account has a */5 crontab entry but its cron daemon was not
 * active during diagnostics. A persistent PM2 job is independent of crond,
 * regenerates real-price market/city PNGs and services private on-demand
 * requests without running Chromium inside PHP-FPM.
 *
 * No HTTP server, external listener, credentials, groups or WhatsApp sends.
 */
const fs = require('fs');
const path = require('path');
const {execFile} = require('child_process');

const ROOT = '/home/spadesta/react-amlak';
const PHP = process.env.SPAD_WA_PREVIEW_PHP || '/opt/alt/php81/usr/bin/php';
const BASE = path.join(ROOT, 'storage', 'app', 'whatsapp-market', 'admin-preview');
const QUEUE = path.join(BASE, 'requests');
const HEARTBEAT = path.join(BASE, 'worker-heartbeat.json');
const MARKET_INTERVAL = 5 * 60 * 1000;
const CITIES_INTERVAL = 30 * 60 * 1000;
const TICK_INTERVAL = 8 * 1000;
const MAX_REQUEST_AGE = 15 * 60 * 1000;
const ATTEMPT_BACKOFF = 60 * 1000;
let busy = false;
let nextMarket = 0;
let nextCities = 0;
let heartbeat = {
  started_at: new Date().toISOString(),
  market_success_at: null,
  cities_success_at: null,
  queue_success_at: null,
  last_check_at: null,
  state: 'starting'
};
const retryAfter = new Map();

function saveHeartbeat() {
  fs.mkdirSync(BASE, {recursive: true, mode: 0o750});
  const tmp = HEARTBEAT + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(heartbeat), {mode:0o640});
  fs.renameSync(tmp, HEARTBEAT);
}
function invoke(kind, province = '', city = '') {
  const args = ['artisan', 'spad:wa-preview-warm', '--kind=' + kind];
  if (kind === 'city' && province && city) {
    args.push('--province=' + province, '--city=' + city);
  }
  return new Promise(resolve => {
    execFile(PHP, args, {
      cwd: ROOT,
      timeout: 150000,
      maxBuffer: 256000,
      encoding: 'utf8',
      windowsHide: true
    }, (err, stdout) => {
      // Partial success is reported by the existing Artisan command with exit 0.
      // Never print provider responses or personal admin data.
      const result = !err && stdout.includes('WA_PREVIEW_CACHE_RESULT=');
      resolve(result);
    });
  });
}
function queueFiles() {
  try {
    fs.mkdirSync(QUEUE, {recursive: true, mode: 0o750});
    return fs.readdirSync(QUEUE)
      .filter(name => /^[a-f0-9]{64}\.json$/.test(name))
      .slice(0, 30);
  } catch (_) { return []; }
}
function validRequest(data) {
  if (!data || !['market','city'].includes(data.kind)) return false;
  if (data.kind === 'market') return true;
  return ['province','city'].every(key =>
    typeof data[key] === 'string' &&
    data[key].trim().length > 0 &&
    data[key].length <= 80 &&
    !/[\x00-\x1f\x7f]/.test(data[key])
  );
}
async function serviceQueue() {
  const files = queueFiles();
  for (const filename of files) {
    const file = path.join(QUEUE, filename);
    try {
      const stat = fs.lstatSync(file);
      if (!stat.isFile() || stat.isSymbolicLink()) continue;
      if (Date.now() - stat.mtimeMs > MAX_REQUEST_AGE) {
        fs.unlinkSync(file);
        retryAfter.delete(filename);
        continue;
      }
      if (retryAfter.get(filename) > Date.now()) continue;
      const data = JSON.parse(fs.readFileSync(file, 'utf8'));
      if (!validRequest(data)) {fs.unlinkSync(file);continue;}
      // Never follow file-replaced paths outside queue; no shell execution.
      const success = await invoke(data.kind, data.province || '', data.city || '');
      if (success) {
        fs.unlinkSync(file);
        retryAfter.delete(filename);
        heartbeat.queue_success_at = new Date().toISOString();
      } else {
        retryAfter.set(filename, Date.now() + ATTEMPT_BACKOFF);
      }
      break; // Only one queued render per tick.
    } catch (_) {
      retryAfter.set(filename, Date.now() + ATTEMPT_BACKOFF);
    }
  }
}
async function tick() {
  if (busy) return;
  busy = true;
  heartbeat.last_check_at = new Date().toISOString();
  try {
    const now = Date.now();
    if (now >= nextMarket) {
      nextMarket = now + MARKET_INTERVAL;
      const success = await invoke('market');
      if (success) heartbeat.market_success_at = new Date().toISOString();
    }
    if (Date.now() >= nextCities) {
      nextCities = Date.now() + CITIES_INTERVAL;
      const success = await invoke('city');
      if (success) heartbeat.cities_success_at = new Date().toISOString();
    }
    await serviceQueue();
    heartbeat.state = 'running';
  } catch (_) {
    heartbeat.state = 'degraded';
  } finally {
    try { saveHeartbeat(); } catch (_) {}
    busy = false;
  }
}
fs.mkdirSync(BASE, {recursive:true, mode:0o750});
setTimeout(() => void tick(), 1000);
setInterval(() => void tick(), TICK_INTERVAL);
console.log('SPAD preview-only PM2 renderer worker running; no WhatsApp messages');
