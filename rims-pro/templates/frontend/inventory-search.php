<?php
?>
<section class="rims-search" x-data="rimsSmartSearch()">
    <input type="search" class="rims-search__input" x-model="query" @input.debounce.300ms="search()" placeholder="e.g. 3 BHK in Whitefield under 2 Cr">
    <div class="rims-search__results">
        <template x-if="results.length === 0 && query.length > 2">
            <p class="rims-search__empty">No results. Try a different builder, location, or budget.</p>
        </template>
        <template x-for="r in results" :key="r.id">
            <a class="rims-search__row" :href="rimsUnitUrl(r)">
                <strong x-text="r.project_name"></strong>
                <span x-text="`${r.bhk} BHK | ${r.area_sqft} sqft | ${r.location}`"></span>
            </a>
        </template>
    </div>
</section>
