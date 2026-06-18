# Implementation Plan: RIMS Pro (Resale Inventory Management System)

## Overview

This plan converts the RIMS Pro design into an incremental, test-driven build for a native WordPress plugin. The build order is: **scaffold → domain → repositories/tenancy → security → cache → testable core services → controllers → theme integration & frontend → view-mode UIs & detail → shortcodes/Elementor → admin screens → integrations → final wiring**. Each layer is wired into the previous one so there is no orphaned code.

**Implementation language:** PHP 8.3+ for the testable core (services, value objects, repositories), Alpine.js + vanilla ES modules for frontend state/logic, Tailwind CSS (build-time, `rims-` prefixed) for styling. The design specifies these concretely (no pseudocode), so they are used throughout.

**Property-based testing convention (from design Testing Strategy):**
- PHP core properties use **Eris + PHPUnit**; Alpine frontend-state properties use **fast-check + Vitest**.
- Each property test runs a **minimum of 100 generated cases**.
- Each property test is tagged with a comment: `// Feature: resale-inventory-management, Property {n}: {property_text}`.
- Exactly **one test per property** (Properties 1–48). Positive and negative branches may live in the same test (e.g., Properties 18, 32, 40, 41).
- Tests run against in-memory repository fakes so property runs are fast and free of `$wpdb`/network cost.

Property-based and other test sub-tasks are marked optional with `*` and may be skipped for a faster MVP, but are strongly recommended.

## Tasks

- [x] 1. Scaffold the WordPress plugin and build/test infrastructure
  - [x] 1.1 Create plugin bootstrap and PSR-4 autoloader
    - Create `rims-pro.php` with plugin headers and the `Composer` PSR-4 autoloader bootstrap for the `includes/` namespaces (`Core`, `Domain`, `Services`, `Repositories`, `Rest`, `Ajax`, `Shortcodes`, `Elementor`, `Admin`, `Frontend`, `Security`, `Tenancy`)
    - Create `composer.json` declaring Dompdf/mPDF, PhpSpreadsheet, Intervention Image, web-push, Eris (dev) dependencies
    - Wire a single `Core\Plugin` entry class that boots on the `plugins_loaded` hook (hooks registered later in task 16.7)
    - _Requirements: 2.1_

  - [x] 1.2 Implement the dependency-injection container
    - Create `Core\Container` that lazily constructs and shares services, repositories, and the tenant resolver
    - Provide accessors used by controllers, shortcodes, and admin screens
    - _Requirements: 2.1_

  - [x] 1.3 Implement activation and deactivation routines
    - Create `Core\Activator` to create the database schema (delegating to the migration runner) and seed default `rims_tenant_settings` (status color map, expiry days, rate-limit window/max, infinite-scroll flag)
    - Create `Core\Deactivator` to unschedule cron tasks while preserving inventory, project, and lead data
    - Register `register_activation_hook` / `register_deactivation_hook` in the bootstrap
    - _Requirements: 2.1, 2.2_

  - [x] 1.4 Implement the versioned migration runner and schema migrations
    - Create `migrations/` runner that tracks an applied-version option and applies pending migrations idempotently
    - Author migration files (InnoDB, utf8mb4, `{wp_prefix}rims_` prefix, `tenant_id` + index on every business table) for: `rims_tenants`, `rims_tenant_settings`, `rims_projects`, `rims_inventory_units`, `rims_unit_variants`, `rims_leads`, `rims_lead_history`, `rims_media`, `rims_analytics_events`, `rims_tags`, `rims_inventory_tags`, `rims_price_history`, `rims_nearby_places`, `rims_bookmarks`, `rims_saved_alerts`, `rims_push_subscriptions`, `rims_crm_config`
    - _Requirements: 2.1, 33.3_

  - [x] 1.5 Set up the asset build pipeline with `rims-` prefix scoping
    - Configure Tailwind with a `rims-` class prefix and a `.rims-root` wrapper; configure PostCSS purge for performance
    - Set up the Alpine.js + vanilla ES-module entry under `assets/src/` building to `assets/dist/`, with all JS identifiers/custom elements `rims-` prefixed and module-scoped
    - _Requirements: 1.6, 25.3_

  - [x] 1.6 Set up the dual property-based test harness
    - Configure PHPUnit + Eris for the PHP core with in-memory repository fakes
    - Configure Vitest + fast-check for Alpine frontend-state modules
    - Add a shared generator module skeleton (random `Inventory_Unit`, `Project`, `Lead`, `FilterSet`, `SearchCriteria`, media descriptors, multi-tenant datasets, credential strings) including edge inputs (empty dataset, whitespace strings, max-size files, no-coordinate units, zero-lead tenants, unicode names)
    - _Requirements: 2.1_

