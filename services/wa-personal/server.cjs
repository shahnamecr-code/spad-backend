'use strict';

/**
 * User-opted-in unofficial WhatsApp Web companion: no contact discovery,
 * inbox readers, group endpoints or arbitrary recipient targeting.
 *
 * All HTTP routes are loopback-only and authenticated with a random key
 * generated outside GitHub by the on-server installer. QR is memory-only.
 */
const fs = require('fs');
const os = require('os');
const http = require('http');
const path = require('path');
const crypto = require('crypto');
const QRCode = require('qrcode');
const {Client, LocalAuth, MessageMedia} = require('whatsapp-web.js');

process.umask(0o077);
const PORT = Number(process.env.SPAD_WA_SELF_PORT || '39317');
const HOST = '127.0.0.1';
const SECRET_FILE = process.env.SPAD_WA_SELF_KEY_FILE || path.join(os.homedir(), 'spad-staging', 'spad-wa-personal', 'bridge.key');
const AUTH_DIR = process.env.SPAD_WA_SELF_AUTH_DIR || path.join(os.homedir(), 'spad-staging', 'spad-wa-personal', 'auth');
const secret = fs.readFileSync(SECRET_FILE, 'utf8').trim();
if (secret.length < 48 || !/^[a-f0-9]{48,128}$/i.test(secret)) {
  throw new Error('The localhost bridge key has not been created securely');
}
fs.mkdirSync(AUTH_DIR, {recursive: true, mode: 0o700});
const secretHash = crypto.createHash('sha256').update(secret).digest();
let state = 'initializing';
let lastError = null;
let qrDataUri = null;
let qrCreatedAt = 0;
let linkedSelfId = null;
let inFlight = false;
let lastSendAt = 0;
const startedAt = Date.now();
// WhatsApp rotates pairing codes every ~20s and, after a few minutes with
// nobody scanning, stops issuing new ones. Only serve a recent code, and
// when it has gone stale, restart this process (PM2 brings it back) so the
// next request gets a fresh code instead of "not ready" forever.
const QR_MAX_AGE_MS = 40_000;
const RESTART_MIN_UPTIME_MS = 90_000;
let restartScheduled = false;
// Set when the phone scan was accepted and WhatsApp is syncing. While set
// (up to LINKING_GRACE_MS) the bridge must never restart: interrupting the
// first sync makes WhatsApp log the new device out.
const LINKING_GRACE_MS = 10 * 60_000;
let linkingSince = 0;
function markLinking(reason) {
  if (state === 'ready') return;
  if (!linkingSince) console.log('SPAD personal bridge: linking in progress (' + reason + ')');
  linkingSince = linkingSince || Date.now();
  state = 'authenticating';
  qrDataUri = null;
  qrCreatedAt = 0;
}

const authStrategy = new LocalAuth({clientId: 'spad-self', dataPath: AUTH_DIR});
const executablePath = process.env.SPAD_WA_CHROME || undefined;
const client = new Client({
  authStrategy,
  puppeteer: {
    headless: true,
    ...(executablePath ? {executablePath} : {}),
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  },
});

client.on('qr', async (raw) => {
  try {
    qrDataUri = await QRCode.toDataURL(raw, {errorCorrectionLevel: 'M', margin: 2, width: 360});
    qrCreatedAt = Date.now();
    linkingSince = 0; // a new code means no scan is being completed
    state = 'pairing';
    lastError = null;
  } catch (err) {
    qrDataUri = null;
    state = 'error';
    lastError = 'QR rendering failed';
  }
});
client.on('authenticated', () => {
  linkingSince = linkingSince || Date.now();
  state = 'authenticating';
  qrDataUri = null;
  qrCreatedAt = 0;
});
client.on('ready', () => {
  const wid = client.info && client.info.wid;
  // No destination phone is accepted from Laravel: derive it from own session.
  linkedSelfId = wid && typeof wid._serialized === 'string'
    ? wid._serialized
    : (wid && wid.user ? String(wid.user) + '@c.us' : null);
  state = linkedSelfId ? 'ready' : 'error';
  qrDataUri = null;
  qrCreatedAt = 0;
  lastError = linkedSelfId ? null : 'Self chat address unavailable';
});
client.on('auth_failure', () => {
  state = 'auth_failed'; qrDataUri = null; linkedSelfId = null; lastError = 'Authentication failed';
  linkingSince = 0;
  restartSoon('authentication failed; starting a clean session for a new QR');
});
client.on('disconnected', () => {
  state = 'disconnected'; qrDataUri = null; linkedSelfId = null; lastError = 'Disconnected';
  linkingSince = 0;
  restartSoon('logged out; starting a clean session for a new QR');
});

