# Projectplan — WordPress thema-marktplaats met live preview

> Doel: een site waar je WordPress-thema's uploadt, waarna automatisch screenshots worden gemaakt én een live preview beschikbaar is (preview.tomtiedemann.com). Verkoop via Mollie.

---

## 0. De kernbeslissing (lees dit eerst)

Het zwaarste, engste deel van dit project — "hoe toon ik een werkende WordPress zonder een WP-server per thema te draaien" — los je op met **WordPress Playground** (WordPress in WebAssembly, draait in de browser van de bezoeker). Dat betekent:

- **Preview** = statische pagina die Playground laadt met jouw thema-zip. Geen container per thema, schaalt oneindig, en de (onveilige) PHP van het thema draait in de sandbox van de bezoeker, niet op jouw server.
- **Screenshots** = dezelfde Playground, maar headless aangestuurd met Playwright.

Eén tool dekt dus je twee moeilijkste onderdelen. De rest van het project (upload, gallery, betalen) is "gewoon" webwerk dat je al kunt.

**Gouden regel voor de volgorde:** valideer eerst de riskantste aanname (zie Stap 0 hieronder) vóór je infra bouwt. Daarna bouw je één dunne verticale slice die end-to-end werkt, en pas daarna maak je het mooi en compleet.

---

## Techstack (samenvatting)

| Laag | Keuze | Waarom |
|---|---|---|
| Storefront | Next.js (App Router), Docker op Proxmox achter NPM | je kent het al |
| Database | MySQL | je kent het al van je ❤️U-festival app; tabellen themes/screenshots/orders |
| Opslag | MinIO (S3-compatible) | publieke URLs die Playground kan fetchen |
| Preview | Statische Playground-loader op preview.tomtiedemann.com | client-side, gratis, veilig |
| Screenshots | Node-worker: `@wp-playground/cli` + Playwright | Playground-tooling is Node-native |
| Queue | Begin synchroon → later Redis + BullMQ | niet over-engineeren |
| Betalen | Mollie (iDEAL) | al uitgezocht, past bij KOR |
| Monitoring | Uptime Kuma / Beszel | heb je al draaien |

---

## Fases

### Stap 0 — Aanname valideren (1 middag, 0 code)

Voordat je iets bouwt: test of Playground jouw thema's acceptabel rendert.

1. Pak één van je eigen thema's, zip 'm, en host de zip ergens publiek tijdelijk (kan zelfs een GitHub release zijn).
2. Maak een `blueprint.json`:
   ```json
   {
     "$schema": "https://playground.wordpress.net/blueprint-schema.json",
     "steps": [
       { "step": "installTheme", "themeData": { "resource": "url", "url": "https://JOUW-URL/thema.zip" }, "options": { "activate": true } }
     ]
   }
   ```
3. Open `https://playground.wordpress.net/?blueprint-url=https://JOUW-URL/blueprint.json`
4. **Kijk kritisch:** ziet het er leeg/kaal uit? → je hebt demo-content nodig (zie Stap 3). Crasht het? → noteer welke thema-types problemen geven.

**Resultaat:** je weet of het hele concept werkt vóór je een regel productiecode schrijft.

---

### Stap 1 — Fundament (1 weekend)

**Repo-structuur** (monorepo, simpel):
```
/web        → Next.js (storefront + admin)
/worker     → Node screenshot-worker
/infra      → docker-compose, blueprints-template, demo-content.xml
```