- [x] 2. Implement domain value objects, enums, and serializers
  - [x] 2.1 Implement `UnitStatus` enum and the status-to-color map
    - Implement `Domain\UnitStatus` (available/blocked/token_received/under_negotiation/sold) and a configurable color map (green/orange/blue/distinct-contrast/gray) sourced from tenant settings
    - _Requirements: 18.4, 18.5_

  - [x]* 2.2 Write property test for status-to-color mapping
    - **Property 11: Status-to-color mapping is total and injective**
    - Assert the map is total over all five statuses and the five colors are pairwise distinct
    - Tag: `// Feature: resale-inventory-management, Property 11`
    - **Validates: Requirements 18.5, 7.6, 9.3**

  - [x] 2.3 Implement `PipelineStage` and `ViewMode` enums
    - Implement ordered `Domain\PipelineStage` (new→contacted→interested→visit_scheduled→negotiation→closed→lost) and `Domain\ViewMode` (Card/Table/BrokerSheet/Map)
    - _Requirements: 16.1, 8.1_

  - [x] 2.4 Implement the `Money` value object with Indian-convention parsing
    - Store amount in paise; parse `3 Cr` → 30,000,000 and `75 L` → 7,500,000 as exact integers; provide `format()`
    - _Requirements: 14.1_

  - [x] 2.5 Implement DTOs and `FilterSet`/`SearchCriteria` value objects
    - Implement `SearchCriteria`, `FilterSet`, and audience-tagged resource DTOs for Inventory_Unit, Project, and Lead
    - _Requirements: 13.1, 31.2_

  - [x] 2.6 Implement the audience-based field-visibility serializer
    - Serialize a unit selecting a field set by audience (visitor / capability-holder); classify `owner_name`, `owner_phone`, `broker_notes` as internal-only
    - _Requirements: 18.8, 22.4_

  - [x]* 2.7 Write property test for owner-field redaction
    - **Property 9: Owner fields are redacted from non-capability audiences**
    - For visitor/non-capability serializations (HTML, REST, PDF, Excel) assert `owner_name`/`owner_phone` are absent; present only for capability holders
    - Tag: `// Feature: resale-inventory-management, Property 9`
    - **Validates: Requirements 18.8, 22.4, 10.10**

  - [x]* 2.8 Write property test for field-complete row/card rendering
    - **Property 8: Card and row rendering are field-complete**
    - For any unit and any listing view, assert all required public fields + action controls present and no internal-only field
    - Tag: `// Feature: resale-inventory-management, Property 8`
    - **Validates: Requirements 7.1, 7.2, 9.1, 10.2, 11.2, 12.2, 16.4, 26.4**

  - [x]* 2.9 Write property test for REST JSON round-trip
    - **Property 36: REST resources round-trip through JSON**
    - Encode then decode each resource DTO and assert equality (respecting audience field set)
    - Tag: `// Feature: resale-inventory-management, Property 36`
    - **Validates: Requirements 31.2**

- [x] 3. Implement the repository layer and multi-tenant scoping
  - [x] 3.1 Implement the base repository with parameterized, tenant-scoped queries
    - Create a `$wpdb`-backed base repository whose query builder injects `WHERE tenant_id = :tenant_id` on reads and stamps `tenant_id` from context (never client input) on writes, using only parameterized statements
    - _Requirements: 23.3, 33.3_

  - [x]* 3.2 Write property test for injection-inert database access
    - **Property 31: Database access is injection-inert**
    - For inputs containing SQL metacharacters, assert results equal the safely-bound equivalent and query structure is unchanged
    - Tag: `// Feature: resale-inventory-management, Property 31`
    - **Validates: Requirements 23.3**

  - [x]* 3.3 Write property test for tenant data isolation
    - **Property 47: Tenant data access is isolated**
    - For interleaved two-tenant datasets, assert reads scoped to one tenant return none of the other's records and writes cannot affect the other's records
    - Tag: `// Feature: resale-inventory-management, Property 47`
    - **Validates: Requirements 33.3**

  - [x] 3.4 Implement concrete repositories
    - Implement Inventory, Project, Lead, LeadHistory, Media, Analytics, Tag, PriceHistory, NearbyPlace, Bookmark, SavedAlert, PushSubscription, Tenant, and CrmConfig repositories on the base repository
    - _Requirements: 23.3, 33.3_

  - [x] 3.5 Implement the tenant and branding resolver
    - Resolve `Tenant` context once per request (by domain in single-site, `tenant_id` mapping in SaaS mode); resolve branding (company name, logo, primary color, contact details) per tenant for frontend/exports/notifications; allow independent per-tenant configuration
    - _Requirements: 33.1, 33.2, 33.4_

  - [x]* 3.6 Write property test for independent tenant branding
    - **Property 48: Tenant branding is independent**
    - Assert changing one tenant's branding/contact config leaves another tenant's unchanged
    - Tag: `// Feature: resale-inventory-management, Property 48`
    - **Validates: Requirements 33.4**

- [x] 4. Checkpoint - foundation
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement the Security Layer
  - [x] 5.1 Implement authorization guards (nonce, capability, REST token)
    - Implement nonce/CSRF verification on state-changing requests, capability checks on management actions, and bearer/token verification for REST writes; guards short-circuit before any service call and log the attempt
    - _Requirements: 23.1, 23.4, 31.3_

  - [x]* 5.2 Write property test for authorization guards
    - **Property 32: Authorization guards reject unauthorized requests**
    - Assert rejection on absent/invalid nonce, missing capability, or missing/invalid/expired token; acceptance only when all applicable guards pass
    - Tag: `// Feature: resale-inventory-management, Property 32`
    - **Validates: Requirements 23.1, 23.4, 31.3**

  - [x] 5.3 Implement context-aware output escaping helpers
    - Implement escaping for all dynamic HTML output contexts so user-supplied values cannot inject executable script
    - _Requirements: 23.2_

  - [x]* 5.4 Write property test for output escaping
    - **Property 30: Output escaping neutralizes injected markup**
    - For strings containing `<script>`, quotes, and entities, assert no executable markup from the value appears in rendered output
    - Tag: `// Feature: resale-inventory-management, Property 30`
    - **Validates: Requirements 23.2**

  - [x] 5.5 Implement input validation (type and length schemas)
    - Implement schema-driven validation that returns a `ValidationResult` with per-field messages and persists nothing on failure
    - _Requirements: 23.6, 18.7, 15.7_

  - [x]* 5.6 Write property test for schema-violation rejection
    - **Property 23: Field validation rejects schema violations before persistence**
    - For inputs with a missing required field, wrong type, or over-length value, assert rejection, no persistence, and the offending field is reported
    - Tag: `// Feature: resale-inventory-management, Property 23`
    - **Validates: Requirements 18.7, 23.6, 15.7**

  - [x] 5.7 Implement the per-client rate limiter
    - Implement a windowed limiter using configured limit N and window W per client; reject beyond N until the window resets (429 + `Retry-After`)
    - _Requirements: 23.5_

  - [x]* 5.8 Write property test for rate limiting
    - **Property 33: Rate limiting caps requests per window**
    - Assert at most N requests accepted within W and all further requests rejected until reset
    - Tag: `// Feature: resale-inventory-management, Property 33`
    - **Validates: Requirements 23.5**

