<?php
/** @var \RimsPro\Domain\Inventory_Unit $unit */
/** @var array $payload */
/** @var array $contact_links */
?>
<article class="rims-unit-detail">
    <header class="rims-unit-detail__hero">
        <h1><?php echo esc_html( $unit->project_name . ' - ' . $unit->bhk . ' BHK' ); ?></h1>
        <p><?php echo esc_html( $unit->builder . ' · ' . $unit->location ); ?></p>
        <p class="rims-unit-detail__price"><strong><?php echo esc_html( $unit->price_label !== '' ? $unit->price_label : '₹ ' . number_format( $unit->price_paise / 100 ) ); ?></strong></p>
    </header>
    <section class="rims-unit-detail__grid">
        <dl>
            <dt>Unit Number</dt><dd><?php echo esc_html( $unit->unit_number ); ?></dd>
            <dt>Tower</dt><dd><?php echo esc_html( $unit->tower ); ?></dd>
            <dt>Floor</dt><dd><?php echo esc_html( (string) $unit->floor ); ?></dd>
            <dt>Facing</dt><dd><?php echo esc_html( $unit->facing ); ?></dd>
            <dt>Carpet Area</dt><dd><?php echo esc_html( $unit->area_sqft . ' sqft' ); ?></dd>
            <dt>Status</dt><dd><span class="rims-status" data-status="<?php echo esc_attr( $unit->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $unit->status ) ) ); ?></span></dd>
        </dl>
        <?php if ( current_user_can( 'manage_options' ) ) : ?>
            <aside class="rims-unit-detail__owner rims-internal">
                <h2>Owner Contact (internal)</h2>
                <p><?php echo esc_html( $unit->owner_name ); ?></p>
                <p><?php echo esc_html( $unit->owner_phone ); ?></p>
                <p><?php echo esc_html( $unit->broker_notes ); ?></p>
            </aside>
        <?php endif; ?>
    </section>
    <footer class="rims-unit-detail__actions">
        <a class="rims-button rims-button--whatsapp" href="<?php echo esc_url( $contact_links['whatsapp'] ); ?>">WhatsApp</a>
        <a class="rims-button rims-button--primary" href="<?php echo esc_url( $contact_links['tel'] ); ?>">Call Now</a>
    </footer>
</article>
