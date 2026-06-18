# RIMS Pro — Resale Inventory Management System

Enterprise-grade real estate resale inventory platform delivered as a **native WordPress plugin**. Premium 2026 PropTech experience: glassmorphism cards, four view modes (Card / Table / Broker Sheet / Map), advanced + natural-language search, Kanban lead pipeline, branded PDF + Excel exports, AI content, PWA + push, CRM integrations, multi-tenant SaaS-ready.

## What's in this repository

```
.kiro/specs/resale-inventory-management/   # full spec (requirements, design, tasks)
rims-pro/                                  # the WordPress plugin source
rims-pro.zip                               # built, installable plugin (top-level rims-pro/ folder)
build.sh                                   # rebuild rims-pro.zip from source
INSTALLATION.md                            # non-technical install guide
```

## Quick install (non-technical)

See **[INSTALLATION.md](INSTALLATION.md)** — upload `rims-pro.zip` in WordPress admin, activate, configure tenant + branding in *RIMS Pro → Settings*, drop the shortcodes / Elementor widgets, optionally add Google Maps / VAPID / CRM keys.

## Quick rebuild (developers)

```bash
cd rims-pro && composer install
cd .. && bash build.sh        # writes rims-pro.zip
```

## Tests

```bash
cd rims-pro
composer install
vendor/bin/phpunit --testdox
```

48 correctness properties from `design.md`, each verified against ≥100 generated inputs.

## Spec

The complete spec lives in [`.kiro/specs/resale-inventory-management/`](.kiro/specs/resale-inventory-management/):

- **`requirements.md`** — 33 EARS-style acceptance criteria
- **`design.md`** — full technical design + 48 named correctness properties
- **`tasks.md`** — 126 implementation tasks across 17 dependency waves

## License

GPL-2.0-or-later