- [x] 6. Implement the Cache Manager
  - [x] 6.1 Implement read-through caching with event invalidation
    - Wrap WP Object Cache with deterministic keys hashed from `(tenant_id, query-shape, normalized-filters, page)`; Redis-ready via drop-in; bounded TTL; write events purge affected key groups within the configured invalidation delay
    - _Requirements: 24.6, 24.7, 24.8_

  - [x]* 6.2 Write property test for cache consistency
    - **Property 34: Cache reads are consistent with the source until invalidation**
    - Assert cached read equals direct query until an invalidation event; after an affecting write, next read reflects the write
    - Tag: `// Feature: resale-inventory-management, Property 34`
    - **Validates: Requirements 24.6, 24.8**

- [x] 7. Implement inventory core services
  - [x] 7.1 Implement `Inventory_Manager` CRUD and status transitions
    - Add/edit/delete units; persist new units with default `Available`; persist status changes with a `status_changed_at` timestamp; reject missing required fields with field-level messages (uses validation from task 5.5)
    - _Requirements: 18.1, 18.2, 18.3, 18.6, 18.7_

  - [x]* 7.2 Write property test for inventory CRUD and status round-trip
    - **Property 22: Inventory create/edit/delete and status changes round-trip**
    - Assert persist→read equals input with default Available; delete makes unit unretrievable; status change persists new status + timestamp
    - Tag: `// Feature: resale-inventory-management, Property 22`
    - **Validates: Requirements 18.2, 18.3, 18.6**

  - [x] 7.3 Implement `Filter_Engine` (conjunctive apply, clear-all, count)
    - Implement pure `apply(FilterSet, Dataset)` over Project/Builder/Location/Sector/BHK/Area/Budget/Facing/Tower/Floor/Status returning `{ units, count }`; clear-all returns the unfiltered (non-expired) set
    - _Requirements: 13.1, 13.2, 13.6, 13.7_

  - [x]* 7.4 Write property test for conjunctive filtering
    - **Property 2: Conjunctive filtering returns exactly the satisfying units**
    - Assert result contains every satisfying unit and no violating unit, across filter combinations including shortcode-attribute and on-page BHK filters
    - Tag: `// Feature: resale-inventory-management, Property 2`
    - **Validates: Requirements 13.2, 2.8, 4.3, 12.4, 12.7**

  - [x]* 7.5 Write property test for clear-all restoring the full set
    - **Property 3: Clearing all filters restores the full set**
    - Assert an empty `FilterSet` returns exactly the unfiltered dataset (excluding expired units)
    - Tag: `// Feature: resale-inventory-management, Property 3`
    - **Validates: Requirements 13.6, 4.4**

  - [x]* 7.6 Write property test for match count equals result size
    - **Property 4: Filtered match count equals result-set size**
    - Assert the displayed count equals the number of units in the filtered result
    - Tag: `// Feature: resale-inventory-management, Property 4`
    - **Validates: Requirements 13.7**

  - [x] 7.7 Implement `Smart_Search_Engine` NL parser and search
    - Implement pure `parse(query)` extracting BHK, budget (Cr/L via `Money`), builder/project, location; `search(criteria, dataset)` returns conjunctive matches priced at or below budget
    - _Requirements: 14.1, 14.2, 14.3, 4.3_

  - [x]* 7.8 Write property test for NL search parse-then-filter
    - **Property 5: Natural-language search parses then filters correctly**
    - Synthesize queries from known `(bhk, budget, builder/project, location)` tuples and assert extracted criteria and exact matching results
    - Tag: `// Feature: resale-inventory-management, Property 5`
    - **Validates: Requirements 14.1, 14.2, 14.3**

  - [x] 7.9 Implement the statistics aggregator
    - Compute Available-Inventory, Project, Builder, and Resale-Deal counts directly from the current dataset (including 0 for empty)
    - _Requirements: 5.3, 5.4_

  - [x]* 7.10 Write property test for quick-statistics aggregates
    - **Property 6: Quick statistics equal the true aggregates**
    - Assert each counter equals the aggregate computed directly from the dataset, including 0 on empty
    - Tag: `// Feature: resale-inventory-management, Property 6`
    - **Validates: Requirements 5.3, 5.4**

  - [x] 7.11 Implement the pagination service
    - Implement ordered, stable pagination returning page slices plus a true total count (also feeding infinite-scroll appends)
    - _Requirements: 31.4, 25.5_

  - [x]* 7.12 Write property test for pagination partitioning
    - **Property 35: Pagination partitions the ordered dataset and reports the true total**
    - Assert concatenation of all pages equals the full ordered dataset (no duplicates/gaps) and reported total equals dataset size
    - Tag: `// Feature: resale-inventory-management, Property 35`
    - **Validates: Requirements 31.4, 25.5**

  - [x] 7.13 Implement `Expiry_Manager` (retention, expire, renew)
    - Expose a configurable retention period; mark a unit expired exactly when age since publication exceeds retention and exclude it from frontend results; renew restores prior status and resets the timer
    - _Requirements: 29.1, 29.2, 29.4_

  - [x]* 7.14 Write property test for age-based expiry
    - **Property 26: Expiry is determined by age and excludes expired units**
    - Assert expired exactly when age > retention and expired units absent from frontend results
    - Tag: `// Feature: resale-inventory-management, Property 26`
    - **Validates: Requirements 29.2**

  - [x]* 7.15 Write property test for renewal restoring status
    - **Property 27: Renewal restores pre-expiry status and resets the timer**
    - Assert renewed unit's status equals pre-expiry status and timer resets to renewal time
    - Tag: `// Feature: resale-inventory-management, Property 27`
    - **Validates: Requirements 29.4**

