# Requirements Document

## Introduction

The Resale Inventory Management System (RIMS Pro) is an enterprise-grade real estate inventory platform delivered as a native WordPress plugin. The plugin targets real estate brokers and builders (initial deployment: GoldLine Estate at https://goldlineestate.com) and is designed for later white-labeling and resale as a multi-tenant SaaS module.

The defining characteristic of RIMS Pro is **native WordPress integration**: every public-facing page rendered by the plugin must inherit the active theme's header, footer, branding, navigation, typography, color system, and responsive behavior so that visitors cannot distinguish plugin pages from native theme pages. On top of this foundation, RIMS Pro provides a premium PropTech experience: a glassmorphism card-based frontend, four distinct inventory view modes (Property Cards, Table, Broker Sheet, Map), advanced filtering and natural-language search, a lead generation and lead management system, an enterprise admin dashboard, media management, analytics, AI-assisted content generation, PDF/Excel exports, and a suite of premium 2026 features (dark mode, bookmarks, property comparison, PWA, push notifications, QR codes, auto-expiry, and CRM integrations).

This document captures the functional and non-functional requirements using EARS patterns and INCOSE quality rules. Technical implementation details (PHP 8.3+, MySQL 8+, Tailwind CSS, Alpine.js, MVC, REST) are deferred to the design phase except where they constitute a verifiable acceptance criterion.

## Glossary

- **RIMS_Pro**: The complete Resale Inventory Management System WordPress plugin, named "RIMS Pro," encompassing all subsystems defined below.
- **Theme_Integration_Engine**: The subsystem responsible for rendering plugin frontend pages within the active WordPress theme's layout using `get_header()` and `get_footer()`.
- **Frontend_Renderer**: The subsystem that produces public-facing HTML for inventory pages, hero sections, statistics, carousels, and grids.
- **View_Manager**: The subsystem that switches the inventory presentation between the four defined view modes.
- **Card_View**: The default inventory presentation showing property cards.
- **Table_View**: The inventory presentation showing rows of Project, BHK, Area, Floor, Price, and Status.
- **Broker_Sheet_View**: The dense list presentation that visually replicates the supplied "PREMIUM RESALE INVENTORY" reference image.
- **Map_View**: The inventory presentation showing properties as pins on an interactive Google Maps surface.
- **Filter_Engine**: The subsystem that applies attribute-based filters to the inventory result set.
- **Smart_Search_Engine**: The subsystem that interprets natural-language and keyword search input and returns matching inventory.
- **Project_Detail_Page**: The page presenting a single project's overview, media, live units, price trend, and nearby locations.
- **Lead_Capture_System**: The subsystem that presents lead forms, sticky contact bars, and popup triggers, and that persists submitted leads.
- **Lead_Management_Board**: The admin Kanban board that organizes leads across pipeline stages with drag-and-drop.
- **Admin_Dashboard**: The WordPress admin interface presenting dashboard widgets, charts, and management screens.
- **Inventory_Manager**: The subsystem that creates, reads, updates, deletes, and manages status of inventory units.
- **Media_Gallery_Manager**: The subsystem that handles upload, processing, and storage of photos, videos, floor plans, and brochures.
- **Analytics_Engine**: The subsystem that records and aggregates engagement events.
- **AI_Content_Generator**: The subsystem that generates SEO descriptions, project overviews, and WhatsApp messages from project details.
- **AI_Tag_Generator**: The subsystem that generates classification tags for inventory units.
- **Export_Engine**: The subsystem that produces PDF and Excel export documents.
- **Security_Layer**: The subsystem enforcing authentication, authorization, input validation, output escaping, CSRF protection, and rate limiting.
- **Cache_Manager**: The subsystem that caches query results and supports object caching backends.
- **Shortcode_Registrar**: The subsystem that registers and renders WordPress shortcodes.
- **Elementor_Widget_Provider**: The subsystem that registers Elementor widgets.
- **REST_API**: The plugin's HTTP API surface exposing inventory, project, lead, and analytics resources.
- **SEO_Engine**: The subsystem that generates SEO metadata, structured data, and crawlable URLs for inventory and project pages.
- **Bookmark_Manager**: The subsystem that lets visitors save and retrieve favorite inventory units.
- **Comparison_Engine**: The subsystem that compares selected inventory units side by side.
- **Recently_Viewed_Tracker**: The subsystem that records recently viewed inventory using browser cookies.
- **Share_Manager**: The subsystem that generates share actions for WhatsApp, Facebook, LinkedIn, Telegram, and Email.
- **PWA_Service**: The subsystem providing Progressive Web App installability and offline shell support.
- **Notification_Service**: The subsystem that delivers push notifications for new inventory alerts.
- **QR_Code_Generator**: The subsystem that generates a QR code for each inventory unit and project.
- **Expiry_Manager**: The subsystem that expires inventory after a configurable retention period.
- **CRM_Integration_Service**: The subsystem that forwards leads to external CRM platforms.
- **Administrator**: A WordPress user with the RIMS Pro management capability who configures the plugin and manages inventory and leads.
- **Broker_Agent**: A WordPress user with capability to manage inventory and leads but not plugin-wide configuration.
- **Visitor**: An unauthenticated public user browsing the frontend inventory pages.
- **Inventory_Unit**: A single resale property record containing project, unit, tower, floor, facing, BHK, area, price, owner, status, and notes.
- **Project**: A named real estate development containing one or more Inventory_Units, with builder, location, possession, and tower metadata.
- **Lead**: A captured prospective-buyer record containing contact details, requirements, source, and pipeline stage.
- **Unit_Status**: One of the defined inventory states: Available, Blocked, Token Received, Under Negotiation, Sold.
- **MP**: Market Price, a price label meaning the unit is offered at prevailing market price.
- **P/p**: Payment plan ratio (for example, 25:75) describing the buyer payment schedule.
- **U/C**: Under Construction, a possession status label.
- **OC**: Occupancy Certificate, a regulatory completion document.
- **BHK**: Bedroom-Hall-Kitchen unit configuration count (for example, 2 BHK, 3 BHK).
- **Core_Web_Vitals**: The Google-defined metrics Largest Contentful Paint, Cumulative Layout Shift, and Interaction to Next Paint.
- **Tenant**: A single broker or builder account in the white-label SaaS deployment model.

## Requirements

### Requirement 1: Native WordPress Theme Integration

**User Story:** As a site owner at GoldLine Estate, I want every inventory page to render inside my active WordPress theme, so that visitors experience the inventory module as a seamless part of my website.

#### Acceptance Criteria

1. WHEN a Visitor requests any RIMS_Pro frontend page, THE Theme_Integration_Engine SHALL render the page output between the active theme's `get_header()` output and `get_footer()` output.
2. THE Theme_Integration_Engine SHALL render RIMS_Pro frontend pages using the active theme's registered navigation menus, color stylesheet, and typography without injecting a standalone page layout.
3. WHEN the active WordPress theme is changed, THE Theme_Integration_Engine SHALL render subsequent RIMS_Pro frontend pages using the newly activated theme's header and footer without requiring plugin reconfiguration.
4. THE Theme_Integration_Engine SHALL apply the active theme's responsive breakpoints to RIMS_Pro frontend pages so that page content reflows at the same viewport widths as native theme content.
5. WHERE the active theme is built with Elementor, THE Theme_Integration_Engine SHALL render RIMS_Pro frontend content inside the Elementor-managed content area of the page.
6. THE Frontend_Renderer SHALL scope all RIMS_Pro CSS class names and JavaScript identifiers under a reserved `rims-` prefix so that plugin styles and scripts do not override theme styles outside RIMS_Pro content regions.
7. IF the active theme does not declare `get_header()` or `get_footer()` support, THEN THE Theme_Integration_Engine SHALL render the WordPress default header and footer and SHALL record a diagnostic notice in the Administrator log.

### Requirement 2: WordPress Plugin Packaging and Shortcodes

**User Story:** As an Administrator, I want RIMS Pro packaged as a standard WordPress plugin with shortcodes, so that I can place inventory features on any page or post.

#### Acceptance Criteria

1. THE RIMS_Pro plugin SHALL register an activation routine that creates the plugin database schema and default configuration.
2. THE RIMS_Pro plugin SHALL register a deactivation routine that removes scheduled tasks while preserving inventory, project, and lead data.
3. THE Shortcode_Registrar SHALL register the shortcode `[resale_inventory]` that renders the full inventory browsing experience.
4. THE Shortcode_Registrar SHALL register the shortcode `[featured_inventory]` that renders the featured inventory carousel.
5. THE Shortcode_Registrar SHALL register the shortcode `[inventory_sheet]` that renders the Broker_Sheet_View.
6. THE Shortcode_Registrar SHALL register the shortcode `[inventory_search]` that renders the inventory search interface.
7. WHEN a registered RIMS_Pro shortcode is placed in page or post content, THE Shortcode_Registrar SHALL render the corresponding interface within the page content area produced by the active theme.
8. WHERE a shortcode attribute specifies a filter value, THE Shortcode_Registrar SHALL render the corresponding interface pre-filtered to that value.

### Requirement 3: Elementor Widget Provision

**User Story:** As an Administrator who builds pages with Elementor, I want RIMS Pro Elementor widgets, so that I can compose inventory layouts using the Elementor editor.

#### Acceptance Criteria

1. WHERE the Elementor plugin is active, THE Elementor_Widget_Provider SHALL register an "Inventory Grid" widget, an "Inventory Search" widget, a "Featured Inventory" widget, and a "Lead Form" widget.
2. WHEN an Administrator drags a RIMS_Pro Elementor widget onto the canvas, THE Elementor_Widget_Provider SHALL render a live preview of the widget within the Elementor editor.
3. THE Elementor_Widget_Provider SHALL expose editable controls for each RIMS_Pro widget that map to the corresponding shortcode attributes.
4. WHERE the Elementor plugin is inactive, THE Elementor_Widget_Provider SHALL register the RIMS_Pro widget definitions and SHALL continue plugin operation without error so that the widgets become available immediately when Elementor is activated.

### Requirement 4: Frontend Hero and Search Section

**User Story:** As a Visitor, I want a prominent search hero on the inventory landing page, so that I can quickly search for properties matching my criteria.

#### Acceptance Criteria

1. THE Frontend_Renderer SHALL render a full-width hero section containing a project search input, a location search input, a BHK selector, a price-range selector, and a search submit control.
2. THE Frontend_Renderer SHALL render the hero section background with an animated gradient, a particle effect layer, and a glass overlay.
3. WHEN a Visitor submits the hero search with one or more criteria, THE Smart_Search_Engine SHALL return inventory matching all submitted criteria and THE Frontend_Renderer SHALL display the matching results.
4. WHEN a Visitor submits the hero search with no criteria, THE Frontend_Renderer SHALL display the unfiltered inventory grid.
5. THE Frontend_Renderer SHALL render the hero section so that input controls remain operable at viewport widths of 360 pixels and above.

### Requirement 5: Animated Quick Statistics

**User Story:** As a Visitor, I want at-a-glance statistics about available inventory, so that I understand the catalog scale at a glance.

#### Acceptance Criteria

1. THE Frontend_Renderer SHALL render counter cards for Available Inventory count, Project count, Builder count, and Resale Deal count.
2. WHEN a quick-statistics card enters the viewport, THE Frontend_Renderer SHALL animate the displayed number from zero to the current actual value.
3. THE Frontend_Renderer SHALL source each quick-statistics value from the current inventory data at page render time.
4. WHERE a statistic value is zero, THE Frontend_Renderer SHALL run the count animation from zero to zero so that every quick-statistics card animates on entering the viewport regardless of value.

### Requirement 6: Featured Inventory Carousel

**User Story:** As a Visitor, I want a carousel of featured properties, so that I can discover highlighted inventory without scrolling the full grid.

#### Acceptance Criteria

1. THE Frontend_Renderer SHALL render a carousel of Inventory_Units marked as featured, displaying for each slide the project image, price, area, and a call-to-action control.
2. WHILE the carousel is displayed and not hovered, THE Frontend_Renderer SHALL advance to the next slide at a fixed interval.
3. WHEN a Visitor hovers over the carousel, THE Frontend_Renderer SHALL pause automatic advancement until the pointer leaves the carousel.
4. WHEN a Visitor activates a carousel call-to-action control, THE Frontend_Renderer SHALL navigate to the corresponding Project_Detail_Page.
5. IF no Inventory_Units are marked as featured, THEN THE Frontend_Renderer SHALL omit the featured inventory carousel from the page.

### Requirement 7: Inventory Grid and Property Cards

**User Story:** As a Visitor, I want a grid of property cards, so that I can scan inventory and take immediate contact actions.

#### Acceptance Criteria

1. THE Card_View SHALL render each Inventory_Unit as a card displaying property image, project name, builder, location, price, area, floor, and a Unit_Status badge.
2. THE Card_View SHALL render on each card a "View Details" control, a "WhatsApp" control, and a "Call Now" control.
3. WHEN a Visitor activates the "View Details" control, THE Frontend_Renderer SHALL navigate to the corresponding Project_Detail_Page.
4. WHEN a Visitor activates the "WhatsApp" control, THE Frontend_Renderer SHALL open a WhatsApp message addressed to the configured contact number with a prefilled message identifying the Inventory_Unit.
5. WHEN a Visitor activates the "Call Now" control, THE Frontend_Renderer SHALL initiate a telephone dialing action to the configured contact number.
6. THE Card_View SHALL render each Unit_Status badge using the configured status color mapping.
7. WHEN a Visitor hovers over a property card, THE Card_View SHALL translate the card vertically by negative 8 pixels and expand the card shadow over a 300-millisecond transition.

### Requirement 8: View Mode Switching

**User Story:** As a Visitor, I want to switch between four inventory presentations, so that I can view inventory in the format that suits my task.

#### Acceptance Criteria

1. THE View_Manager SHALL provide controls to switch the inventory presentation between Card_View, Table_View, Broker_Sheet_View, and Map_View.
2. THE View_Manager SHALL present Card_View as the default inventory presentation on initial page load.
3. WHEN a Visitor selects a view mode, THE View_Manager SHALL render the current inventory result set in the selected view mode while preserving the active filters and the active search criteria independently.
4. THE View_Manager SHALL persist the Visitor's selected view mode for the duration of the browsing session.
5. IF the View_Manager cannot preserve the active filters and search criteria during a view switch, THEN THE View_Manager SHALL block the view switch and SHALL retain the current view mode.

### Requirement 9: Table View

**User Story:** As a Visitor, I want a tabular inventory view, so that I can compare units across structured columns.

#### Acceptance Criteria

1. THE Table_View SHALL render the current inventory result set as rows with columns for Project, BHK, Area, Floor, Price, and Unit_Status.
2. WHEN a Visitor activates a sortable column header, THE Table_View SHALL sort the displayed rows by that column in ascending order, and SHALL toggle to descending order on the next activation of the same header.
3. THE Table_View SHALL render the Unit_Status column value using the configured status color mapping.

### Requirement 10: Broker Sheet View

**User Story:** As a Broker_Agent, I want a dense "PREMIUM RESALE INVENTORY" sheet that matches the reference image, so that I can present and distribute inventory in the familiar broker-sheet format.

#### Acceptance Criteria

1. THE Broker_Sheet_View SHALL render a navy-colored header bar containing the title "PREMIUM RESALE INVENTORY".
2. THE Broker_Sheet_View SHALL render each Inventory_Unit as a dense row displaying the project name, the BHK and area and floor details, and the price column.
3. THE Broker_Sheet_View SHALL render price labels using the broker shorthand vocabulary, including "@ MP" for Market Price, payment-plan ratios in "P/p" notation, "U/C" for under-construction units, and "CR WITHOUT OC" annotations.
4. WHERE an Inventory_Unit offers multiple area or option variants, THE Broker_Sheet_View SHALL render the variants within a single project row consistent with the reference image layout (for example, "3 BHK 2215, 2520 SQ FT @ MP").
5. THE Broker_Sheet_View SHALL render a "Print" control, an "Export PDF" control, an "Export Excel" control, and a "Share WhatsApp" control.
6. WHEN a Visitor activates the "Print" control, THE Broker_Sheet_View SHALL open the browser print dialog with a print-optimized stylesheet applied to the sheet.
7. WHEN a Broker_Agent activates the "Export PDF" control, THE Export_Engine SHALL produce a PDF document of the current Broker_Sheet_View result set.
8. WHEN a Broker_Agent activates the "Export Excel" control, THE Export_Engine SHALL produce an Excel document of the current Broker_Sheet_View result set.
9. THE Broker_Sheet_View SHALL render the sheet so that its column layout, header styling, and row density visually match the supplied reference image at desktop viewport widths of 1024 pixels and above.
10. WHERE a user lacks the inventory-management capability, THE Broker_Sheet_View SHALL display the sheet for viewing but SHALL disable the Export PDF control and the Export Excel control.

### Requirement 11: Map View

**User Story:** As a Visitor, I want to see inventory on an interactive map, so that I can evaluate properties by geographic location.

#### Acceptance Criteria

1. THE Map_View SHALL render the current inventory result set as pins on an interactive Google Maps surface positioned at each unit's geographic coordinates.
2. THE Map_View SHALL render for each pin a label displaying the unit price and area.
3. WHEN a Visitor selects a map pin, THE Map_View SHALL display an information overlay with the project name, price, area, and a control linking to the Project_Detail_Page.
4. IF an Inventory_Unit lacks geographic coordinates, THEN THE Map_View SHALL omit that unit from the map and SHALL list the omitted unit in a fallback list adjacent to the map.
5. IF the Google Maps service fails to load, THEN THE Map_View SHALL display an error notice and SHALL offer a control to switch to Card_View.
6. WHILE the Google Maps service has not finished loading, THE Map_View SHALL defer rendering inventory pins until the service becomes available.

### Requirement 12: Project Detail Page

**User Story:** As a Visitor, I want a detailed project page, so that I can evaluate a project's units, pricing trend, and surroundings.

#### Acceptance Criteria

1. THE Project_Detail_Page SHALL render a hero banner with an image slider that advances automatically, supports a fullscreen mode, and lazy-loads images.
2. THE Project_Detail_Page SHALL render a project overview displaying project name, builder, location, possession status, and total tower count.
3. THE Project_Detail_Page SHALL render the project's live Inventory_Units with filter controls for 2 BHK, 3 BHK, 4 BHK, and Penthouse.
4. WHEN a Visitor applies a BHK filter on the Project_Detail_Page, THE Project_Detail_Page SHALL display only the units matching the selected BHK configuration.
5. THE Project_Detail_Page SHALL render a price trend widget that displays the project's price history for a selectable window of the last 6 months or the last 12 months.
6. THE Project_Detail_Page SHALL render nearby-location cards for Schools, Hospitals, Metro, Mall, and Airport.
7. WHEN a Visitor selects a nearby-location card, THE Project_Detail_Page SHALL display the associated nearby places for the project.

### Requirement 13: Advanced Filter System

**User Story:** As a Visitor, I want a comprehensive filter system, so that I can narrow inventory to my exact requirements on any device.

#### Acceptance Criteria

1. THE Filter_Engine SHALL provide filter controls for Project, Builder, Location, Sector, BHK, Area, Budget, Facing, Tower, Floor, and Unit_Status.
2. WHEN a Visitor applies one or more filters, THE Filter_Engine SHALL return only the Inventory_Units that satisfy every applied filter.
3. WHEN a Visitor applies a filter, THE Frontend_Renderer SHALL update the displayed inventory without a full page reload.
4. WHERE the viewport width is below 768 pixels, THE Frontend_Renderer SHALL present the filter controls in a bottom drawer that opens with a slide-up animation when a Visitor activates the filter control.
5. WHERE the viewport width is 768 pixels or above, THE Frontend_Renderer SHALL present the filter controls in a sidebar.
6. WHEN a Visitor clears all filters, THE Filter_Engine SHALL return the unfiltered inventory result set.
7. THE Frontend_Renderer SHALL display the count of matching Inventory_Units whenever the applied filters change.

### Requirement 14: Smart Natural-Language Search

**User Story:** As a Visitor, I want to search using natural language, so that I can find properties by typing requirements the way I think about them.

#### Acceptance Criteria

1. WHEN a Visitor submits a natural-language query expressing a BHK count and a budget (for example, "3 BHK under 3 Cr"), THE Smart_Search_Engine SHALL return Inventory_Units matching the extracted BHK count and priced at or below the extracted budget.
2. WHEN a Visitor submits a query containing a builder or project name (for example, "Godrej Air"), THE Smart_Search_Engine SHALL return Inventory_Units matching that builder or project name.
3. WHEN a Visitor submits a query containing a location name (for example, "Golf Course Extension Road"), THE Smart_Search_Engine SHALL return Inventory_Units located on or near that location.
4. WHILE a Visitor types in the smart search input, THE Smart_Search_Engine SHALL return updated matching results after the input pauses for 300 milliseconds.
5. IF a query matches no Inventory_Units, THEN THE Smart_Search_Engine SHALL return an empty result set and THE Frontend_Renderer SHALL display a no-results message with suggested alternative searches.

### Requirement 15: Lead Capture and Contact Actions

**User Story:** As a Visitor, I want easy ways to express interest and contact the broker, so that I can start a buying conversation quickly.

#### Acceptance Criteria

1. WHERE the viewport width is 768 pixels or above, THE Lead_Capture_System SHALL render a floating sticky bar containing a Call control, a WhatsApp control, and a Schedule Visit control.
2. WHEN a Visitor has remained on an inventory page for 10 seconds without submitting a lead, THE Lead_Capture_System SHALL display the lead popup once per browsing session.
3. WHEN a Visitor exhibits exit-intent pointer movement on a desktop viewport, THE Lead_Capture_System SHALL display the lead popup once per browsing session.
4. WHEN a Visitor activates a "View More" control, THE Lead_Capture_System SHALL display the lead popup.
5. THE Lead_Capture_System SHALL present a multi-step lead form with Step 1 collecting name and mobile number, Step 2 collecting email, and Step 3 collecting requirements.
6. WHEN a Visitor submits the lead form with a present name and a present mobile number, THE Lead_Capture_System SHALL persist the Lead with its capture source.
7. IF the name is missing, or the mobile number is missing, or the mobile number fails format validation, THEN THE Lead_Capture_System SHALL reject the submission, SHALL not persist the Lead, and SHALL display a field-level validation message.
8. WHEN a Lead is successfully persisted, THE Lead_Capture_System SHALL display a confirmation message to the Visitor.

### Requirement 16: Lead Management Kanban Board

**User Story:** As a Broker_Agent, I want a Kanban board for leads, so that I can move prospects through a sales pipeline.

#### Acceptance Criteria

1. THE Lead_Management_Board SHALL render pipeline columns for New, Contacted, Interested, Visit Scheduled, Negotiation, Closed, and Lost.
2. WHEN a captured Lead is persisted, THE Lead_Management_Board SHALL place the Lead in the New column.
3. WHEN a Broker_Agent drags a Lead card to a different column, THE Lead_Management_Board SHALL update the Lead pipeline stage to the target column and SHALL persist the change.
4. THE Lead_Management_Board SHALL display on each Lead card the lead name, mobile number, requirement summary, and capture source.
5. WHEN a Broker_Agent opens a Lead card, THE Lead_Management_Board SHALL display the full Lead detail including contact history.
6. THE Lead_Management_Board SHALL display the count of Leads in each pipeline column.

### Requirement 17: Enterprise Admin Dashboard

**User Story:** As an Administrator, I want a modern admin dashboard with key metrics, so that I can monitor business performance at a glance.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL render summary widgets for Revenue, Inventory count, Lead count, and Conversion rate.
2. THE Admin_Dashboard SHALL render charts for Lead Sources, Lead Funnel, Monthly Trends, and Inventory Trends.
3. THE Admin_Dashboard SHALL provide a dark mode and a light mode, and SHALL persist the selected mode per Administrator.
4. THE Admin_Dashboard SHALL compute each dashboard metric from the current persisted inventory, lead, and analytics data.
5. WHEN an Administrator selects a date range, THE Admin_Dashboard SHALL recompute the displayed widgets and charts for the selected range, and SHALL update the widgets and the charts independently as each completes recomputation.
6. THE Admin_Dashboard SHALL recompute the displayed widgets and charts only in response to an explicit date-range selection by an Administrator.

### Requirement 18: Inventory Management

**User Story:** As a Broker_Agent, I want to add and manage inventory units with full detail, so that I can maintain an accurate catalog.

#### Acceptance Criteria

1. THE Inventory_Manager SHALL provide an add-inventory form with fields for Project, Unit Number, Tower, Floor, Facing, BHK, Area, Price, Owner Name, Owner Phone, and Broker Notes.
2. WHEN a Broker_Agent submits a valid add-inventory form, THE Inventory_Manager SHALL persist a new Inventory_Unit and SHALL assign it the Available status by default.
3. THE Inventory_Manager SHALL allow a Broker_Agent to edit and to delete an existing Inventory_Unit.
4. THE Inventory_Manager SHALL support the Unit_Status values Available, Blocked, Token Received, Under Negotiation, and Sold.
5. THE Inventory_Manager SHALL map each Unit_Status to a distinct display color: Available to green, Blocked to orange, Token Received to blue, Under Negotiation to a distinct contrasting color, and Sold to gray.
6. WHEN a Broker_Agent changes the status of an Inventory_Unit, THE Inventory_Manager SHALL persist the new status and SHALL record the status-change timestamp.
7. IF a required add-inventory field is missing, THEN THE Inventory_Manager SHALL reject the submission and SHALL display a field-level validation message identifying the missing field.
8. THE Inventory_Manager SHALL restrict Owner Name and Owner Phone fields so that THE Frontend_Renderer does not display those fields to a Visitor.

### Requirement 19: Media Gallery Management

**User Story:** As a Broker_Agent, I want to upload and process property media, so that listings present rich, branded visuals.

#### Acceptance Criteria

1. THE Media_Gallery_Manager SHALL accept uploads of photos, videos, floor plans, and brochures for an Inventory_Unit or a Project.
2. THE Media_Gallery_Manager SHALL support drag-and-drop file selection for media upload.
3. WHEN a Broker_Agent uploads an image, THE Media_Gallery_Manager SHALL provide a crop control and SHALL store the cropped result.
4. WHEN a Broker_Agent uploads an image, THE Media_Gallery_Manager SHALL produce a compressed derivative of the image for frontend display.
5. WHERE watermarking is enabled in configuration, THE Media_Gallery_Manager SHALL apply the configured watermark to uploaded images.
6. IF an uploaded file size is greater than the configured maximum size, or the file uses a disallowed file type, THEN THE Media_Gallery_Manager SHALL reject the upload and SHALL display an error message identifying the constraint.
7. WHERE an uploaded file size is equal to the configured maximum size, THE Media_Gallery_Manager SHALL accept the upload.

### Requirement 20: Analytics and Engagement Tracking

**User Story:** As an Administrator, I want engagement analytics, so that I can understand which inventory and projects attract interest.

#### Acceptance Criteria

1. WHEN a Visitor views an inventory page, THE Analytics_Engine SHALL record a page-view event.
2. WHEN a Visitor views an Inventory_Unit detail, THE Analytics_Engine SHALL record an inventory-view event for that unit.
3. WHEN a Visitor activates a WhatsApp control, THE Analytics_Engine SHALL record a WhatsApp-click event.
4. WHEN a Visitor activates a Call Now control, THE Analytics_Engine SHALL record a phone-click event.
5. WHEN a Lead is persisted, THE Analytics_Engine SHALL record a lead-generated event.
6. THE Analytics_Engine SHALL aggregate recorded events into a Most Viewed Projects ranking and a Most Viewed Units ranking.
7. THE Analytics_Engine SHALL present the Most Viewed Projects ranking and the Most Viewed Units ranking as heatmap visualizations in the Admin_Dashboard.
8. IF no engagement events have been recorded, THEN THE Analytics_Engine SHALL defer generating rankings until engagement data exists.

### Requirement 21: AI Content Generation

**User Story:** As a Broker_Agent, I want AI to draft listing content, so that I can publish SEO-ready copy without writing it manually.

#### Acceptance Criteria

1. WHEN a Broker_Agent submits project details to the AI_Content_Generator, THE AI_Content_Generator SHALL return an SEO description, a project overview, and a WhatsApp message.
2. THE AI_Content_Generator SHALL present each generated output in an editable field before the Broker_Agent saves the content.
3. IF the AI generation request fails, THEN THE AI_Content_Generator SHALL display an error message and SHALL leave existing saved content unchanged.
4. WHEN a Broker_Agent requests tags from the AI_Tag_Generator for an Inventory_Unit, THE AI_Tag_Generator SHALL return classification tags drawn from a configured tag vocabulary including Luxury, Golf View, Corner Unit, Park Facing, Urgent Sale, and Investor Deal.
5. THE AI_Tag_Generator SHALL persist only the suggested tags that a Broker_Agent explicitly accepts, and SHALL not persist any suggested tag in the absence of explicit acceptance.

### Requirement 22: Export System

**User Story:** As a Broker_Agent, I want branded PDF and Excel exports, so that I can share inventory with clients and partners.

#### Acceptance Criteria

1. WHEN a Broker_Agent requests a PDF export, THE Export_Engine SHALL produce a PDF document containing the company logo, the inventory sheet, project images, price, and area.
2. WHEN a Broker_Agent requests an Excel export, THE Export_Engine SHALL produce an Excel workbook containing an Inventory sheet, a Projects sheet, and a Leads sheet.
3. THE Export_Engine SHALL include in each export only the Inventory_Units present in the current filtered result set at the time of the export request.
4. THE Export_Engine SHALL exclude Owner Name and Owner Phone fields from exports generated by a user without the inventory-management capability.
5. IF an export request produces no records, THEN THE Export_Engine SHALL display a notice that no records are available and SHALL not generate an empty document.

### Requirement 23: Security and Compliance

**User Story:** As a site owner, I want the plugin to follow security best practices, so that my site and customer data remain protected.

#### Acceptance Criteria

1. WHEN any state-changing request is submitted to RIMS_Pro, THE Security_Layer SHALL validate a WordPress nonce and SHALL reject the request if the nonce is absent or invalid.
2. THE Security_Layer SHALL escape all dynamic output rendered into HTML so that user-supplied values cannot inject executable script.
3. THE Security_Layer SHALL execute all database queries using parameterized statements so that user-supplied values cannot alter query structure.
4. WHEN a request invokes a management action, THE Security_Layer SHALL verify that the requesting user holds the required WordPress capability and SHALL reject the request if the capability is absent.
5. WHEN a single client submits more than the configured number of form or API requests within the configured time window, THE Security_Layer SHALL reject additional requests from that client until the window resets.
6. THE Security_Layer SHALL validate every input field against its expected type and length before the input is persisted.
7. THE Security_Layer SHALL serve all RIMS_Pro REST_API and AJAX endpoints over HTTPS.

### Requirement 24: Performance

**User Story:** As a Visitor, I want fast-loading inventory pages, so that I can browse without delay.

#### Acceptance Criteria

1. THE Frontend_Renderer SHALL lazy-load inventory images so that off-screen images are requested only as they approach the viewport.
2. WHEN a Visitor navigates between inventory result pages, THE Frontend_Renderer SHALL load the next page of results by AJAX without a full page reload.
3. THE RIMS_Pro frontend inventory landing page SHALL achieve a Lighthouse Performance score of 90 or above on a desktop test profile.
4. THE RIMS_Pro frontend inventory landing page SHALL meet the Google "good" thresholds for Core_Web_Vitals on a desktop test profile.
5. WHEN the REST_API receives an inventory list request under the configured reference dataset, THE REST_API SHALL return the response within 300 milliseconds at the server.
6. THE Cache_Manager SHALL cache inventory query results and SHALL serve cached results until the affected cache entries are invalidated.
7. WHERE a Redis object cache backend is configured, THE Cache_Manager SHALL store cached query results in the Redis backend regardless of any other configured cache backend.
8. WHEN underlying inventory data changes, THE Cache_Manager SHALL invalidate the affected cached query results within the configured invalidation delay.

### Requirement 25: Premium Frontend Experience

**User Story:** As a Visitor, I want a premium, modern interface with dark mode and smooth interactions, so that the platform feels like a high-end PropTech product.

#### Acceptance Criteria

1. THE Frontend_Renderer SHALL provide a dark mode and a light mode with an animated toggle control.
2. WHEN a Visitor toggles the display mode, THE Frontend_Renderer SHALL apply the selected mode and SHALL persist the selection across page navigations within the session.
3. THE Frontend_Renderer SHALL render inventory pages using a glassmorphism, card-based visual style.
4. WHILE inventory data is loading, THE Frontend_Renderer SHALL display skeleton placeholders, and SHALL transition loaded content with a fade-in animation, a slide-up animation, or both.
5. WHERE infinite scroll is enabled, THE Frontend_Renderer SHALL load and append the next set of Inventory_Units as the Visitor scrolls toward the end of the current set.

### Requirement 26: Bookmarks, Comparison, and Recently Viewed

**User Story:** As a Visitor, I want to save, compare, and revisit properties, so that I can manage my shortlist while browsing.

#### Acceptance Criteria

1. WHEN a Visitor bookmarks an Inventory_Unit, THE Bookmark_Manager SHALL save the unit to the Visitor's favorites and SHALL make the favorites retrievable within the session.
2. WHEN a Visitor selects an Inventory_Unit for comparison, THE Comparison_Engine SHALL add the unit to the comparison set up to a maximum of 4 units.
3. IF a Visitor attempts to add a fifth unit to the comparison set, THEN THE Comparison_Engine SHALL reject the addition and SHALL display a message that the comparison limit is 4 units.
4. WHEN a Visitor opens the comparison view, THE Comparison_Engine SHALL render the selected units side by side in a comparison table of their attributes.
5. WHEN a Visitor views an Inventory_Unit, THE Recently_Viewed_Tracker SHALL record the unit identifier in a browser cookie.
6. THE Recently_Viewed_Tracker SHALL display the recently viewed Inventory_Units from the browser cookie in most-recent-first order.

### Requirement 27: Sharing and QR Codes

**User Story:** As a Visitor or Broker_Agent, I want to share listings and generate QR codes, so that I can distribute inventory across channels.

#### Acceptance Criteria

1. THE Share_Manager SHALL provide share controls for WhatsApp, Facebook, LinkedIn, Telegram, and Email on each Inventory_Unit.
2. WHEN a Visitor activates a share control, THE Share_Manager SHALL open the corresponding channel with a prefilled link to the Inventory_Unit.
3. THE QR_Code_Generator SHALL generate a QR code for each Inventory_Unit and each Project that encodes the public URL of that record.
4. WHEN a QR code is scanned, THE QR code SHALL resolve to the public detail page of the encoded record.

### Requirement 28: PWA and Push Notifications

**User Story:** As a Visitor, I want to install the inventory app and receive new-inventory alerts, so that I can stay informed.

#### Acceptance Criteria

1. THE PWA_Service SHALL serve a web app manifest and a service worker so that the RIMS_Pro frontend is installable as a Progressive Web App.
2. WHILE the device is offline, THE PWA_Service SHALL serve a cached application shell.
3. WHERE a Visitor has granted notification permission, THE Notification_Service SHALL send a push notification when a new Inventory_Unit matching the Visitor's saved alert criteria is published.
4. IF a Visitor has not granted notification permission, THEN THE Notification_Service SHALL not send push notifications to that Visitor.

### Requirement 29: Inventory Auto-Expiry

**User Story:** As an Administrator, I want stale inventory to expire automatically, so that the catalog remains current without manual cleanup.

#### Acceptance Criteria

1. THE Expiry_Manager SHALL expose a configurable retention period in days for Inventory_Units.
2. WHEN an Inventory_Unit's age since publication exceeds the configured retention period, THE Expiry_Manager SHALL mark the unit as expired and SHALL exclude the unit from frontend inventory results.
3. THE Expiry_Manager SHALL notify the owning Broker_Agent when an Inventory_Unit is expired.
4. WHERE an Administrator renews an expired Inventory_Unit, THE Expiry_Manager SHALL restore the unit to its prior status and SHALL reset the retention timer.

### Requirement 30: CRM Integration

**User Story:** As an Administrator, I want captured leads forwarded to my CRM, so that my sales team works leads in their existing tools.

#### Acceptance Criteria

1. THE CRM_Integration_Service SHALL support configurable integrations with Sell.Do, LeadSquared, HubSpot, and Zoho CRM.
2. WHEN a Lead is persisted and a CRM integration is enabled, THE CRM_Integration_Service SHALL forward the Lead to the configured CRM platform.
3. IF a CRM forwarding request fails, THEN THE CRM_Integration_Service SHALL retry the forwarding according to the configured retry policy and SHALL record the failure in the Administrator log.
4. THE CRM_Integration_Service SHALL store CRM credentials in encrypted form.
5. THE CRM_Integration_Service SHALL require a configured retry policy before forwarding any Lead to a CRM platform.

### Requirement 31: REST API and Mobile Readiness

**User Story:** As a future mobile app developer, I want a documented REST API, so that Android and iOS apps can consume inventory data.

#### Acceptance Criteria

1. THE REST_API SHALL expose endpoints to list and retrieve Inventory_Units, Projects, and Leads.
2. THE REST_API SHALL return responses in JSON format.
3. WHEN the REST_API receives a write-operation request, THE REST_API SHALL require a valid authentication token and SHALL reject the request if the token is missing, invalid, or expired.
4. THE REST_API SHALL support pagination parameters for list endpoints and SHALL return the total record count with each paginated response.
5. THE REST_API SHALL version its endpoints under a versioned URL namespace so that future changes do not break existing clients.

### Requirement 32: SEO

**User Story:** As a site owner, I want inventory and project pages to be search-engine optimized, so that listings attract organic traffic.

#### Acceptance Criteria

1. THE SEO_Engine SHALL generate a crawlable, human-readable URL for each Inventory_Unit and each Project.
2. THE SEO_Engine SHALL generate a unique title tag and meta description for each Inventory_Unit and each Project from the record data.
3. THE SEO_Engine SHALL emit structured data markup for each Project describing the real estate listing.
4. THE SEO_Engine SHALL include published Inventory_Unit and Project URLs in an XML sitemap.

### Requirement 33: Multi-Tenant White-Label Readiness

**User Story:** As the product owner, I want the plugin architected for white-label SaaS resale, so that I can sell it to multiple brokers and builders.

#### Acceptance Criteria

1. THE RIMS_Pro plugin SHALL store configurable branding settings including company name, logo, primary color, and contact details.
2. THE RIMS_Pro plugin SHALL apply the configured branding to frontend pages, exports, and notifications.
3. WHERE the deployment is configured for multi-tenant operation, THE RIMS_Pro plugin SHALL scope inventory, project, and lead data to the owning Tenant so that one Tenant cannot read or modify another Tenant's data.
4. THE RIMS_Pro plugin SHALL allow each Tenant's branding and contact configuration to be set independently of other Tenants.
