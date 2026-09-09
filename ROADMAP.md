# Trade Studio — roadmap

Working name. Rename when David picks one. Wastes and Dead Harvest stay parked.

**Pitch:** websites and simple job tools for small local construction and home-service shops that have a bad site, a Facebook page, or nothing. Fast. Cheap. Looks like *their* company, not a template farm.

**Who:** painters, HVAC, roofers, plumbers, landscapers, drywall — owner-operators and small crews.

**Proof we already have:** Brightline Painting (real Greenville painter site + field ops in `repos/tradeops-template` and `repos/ASPERPLAN`). Use as a case, not as this studio’s brand.

## Split

| Lane | Owner |
|---|---|
| Landing look, copy, static page, art | Grok |
| Lead form (mail/SMS), hosting, later PM tool | Claude |

## Phase 0 — lock (this week)

- [ ] David names the studio (page currently says **Trade Studio**).
- [ ] David sets a price or a range. Page does **not** invent a dollar amount.
- [ ] David sets the inbound number / email for leads.
- [ ] One target trade for the first template (recommendation: **painting**, because Brightline exists).

## Phase 1 — this landing (now)

- [x] Single static page: problem, offer, how it works, optional jobs tool, contact.
- [x] Claude: `lead.php` + `site.js` — disk-first `leads.jsonl`, honeypot, validation. Delivery waits on David’s inbox + SMS.
- [ ] David: inbound email (and SMS number/carrier or Twilio). Host later.
- [ ] Host it (Cloudflare Pages / Netlify is enough).
- [ ] Point a domain when David has one.

## Phase 2 — first customer site (days, not months)

A real site for one shop (Brightline or a second painter):

- Service-area pages (Greenville, Spartanburg, …)
- Quote form
- Before/after or job photos
- Click-to-call, hours, license/insured
- Mobile-first, fast

Deliverable: they can send the URL to a homeowner today.

## Phase 3 — the “jobs” add-on (only if they ask)

Not a second Procore. A phone-simple board:

- Jobs / lots
- Crew assigned
- Photos from the field
- Status: scheduled → in progress → done

Start from what we already know in tradeops — strip it down, don’t clone Brightline’s whole back office.

## Phase 4 — a second trade

Copy the painter template to HVAC or roofing. Same bones, their colors, their truck, their nouns (not “lots” if they don’t say lots).

## Out of scope until someone pays

Multi-company SaaS marketplace, custom native apps, Google Ads management as a product, inventory, payroll.

## How we sell it

1. Twenty-minute call.
2. They send colors, logo if any, service towns, 8–20 photos.
3. Draft in a few days.
4. Live. Fixed fee. Hosting optional and cheap.
5. Jobs board later, same relationship.

## Run the landing locally

```
cd C:\Users\monke\Documents\TradeStudio
# any static server; see README
```
