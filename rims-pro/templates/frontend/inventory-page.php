<?php
/**
 * @var int   $tenant_id
 * @var array $branding
 * @var array $featured
 * @var array $stats
 * @var array $page
 * @var array $items_arr
 */
$serial = $serial ?? null;
$units  = $page['items'] ?? [];
?>
<?php include __DIR__ . '/hero-stats.php'; ?>

<?php if ( ! empty( $featured ) ) : ?>
    <?php $featured_items = $featured; include __DIR__ . '/featured-carousel.php'; ?>
<?php endif; ?>

<section class="rims-listing" x-data="rimsListing()">
    <header class="rims-listing__header">
        <div class="rims-listing__view-modes" role="tablist">
            <button type="button" class="rims-tab" :class="{ 'rims-tab--active': mode === 'card' }" @click="setMode('card')">Card</button>
            <button type="button" class="rims-tab" :class="{ 'rims-tab--active': mode === 'table' }" @click="setMode('table')">Table</button>
            <button type="button" class="rims-tab" :class="{ 'rims-tab--active': mode === 'broker_sheet' }" @click="setMode('broker_sheet')">Broker Sheet</button>
            <button type="button" class="rims-tab" :class="{ 'rims-tab--active': mode === 'map' }" @click="setMode('map')">Map</button>
        </div>
        <div class="rims-listing__count" x-text="`${count} matching units`"></div>
        <button class="rims-button rims-button--ghost" @click="toggleColorMode()">
            <span x-show="colorMode === 'light'">Dark</span>
            <span x-show="colorMode === 'dark'">Light</span>
        </button>
    </header>

    <aside class="rims-filters" :class="{ 'rims-filters--open': drawerOpen }">
        <div class="rims-filters__group">
            <label>Builder<input type="text" x-model="filters.builder"></label>
            <label>Location<input type="text" x-model="filters.location"></label>
            <label>BHK
                <select x-model="filters.bhk">
                    <option value="">Any</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </label>
            <label>Status
                <select x-model="filters.status">
                    <option value="">Any</option>
                    <option value="available">Available</option>
                    <option value="blocked">Blocked</option>
                    <option value="token_received">Token Received</option>
                    <option value="under_negotiation">Under Negotiation</option>
                    <option value="sold">Sold</option>
                </select>
            </label>
            <label>Min area<input type="number" x-model.number="filters.area_min"></label>
            <label>Max area<input type="number" x-model.number="filters.area_max"></label>
            <label>Max budget (paise)<input type="number" x-model.number="filters.budget_max"></label>
            <button class="rims-button rims-button--primary" type="button" @click="applyFilters()">Apply</button>
            <button class="rims-button rims-button--ghost" type="button" @click="clearFilters()">Clear</button>
        </div>
    </aside>

    <main class="rims-results">
        <template x-if="mode === 'card'">
            <div class="rims-cards">
                <template x-for="u in items" :key="u.id">
                    <article class="rims-card" x-data x-bind:data-status="u.status">
                        <div class="rims-card__image" :style="`background-image:url(${u.image_url || ''})`"></div>
                        <div class="rims-card__body">
                            <h3 class="rims-card__title" x-text="u.project_name"></h3>
                            <p class="rims-card__sub" x-text="`${u.builder} | ${u.location}`"></p>
                            <p class="rims-card__price" x-text="u.price_label || rimsFormatPaise(u.price_paise)"></p>
                            <p class="rims-card__meta" x-text="`${u.bhk} BHK | ${u.area_sqft} sqft | Floor ${u.floor}`"></p>
                            <span class="rims-status" :style="`background:${rimsStatusColor(u.status)}`" x-text="rimsStatusLabel(u.status)"></span>
                        </div>
                        <footer class="rims-card__actions">
                            <a class="rims-button rims-button--ghost" :href="rimsUnitUrl(u)">View Details</a>
                            <a class="rims-button rims-button--whatsapp" :href="rimsWhatsappLink(u)">WhatsApp</a>
                            <a class="rims-button rims-button--primary" :href="rimsCallLink()">Call Now</a>
                        </footer>
                    </article>
                </template>
            </div>
        </template>

        <template x-if="mode === 'table'">
            <table class="rims-table">
                <thead>
                    <tr>
                        <th @click="sort('project')">Project</th>
                        <th @click="sort('bhk')">BHK</th>
                        <th @click="sort('area')">Area</th>
                        <th @click="sort('floor')">Floor</th>
                        <th @click="sort('price')">Price</th>
                        <th @click="sort('status')">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="u in items" :key="u.id">
                        <tr>
                            <td x-text="u.project_name"></td>
                            <td x-text="u.bhk"></td>
                            <td x-text="`${u.area_sqft} sqft`"></td>
                            <td x-text="u.floor"></td>
                            <td x-text="u.price_label || rimsFormatPaise(u.price_paise)"></td>
                            <td><span class="rims-status" :style="`background:${rimsStatusColor(u.status)}`" x-text="rimsStatusLabel(u.status)"></span></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

        <template x-if="mode === 'broker_sheet'">
            <div class="rims-broker-sheet">
                <header class="rims-broker-sheet__header">
                    <strong>PREMIUM RESALE INVENTORY</strong>
                </header>
                <table class="rims-broker-sheet__table">
                    <thead>
                        <tr><th>Project</th><th>BHK / Area</th><th>Floor</th><th>Price</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <template x-for="r in brokerRows" :key="r.project + r.bhk">
                            <tr>
                                <td x-text="r.project"></td>
                                <td x-text="`${r.bhk} BHK ${r.areas.join(', ')} SQ FT`"></td>
                                <td x-text="r.floor"></td>
                                <td x-text="r.price_label"></td>
                                <td><span class="rims-status" :style="`background:${rimsStatusColor(r.status)}`" x-text="rimsStatusLabel(r.status)"></span></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <template x-if="mode === 'map'">
            <div class="rims-map">
                <div id="rims-map-canvas" style="height:60vh;background:#e5e7eb"></div>
                <ul class="rims-map__fallback">
                    <template x-for="u in items.filter(u => !u.latitude || !u.longitude)" :key="u.id">
                        <li><a :href="rimsUnitUrl(u)" x-text="`${u.project_name} - ${u.bhk} BHK, ${u.area_sqft} sqft`"></a></li>
                    </template>
                </ul>
            </div>
        </template>
    </main>

    <footer class="rims-listing__footer">
        <button class="rims-button rims-button--primary" type="button" @click="loadMore()" x-show="canLoadMore">Load more</button>
    </footer>
</section>

<script type="application/json" id="rims-initial-state">
<?php echo wp_json_encode( [ 'items' => $items_arr ] ); ?>
</script>