- [x] 8. Checkpoint - inventory core
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Implement view-formatting services
  - [x] 9.1 Implement featured-carousel selection
    - Return exactly the units flagged featured (with image, price, area, CTA data); return empty when none are flagged
    - _Requirements: 6.1, 6.5_

  - [x]* 9.2 Write property test for featured-carousel selection
    - **Property 7: Featured carousel contains exactly the featured units**
    - Assert exactly the featured units are returned and nothing when none flagged
    - Tag: `// Feature: resale-inventory-management, Property 7`
    - **Validates: Requirements 6.1, 6.5**

  - [x] 9.3 Implement table column-sort logic
    - Implement sort over Project/BHK/Area/Floor/Price/Status: first activation ascending, second toggles descending
    - _Requirements: 9.2_

  - [x]* 9.4 Write property test for ascending-then-descending sort
    - **Property 14: Column sort orders ascending then toggles descending**
    - Assert first activation non-decreasing, second activation reverse ordering
    - Tag: `// Feature: resale-inventory-management, Property 14`
    - **Validates: Requirements 9.2**

  - [x] 9.5 Implement broker-sheet formatter (shorthand + multi-variant)
    - Render price labels verbatim (`@ MP`, `P/p` ratio, `U/C`, `CR WITHOUT OC`); collapse multiple area/option variants into a single project row containing every variant area
    - _Requirements: 10.3, 10.4_

  - [x]* 9.6 Write property test for broker-sheet shorthand and variants
    - **Property 15: Broker-sheet shorthand and multi-variant rendering**
    - Assert verbatim shorthand label and exactly one row containing every variant area
    - Tag: `// Feature: resale-inventory-management, Property 15`
    - **Validates: Requirements 10.3, 10.4**

  - [x] 9.7 Implement map-partition logic
    - Partition a dataset into pinned units (have coordinates) and a fallback list (lack coordinates)
    - _Requirements: 11.4_

  - [x]* 9.8 Write property test for map partitioning
    - **Property 16: Map pinning partitions the dataset by coordinate presence**
    - Assert pinned set = units with coordinates, fallback = units without, disjoint and exhaustive
    - Tag: `// Feature: resale-inventory-management, Property 16`
    - **Validates: Requirements 11.4**

  - [x] 9.9 Implement price-trend window selector
    - Select price-history points whose `recorded_at` falls within a 6-month or 12-month window relative to now
    - _Requirements: 12.5_

  - [x]* 9.10 Write property test for price-trend window selection
    - **Property 17: Price-trend window selection returns only in-window points**
    - Assert the series equals exactly the in-window points for the selected window
    - Tag: `// Feature: resale-inventory-management, Property 17`
    - **Validates: Requirements 12.5**

  - [x] 9.11 Implement contact-link builder
    - Build a WhatsApp link to the configured number with a prefilled message identifying the unit, and a `tel:` Call link to the configured number
    - _Requirements: 7.4, 7.5_

  - [x]* 9.12 Write property test for contact links
    - **Property 10: Contact links target the configured number and identify the unit**
    - Assert WhatsApp link targets configured number with unit-identifying message and Call link is a telephone action to that number
    - Tag: `// Feature: resale-inventory-management, Property 10`
    - **Validates: Requirements 7.4, 7.5**

- [x] 10. Implement lead and analytics services
  - [x] 10.1 Implement `Lead_Capture` validation and persistence
    - Validate name present and mobile present + format-valid; persist `Lead` with capture source in `new` stage on success; reject with field-level message on missing/malformed name or mobile (persist nothing)
    - _Requirements: 15.5, 15.6, 15.7_

  - [x]* 10.2 Write property test for lead submission (valid and invalid)
    - **Property 18: Valid lead submission persists with its capture source; invalid submission is rejected**
    - Assert valid input persists a matching `Lead` in `new` with source; missing/malformed name or mobile rejects, persists nothing, and produces a field-level message
    - Tag: `// Feature: resale-inventory-management, Property 18`
    - **Validates: Requirements 15.6, 15.7, 16.2**

  - [x] 10.3 Implement `Lead_Management` pipeline moves and counts
    - Move a lead to a target stage (persist + record `rims_lead_history`); compute per-column counts
    - _Requirements: 16.2, 16.3, 16.6_

  - [x]* 10.4 Write property test for pipeline-stage moves
    - **Property 19: Lead pipeline-stage moves round-trip**
    - Assert moving a lead to any target column yields a persisted stage equal to the target
    - Tag: `// Feature: resale-inventory-management, Property 19`
    - **Validates: Requirements 16.3**

  - [x]* 10.5 Write property test for per-column lead counts
    - **Property 20: Per-column lead counts are consistent**
    - Assert each column count equals leads in that stage and the sum equals the total lead count
    - Tag: `// Feature: resale-inventory-management, Property 20`
    - **Validates: Requirements 16.6**

  - [x] 10.6 Implement `Analytics_Engine` event recording and rankings
    - Record exactly one event per action (page_view/inventory_view/whatsapp_click/phone_click/lead_generated) referencing the correct entity; aggregate Most-Viewed-Projects and Most-Viewed-Units rankings; defer rankings when no events exist
    - _Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.6, 20.8_

  - [x]* 10.7 Write property test for engagement events and rankings
    - **Property 25: Engagement events and rankings are faithful to actions**
    - Assert one matching event per action referencing the correct entity, and rankings ordered by descending view count
    - Tag: `// Feature: resale-inventory-management, Property 25`
    - **Validates: Requirements 20.1, 20.2, 20.3, 20.4, 20.5, 20.6**

  - [x] 10.8 Implement the dashboard metrics calculator
    - Compute, over a selected date range, Revenue (sum of in-range sold-unit prices), in-range Inventory and Lead counts, and Conversion rate (closed leads / total leads, 0 when none)
    - _Requirements: 17.4, 17.5_

  - [x]* 10.9 Write property test for dashboard metrics
    - **Property 21: Dashboard metrics equal their definitions over the selected range**
    - Assert each metric equals its definition over the selected range, including 0 conversion on no leads
    - Tag: `// Feature: resale-inventory-management, Property 21`
    - **Validates: Requirements 17.4, 17.5**