// Pairing diagnostics for the PM2 log: what WhatsApp Web itself shows while
// linking (e.g. a passkey/security step or a link error). Never logs the QR
// payload, phone numbers or chat content; long digit runs are masked.
let qrCount = 0;
let lastPageText = '';
const note = msg => console.log('SPAD personal bridge: ' + msg);
client.on('qr', () => { qrCount++; if (qrCount === 1 || qrCount % 5 === 0) note('pairing code #' + qrCount); });
client.on('loading_screen', (pct, msg) => { note('loading ' + pct + '% ' + String(msg || '').slice(0, 60)); markLinking('loading screen'); });
client.on('change_state', s => note('wa state ' + String(s).slice(0, 40)));
client.on('authenticated', () => note('phone scan accepted (authenticated)'));
client.on('auth_failure', m => note('auth_failure ' + String(m || '').slice(0, 120)));
client.on('disconnected', r => note('disconnected ' + String(r || '').slice(0, 80)));
client.on('ready', () => note('ready (linked)'));
setInterval(async () => {
  if (state === 'ready' || !client.pupPage) return;
  try {
    const text = await client.pupPage.evaluate(() =>
      ((document.body && document.body.innerText) || '').replace(/\s+/g, ' ').slice(0, 400));
    const clean = text.replace(/\d{5,}/g, '<n>');
    if (clean && clean !== lastPageText) { lastPageText = clean; note('page: ' + clean); }
    if (/Loading your chats|messages are downloading|Logging in/i.test(clean)) markLinking('page shows sync');
  } catch (_) {}
}, 4000).unref();

function qrFresh() {
  return !!qrDataUri && Date.now() - qrCreatedAt <= QR_MAX_AGE_MS;
}
function linkingActive() {
  return !!linkingSince && Date.now() - linkingSince < LINKING_GRACE_MS;
}
function restartSoon(reason) {
  if (restartScheduled) return;
  restartScheduled = true;
  console.log('SPAD personal bridge: ' + reason);
  setTimeout(async () => {
    try { server.close(); } catch (_) {}
    try { await Promise.race([client.destroy(), new Promise(r => setTimeout(r, 8000))]); } catch (_) {}
    process.exit(0); // PM2 autorestart starts a new browser session.
  }, 3000);
}
function requestFreshPairing() {
  if (restartScheduled) return true;
  // Never interrupt a linked session, a scan being completed (first sync),
  // or a session that just started.
  if (state === 'ready' || linkingActive()) return false;
  if (Date.now() - startedAt < RESTART_MIN_UPTIME_MS) return false;
  restartScheduled = true;
  console.log('SPAD personal bridge: pairing code expired; restarting for a fresh QR');
  setTimeout(async () => {
    try { server.close(); } catch (_) {}
    try { await Promise.race([client.destroy(), new Promise(r => setTimeout(r, 8000))]); } catch (_) {}
    process.exit(0); // PM2 autorestart starts a new browser session.
  }, 250);
  return true;
}
function authorized(req) {
  const submitted = req.headers['x-spad-self-key'];
  if (typeof submitted !== 'string' || submitted.length > 256) return false;
  const hash = crypto.createHash('sha256').update(submitted).digest();
  return crypto.timingSafeEqual(secretHash, hash);
}
function reply(res, status, data) {
  const bytes = Buffer.from(JSON.stringify(data));
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store, no-cache, must-revalidate',
    'X-Content-Type-Options': 'nosniff',
    'Content-Length': bytes.length,
  });
  res.end(bytes);
}
function maskedPhone() {
  if (!linkedSelfId) return null;
  const m = linkedSelfId.match(/^(\d+)/);
  return m ? ('••••' + m[1].slice(-4)) : 'متصل';
}
async function bodyJson(req) {
  if (!String(req.headers['content-type'] || '').startsWith('application/json')) {
    throw new Error('JSON content type required');
  }
  let text = '';
  for await (const chunk of req) {
    text += chunk.toString('utf8');
    if (text.length > 4_300_000) throw new Error('Request too large');
  }
  return JSON.parse(text);
}
async function sendSelf(body) {
  if (state !== 'ready' || !linkedSelfId || !client.info || !client.info.wid) {
    throw new Error('WhatsApp personal account is not connected');
  }
  if (inFlight || Date.now() - lastSendAt < 45_000) {
    throw new Error('Please wait before sending another poster');
  }
  if (!body || typeof body.image_base64 !== 'string' ||
      !/^[A-Za-z0-9+/=\r\n]+$/.test(body.image_base64) ||
      body.image_base64.length > 4_000_000 ||
      typeof body.caption !== 'string' || body.caption.length > 1024) {
    throw new Error('Invalid PNG payload');
  }
  const png = Buffer.from(body.image_base64, 'base64');
  if (png.length < 40_000 || png.length > 3_000_000 ||
      !png.subarray(0, 8).equals(Buffer.from('89504e470d0a1a0a', 'hex'))) {
    throw new Error('Only SPAD-rendered PNG images are accepted');
  }
  inFlight = true;
  try {
    const media = new MessageMedia('image/png', png.toString('base64'), 'spad-market.png');
    const message = await client.sendMessage(linkedSelfId, media, {caption: body.caption});
    if (!message || !message.id) throw new Error('WhatsApp did not confirm the self-chat send');
    lastSendAt = Date.now();
    return {
      accepted: true,
      // Id only, never expose contact list or WhatsApp chat content.
      message_id: String(message.id._serialized || message.id.id || '').slice(0, 140),
    };
  } finally {
    inFlight = false;
  }
}