**MySQL-schema** (start minimaal):
```sql
CREATE TABLE themes (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(191) UNIQUE NOT NULL,
  name         VARCHAR(255) NOT NULL,
  description  TEXT,
  zip_url      TEXT NOT NULL,                              -- MinIO URL
  price_cents  INT NOT NULL DEFAULT 0,
  status       VARCHAR(20) NOT NULL DEFAULT 'processing',  -- processing|live|failed
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE screenshots (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  theme_id  BIGINT UNSIGNED NOT NULL,
  url       TEXT NOT NULL,
  viewport  VARCHAR(20) NOT NULL,   -- desktop|tablet|mobile
  page      VARCHAR(20) NOT NULL,   -- home|blog|single
  sort      INT DEFAULT 0,
  FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  theme_id       BIGINT UNSIGNED,
  email          VARCHAR(255) NOT NULL,
  mollie_id      VARCHAR(64),
  status         VARCHAR(20) NOT NULL DEFAULT 'open',  -- open|paid|failed
  download_token VARCHAR(64),
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (theme_id) REFERENCES themes(id)
) ENGINE=InnoDB;
```

> **MySQL-aandachtspunten:**
> - **Auto-increment i.p.v. UUID** — MySQL kan `UUID()` niet als kolom-default gebruiken. Geen probleem: je publieke URLs draaien op de `slug`, niet op het id. Wil je toch UUID's, genereer ze in Next.js en sla op als `CHAR(36)`.
> - **`TIMESTAMP` i.p.v. `TIMESTAMPTZ`** — sla tijden in UTC op, reken om in de app.
> - **`VARCHAR(191)` voor `slug`** — veilige indexlengte met utf8mb4. Praat met MySQL via `mysql2` of Prisma.
```

**MinIO** opzetten als Docker-container (buckets: `themes`, `screenshots`), met een publieke read-policy op die buckets zodat Playground en de browser de bestanden kunnen ophalen.

**Next.js skeleton** met Docker + NPM achter een subdomein (bv. `themes.tomtiedemann.com`), net als je festival-app.

---

### Stap 2 — Upload + opslag (de saaie maar cruciale laag)

In de admin (achter auth — Vaultwarden/eigen login, niet publiek!):

1. Form: thema-naam, prijs, `.zip` upload.
2. Backend: upload zip naar MinIO bucket `themes`, lees de `style.css`-header uit voor naam/versie (optioneel maar netjes), schrijf rij in `themes` met `status = 'processing'`.
3. Trigger de screenshot-job (in Stap 4; voor nu mag dit een TODO zijn).

**Validatie:** check dat het een geldige thema-zip is (bevat `style.css` met een `Theme Name:`-header). Weiger de rest.

---

### Stap 3 — Preview + demo-content (de leuke, makkelijke winst)

1. **Demo-content (WXR):** maak één generiek `demo-content.xml` (een paar posts, een pagina, een menu). Dit bepaalt 80% van hoe "af" alles oogt. Maak 'm één keer in een lokale WP, exporteer via Tools → Export.
2. **Blueprint per thema** genereer je dynamisch (een Next.js API-route die JSON teruggeeft):
   ```json
   {
     "steps": [
       { "step": "installTheme", "themeData": { "resource": "url", "url": "<zip_url>" }, "options": { "activate": true } },
       { "step": "importWxr", "file": { "resource": "url", "url": "https://JOUW-CDN/demo-content.xml" } },
       { "step": "setSiteOptions", "options": { "show_on_front": "page", "permalink_structure": "/%postname%/" } }
     ]
   }
   ```
3. **preview.tomtiedemann.com:** statische pagina die linkt naar/embed:
   `https://playground.wordpress.net/?blueprint-url=https://themes.tomtiedemann.com/api/blueprint/<slug>`
   → "Preview"-knop op je detailpagina wijst hierheen.

**v1:** gebruik de gehoste `playground.wordpress.net`. **Later (Stap 7):** self-host de Playground-bundle op je eigen subdomein voor controle en privacy.

---

### Stap 4 — Screenshots automatiseren

**Node-worker** die per thema:

1. Een Playground-instantie start: `npx @wp-playground/cli server --blueprint=./blueprint.json` (CLI heeft `server`, `run-blueprint`, `build-snapshot`).
2. Met **Playwright** naar de lokale Playground-URL navigeert.
3. Screenshots maakt in 3 viewports (1440px / 768px / 375px) van 2-3 pagina's (home, blog, single).
4. Uploadt naar MinIO bucket `screenshots`, schrijft rijen in `screenshots`, zet `themes.status = 'live'`.

