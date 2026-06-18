<?php
/** @var array $metrics */
/** @var array $top_proj */
/** @var array $top_unit */
?>
<div class="wrap rims-admin">
    <h1>RIMS Pro - Dashboard</h1>

    <div class="rims-admin__widgets">
        <div class="rims-admin__widget">
            <h3>Revenue (30d)</h3>
            <p class="rims-admin__metric">₹ <?php echo number_format( ( $metrics['revenue_paise'] ?? 0 ) / 100 ); ?></p>
        </div>
        <div class="rims-admin__widget">
            <h3>Inventory Added</h3>
            <p class="rims-admin__metric"><?php echo (int) ( $metrics['inventory_count'] ?? 0 ); ?></p>
        </div>
        <div class="rims-admin__widget">
            <h3>Leads</h3>
            <p class="rims-admin__metric"><?php echo (int) ( $metrics['lead_count'] ?? 0 ); ?></p>
        </div>
        <div class="rims-admin__widget">
            <h3>Conversion</h3>
            <p class="rims-admin__metric"><?php echo number_format( ( $metrics['conversion'] ?? 0 ) * 100, 1 ); ?>%</p>
        </div>
    </div>

    <div class="rims-admin__charts">
        <div class="rims-admin__chart">
            <h3>Lead Sources</h3>
            <canvas id="rims-chart-sources" height="200"></canvas>
        </div>
        <div class="rims-admin__chart">
            <h3>Lead Funnel</h3>
            <canvas id="rims-chart-funnel" height="200"></canvas>
        </div>
        <div class="rims-admin__chart">
            <h3>Monthly Trends</h3>
            <canvas id="rims-chart-monthly" height="200"></canvas>
        </div>
        <div class="rims-admin__chart">
            <h3>Inventory Trends</h3>
            <canvas id="rims-chart-inventory" height="200"></canvas>
        </div>
    </div>

    <div class="rims-admin__heatmaps">
        <h3>Most-Viewed Projects</h3>
        <ol>
            <?php foreach ( $top_proj as $row ) : ?>
                <li>Project #<?php echo (int) $row['entity_id']; ?> - <?php echo (int) $row['count']; ?> views</li>
            <?php endforeach; ?>
            <?php if ( empty( $top_proj ) ) : ?>
                <li><em>Rankings will appear once visitors view inventory.</em></li>
            <?php endif; ?>
        </ol>
        <h3>Most-Viewed Units</h3>
        <ol>
            <?php foreach ( $top_unit as $row ) : ?>
                <li>Unit #<?php echo (int) $row['entity_id']; ?> - <?php echo (int) $row['count']; ?> views</li>
            <?php endforeach; ?>
            <?php if ( empty( $top_unit ) ) : ?>
                <li><em>Rankings will appear once visitors view inventory.</em></li>
            <?php endif; ?>
        </ol>
    </div>
</div>