- [x] 11. Checkpoint - lead & analytics
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Implement media, AI, and export services
  - [x] 12.1 Implement `Media_Gallery_Manager` upload pipeline
    - Accept photos/videos/floor plans/brochures; reject oversize or disallowed type with a constraint message; accept when size equals the maximum; crop and store cropped result; produce a compressed derivative no larger than the original; apply watermark when enabled
    - _Requirements: 19.1, 19.3, 19.4, 19.5, 19.6, 19.7_

  - [x]* 12.2 Write property test for media upload validation
    - **Property 24: Media upload validation honors size and type bounds**
    - Assert reject on oversize/disallowed, accept at exactly max with allowed type, and compressed derivative byte size ≤ original
    - Tag: `// Feature: resale-inventory-management, Property 24`
    - **Validates: Requirements 19.6, 19.7, 19.4**

  - [x] 12.3 Implement `AI_Content_Generator` and `AI_Tag_Generator`
    - Generate SEO description, project overview, and WhatsApp message (returned editable); on failure surface an error and leave saved content unchanged; suggest tags from the configured vocabulary and persist only explicitly accepted tags
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5_

  - [x]* 12.4 Write property test for vocabulary-bounded AI tags
    - **Property 28: AI tag suggestions are vocabulary-bounded and persist only on acceptance**
    - Assert every suggestion is in the configured vocabulary and persisted active tags equal exactly the accepted subset
    - Tag: `// Feature: resale-inventory-management, Property 28`
    - **Validates: Requirements 21.4, 21.5**

  - [x] 12.5 Implement `Export_Engine` (PDF and Excel)
    - Generate branded PDF (logo, sheet, project images, price, area) and a multi-sheet Excel (Inventory/Projects/Leads); include only the current filtered result set; redact owner fields for non-capability users; show a "no records" notice and generate nothing when empty
    - _Requirements: 10.7, 10.8, 22.1, 22.2, 22.3, 22.4, 22.5_

  - [x]* 12.6 Write property test for export result-set fidelity
    - **Property 29: Exports contain exactly the current filtered set**
    - Assert exported records equal exactly the units in the current filtered result at request time
    - Tag: `// Feature: resale-inventory-management, Property 29`
    - **Validates: Requirements 22.3**

- [x] 13. Implement premium-feature services
  - [x] 13.1 Implement `Comparison_Engine` (cap of 4)
    - Add units up to a maximum of 4; reject a fifth distinct unit with a limit message, leaving the set unchanged; render side-by-side comparison attributes
    - _Requirements: 26.2, 26.3, 26.4_

  - [x] 13.2 Implement `Bookmark_Manager` and `Recently_Viewed_Tracker`
    - Persist bookmarks keyed by visitor token, retrievable within session; record viewed unit ids to cookie; return recently-viewed most-recent-first and de-duplicated to latest view
    - _Requirements: 26.1, 26.5, 26.6_

  - [x] 13.3 Implement `Share_Manager` and `QR_Code_Generator`
    - Provide WhatsApp/Facebook/LinkedIn/Telegram/Email share actions embedding the unit's public URL; generate a QR per unit/project encoding the public URL that resolves to the public detail page
    - _Requirements: 27.1, 27.2, 27.3, 27.4_

  - [x]* 13.4 Write property test for share links and QR codes
    - **Property 39: Share links and QR codes resolve to the record's public URL**
    - Assert each share action embeds the unit's public URL and decoding a QR yields exactly the record's public detail URL
    - Tag: `// Feature: resale-inventory-management, Property 39`
    - **Validates: Requirements 27.2, 27.3, 27.4**

  - [x] 13.5 Implement `SEO_Engine` (slugs, metadata, JSON-LD, sitemap)
    - Generate URL-safe, human-readable, per-tenant-unique slugs that resolve back to their record; derive unique title/meta-description per record; emit JSON-LD real-estate listing markup for projects; build an XML sitemap of published (non-expired) URLs
    - _Requirements: 32.1, 32.2, 32.3, 32.4_

  - [x]* 13.6 Write property test for SEO slug safety, uniqueness, and resolution
    - **Property 43: SEO slugs are URL-safe, unique, and resolve back to their record**
    - Assert slugs are URL-safe/human-readable, resolve to the originating record, and never collide within a tenant
    - Tag: `// Feature: resale-inventory-management, Property 43`
    - **Validates: Requirements 32.1**

  - [x]* 13.7 Write property test for data-derived SEO metadata
    - **Property 44: SEO metadata is data-derived and unique per record**
    - Assert title and meta description are derived from record data and unique per record
    - Tag: `// Feature: resale-inventory-management, Property 44`
    - **Validates: Requirements 32.2**

  - [x]* 13.8 Write property test for project structured data
    - **Property 45: Project structured data is valid and complete**
    - Assert emitted markup is valid JSON-LD describing a real-estate listing with required fields derived from the project
    - Tag: `// Feature: resale-inventory-management, Property 45`
    - **Validates: Requirements 32.3**

  - [x]* 13.9 Write property test for sitemap URL set
    - **Property 46: The sitemap contains exactly the published record URLs**
    - Assert the sitemap URL set equals exactly published (non-expired) unit/project URLs and excludes unpublished/expired records
    - Tag: `// Feature: resale-inventory-management, Property 46`
    - **Validates: Requirements 32.4**

  - [x] 13.10 Implement `Notification_Service` push delivery
    - Deliver a push to a visitor if and only if permission is granted and a newly published unit matches the visitor's saved alert criteria; never push without permission
    - _Requirements: 28.3, 28.4_

  - [x]* 13.11 Write property test for push delivery conditions
    - **Property 40: Push is delivered exactly when permission is granted and criteria match**
    - Assert delivery iff permission granted AND unit matches saved criteria
    - Tag: `// Feature: resale-inventory-management, Property 40`
    - **Validates: Requirements 28.3, 28.4**

  - [x] 13.12 Implement `CRM_Integration_Service` (config, forward, retry, encryption)
    - Support Sell.Do/LeadSquared/HubSpot/Zoho config; forward a persisted lead iff the platform is enabled and a retry policy is configured; retry per policy and log failures; store credentials encrypted at rest
    - _Requirements: 30.1, 30.2, 30.3, 30.4, 30.5_

  - [x]* 13.13 Write property test for CRM forwarding conditions
    - **Property 41: CRM forwarding occurs only when enabled with a configured retry policy**
    - Assert a forward is attempted iff the platform is enabled and a retry policy exists; none attempted when policy absent
    - Tag: `// Feature: resale-inventory-management, Property 41`
    - **Validates: Requirements 30.2, 30.5**

  - [x]* 13.14 Write property test for CRM credential encryption
    - **Property 42: CRM credentials round-trip through encryption and are never stored in plaintext**
    - Assert stored representation differs from plaintext and decrypts back to the original
    - Tag: `// Feature: resale-inventory-management, Property 42`
    - **Validates: Requirements 30.4**

