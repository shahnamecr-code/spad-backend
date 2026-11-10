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
    state = 'pairing';
    lastError = null;
  } catch (err) {
    qrDataUri = null;
    state = 'error';
    lastError = 'QR rendering failed';
  }
});
client.on('authenticated', () => {
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
});
client.on('disconnected', () => {
  state = 'disconnected'; qrDataUri = null; linkedSelfId = null; lastError = 'Disconnected';
});

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
        pairing_available: state === 'pairing' && !!qrDataUri,
        // Avoid echoing raw browser/WhatsApp errors.
        error: lastError,
      });
    }
    if (req.method === 'GET' && pathname === '/qr') {
      if (state === 'ready') return reply(res, 409, {error: 'already_connected'});
      if (!qrDataUri || Date.now() - qrCreatedAt > 75_000) {
        return reply(res, 202, {state, image: null, message: 'waiting_for_new_qr'});
      }
      return reply(res, 200, {state: 'pairing', image: qrDataUri, expires_in: Math.max(0, 75 - Math.floor((Date.now() - qrCreatedAt) / 1000))});
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

server.listen(PORT, HOST, () => {
  console.log('SPAD personal WhatsApp loopback bridge listening; QR/manual approval required');
  client.initialize().catch(() => {
    state = 'error';
    lastError = 'WhatsApp browser failed to initialize';
  });
});
process.on('SIGTERM', async () => {
  server.close();
  try { await client.destroy(); } catch (_) {}
  process.exit(0);
});
