<?php
/**
 * Update Server for CMI Gateway Plugin
 * 
 * This is a sample update server that you can host on your own server.
 * It provides plugin information and updates to WordPress installations.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Register the REST API endpoints
add_action('rest_api_init', function () {
    register_rest_route('wc-cmi/v1', '/update-check', array(
        'methods' => 'GET',
        'callback' => 'wc_cmi_update_check_callback',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Callback for the update check endpoint
 */
function wc_cmi_update_check_callback($request) {
    $plugin_info = array(
        'name' => 'CMI Payment Gateway for WooCommerce',
        'slug' => 'wc-cmi-gateway',
        'version' => '1.0.1', // New version number
        'tested' => '6.3', // WordPress version tested up to
        'requires' => '5.8', // Minimum WordPress version required
        'requires_php' => '8.1', // Minimum PHP version required
        'author' => 'Sohail Koutari',
        'author_profile' => 'https://github.com/sohailkoutari',
        'download_url' => 'https://your-domain.com/downloads/wc-cmi-gateway-1.0.1.zip',
        'last_updated' => '2025-09-26 12:00:00',
        'homepage' => 'https://wordpress.org/plugins/wc-cmi-gateway/',
        'sections' => array(
            'description' => '
                <p>Official CMI (Centre Monétique Interbancaire) payment gateway for WooCommerce. 
                Accept credit card payments securely in Morocco.</p>
                <h4>Features:</h4>
                <ul>
                    <li>Secure payment processing with 3D Secure</li>
                    <li>Support for multiple currencies (MAD, EUR, USD)</li>
                    <li>Detailed transaction information</li>
                    <li>Comprehensive payment verification</li>
                </ul>
            ',
            'installation' => '
                <ol>
                    <li>Upload the plugin files to wp-content/plugins/wc-cmi-gateway</li>
                    <li>Activate the plugin</li>
                    <li>Go to WooCommerce > Settings > Payments</li>
                    <li>Configure your CMI credentials</li>
                </ol>
            ',
            'changelog' => '
                <h4>1.0.1 - September 26, 2025</h4>
                <ul>
                    <li>Added French and Arabic translations</li>
                    <li>Improved error handling</li>
                    <li>Added automatic updates support</li>
                    <li>Fixed currency validation issues</li>
                </ul>
                
                <h4>1.0.0 - September 1, 2025</h4>
                <ul>
                    <li>Initial release</li>
                </ul>
            '
        ),
        'banners' => array(
            'low' => 'https://your-domain.com/images/banner-772x250.jpg',
            'high' => 'https://your-domain.com/images/banner-1544x500.jpg'
        )
    );

    return new WP_REST_Response($plugin_info, 200);
}
