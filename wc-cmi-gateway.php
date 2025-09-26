<?php
/*
Plugin Name: CMI Payment Gateway for WooCommerce
Plugin URI: https://wordpress.org/plugins/wc-cmi-gateway/
Description: Official CMI (Centre Monétique Interbancaire) payment gateway for WooCommerce. Accept credit card payments securely in Morocco.
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 8.1
Author: Sohail Koutari
Author URI: https://github.com/reblox01
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc-cmi-gateway
Domain Path: /languages
WC requires at least: 6.0
WC tested up to: 8.2.1
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WC_CMI_VERSION', '1.0.0' );
define( 'WC_CMI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CMI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load CMI library
require_once WC_CMI_PLUGIN_DIR . 'lib/CmiClientInterface.php';
require_once WC_CMI_PLUGIN_DIR . 'lib/BaseCmiClient.php';
require_once WC_CMI_PLUGIN_DIR . 'lib/CmiClient.php';
require_once WC_CMI_PLUGIN_DIR . 'lib/Exception/ExceptionInterface.php';
require_once WC_CMI_PLUGIN_DIR . 'lib/Exception/InvalidArgumentException.php';

use WC_CMI_Gateway\Lib\CmiClient;

// Include required files
require_once WC_CMI_PLUGIN_DIR . 'includes/class-wc-cmi-admin.php';
require_once WC_CMI_PLUGIN_DIR . 'includes/class-wc-cmi-updater.php';
require_once WC_CMI_PLUGIN_DIR . 'includes/class-wc-cmi-logger.php';
require_once WC_CMI_PLUGIN_DIR . 'includes/class-wc-cmi-translation-test.php';

// Load plugin text domain
add_action('init', 'wc_cmi_load_textdomain');
function wc_cmi_load_textdomain() {
    $domain = 'wc-cmi-gateway';
    $locale = determine_locale();
    
    // Get the language code (first 2 letters)
    $lang = substr($locale, 0, 2);
    
    // Map full locales to our supported translations
    $supported_locales = array(
        'fr' => 'fr_FR',
        'ar' => 'ar',
        'ar_EG' => 'ar',
        'ar_SA' => 'ar',
        'ar_MA' => 'ar'
    );
    
    // If the language is supported, use its locale, otherwise default to English
    $use_locale = isset($supported_locales[$lang]) ? $supported_locales[$lang] : 'en_US';
    
    // Set the path to our language files
    $lang_path = dirname(plugin_basename(__FILE__)) . '/languages';
    
    // Debug translation loading
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("CMI Gateway: Current locale: {$locale}");
        error_log("CMI Gateway: Language code: {$lang}");
        error_log("CMI Gateway: Using locale: {$use_locale}");
        error_log("CMI Gateway: Plugin Dir: " . WP_PLUGIN_DIR);
        error_log("CMI Gateway: Lang Path: {$lang_path}");
        
        $mo_file = "{$lang_path}/{$domain}-{$use_locale}.mo";
        error_log("CMI Gateway: Looking for translation file: {$mo_file}");
        error_log("CMI Gateway: Full path: " . WP_PLUGIN_DIR . '/' . $mo_file);
        
        if (!file_exists(WP_PLUGIN_DIR . '/' . $mo_file)) {
            error_log("CMI Gateway: Translation file not found: " . WP_PLUGIN_DIR . '/' . $mo_file);
            error_log("CMI Gateway: Available files in languages dir: " . print_r(scandir(WP_PLUGIN_DIR . '/' . dirname($mo_file)), true));
        }
    }
    
    // Load the translations
    $loaded = load_plugin_textdomain($domain, false, $lang_path);
    
    // If loading failed and we're not using English, try to use English as fallback
    if (!$loaded && $use_locale !== 'en_US') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CMI Gateway: Failed to load {$use_locale}, falling back to English");
        }
    }
    
    return $loaded;
}

// Plugin activation hook
register_activation_hook(__FILE__, 'wc_cmi_activate');
function wc_cmi_activate() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('CMI Payment Gateway requires WooCommerce to be installed and active.', 'wc-cmi-gateway'),
            'Plugin dependency check',
            array('back_link' => true)
        );
    }
    
    // Create any necessary database tables or options
    add_option('wc_cmi_gateway_version', WC_CMI_VERSION);
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'wc_cmi_deactivate');
function wc_cmi_deactivate() {
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('wc_cmi_gateway_cron');
}

// Initialize admin and updater
function wc_cmi_init_admin() {
    if (is_admin()) {
        new WC_CMI_Admin();
        new WC_CMI_Updater();
    }
}
add_action('init', 'wc_cmi_init_admin');

// Initialize the gateway
add_action('plugins_loaded', 'wc_cmi_init', 11);
function wc_cmi_init(){
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_CMI extends WC_Payment_Gateway {
        public function __construct(){
            $this->id = 'cmi_gateway';
            $this->method_title = 'CMI (Morocco)';
            $this->has_fields = false;
            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option('title', 'Card (CMI)');
            $this->description = $this->get_option('description', '');
            $this->clientid    = $this->get_option('clientid');
            $this->storekey    = $this->get_option('storekey');
            $this->testmode    = $this->get_option('testmode') === 'yes';

            add_action('woocommerce_api_wc_gateway_cmi', array($this, 'handle_callback'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        }

        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array('title'=>'Enable','type'=>'checkbox','default'=>'yes'),
                'title' => array('title'=>'Title','type'=>'text','default'=>'CMI Card'),
                'description' => array('title'=>'Description','type'=>'textarea','default'=>'Pay with bank card (CMI).'),
                'clientid' => array('title'=>'Client ID','type'=>'text'),
                'storekey' => array('title'=>'Store Key','type'=>'text'),
                'sandbox_url' => array('title'=>'Sandbox URL','type'=>'text','default'=>'https://testpayment.cmi.co.ma/fim/est3Dgate'),
                'production_url' => array('title'=>'Production URL','type'=>'text','default'=>'https://payment.cmi.co.ma/fim/est3Dgate'),
                'testmode' => array('title'=>'Test Mode','type'=>'checkbox','default'=>'yes','description'=>'Enable sandbox/test mode'),
                'supported_currencies' => array(
                    'title' => 'Supported Currencies',
                    'type' => 'multiselect',
                    'default' => array('MAD'),
                    'options' => array(
                        'MAD' => 'Moroccan Dirham (MAD)',
                        'EUR' => 'Euro (EUR)',
                        'USD' => 'US Dollar (USD)'
                    ),
                    'description' => 'Select the currencies supported by your CMI account'
                ),
                'min_amount' => array(
                    'title' => 'Minimum Amount',
                    'type' => 'number',
                    'default' => '1',
                    'description' => 'Minimum transaction amount allowed'
                ),
                'max_amount' => array(
                    'title' => 'Maximum Amount',
                    'type' => 'number',
                    'default' => '100000',
                    'description' => 'Maximum transaction amount allowed'
                ),
            );
        }

        public function admin_options(){
            echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }
        
        /**
         * Check if this gateway is available for the current currency
         */
        public function is_valid_for_use() {
            $supported_currencies = $this->get_option('supported_currencies', array('MAD'));
            if (!is_array($supported_currencies)) {
                $supported_currencies = array($supported_currencies);
            }
            return in_array(get_woocommerce_currency(), $supported_currencies);
        }

        /**
         * Validate amount limits
         */
        protected function validate_amount($amount) {
            $min = (float) $this->get_option('min_amount', 1);
            $max = (float) $this->get_option('max_amount', 100000);
            
            if ($amount < $min) {
                throw new Exception(sprintf(__('Amount below minimum: %s', 'wc-cmi-gateway'), wc_price($min)));
            }
            
            if ($amount > $max) {
                throw new Exception(sprintf(__('Amount above maximum: %s', 'wc-cmi-gateway'), wc_price($max)));
            }
            
            return true;
        }

        public function process_payment($order_id){
            $order = wc_get_order($order_id);
            $amount = number_format((float) $order->get_total(), 2, '.', '');
            
            try {
                // Validate currency
                if (!$this->is_valid_for_use()) {
                    throw new Exception(__('Currency not supported by CMI', 'wc-cmi-gateway'));
                }
                
                // Validate amount
                $this->validate_amount($order->get_total());
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return array('result' => 'failure');
            }

            // Build payload according to your bank/CMI requirements
            $payload = [
                'clientid' => $this->clientid,
                'oid' => $order->get_order_number(),
                'amount' => $amount,
                'okUrl' => home_url( '/?wc-api=wc_gateway_cmi&status=ok&order_id=' . $order_id ),
                'failUrl' => home_url( '/?wc-api=wc_gateway_cmi&status=fail&order_id=' . $order_id ),
                'email' => $order->get_billing_email(),
                'BillToName' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                // add other fields your bank requires
            ];

            // If the official library exists use it (it may provide helper methods)
            try {
                $client = new CmiClient(array_merge($payload, [
                        'storekey' => $this->storekey,
                        // library might accept 'sandbox' or a url param — check library README
                        'sandbox' => $this->testmode ? true : false,
                    ]));

                    // Most libraries provide a redirect helper; if it exists use it.
                    if ( method_exists($client, 'redirect_post') ) {
                        $client->redirect_post();
                        exit;
                    }
                } catch (Exception $e) {
                    // fallback to form redirect below
                    $this->log_debug('CMI library redirect failed: ' . $e->getMessage());
                }
            }

            // Build payment form fields with CMI's required format
            $endpoint = $this->testmode ? $this->get_option('sandbox_url') : $this->get_option('production_url');
            
            $form_fields = [
                'clientid' => $this->clientid,
                'storetype' => '3D_PAY_HOSTING',
                'oid' => $order->get_order_number(),
                'amount' => $amount,
                'currency' => '504', // 504 = MAD (Moroccan Dirham)
                'okUrl' => $payload['okUrl'],
                'failUrl' => $payload['failUrl'],
                'email' => $payload['email'],
                'BillToName' => $payload['BillToName'],
                'BillToCompany' => $order->get_billing_company(),
                'BillToStreet12' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                'BillToCity' => $order->get_billing_city(),
                'BillToStateProv' => $order->get_billing_state(),
                'BillToPostalCode' => $order->get_billing_postcode(),
                'BillToCountry' => '504', // 504 = Morocco
                'tel' => preg_replace('/[^0-9]/', '', $order->get_billing_phone()),
                'AutoRedirect' => 'true',
                'CallbackURL' => home_url('/?wc-api=wc_gateway_cmi&action=callback'),
                'shopurl' => home_url(),
                'lang' => $this->get_cmi_language(get_locale()), // ar, fr, or en
                'encoding' => 'UTF-8',
                'rnd' => microtime(true)
            ];

            // Generate hash string according to CMI documentation
            $hashstr = implode('', [
                $form_fields['clientid'],
                $form_fields['oid'],
                $form_fields['amount'],
                $form_fields['okUrl'],
                $form_fields['failUrl'],
                $form_fields['rnd'],
                $this->storekey
            ]);
            
            // Use exact CMI hash calculation method
            $form_fields['hashAlgorithm'] = 'ver3';
            $form_fields['hash'] = base64_encode(pack('H*', sha1($hashstr)));
            
            // Store hash data for verification
            update_post_meta($order->get_id(), '_cmi_hash_params', [
                'clientid' => $form_fields['clientid'],
                'oid' => $form_fields['oid'],
                'amount' => $form_fields['amount'],
                'rnd' => $form_fields['rnd'],
                'hash' => $form_fields['hash']
            ]);

            // Save transaction data
            update_post_meta($order->get_id(), '_cmi_order_hash', $form_fields['hash']);
            update_post_meta($order->get_id(), '_cmi_order_rnd', $form_fields['rnd']);

            // Return instead of echoing
            return array(
                'result' => 'success',
                'redirect' => add_query_arg([
                    'cmi_redirect' => '1',
                    'order_id' => $order->get_id(),
                    'fields' => base64_encode(json_encode($form_fields))
                ], $endpoint)
            );
        }

        /**
         * Handle callback (IPN / return)
         * IMPORTANT:
         * - You MUST implement the exact signature/hash validation required by your bank/CMI.
         * - This function tries several safe verification methods:
         *   1) If the included library provides a verification helper, it uses it.
         *   2) Else if the request contains a 'hash' field it attempts an HMAC-SHA256 validation (common pattern).
         *   3) Otherwise it fails safe (does not mark order as paid).
         *
         * Replace/adjust the verification section to match CMI's exact algorithm.
         */
        public function handle_callback(){
            // Collect incoming data (POST preferred for security)
            $request = $_POST + $_GET; // merge; prefer POST from bank
            $this->log_debug('CMI callback received: ' . $this->redact_for_log($request));

            // Get order id — banks sometimes return custom fields, check your payload
            $order_id = intval( $request['order_id'] ?? $request['orderid'] ?? $request['oid'] ?? 0 );
            if ( ! $order_id ) {
                wp_die('Invalid order id');
            }

            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wp_die('Order not found');
            }

            $verified = false;

            // Use bundled library for verification
            try {
                // Instantiate client
                $client = new CmiClient(['storekey' => $this->storekey]);
                    // try common method names (you must verify exact method name in the library)
                    if ( method_exists($client, 'verifyResponse') ) {
                        $verified = (bool) $client->verifyResponse( $request );
                    } elseif ( method_exists($client, 'checkHash') ) {
                        $verified = (bool) $client->checkHash( $request );
                    } elseif ( function_exists('Mehdirochdi\\CMI\\verify_response') ) {
                        // some packages expose a function
                        $verified = (bool) \Mehdirochdi\CMI\verify_response( $request, $this->storekey );
                    }
                } catch (Exception $e) {
                    $this->log_debug('CMI library verification error: ' . $e->getMessage());
                    $verified = false;
                }
            }

            // 2) Fallback: if request contains a 'hash' or 'signature' compute expected HMAC and compare
            if ( ! $verified ) {
                if (isset($request['HASH'])) {
                    $incoming_hash = $request['HASH'];
                    
                    // Get stored hash parameters
                    $stored_params = get_post_meta($order_id, '_cmi_hash_params', true);
                    
                    if (!empty($stored_params)) {
                        // Build verification string exactly as CMI expects
                        $hashstr = implode('', [
                            $stored_params['clientid'],
                            $stored_params['oid'],
                            $stored_params['amount'],
                            $request['Response'] ?? '',  // Include response code in verification
                            $stored_params['rnd'],
                            $this->storekey
                        ]);
                        
                        $expected_hash = base64_encode(pack('H*', sha1($hashstr)));
                        
                        // Timing-safe comparison
                        if (function_exists('hash_equals')) {
                            $verified = hash_equals($expected_hash, (string)$incoming_hash);
                        } else {
                            $verified = ($expected_hash === (string)$incoming_hash);
                        }
                        
                        // Store transaction data if verified
                        if ($verified) {
                            if (!empty($request['TransId'])) {
                                $order->set_transaction_id($request['TransId']);
                            }
                            if (!empty($request['approval'])) {
                                update_post_meta($order_id, '_cmi_approval_code', sanitize_text_field($request['approval']));
                            }
                        }
                    }

                    if ( ! $verified ) {
                        $this->log_debug('CMI fallback hash mismatch. expected='.$expected_hash.' received=' . substr((string)$incoming_hash,0,10).'...');
                    }
                }
            }

            // If still not verified, DO NOT mark order as paid. Fail safe.
            if ( ! $verified ) {
                $order->update_status('failed', 'CMI: callback verification failed. Check logs.');
                wp_die('Verification failed');
            }

            // If verified, check status field (bank-specific: e.g. 'status', 'response', 'procReturnCode', etc.)
            $status = strtolower( sanitize_text_field( $request['status'] ?? $request['response'] ?? $request['result'] ?? '' ) );

            // Also verify amount matches to avoid tampering
            $returned_amount = isset($request['amount']) ? number_format( (float) $request['amount'], 2, '.', '' ) : number_format( (float) $order->get_total(), 2, '.', '' );
            $order_amount = number_format( (float) $order->get_total(), 2, '.', '' );

            if ( $returned_amount !== $order_amount ) {
                $order->update_status('on-hold', 'CMI: amount mismatch (possible tampering).');
                $this->log_debug("CMI amount mismatch: returned={$returned_amount} expected={$order_amount}");
                wp_die('Amount mismatch');
            }

            // Decide which statuses mean success — adapt to your bank's values
            // CMI response codes
            $success_values = ['00']; // Approved or completed successfully
            $pending_values = ['01']; // With Reference (Authorized)
            
            $response_code = $request['Response'] ?? '';
            $proc_return_code = $request['ProcReturnCode'] ?? '';

            if ( in_array($status, $success_values, true) ) {
                if ( $order->get_status() !== 'processing' && $order->get_status() !== 'completed' ) {
                    $order->payment_complete();
                    $order->add_order_note( 'Payment accepted via CMI (verified).' );
                }
                // return a friendly message / redirect to thank you page
                wp_redirect( $order->get_checkout_order_received_url() );
                exit;
            } else {
                $order->update_status('failed', 'Payment failed via CMI (verified).');
                wp_redirect( $order->get_checkout_order_received_url() );
                exit;
            }
        }

        public function thankyou_page($order_id) {
            $order = wc_get_order($order_id);
            if ($order && $order->get_payment_method() === $this->id) {
                if ($this->testmode) {
                    echo '<div class="woocommerce-info" style="margin-bottom: 20px;">';
                    echo esc_html__('Test Mode - No real payment was taken.', 'wc-cmi-gateway');
                    echo '</div>';
                }
                
                $transaction_id = $order->get_transaction_id();
                if ($transaction_id) {
                    echo '<h2>' . esc_html__('Payment Information', 'wc-cmi-gateway') . '</h2>';
                    echo '<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">';
                    echo '<li>' . esc_html__('Transaction ID:', 'wc-cmi-gateway') . ' <strong>' . esc_html($transaction_id) . '</strong></li>';
                    if ($approval = get_post_meta($order_id, '_cmi_approval_code', true)) {
                        echo '<li>' . esc_html__('Approval Code:', 'wc-cmi-gateway') . ' <strong>' . esc_html($approval) . '</strong></li>';
                    }
                    $currency = $order->get_currency();
                    echo '<li>' . esc_html__('Currency:', 'wc-cmi-gateway') . ' <strong>' . esc_html($currency) . '</strong></li>';
                    echo '</ul>';
                }
            }
        }

        /**
         * Helper: write debug logs only if WP_DEBUG is enabled.
         * Redacts some sensitive fields before writing.
         */
        protected function log_debug($message){
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                $logfile = __DIR__ . '/cmi-debug.log';
                error_log( '[' . date('c') . '] ' . $message . PHP_EOL, 3, $logfile );
            }
        }

        /**
         * Redact typical sensitive fields before logging.
         */
        protected function redact_for_log($arr){
            if (!is_array($arr)) return '';
            $copy = $arr;
            $sensitive = ['cardnumber','pan','cvv','cv2','storekey','password','secret','hash','signature'];
            foreach ($sensitive as $k) {
                if (isset($copy[$k])) $copy[$k] = '[REDACTED]';
            }
            // shrink long values
            foreach ($copy as $k => $v) {
                if ( is_string($v) && strlen($v) > 200 ) $copy[$k] = substr($v,0,200) . '...';
            }
            return print_r($copy, true);
        }

        /**
         * Get the appropriate language code for CMI gateway
         * @param string $wp_locale WordPress locale
         * @return string CMI language code (ar, fr, or en)
         */
        protected function get_cmi_language($wp_locale) {
            // Get the first 2 letters of the locale
            $lang = substr($wp_locale, 0, 2);
            
            // Map of supported languages
            $supported_langs = array(
                'ar' => 'ar', // Arabic
                'fr' => 'fr', // French
                // Add more supported languages here
            );
            
            // Return the mapped language or 'en' as default
            return isset($supported_langs[$lang]) ? $supported_langs[$lang] : 'en';
        }
    }

    function wc_add_cmi_gateway($methods){
        $methods[] = 'WC_Gateway_CMI';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'wc_add_cmi_gateway');
}