- [x] 14. Checkpoint - services complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. Implement controllers (REST and AJAX)
  - [x] 15.1 Implement REST controllers under `rims/v1`
    - Implement list/retrieve for Inventory_Units, Projects, Leads returning JSON; write ops require a valid token; list endpoints support pagination params and return total count; versioned namespace
    - _Requirements: 31.1, 31.2, 31.3, 31.4, 31.5_

  - [x] 15.2 Implement AJAX handlers
    - Implement handlers for filter apply, smart search, view switch, infinite-scroll page load, and lead submit, returning the uniform `{ data, meta }` / `{ error }` envelope without full page reload
    - _Requirements: 13.3, 24.2_

  - [x] 15.3 Wire Security_Layer guards into all controllers
    - Apply nonce/token, capability, rate-limit, and validation/escaping guards at every REST/AJAX entry point before dispatch
    - _Requirements: 23.1, 23.4, 23.5, 31.3_

- [x] 16. Implement theme integration and frontend rendering
  - [x] 16.1 Implement `Theme_Integration_Engine`
    - Resolve RIMS virtual routes; render body between the active theme's `get_header()`/`get_footer()`; inject into the Elementor-managed content area when present; fall back to WP defaults with a diagnostic admin notice when header/footer support is absent; inherit theme menus/typography/breakpoints
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.7_

  - [x] 16.2 Implement `Frontend_Renderer` hero section
    - Render full-width hero with project/location inputs, BHK selector, price-range selector, submit; animated gradient + particle layer + glass overlay; operable at ≥ 360 px; submit wired to Smart_Search_Engine/Filter_Engine
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 16.3 Implement animated quick-statistics cards
    - Render Available/Project/Builder/Resale-Deal counter cards sourced from the statistics aggregator, animating from zero on viewport entry (including 0→0)
    - _Requirements: 5.1, 5.2_

  - [x] 16.4 Implement the featured-inventory carousel UI
    - Render featured slides (image, price, area, CTA) from the selection service; auto-advance with hover pause; CTA navigates to the Project_Detail_Page
    - _Requirements: 6.2, 6.3, 6.4_

  - [x] 16.5 Implement `Card_View` glassmorphism cards
    - Render each unit card (image, project, builder, location, price, area, floor, status badge using the color map) with View Details / WhatsApp / Call Now controls (from contact-link builder) and a hover lift of −8 px over 300 ms
    - _Requirements: 7.1, 7.2, 7.3, 7.6, 7.7_

  - [x]* 16.6 Write property test for CSS/JS prefix scoping
    - **Property 1: CSS/JS prefix scoping**
    - For any rendered RIMS fragment, assert every authored CSS class and JS/DOM identifier begins with `rims-`
    - Tag: `// Feature: resale-inventory-management, Property 1`
    - **Validates: Requirements 1.6**

  - [x] 16.7 Implement dark/light mode, skeletons, infinite scroll, and lazy-load
    - Implement an animated dark/light toggle persisted across navigations in-session; skeleton placeholders with fade/slide-in; lazy-loaded images; infinite-scroll append wired to the pagination service when enabled
    - _Requirements: 25.1, 25.2, 25.3, 25.4, 25.5, 24.1_

  - [x]* 16.8 Write frontend property test for view/color mode persistence
    - **Property 12: Selected display/view mode round-trips within a session**
    - fast-check/Vitest: assert any selected view mode and color mode, after persist+re-read in-session, equals the value set
    - Tag: `// Feature: resale-inventory-management, Property 12`
    - **Validates: Requirements 8.4, 17.3, 25.2**

  - [x] 16.9 Implement the `View_Manager` Alpine state module
    - Provide controls to switch Card/Table/BrokerSheet/Map with Card default; preserve active filters and search independently across switches; persist selected mode for the session; block the switch and retain the current mode if state cannot be preserved
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x]* 16.10 Write frontend property test for view-switch state preservation
    - **Property 13: View switching preserves filters and search independently**
    - fast-check/Vitest: assert switching views yields a state whose filters and search criteria each equal the originals
    - Tag: `// Feature: resale-inventory-management, Property 13`
    - **Validates: Requirements 8.3**

