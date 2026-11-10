# SPAD personal WhatsApp self-chat companion (opt in)

This integration is **separate** from the official Meta Groups API in the SPAD admin.

## Behavior and limits

- The owner links **their own WhatsApp** using a QR code in the Super Admin panel.
- The connector uses the **unofficial WhatsApp Web client** `whatsapp-web.js`. This is **not** Meta Cloud API, can stop working without notice, and can lead to account restrictions. Nothing in the source makes it official or risk-free.
- It sends an image **only to the session's own self-chat**, never to contacts, groups or channels. The server never accepts an arbitrary destination number.
- It does **not** read conversations or contacts and does not subscribe to message events. However, by its nature a Linked Device session has significant WhatsApp account privileges and must be protected.
- It is **disabled by default**. After linking, an administrator separately enables scheduled self-delivery. Manual test requires an additional confirmation.
- Reuses the existing SPAD approved image templates and existing Iran Market/Nemuda prices and published scheduling configuration. A city delivery sends **the first poster page (12 neighborhoods)**, not all possible pages.
- Delivery caps: **at most 12 enqueued jobs/day**, at most one scheduled send per scheduler tick, and a minimum 45 seconds between outbound media at the node bridge. Jobs are idempotent and never auto-retried when acknowledgement is unknown.
- "accepted_local" confirms the linked client accepted the send operation, not final receipt on mobile.

## Installation

Use the dedicated protected, guarded live deploy workflow; it installs dependencies and a unique local 64+ character API key on the server **outside the repository**, and starts a PM2 process binding only `127.0.0.1:39317`. The browser LocalAuth session is stored in a permission-restricted non-public folder under `/home/spadesta/spad-staging/spad-wa-personal/auth`.

The Laravel API reaches the bridge over loopback and signs every request with the locally generated shared key. Never print or commit the key or authentication session.

The administrator opens `/spad-admin/whatsapp-market`, requests the pairing QR, and on the phone navigates to **WhatsApp → Linked Devices → Link a device** to scan it. This cannot be completed remotely by GitHub Actions or ChatGPT and **must be done by the account owner**.

The old Meta connector is unchanged and remains independent. Never use this service to send to arbitrary accounts.

To revoke the session: on the phone open **WhatsApp → Linked Devices** and unlink the SPAD device. Disable scheduled personal delivery in the SPAD admin before revoking.

## Privacy and security

- Access to QR, status and send actions is guarded by existing `auth:sanctum,admin` plus the `Super Admin` role.
- Bridge listens only on localhost and authenticates using a constant-time key comparison.
- No public inbound webhooks or external APIs beyond WhatsApp Web. Do not expose PM2 port 39317 through nginx or a firewall.
- Do not store personal phone number, QR code, browser cookies or authentication folder in GitHub, logs, publicly mounted directories or CDN.
- Avoid sending unrelated private messages or customer personal data.
- Reset browser pairing sessions on device loss or suspected compromise.

## Operational notes

`php artisan spad:wa-personal-dispatch` is called from the existing Laravel scheduler. Enabled state is tracked in `spad_wa_personal_config` and all attempts logged in `spad_wa_personal_jobs`.

If WhatsApp Web changes or QR pairing is unavailable, keep personal delivery disabled and use the official business sender-to-personal-number workflow instead.