**Queue:** begin **synchroon** (job direct na upload, admin wacht even). Pas als het traag/druk wordt: Redis + BullMQ ertussen. Niet eerder.

> Je kent dit patroon al van je faster-whisper subtitle-API (FastAPI + job queue + worker). Mag in Python met `playwright-python`, maar de Playground-CLI is Node — dus óf de hele worker in Node, óf FastAPI die een Node-script als subprocess aanroept. Voor v1: één Node-worker, scheelt een taalbrug.

---

### Stap 5 — Gallery / storefront

- **Overzicht:** grid van thema's met hun hero-screenshot.
- **Detailpagina:** alle screenshots (carousel), beschrijving, prijs, "Live preview"-knop (→ Stap 3), "Kopen"-knop (→ Stap 6).
- Filter `status = 'live'` zodat nog-verwerkende thema's verborgen blijven.

Hier kun je de `frontend-design`-aanpak loslaten zodat het er niet generiek uitziet.

---

### Stap 6 — Betalen (Mollie)

1. "Kopen" → maak `orders`-rij (`status = 'open'`) → maak Mollie-payment → redirect naar Mollie.
2. **Webhook** (`/api/mollie/webhook`): bij `paid` → genereer `download_token`, zet order op `paid`.
3. Bedankpagina/mail met download-link die de zip uit MinIO serveert (signed URL of via je backend, met token-check).
4. **Boekhouding:** denk aan KOR/factuur. Bewaar minimaal e-mail, bedrag, datum, factuurnummer. (Geen financieel/juridisch advies — check de exacte KvK/KOR-regels even los.)

---

### Stap 7 — Polish & hardening

- **Self-host Playground-bundle** op preview.tomtiedemann.com (geen afhankelijkheid van playground.wordpress.net, en je kunt WP/PHP-versies pinnen).
- **Security:** upload alleen achter admin-auth; zip-grootte limiteren; bedenk of je ooit thema's van derden accepteert (dan pas écht oppassen). Onthoud: dankzij Playground draait thema-PHP nooit op je eigen server.
- **Monitoring:** Uptime Kuma op de 3 subdomeinen, Beszel op de worker-host.
- **Caching:** screenshots zijn statisch → lange cache-headers + via Cloudflare.

---

## Waar begin je deze week?

1. **Vandaag/morgen (Stap 0):** test één eigen thema in playground.wordpress.net met een handmatige blueprint. Dit is de hele go/no-go van het project en kost je een middag.
2. **Dit weekend (Stap 1 + dunne slice van 3):** Postgres-schema + MinIO + Next.js skeleton, en hardcode één thema met een werkende preview-knop. Eén thema dat van upload-URL → live preview gaat. Nog geen screenshots, nog geen betalen.
3. **Daarna:** Stap 4 (screenshots), dan 5 (gallery), dan 6 (Mollie). Elke stap is op zichzelf af en demonstreerbaar.

De truc: bouw de **dunne verticale slice** (één thema, end-to-end zichtbaar) vóór je de breedte invult. Dan heb je vroeg iets werkends en zie je problemen op tijd.

---

## Valkuilen / aandachtspunten

- **Leeg thema = lelijke screenshots.** Demo-content (WXR) is geen "nice to have", doe het in Stap 3.
- **Niet alle thema's renderen perfect in Playground.** Classic themes met zware plugin-afhankelijkheden (bv. WooCommerce-demo's) kunnen haperen. Noteer in Stap 0 welke types werken; val pas terug op echte WP-containers (met Traefik dynamic routing) als het écht moet.
- **Over-engineer de queue niet.** Synchroon starten is prima tot je het tegendeel meet.
- **Admin nooit publiek.** Upload achter auth.
