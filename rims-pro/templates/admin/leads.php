<?php
/** @var array $columns */
?>
<div class="wrap rims-admin">
    <h1>RIMS Pro - Leads</h1>
    <div class="rims-kanban">
        <?php foreach ( $columns as $stage => $col ) : ?>
            <div class="rims-kanban__col" data-stage="<?php echo esc_attr( $stage ); ?>">
                <h3><?php echo esc_html( $col['label'] . ' (' . count( $col['leads'] ) . ')' ); ?></h3>
                <?php foreach ( $col['leads'] as $l ) : /** @var \RimsPro\Domain\Lead $l */ ?>
                    <div class="rims-kanban__card" draggable="true" data-id="<?php echo (int) $l->id; ?>">
                        <strong><?php echo esc_html( $l->name ); ?></strong>
                        <small><?php echo esc_html( $l->mobile ); ?></small>
                        <p><?php echo esc_html( substr( (string) ( $l->requirements ?? '' ), 0, 80 ) ); ?></p>
                        <span class="rims-kanban__source">Source: <?php echo esc_html( $l->source ); ?></span>
                        <form method="post" style="margin-top:6px">
                            <?php wp_nonce_field( 'rims_pro_leads' ); ?>
                            <input type="hidden" name="rims_lead_action" value="move">
                            <input type="hidden" name="id" value="<?php echo (int) $l->id; ?>">
                            <select name="stage" onchange="this.form.submit()">
                                <?php foreach ( \RimsPro\Domain\PipelineStage::cases() as $s ) : ?>
                                    <option value="<?php echo $s->value; ?>" <?php selected( $l->pipeline_stage, $s->value ); ?>><?php echo esc_html( $s->label() ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
