<?php
/** @var array $units */
/** @var array $projects */
?>
<div class="wrap rims-admin">
    <h1>RIMS Pro - Inventory</h1>
    <?php settings_errors( 'rims_pro_inventory' ); ?>

    <form method="post">
        <?php wp_nonce_field( 'rims_pro_inventory' ); ?>
        <input type="hidden" name="rims_action" value="create">
        <h2>Add Unit</h2>
        <table class="form-table">
            <tr>
                <th><label for="rims_project">Project</label></th>
                <td>
                    <select name="project_id" id="rims_project">
                        <option value="">- Select -</option>
                        <?php foreach ( $projects as $p ) : ?>
                            <option value="<?php echo (int) $p->id; ?>"><?php echo esc_html( $p->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr><th><label>Unit Number</label></th><td><input type="text" name="unit_number" required></td></tr>
            <tr><th><label>Tower</label></th><td><input type="text" name="tower"></td></tr>
            <tr><th><label>Floor</label></th><td><input type="number" name="floor"></td></tr>
            <tr><th><label>Facing</label></th><td>
                <select name="facing">
                    <?php foreach ( [ 'N','S','E','W','NE','NW','SE','SW' ] as $f ) : ?>
                        <option value="<?php echo $f; ?>"><?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th><label>BHK</label></th><td><input type="number" step="0.5" name="bhk" required></td></tr>
            <tr><th><label>Area (sqft)</label></th><td><input type="number" name="area_sqft" required></td></tr>
            <tr><th><label>Price (rupees)</label></th><td><input type="number" step="0.01" name="price" required></td></tr>
            <tr><th><label>Owner Name (internal)</label></th><td><input type="text" name="owner_name"></td></tr>
            <tr><th><label>Owner Phone (internal)</label></th><td><input type="tel" name="owner_phone"></td></tr>
            <tr><th><label>Broker Notes (internal)</label></th><td><textarea name="broker_notes"></textarea></td></tr>
        </table>
        <button class="button button-primary" type="submit">Save</button>
    </form>

    <h2>Existing Units</h2>
    <table class="wp-list-table widefat striped">
        <thead><tr>
            <th>ID</th><th>Project</th><th>Unit</th><th>BHK</th><th>Area</th><th>Price</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
            <?php foreach ( $units as $u ) : ?>
                <tr>
                    <td><?php echo (int) $u->id; ?></td>
                    <td><?php echo esc_html( $u->project_name ); ?></td>
                    <td><?php echo esc_html( $u->unit_number ); ?></td>
                    <td><?php echo esc_html( (string) $u->bhk ); ?></td>
                    <td><?php echo (int) $u->area_sqft; ?> sqft</td>
                    <td>₹ <?php echo number_format( $u->price_paise / 100 ); ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <?php wp_nonce_field( 'rims_pro_inventory' ); ?>
                            <input type="hidden" name="rims_action" value="change_status">
                            <input type="hidden" name="id" value="<?php echo (int) $u->id; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach ( \RimsPro\Domain\UnitStatus::cases() as $s ) : ?>
                                    <option value="<?php echo $s->value; ?>" <?php selected( $u->status, $s->value ); ?>><?php echo esc_html( $s->label() ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete unit?');" style="display:inline">
                            <?php wp_nonce_field( 'rims_pro_inventory' ); ?>
                            <input type="hidden" name="rims_action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $u->id; ?>">
                            <button class="button-link-delete" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
