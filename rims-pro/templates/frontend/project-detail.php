<?php
/** @var \RimsPro\Domain\Project $project */
/** @var array $units */
/** @var array $nearby */
/** @var array $branding */
?>
<article class="rims-project-detail">
    <header class="rims-project-detail__hero">
        <h1><?php echo esc_html( $project->name ); ?></h1>
        <p><?php echo esc_html( $project->builder . ' · ' . $project->location ); ?></p>
        <p><?php echo esc_html( $project->possession_status . ' · ' . $project->tower_count . ' towers' ); ?></p>
    </header>
    <section class="rims-project-detail__overview">
        <h2>Overview</h2>
        <p><?php echo esc_html( $project->overview ); ?></p>
    </section>
    <section class="rims-project-detail__units" x-data="rimsProjectUnits()">
        <h2>Live Units</h2>
        <div class="rims-project-detail__filters">
            <button @click="bhk=null">All</button>
            <button @click="bhk=2">2 BHK</button>
            <button @click="bhk=3">3 BHK</button>
            <button @click="bhk=4">4 BHK</button>
            <button @click="bhk='ph'">Penthouse</button>
        </div>
        <ul class="rims-project-detail__list">
            <?php foreach ( $units as $u ) : /** @var \RimsPro\Domain\Inventory_Unit $u */ ?>
                <li data-bhk="<?php echo esc_attr( (string) $u->bhk ); ?>" data-penthouse="<?php echo $u->is_penthouse ? '1' : '0'; ?>">
                    <a href="<?php echo esc_url( $u->publicUrl() ); ?>"><?php echo esc_html( $u->bhk . ' BHK | ' . $u->area_sqft . ' sqft | ' . $u->price_label ); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="rims-project-detail__trend">
        <h2>Price Trend</h2>
        <canvas id="rims-trend" height="160"></canvas>
        <div class="rims-project-detail__trend-controls">
            <button data-window="6">6 months</button>
            <button data-window="12">12 months</button>
        </div>
    </section>
    <section class="rims-project-detail__nearby">
        <h2>Nearby</h2>
        <ul>
            <?php foreach ( $nearby as $n ) : ?>
                <li><strong><?php echo esc_html( ucfirst( (string) $n['category'] ) ); ?>:</strong> <?php echo esc_html( $n['name'] . ' (' . $n['distance_km'] . ' km)' ); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</article>
<script>
window.RIMS_PROJECT = <?php echo wp_json_encode( [ 'project' => get_object_vars( $project ) ] ); ?>;
</script>
