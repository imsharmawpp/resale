<?php
/** @var array{available:int,projects:int,builders:int,deals:int} $stats */
if ( ! isset( $stats ) ) { return; }
?>
<section class="rims-hero" x-data="rimsHero()">
    <div class="rims-hero__bg" aria-hidden="true">
        <div class="rims-hero__particles"></div>
        <div class="rims-hero__overlay"></div>
    </div>
    <div class="rims-hero__inner">
        <h1 class="rims-hero__title">Premium Resale Inventory</h1>
        <p class="rims-hero__subtitle">Live, broker-curated apartments. Filter, compare, share.</p>
        <form class="rims-hero__search" @submit.prevent="submit()">
            <input type="text" class="rims-hero__input" name="q" placeholder="Project, location, builder..." x-model="q" aria-label="Search">
            <select class="rims-hero__input" x-model="bhk" aria-label="BHK">
                <option value="">Any BHK</option>
                <option value="2">2 BHK</option>
                <option value="3">3 BHK</option>
                <option value="4">4 BHK</option>
                <option value="5">5 BHK / Penthouse</option>
            </select>
            <select class="rims-hero__input" x-model="budget" aria-label="Budget">
                <option value="">Any budget</option>
                <option value="10000000">Under 1 Cr</option>
                <option value="30000000">Under 3 Cr</option>
                <option value="50000000">Under 5 Cr</option>
                <option value="100000000">Under 10 Cr</option>
            </select>
            <button type="submit" class="rims-button rims-button--primary">Search</button>
        </form>
    </div>
</section>

<section class="rims-stats" x-data="rimsStats(<?php echo (int) $stats['available'] . ',' . (int) $stats['projects'] . ',' . (int) $stats['builders'] . ',' . (int) $stats['deals']; ?>)" x-intersect="animate()">
    <div class="rims-stats__card">
        <span class="rims-stats__num" x-text="available"></span>
        <span class="rims-stats__label">Available Inventory</span>
    </div>
    <div class="rims-stats__card">
        <span class="rims-stats__num" x-text="projects"></span>
        <span class="rims-stats__label">Projects</span>
    </div>
    <div class="rims-stats__card">
        <span class="rims-stats__num" x-text="builders"></span>
        <span class="rims-stats__label">Builders</span>
    </div>
    <div class="rims-stats__card">
        <span class="rims-stats__num" x-text="deals"></span>
        <span class="rims-stats__label">Resale Deals</span>
    </div>
</section>
