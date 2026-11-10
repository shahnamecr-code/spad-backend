# SPAD WhatsApp preview CLI worker

This optional persistent worker is meant for hosts where `crontab` exists but Cron is not reliably running, and where PHP-FPM cannot launch the Chromium poster renderer.

- Code: `services/wa-preview-refresh/worker.cjs`
- Uses existing `php artisan spad:wa-preview-warm` to render **real price data** into private Laravel cache.
- Refreshes FX/gold every five minutes, cities every thirty minutes, and serves single-city requests from a hashed private file queue.
- Does not accept HTTP requests, send WhatsApp messages, read chats, expose secrets, or modify market data.
- PM2 process name: `spad-wa-preview-refresh`.
- Heartbeat file (private): `storage/app/whatsapp-market/admin-preview/worker-heartbeat.json`.
- The Super Admin preview endpoint never launches Chromium in the web PHP process. If cache is absent or expired, it returns an HTTP 202 state while the worker regenerates the image. The UI polls for a short bounded period.
- If the real-price feed is missing/stale, the worker will **not** fabricate prices or serve stale banners as if current. The UI can report that fresh preview was not completed.
- The existing Laravel schedule remains as a fallback when hosting Cron becomes available.

Deploy through the guarded SPAD workflow and keep the worker script private. Use `pm2 status spad-wa-preview-refresh` for health; restart only this process if required.