- [x] 17. Implement view-mode UIs and the project detail page
  - [x] 17.1 Implement `Table_View` UI
    - Render Project/BHK/Area/Floor/Price/Status rows; wire sortable headers to the sort service; color the status cell via the color map
    - _Requirements: 9.1, 9.2, 9.3_

  - [x] 17.2 Implement `Broker_Sheet_View` UI
    - Render the navy "PREMIUM RESALE INVENTORY" header and dense rows from the broker-sheet formatter; Print (print-optimized stylesheet), Export PDF, Export Excel, Share WhatsApp controls; disable PDF/Excel controls for users lacking the inventory-management capability; layout matching the reference image at ≥ 1024 px
    - _Requirements: 10.1, 10.2, 10.5, 10.6, 10.9, 10.10_

  - [x] 17.3 Implement `Map_View` UI
    - Render Google Maps pins from the partition service with price+area labels and an info overlay (project, price, area, detail link); list coordinate-less units in an adjacent fallback list; defer pins until Maps loads; on load failure show an error with a "switch to Card View" control
    - _Requirements: 11.1, 11.2, 11.3, 11.5, 11.6_

  - [x] 17.4 Implement the `Project_Detail_Page`
    - Render an auto-advancing, fullscreen-capable, lazy-loading hero slider; project overview (name, builder, location, possession, tower count); live units with 2/3/4 BHK + Penthouse filters (via Filter_Engine); a 6/12-month price-trend chart (from the trend selector); nearby cards (Schools/Hospitals/Metro/Mall/Airport) revealing associated places on selection
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_

  - [x] 17.5 Implement the advanced filter UI
    - Render filter controls as a sidebar at ≥ 768 px and a slide-up bottom drawer at < 768 px; update inventory and the matching count via AJAX without full reload
    - _Requirements: 13.3, 13.4, 13.5, 13.7_

  - [x] 17.6 Implement the smart-search UI
    - Wire the search input to the parser with a 300 ms debounce; render a no-results message with suggested alternative searches on empty results
    - _Requirements: 14.4, 14.5_

  - [x] 17.7 Implement the lead-capture UI
    - Render the floating sticky bar (Call/WhatsApp/Schedule Visit) at ≥ 768 px; trigger the popup on 10 s dwell, desktop exit-intent (each once per session), and "View More"; render the 3-step form; show field-level validation and a success confirmation
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5, 15.8_

  - [x] 17.8 Implement bookmarks, comparison, recently-viewed, and share UI
    - Render bookmark toggles, the comparison tray + side-by-side table, the recently-viewed strip, and per-unit share controls wired to the respective services
    - _Requirements: 26.1, 26.4, 26.6, 27.1_

  - [x]* 17.9 Write frontend property test for comparison-set cap
    - **Property 37: Comparison set never exceeds four units**
    - fast-check/Vitest: assert the set never exceeds 4 and a fifth distinct add is rejected with a limit message, leaving the set unchanged
    - Tag: `// Feature: resale-inventory-management, Property 37`
    - **Validates: Requirements 26.2, 26.3**

  - [x]* 17.10 Write frontend property test for recently-viewed ordering
    - **Property 38: Bookmarks and recently-viewed round-trip with correct ordering**
    - fast-check/Vitest: assert recently-viewed is most-recent-first and de-duplicated to each unit's latest view (with a companion bookmark round-trip example)
    - Tag: `// Feature: resale-inventory-management, Property 38`
    - **Validates: Requirements 26.1, 26.5, 26.6**

- [x] 18. Checkpoint - frontend
  - Ensure all tests pass, ask the user if questions arise.

- [x] 19. Implement shortcodes and Elementor widgets
  - [x] 19.1 Implement `Shortcode_Registrar`
    - Register `[resale_inventory]`, `[featured_inventory]`, `[inventory_sheet]`, `[inventory_search]`; render each within the theme content area; map attributes (project/builder/bhk/status) to pre-applied filters
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_

  - [x] 19.2 Implement `Elementor_Widget_Provider`
    - Register Inventory Grid, Inventory Search, Featured Inventory, and Lead Form widgets with live editor preview and controls mapped one-to-one to shortcode attributes; register definitions safely as a no-op when Elementor is inactive
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 20. Implement admin screens
  - [x] 20.1 Implement the `Admin_Dashboard` screen
    - Render Revenue/Inventory/Lead/Conversion widgets and Lead Sources/Lead Funnel/Monthly Trends/Inventory Trends charts (Chart.js) from the metrics calculator; per-admin persisted dark/light; recompute widgets and charts independently only on explicit date-range selection
    - _Requirements: 17.1, 17.2, 17.3, 17.5, 17.6_

  - [x] 20.2 Implement analytics heatmap visualizations
    - Render Most-Viewed-Projects and Most-Viewed-Units rankings as dashboard heatmaps from the analytics rankings
    - _Requirements: 20.7_

  - [x] 20.3 Implement inventory CRUD admin screens
    - Render the add/edit form (Project, Unit Number, Tower, Floor, Facing, BHK, Area, Price, Owner Name, Owner Phone, Broker Notes) and list/edit/delete actions wired to `Inventory_Manager` with field-level validation
    - _Requirements: 18.1, 18.3_

  - [x] 20.4 Implement the lead Kanban board UI
    - Render the seven pipeline columns with drag-and-drop persistence, per-card name/mobile/requirement/source, per-column counts, and a card-detail view with contact history
    - _Requirements: 16.1, 16.3, 16.4, 16.5, 16.6_

  - [x] 20.5 Implement the media gallery admin UI
    - Render drag-and-drop upload with image crop wired to `Media_Gallery_Manager`
    - _Requirements: 19.2, 19.3_

  - [x] 20.6 Implement the AI content/tags admin UI
    - Render editable generated content fields and the tag-suggestion accept/reject UI wired to the AI services
    - _Requirements: 21.2_

