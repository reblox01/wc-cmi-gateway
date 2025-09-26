<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_CMI_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(WC_CMI_PLUGIN_DIR . 'wc-cmi-gateway.php'), 
            array($this, 'add_plugin_action_links'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Add order meta box
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    }

    /**
     * Add links to plugin listing
     */
    public function add_plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=cmi_gateway') . '">' . 
                __('Settings', 'wc-cmi-gateway') . '</a>',
            '<a href="https://wordpress.org/plugins/wc-cmi-gateway/#faq">' . 
                __('FAQ', 'wc-cmi-gateway') . '</a>'
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Show admin notices
     */
    public function admin_notices() {
        if (!$this->is_gateway_configured()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php
                    printf(
                        __('CMI Gateway requires configuration. Please %1$sconfigure your CMI credentials%2$s to start accepting payments.', 'wc-cmi-gateway'),
                        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=cmi_gateway') . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add meta boxes to order page
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wc-cmi-payment-info',
            __('CMI Payment Information', 'wc-cmi-gateway'),
            array($this, 'payment_info_meta_box'),
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Display payment info meta box
     */
    public function payment_info_meta_box($post) {
        $order = wc_get_order($post->ID);
        if ($order && $order->get_payment_method() === 'cmi_gateway') {
            $transaction_id = $order->get_transaction_id();
            $approval_code = get_post_meta($post->ID, '_cmi_approval_code', true);
            ?>
            <table class="form-table">
                <?php if ($transaction_id): ?>
                <tr>
                    <th><?php _e('Transaction ID:', 'wc-cmi-gateway'); ?></th>
                    <td><?php echo esc_html($transaction_id); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($approval_code): ?>
                <tr>
                    <th><?php _e('Approval Code:', 'wc-cmi-gateway'); ?></th>
                    <td><?php echo esc_html($approval_code); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php _e('Mode:', 'wc-cmi-gateway'); ?></th>
                    <td>
                        <?php 
                        $gateway = new WC_Gateway_CMI();
                        echo $gateway->testmode ? 
                            '<mark class="notice">' . __('Test Mode', 'wc-cmi-gateway') . '</mark>' : 
                            __('Live Mode', 'wc-cmi-gateway'); 
                        ?>
                    </td>
                </tr>
            </table>
            <?php
        }
    }

    /**
     * Check if gateway is configured
     */
    private function is_gateway_configured() {
        $gateway = new WC_Gateway_CMI();
        return !empty($gateway->get_option('clientid')) && !empty($gateway->get_option('storekey'));
    }
}