const server = http.createServer(async (req, res) => {
  try {
    if (!authorized(req)) return reply(res, 401, {error: 'unauthorized'});
    const pathname = new URL(req.url, 'http://127.0.0.1').pathname;
    if (req.method === 'GET' && pathname === '/status') {
      return reply(res, 200, {
        state,
        ready: state === 'ready',
        linked_number_masked: maskedPhone(),
        pairing_available: state === 'pairing' && qrFresh(),
        // Avoid echoing raw browser/WhatsApp errors.
        error: lastError,
      });
    }
    if (req.method === 'GET' && pathname === '/qr') {
      if (state === 'ready') return reply(res, 409, {error: 'already_connected'});
      if (state === 'authenticating' || linkingActive()) {
        return reply(res, 202, {state, image: null, message: 'authenticating'});
      }
      if (!qrFresh()) {
        const restarting = requestFreshPairing();
        return reply(res, 202, {state: restarting ? 'restarting' : state, image: null, message: 'waiting_for_new_qr'});
      }
      return reply(res, 200, {state: 'pairing', image: qrDataUri,
        expires_in: Math.max(0, Math.floor((QR_MAX_AGE_MS - (Date.now() - qrCreatedAt)) / 1000))});
    }
    if (req.method === 'POST' && pathname === '/send-self') {
      const body = await bodyJson(req);
      const result = await sendSelf(body);
      return reply(res, 200, result);
    }
    return reply(res, 404, {error: 'not_found'});
  } catch (err) {
    const message = String(err && err.message || '');
    const code = message.includes('wait') ? 429 : (message.includes('connected') ? 409 : 422);
    return reply(res, code, {error: message.slice(0, 150)});
  }
});

// A previous bridge's Chrome can outlive its Node process for a few seconds
// (PM2 restart) and keep the profile locked, so the new browser fails to
// start. Stop only Chrome processes using THIS bridge's private profile.
function stopLeftoverBrowsers() {
  const marker = Buffer.from(path.resolve(AUTH_DIR));
  let stopped = 0;
  for (const pid of fs.readdirSync('/proc')) {
    if (!/^\d+$/.test(pid) || Number(pid) === process.pid) continue;
    try {
      const cmd = fs.readFileSync('/proc/' + pid + '/cmdline');
      if (cmd.includes(marker) && cmd.includes(Buffer.from('chrom'))) {
        process.kill(Number(pid), 'SIGKILL'); stopped++;
      }
    } catch (_) {}
  }
  if (stopped) console.log('SPAD personal bridge: stopped ' + stopped + ' leftover browser process(es)');
  return stopped;
}

server.listen(PORT, HOST, () => {
  console.log('SPAD personal WhatsApp loopback bridge listening; QR/manual approval required');
  const waitMs = stopLeftoverBrowsers() ? 1500 : 0;
  setTimeout(() => {
    client.initialize().catch(() => {
      state = 'error';
      lastError = 'WhatsApp browser failed to initialize';
      // Retry in a fresh process instead of staying broken.
      setTimeout(() => restartSoon('browser failed to start; retrying'), 10_000);
    });
  }, waitMs);
});
process.on('SIGTERM', async () => {
  server.close();
  try { await client.destroy(); } catch (_) {}
  process.exit(0);
});
