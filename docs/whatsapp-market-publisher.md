# SPAD WhatsApp Market Publisher

Status: feature branch only; **disabled by default**; do not assume this is deployed.
No credentials are committed. Configure through the Super Admin screen after merging and migrating.

## Sources
- Currency/gold/coins: existing MarketPriceService, respecting each ticker's timestamp and the existing thousand-toman coin value contract.
- Cities and neighborhoods: existing NemudaPropertyMarket place-index and children; only actual positive published price_per_m2 values (toman).
- Public poster attribution: Nemuda CC BY 4.0 (https://nemuda.com/data), plus the provider timestamp and an indicative-price disclaimer.

## Scheduling
Uses the Laravel schedule:run host cron (every minute) and Asia/Tehran time.
Gold/FX slots: configurable HH:MM, default 10:00 and 18:00. Optional hourly mode sends at HH:00 each hour (unless the exact daily slot also matches; no duplicates).
City slots: default 11:00 daily, one or more selected cities.
Job uniqueness is date + schedule + city + page. A database unique slot_key prevents duplicates across concurrent workers. Production requires an operational database, Laravel cron, and a renderer.

## Poster renderer
Public admin SVG preview works without image tooling. Real WhatsApp images require server-side Imagick capable of rendering SVG with correctly joined Persian text and a configured compatible Arabic font. If conversion fails, the job stops and reports the error; it will not send a blank or malformed image. Validate the resulting PNG in staging before enabling the scheduler.

## Delivery
Official Meta Cloud API Groups capability only: OBA eligibility, small invite-only groups, group ID and business phone number ID from Meta, bearer token. Not supported: bulk publishing into existing general WhatsApp groups or directly into WhatsApp Channels via the ordinary official Cloud API. This feature deliberately has **no** unofficial WhatsApp Web login / QR automation.
The panel displays previews and per-group results. A channel post requires a separate explicitly vetted integration with independently verified API support.
All outbound delivery is OFF until Super Admin explicitly enables it.

## Deployment prerequisites
1. Review/merge the isolated branch after any concurrent CRM/SEO/tour work lands; reconcile changes to app/Console/Kernel.php, routes/api.php, resources/admin/views/app/index.js, resources/admin/constants/menu.js.
2. Run `php artisan migrate --force`.
3. Confirm server cron runs `php artisan schedule:run` every minute.
4. Install/validate Imagick SVG rendering and Persian fonts. Verify public HTTPS URL for /storage/whatsapp-market/*.png is accessible by Meta.
5. Obtain Meta OBA + Groups API eligibility, Phone Number ID, access token, permitted group IDs; set them privately in Super Admin.
6. Preview banners, trigger a manual test only with approved groups, check delivery receipts/logs, then enable schedule.
7. Never call live site/WhatsApp publication successful without checking actual delivery.

Security: roles()->where('name', 'Super Admin'); encrypted API token; credentials not returned; no user/property personal data; source timestamps checked; jobs are idempotent.
