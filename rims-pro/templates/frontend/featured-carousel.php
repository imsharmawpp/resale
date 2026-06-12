<?php
/** @var array $featured_items */
$items = $featured_items ?? ( $featured ?? [] );
if ( empty( $items ) ) {
    return;
}
?>
<section class="rims-carousel" x-data="rimsCarousel(<?php echo (int) count( $items ); ?>)" @mouseenter="paused=true" @mouseleave="paused=false">
    <h2 class="rims-carousel__title">Featured Listings</h2>
    <div class="rims-carousel__track">
        <?php foreach ( $items as $i => $u ) :
            /** @var \RimsPro\Domain\Inventory_Unit $u */
            ?>
            <article class="rims-carousel__slide" :class="{ 'rims-carousel__slide--active': active === <?php echo (int) $i; ?> }">
                <div class="rims-carousel__image" style="background:linear-gradient(135deg,#1e3a8a,#7c3aed)"></div>
                <div class="rims-carousel__body">
                    <h3><?php echo esc_html( $u->project_name ); ?></h3>
                    <p><?php echo esc_html( ( $u->bhk ) . ' BHK | ' . $u->area_sqft . ' sqft' ); ?></p>
                    <p><strong><?php echo esc_html( $u->price_label !== '' ? $u->price_label : ( '₹ ' . number_format( $u->price_paise / 100 ) ) ); ?></strong></p>
                    <a class="rims-button rims-button--primary" href="<?php echo esc_url( $u->publicUrl() ); ?>">View Project</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
