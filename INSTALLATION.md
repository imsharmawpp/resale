# RIMS Pro — Installation Guide

A step-by-step guide for installing **RIMS Pro (Resale Inventory Management System)** on a WordPress site such as `goldlineestate.com`. Written for a non-technical site owner.

---

## What you receive

A single ZIP file: **`rims-pro.zip`** (built from this repository — see the **“Building from source”** section if you ever need to rebuild it).

When unpacked into WordPress, the ZIP creates one folder named `rims-pro/` inside `wp-content/plugins/`.

## System requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 6.4 |
| PHP | 8.3 (8.3 or 8.4 recommended) |
| MySQL / MariaDB | MySQL 8.0+ or MariaDB 10.6+ |
| HTTPS | **Required** for the public site (REST endpoints expect TLS) |
| Server memory | 256 MB PHP memory_limit recommended |

> If your hosting panel does not yet show PHP 8.3+, ask your host to upgrade before continuing. RIMS Pro will fail to activate on PHP 8.2 or earlier.

---

## 1. Upload and activate the plugin

1. Sign in to WordPress at `https://goldlineestate.com/wp-admin/`.
2. In the left sidebar, click **Plugins → Add New → Upload Plugin**.
3. Click **Choose File**, select `rims-pro.zip`, then **Install Now**.
4. When WordPress confirms “Plugin installed successfully,” click **Activate Plugin**.

On activation RIMS Pro will:

- Create the database tables (all prefixed `wp_rims_…`) — your existing data is **not** touched.
- Seed default tenant settings (color map, expiry days, rate limit).
- Schedule the daily auto-expiry cron job.
- Register pretty URLs (`/inventory`, `/inventory/{slug}`, `/project/{slug}`, `/broker-sheet`).

If you ever see a yellow admin notice that says *“active theme does not declare header/footer support”*, your theme is non-standard; switch to any header-aware theme (Hello Elementor, Astra, GeneratePress) and the notice will disappear.

---

## 2. Configure tenant + brand settings

1. In WordPress admin, open **RIMS Pro → Settings**.
2. Set:
   - **Company Name** — e.g. *GoldLine Estate*
   - **Logo URL** — uploaded via Media Library
   - **Primary Color** — your brand color (drives buttons, hero, broker-sheet header)
   - **Contact Phone** — used for the *Call Now* button (E.164 form, e.g. `+919876543210`)
   - **WhatsApp Number** — used for the *WhatsApp* deep links and prefilled message
   - **Inventory Expiry (days)** — listings older than this are auto-archived (default 90)
   - **Rate Limit** — requests/window per visitor (default 60/60s)
   - **Infinite Scroll** — toggles infinite scroll vs. paginated listings
3. Click **Save Settings**.

Each tenant’s branding is independent (Property 48). Changing one tenant’s settings does not affect any other tenant in a multi-site SaaS deployment.

---

## 3. Add inventory and projects

1. **RIMS Pro → Inventory**. Use the *Add Unit* form to create units (Project, Unit Number, Tower, Floor, Facing, BHK, Area, Price, Owner Name, Owner Phone, Broker Notes).
2. The status defaults to **Available**. Use the per-row dropdown to change status; the change is timestamped.
3. The **Owner Name / Phone / Broker Notes** are *internal-only* — never shown to website visitors, never embedded in PDF/Excel exports for users without the inventory-management capability (Property 9).
4. To create projects, you can either insert rows directly via the WP-CLI / DB, or extend the admin via the REST endpoint `POST /wp-json/rims/v1/projects` with an API token.

---

## 4. Drop the listing onto a page

You have three equivalent options. Pick whichever is easiest.

### A. Pretty URLs (recommended)
RIMS Pro automatically creates these pages:
- `/inventory` — full listing with hero, stats, filters, four view modes
- `/broker-sheet` — print-optimized broker sheet with “PREMIUM RESALE INVENTORY” header
- `/inventory/{slug}` — unit detail page
- `/project/{slug}` — project detail page (chart, units, nearby places)

If links return 404, go to **Settings → Permalinks** and click **Save Changes** once. WordPress will rebuild rewrite rules.

### B. Shortcodes
Paste any of these into a page or post:
```
[resale_inventory project="" builder="" bhk="" status=""]
[featured_inventory]
[inventory_sheet]
[inventory_search]
```

Filter attributes are pre-applied — e.g. `[resale_inventory builder="M3M" bhk="3"]` shows only 3 BHK by M3M.

### C. Elementor widgets
If Elementor is active, RIMS Pro registers four widgets in the General category:
- **RIMS Inventory Grid** — drop on any page; supports project/builder/bhk/status controls.
- **RIMS Inventory Search** — natural-language smart search bar.
- **RIMS Featured Inventory** — auto-rotating featured carousel.
- **RIMS Lead Form** — three-step lead capture form.

Drag the widget into any Elementor section and edit options in the right panel.

---

## 5. External keys (optional but recommended)

RIMS Pro works without any third-party keys. To enable premium features, add the following to `wp-config.php` (above the `/* That's all */` line):

```php
// Encryption key for CRM credentials (32+ characters; back this up).
define( 'RIMS_PRO_CRYPTO_KEY', 'a-very-long-random-string-here' );

// Google Maps (Map View).
define( 'RIMS_PRO_GMAPS_KEY', 'AIzaSy...' );

// Web Push (PWA notifications).
define( 'RIMS_PRO_VAPID_PUBLIC',  'B...' );
define( 'RIMS_PRO_VAPID_PRIVATE', 'A...' );
```

Then in `Settings → CRM` (or via WP-CLI / the `/rims/v1/crm` REST endpoint), add credentials for any of: **Sell.Do, LeadSquared, HubSpot, Zoho**. Credentials are encrypted at rest (Property 42); a forward only fires when the platform is enabled **and** a retry policy is configured (Property 41).

---

## 6. PWA + push (optional)

RIMS Pro publishes a manifest at `/rims-manifest.webmanifest` and a service worker at `/rims-sw.js`. Visitors can install the site to their home screen. With VAPID keys configured, web push fires when a newly published unit matches a saved visitor alert (Property 40).

---

## 7. Test on a staging page

1. Create a draft page **/inventory-staging**, drop the `[resale_inventory]` shortcode, set status to *Draft → Preview*.
2. Submit a fake lead to confirm:
   - Lead lands in **RIMS Pro → Leads** column **New**.
   - Drag-and-drop persists stage changes.
3. Click **WhatsApp** / **Call Now** on a card and confirm the deep link uses your configured number.
4. Switch **Card → Table → Broker Sheet → Map**. The four views share state (Property 13) and persist your selection in-session (Property 12).
5. Try the smart search: *“3 BHK in Whitefield under 2 Cr”* — it should parse and filter conjunctively (Property 5).

When everything looks right, publish the page and replace the draft with the production page.

---

## 8. Building from source (developers only)

If you ever need to rebuild the zip from this repository:

```bash
# Inside the repo:
cd rims-pro && composer install --no-dev --optimize-autoloader
cd .. && bash build.sh
```

This produces `rims-pro.zip` at the repo root.

To run the test suite:

```bash
cd rims-pro
composer install
vendor/bin/phpunit --testdox
```

The full property suite verifies all 48 correctness properties from `design.md` over 100+ generated inputs each (≈24,000 assertions in under a second).

---

## Support / questions

- The plugin will write a diagnostic notice to the WP admin if anything is mis-configured (theme support, permalinks, missing keys).
- For everyday questions email your developer with a screenshot — most issues are fixed in **Settings**.
