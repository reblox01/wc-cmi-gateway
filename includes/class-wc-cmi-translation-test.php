<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_CMI_Translation_Test {
    public static function test_translations() {
        $tests = array(
            'Settings' => __('Settings', 'wc-cmi-gateway'),
            'Test Mode' => __('Test Mode', 'wc-cmi-gateway'),
            'Client ID' => __('Client ID', 'wc-cmi-gateway'),
            'Store Key' => __('Store Key', 'wc-cmi-gateway'),
            'CMI Payment Information' => __('CMI Payment Information', 'wc-cmi-gateway'),
            'Transaction ID:' => __('Transaction ID:', 'wc-cmi-gateway'),
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('CMI Gateway Translation Test', 'wc-cmi-gateway') . '</h1>';
        
        // Show current locale
        $current_locale = determine_locale();
        echo '<p>' . sprintf(esc_html__('Current locale: %s', 'wc-cmi-gateway'), $current_locale) . '</p>';
        
        // Show translation file status
        $mo_file = WP_PLUGIN_DIR . '/wc-cmi-gateway/languages/wc-cmi-gateway-' . $current_locale . '.mo';
        echo '<p>' . sprintf(esc_html__('Translation file (.mo): %s', 'wc-cmi-gateway'), 
            file_exists($mo_file) ? '✅ ' . esc_html($mo_file) : '❌ ' . esc_html($mo_file) . ' (not found)') . '</p>';
        
        echo '<table class="widefat">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Original Text', 'wc-cmi-gateway') . '</th>';
        echo '<th>' . esc_html__('Translated Text', 'wc-cmi-gateway') . '</th>';
        echo '<th>' . esc_html__('Status', 'wc-cmi-gateway') . '</th>';
        echo '</tr></thead>';
        
        foreach ($tests as $original => $translated) {
            echo '<tr>';
            echo '<td>' . esc_html($original) . '</td>';
            echo '<td>' . esc_html($translated) . '</td>';
            echo '<td>' . ($original !== $translated ? '✅' : '❌') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</div>';
    }
}

// Add menu item to test translations
add_action('admin_menu', 'wc_cmi_add_translation_test_page');
function wc_cmi_add_translation_test_page() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        add_submenu_page(
            'woocommerce',
            __('CMI Translation Test', 'wc-cmi-gateway'),
            __('CMI Translation Test', 'wc-cmi-gateway'),
            'manage_woocommerce',
            'wc-cmi-translation-test',
            array('WC_CMI_Translation_Test', 'test_translations')
        );
    }
}
