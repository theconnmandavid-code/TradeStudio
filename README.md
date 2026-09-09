# Trade Studio

Landing page for a studio that builds **websites** and optional **job tools** for small local construction and home-service companies.

Working name. See `ROADMAP.md`.

```
cd C:\Users\monke\Documents\TradeStudio
```

Serve with anything static. Example (Node from the Dead Harvest portable install):

```
C:\Users\monke\Documents\DeadHarvest\node\npx.cmd --yes serve -l 5173
```

Then open http://localhost:5173

**But note:** a static server can't run `lead.php`, so the form will correctly
report "not connected yet." To exercise the real lead flow, use PHP instead:

```
php -S 127.0.0.1:8787
```

Then open http://127.0.0.1:8787 — PHP is already installed here (8.4).

## The lead form

`lead.php` takes the submission. Field names must stay in sync with the form in
`index.html`: `name, company, phone, email, city, trade, message`, plus the
honeypot `website`.

**Every accepted lead is appended to `leads.jsonl` before any send is attempted.**
Mail fails for boring reasons — `mail()` disabled on the host, a throttled SMS
gateway, a typo in an address — and a lead that only existed inside an email that
didn't send is lost money. Disk first, then notify. `leads.jsonl` is gitignored,
and it is the backstop if delivery ever breaks quietly.

It runs with no configuration: records the lead, skips delivery, returns success.
That's the local mode, so the form is testable before hosting is chosen.

### Going live

1. `copy config.example.php config.php` and fill it in (gitignored, no secrets in the repo).
2. `to_email` — where leads land.
3. `to_sms` — optional. Carrier email-to-SMS gateway is free and needs no account
   (e.g. `5551234567@vtext.com` for Verizon). Carriers throttle and sometimes drop
   these, so treat the text as a nudge and the email as the record.
4. `from_email` — use an address at the site's own domain once there is one. A
   `From:` that doesn't match the sending domain is the usual reason mail lands in spam.
5. Host must run PHP. Any cheap shared host does; so does a small VPS. If we end up
   on a static host (Netlify/Cloudflare Pages) instead, `lead.php` gets swapped for
   that platform's form handling or a function — only the `fetch` URL in `site.js`
   changes.

### Protections

Honeypot (`website`), per-IP hourly rate limit, length caps on every field, CRLF
stripped from anything reaching a mail header (header injection), JSON-only
responses so nothing user-supplied is reflected as HTML.
