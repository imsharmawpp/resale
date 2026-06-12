<?php
/** @var array $branding */
?>
<div class="wrap rims-admin">
    <h1>RIMS Pro - Settings</h1>
    <?php settings_errors( 'rims_pro_settings' ); ?>
    <form method="post">
        <?php wp_nonce_field( 'rims_pro_settings' ); ?>
        <input type="hidden" name="rims_settings_action" value="save">
        <table class="form-table">
            <tr><th>Company Name</th><td><input type="text" name="company_name" value="<?php echo esc_attr( (string) ( $branding['company_name'] ?? '' ) ); ?>"></td></tr>
            <tr><th>Logo URL</th><td><input type="url" name="logo_url" value="<?php echo esc_attr( (string) ( $branding['logo_url'] ?? '' ) ); ?>"></td></tr>
            <tr><th>Primary Color</th><td><input type="color" name="primary_color" value="<?php echo esc_attr( (string) ( $branding['primary_color'] ?? '#1e3a8a' ) ); ?>"></td></tr>
            <tr><th>Contact Phone</th><td><input type="tel" name="contact_phone" value="<?php echo esc_attr( (string) ( $branding['contact_phone'] ?? '' ) ); ?>"></td></tr>
            <tr><th>WhatsApp Number</th><td><input type="tel" name="contact_whatsapp" value="<?php echo esc_attr( (string) ( $branding['contact_whatsapp'] ?? '' ) ); ?>"></td></tr>
            <tr><th>Inventory Expiry (days)</th><td><input type="number" name="expiry_days" value="<?php echo (int) ( $branding['expiry_days'] ?? 90 ); ?>"></td></tr>
            <tr><th>Rate Limit (requests)</th><td><input type="number" name="rate_limit_max" value="<?php echo (int) ( $branding['rate_limit_max'] ?? 60 ); ?>"></td></tr>
            <tr><th>Rate Limit Window (sec)</th><td><input type="number" name="rate_limit_window" value="<?php echo (int) ( $branding['rate_limit_window'] ?? 60 ); ?>"></td></tr>
            <tr><th>Infinite Scroll</th><td><input type="checkbox" name="infinite_scroll_enabled" <?php checked( ! empty( $branding['infinite_scroll_enabled'] ) ); ?>></td></tr>
            <tr><th>Watermark Media</th><td><input type="checkbox" name="watermark_enabled" <?php checked( ! empty( $branding['watermark_enabled'] ) ); ?>></td></tr>
        </table>
        <button class="button button-primary" type="submit">Save Settings</button>
    </form>
    <hr>
    <h2>API Tokens</h2>
    <p>Generate a token for REST writes by setting the <code>rims_pro_api_tokens</code> option (array of strings) via WP-CLI or a plugin like Code Snippets.</p>
    <h2>CRM Configuration</h2>
    <p>Use the WP-Admin -&gt; Tools -&gt; CRM Config screen (or the REST <code>/rims/v1/crm</code> endpoint) to register Sell.Do, LeadSquared, HubSpot, or Zoho credentials.</p>
</div>
