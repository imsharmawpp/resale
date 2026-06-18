<?php
/** @var array $rows */
/** @var bool  $can_export */
/** @var array $branding */
?>
<section class="rims-broker-sheet rims-broker-sheet--full">
    <header class="rims-broker-sheet__header">
        <h1>PREMIUM RESALE INVENTORY</h1>
        <div class="rims-broker-sheet__brand">
            <?php if ( ! empty( $branding['logo_url'] ) ) : ?>
                <img src="<?php echo esc_url( (string) $branding['logo_url'] ); ?>" alt="<?php echo esc_attr( (string) ( $branding['company_name'] ?? '' ) ); ?>">
            <?php endif; ?>
            <strong><?php echo esc_html( (string) ( $branding['company_name'] ?? 'Brokerage' ) ); ?></strong>
        </div>
    </header>
    <div class="rims-broker-sheet__controls rims-no-print">
        <button class="rims-button rims-button--ghost" onclick="window.print()">Print</button>
        <?php if ( $can_export ) : ?>
            <a class="rims-button rims-button--primary" href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=rims_export&format=pdf' ) ); ?>">Export PDF</a>
            <a class="rims-button rims-button--primary" href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=rims_export&format=xlsx' ) ); ?>">Export Excel</a>
        <?php else : ?>
            <button class="rims-button rims-button--primary" disabled>Export PDF</button>
            <button class="rims-button rims-button--primary" disabled>Export Excel</button>
        <?php endif; ?>
        <a class="rims-button rims-button--whatsapp" href="https://wa.me/?text=<?php echo rawurlencode( home_url( '/broker-sheet' ) ); ?>">Share WhatsApp</a>
    </div>
    <table class="rims-broker-sheet__table">
        <thead>
            <tr>
                <th>Project</th>
                <th>BHK / Area</th>
                <th>Floor</th>
                <th>Price</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $rows as $r ) : ?>
                <tr>
                    <td><?php echo esc_html( (string) $r['project'] ); ?></td>
                    <td><?php echo esc_html( $r['bhk'] . ' BHK ' . implode( ', ', $r['areas'] ) . ' SQ FT' ); ?></td>
                    <td><?php echo esc_html( (string) $r['floor'] ); ?></td>
                    <td><?php echo esc_html( (string) $r['price_label'] ); ?></td>
                    <td><span class="rims-status" data-status="<?php echo esc_attr( (string) $r['status'] ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $r['status'] ) ) ); ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
