<?php
declare(strict_types=1);

namespace RimsPro\Elementor\Widgets;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

final class Lead_Form_Widget extends RIMS_Widget_Base {
    public function get_name() { return 'rims_lead_form'; }
    public function get_title() { return __( 'RIMS Lead Form', 'rims-pro' ); }
    public function get_icon() { return 'eicon-form-horizontal'; }
    protected function render() {
        $nonce = wp_create_nonce( RIMS_PRO_NONCE_ACTION );
        echo '<form class="rims-lead-form" data-rims-nonce="' . esc_attr( $nonce ) . '">';
        echo '<input type="text" name="name" placeholder="Your name" required>';
        echo '<input type="tel" name="mobile" placeholder="Mobile number" required>';
        echo '<input type="email" name="email" placeholder="Email">';
        echo '<textarea name="requirements" placeholder="Your requirements"></textarea>';
        echo '<button type="submit" class="rims-button rims-button--primary">Submit</button>';
        echo '</form>';
    }
}