- [x] 21. Implement integrations, PWA, and scheduled jobs
  - [x] 21.1 Implement the PWA manifest and service worker
    - Serve a web app manifest and a Workbox service worker for installability and an offline cached shell
    - _Requirements: 28.1, 28.2_

  - [x] 21.2 Wire push subscription capture and delivery
    - Capture permission-gated push subscriptions and trigger `Notification_Service` on new matching unit publication
    - _Requirements: 28.3, 28.4_

  - [x] 21.3 Wire SEO output and routing
    - Register pretty-permalink routes for unit/project slugs, emit per-record title/meta tags and project JSON-LD, and register the XML sitemap
    - _Requirements: 32.1, 32.2, 32.3, 32.4_

  - [x] 21.4 Implement the expiry cron job and broker notification
    - Schedule a recurring job that expires aged units and notifies the owning Broker_Agent
    - _Requirements: 29.2, 29.3_

  - [x] 21.5 Wire CRM forward-on-persist with retry and logging
    - Trigger `CRM_Integration_Service` when a lead is persisted, retrying per policy and logging failures to the admin log
    - _Requirements: 30.2, 30.3_

  - [x] 21.6 Wire QR-code endpoints and share output
    - Expose QR generation for units/projects and render share actions resolving to public URLs
    - _Requirements: 27.2, 27.3, 27.4_

- [x] 22. Final wiring and integration tests
  - [x] 22.1 Wire all entry points in the bootstrap
    - Register REST routes, AJAX handlers, shortcodes, Elementor widgets, admin menus, cron schedules, frontend routes, and enqueue built assets through `Core\Plugin`/`Core\Container` so every component is reachable end-to-end
    - _Requirements: 1.1, 2.3, 3.1, 23.7, 31.5_

  - [x]* 22.2 Write integration and smoke tests for external wiring
    - Cover theme render ordering/inheritance (1.1–1.3, 1.5), Elementor preview (3.2), Google Maps pin rendering (11.1), AJAX no-reload pagination (13.3, 24.2), PDF/Excel generation (10.7, 10.8, 22.1, 22.2), Redis backend storage (24.7), service-worker offline shell (28.2), CRM forward + retry/log (30.3), HTTPS endpoint enforcement (23.7), activation schema/defaults (2.1), deactivation cleanup (2.2), versioned REST namespace (31.5), and branding storage (33.1)
    - _Requirements: 1.1, 1.5, 2.1, 2.2, 3.2, 10.7, 10.8, 11.1, 13.3, 23.7, 24.2, 24.7, 28.2, 30.3, 31.5, 33.1_

- [x] 23. Final checkpoint - full system
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional test sub-tasks (property, frontend property, unit, integration, smoke) and can be skipped for a faster MVP; core implementation tasks are never optional.
- Each task references specific granular requirements and, where applicable, a named design property for traceability.
- All 48 correctness properties are covered exactly once: Properties 12, 13, 37, 38 use fast-check/Vitest against Alpine frontend-state modules; the remaining properties use Eris/PHPUnit against the PHP core. Every property test runs ≥ 100 cases and is tagged `// Feature: resale-inventory-management, Property {n}`.
- Visual, timing, infrastructure, and one-time-setup criteria (Lighthouse, Core Web Vitals, animations, Redis backend, HTTPS transport, activation routines) are validated by the integration/smoke/performance/visual tests in task 22.2 per the design Testing Strategy, not by property tests.
- Build order is strictly layered (domain → repositories/tenancy → security → cache → services → controllers → frontend/theme → admin → integrations → wiring) so no code is orphaned and each layer integrates into the previous one.
- Checkpoints provide incremental validation at the end of the foundation, inventory core, lead/analytics, service, frontend, and full-system stages.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.5", "1.6"] },
    { "id": 1, "tasks": ["1.3", "1.4", "2.1", "2.3", "2.4"] },
    { "id": 2, "tasks": ["2.2", "2.5", "2.6", "3.1"] },
    { "id": 3, "tasks": ["2.7", "2.8", "2.9", "3.2", "3.4", "3.5"] },
    { "id": 4, "tasks": ["3.3", "3.6", "5.1", "5.3", "5.5", "5.7", "6.1"] },
    { "id": 5, "tasks": ["5.2", "5.4", "5.6", "5.8", "6.2", "7.1", "7.3", "7.7", "7.9", "7.11", "7.13"] },
    { "id": 6, "tasks": ["7.2", "7.4", "7.5", "7.6", "7.8", "7.10", "7.12", "7.14", "7.15", "9.1", "9.3", "9.5", "9.7", "9.9", "9.11"] },
    { "id": 7, "tasks": ["9.2", "9.4", "9.6", "9.8", "9.10", "9.12", "10.1", "10.3", "10.6", "10.8"] },
    { "id": 8, "tasks": ["10.2", "10.4", "10.5", "10.7", "10.9", "12.1", "12.3", "12.5", "13.1", "13.2", "13.3", "13.5", "13.10", "13.12"] },
    { "id": 9, "tasks": ["12.2", "12.4", "12.6", "13.4", "13.6", "13.7", "13.8", "13.9", "13.11", "13.13", "13.14"] },
    { "id": 10, "tasks": ["15.1", "15.2", "15.3"] },
    { "id": 11, "tasks": ["16.1", "16.2", "16.3", "16.4", "16.5", "16.7", "16.9"] },
    { "id": 12, "tasks": ["16.6", "16.8", "16.10", "17.1", "17.2", "17.3", "17.4", "17.5", "17.6", "17.7", "17.8"] },
    { "id": 13, "tasks": ["17.9", "17.10", "19.1", "19.2", "20.1", "20.2", "20.3", "20.4", "20.5", "20.6"] },
    { "id": 14, "tasks": ["21.1", "21.2", "21.3", "21.4", "21.5", "21.6"] },
    { "id": 15, "tasks": ["22.1"] },
    { "id": 16, "tasks": ["22.2"] }
  ]
}
```
