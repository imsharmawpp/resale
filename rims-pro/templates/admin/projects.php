<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
    <h1><?php esc_html_e( 'RIMS Pro - Projects', 'rims-pro' ); ?></h1>
    <?php settings_errors( 'rims_pro_projects' ); ?>

    <h2><?php echo $editing ? 'Edit Project' : 'Add Project'; ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field( 'rims_pro_projects' ); ?>
        <input type="hidden" name="rims_action" value="<?php echo $editing ? 'update' : 'create'; ?>">
        <?php if ( $editing ): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr( (string) $editing->id ); ?>">
        <?php endif; ?>
        <table class="form-table">
            <tr><th><label for="name">Project Name *</label></th>
                <td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $editing->name ?? '' ); ?>"></td></tr>
            <tr><th><label for="builder">Builder</label></th>
                <td><input type="text" id="builder" name="builder" class="regular-text" value="<?php echo esc_attr( $editing->builder ?? '' ); ?>"></td></tr>
            <tr><th><label for="location">Location</label></th>
                <td><input type="text" id="location" name="location" class="regular-text" value="<?php echo esc_attr( $editing->location ?? '' ); ?>"></td></tr>
            <tr><th><label for="sector">Sector</label></th>
                <td><input type="text" id="sector" name="sector" class="regular-text" value="<?php echo esc_attr( $editing->sector ?? '' ); ?>" placeholder="e.g. Sector 93, Golf Course Ext Road"></td></tr>
            <tr><th><label for="possession_status">Possession Status</label></th>
                <td><select id="possession_status" name="possession_status">
                    <option value="">-- Select --</option>
                    <option value="Ready" <?php selected( $editing->possession_status ?? '', 'Ready' ); ?>>Ready to Move</option>
                    <option value="U/C" <?php selected( $editing->possession_status ?? '', 'U/C' ); ?>>Under Construction</option>
                    <option value="New Launch" <?php selected( $editing->possession_status ?? '', 'New Launch' ); ?>>New Launch</option>
                    <option value="Nearing Possession" <?php selected( $editing->possession_status ?? '', 'Nearing Possession' ); ?>>Nearing Possession</option>
                </select></td></tr>
            <tr><th><label for="tower_count">Total Towers</label></th>
                <td><input type="number" id="tower_count" name="tower_count" min="0" value="<?php echo esc_attr( (string) ( $editing->tower_count ?? 0 ) ); ?>"></td></tr>
            <tr><th><label for="latitude">Latitude</label></th>
                <td><input type="text" id="latitude" name="latitude" class="regular-text" value="<?php echo esc_attr( (string) ( $editing->latitude ?? '' ) ); ?>" placeholder="e.g. 28.4595"></td></tr>
            <tr><th><label for="longitude">Longitude</label></th>
                <td><input type="text" id="longitude" name="longitude" class="regular-text" value="<?php echo esc_attr( (string) ( $editing->longitude ?? '' ) ); ?>" placeholder="e.g. 77.0266"></td></tr>
            <tr><th><label for="overview">Overview</label></th>
                <td><textarea id="overview" name="overview" rows="4" class="large-text"><?php echo esc_textarea( $editing->overview ?? '' ); ?></textarea></td></tr>
            <tr><th><label for="seo_title">SEO Title</label></th>
                <td><input type="text" id="seo_title" name="seo_title" class="regular-text" value="<?php echo esc_attr( $editing->seo_title ?? '' ); ?>"></td></tr>
            <tr><th><label for="seo_description">SEO Description</label></th>
                <td><textarea id="seo_description" name="seo_description" rows="2" class="large-text"><?php echo esc_textarea( $editing->seo_description ?? '' ); ?></textarea></td></tr>
        </table>
        <?php submit_button( $editing ? 'Update Project' : 'Add Project' ); ?>
    </form>

    <h2>Existing Projects</h2>
    <?php if ( empty( $projects ) ): ?>
        <p>No projects yet. Add your first project above.</p>
    <?php else: ?>
        <table class="widefat fixed striped">
            <thead><tr>
                <th>Name</th><th>Builder</th><th>Location</th><th>Sector</th><th>Possession</th><th>Towers</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ( $projects as $p ): ?>
                <tr>
                    <td><strong><?php echo esc_html( $p->name ); ?></strong></td>
                    <td><?php echo esc_html( $p->builder ); ?></td>
                    <td><?php echo esc_html( $p->location ); ?></td>
                    <td><?php echo esc_html( $p->sector ); ?></td>
                    <td><?php echo esc_html( $p->possession_status ); ?></td>
                    <td><?php echo esc_html( (string) $p->tower_count ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rims-pro-projects&edit=' . $p->id ) ); ?>">Edit</a> |
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this project?')">
                            <?php wp_nonce_field( 'rims_pro_projects' ); ?>
                            <input type="hidden" name="rims_action" value="delete">
                            <input type="hidden" name="id" value="<?php echo esc_attr( (string) $p->id ); ?>">
                            <button type="submit" class="button-link" style="color:#b32d2e;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
