<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_CMI_Logger {
    /**
     * @var WC_Logger
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = wc_get_logger();
    }

    /**
     * Log debug message
     */
    public function debug($message, $context = array()) {
        if (!$this->is_logging_enabled()) {
            return;
        }

        $this->log('debug', $message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }

    /**
     * Log message with level
     */
    private function log($level, $message, $context = array()) {
        // Add timestamp and request ID
        $context = array_merge($context, array(
            'source' => 'wc-cmi-gateway',
            'request_id' => uniqid('cmi_'),
        ));

        // If message is array or object, convert to string
        if (is_array($message) || is_object($message)) {
            $message = print_r($this->redact_sensitive_data($message), true);
        }

        $this->logger->log($level, $message, $context);
    }

    /**
     * Check if debug logging is enabled
     */
    private function is_logging_enabled() {
        $gateway = new WC_Gateway_CMI();
        return 'yes' === $gateway->get_option('debug_log', 'no');
    }

    /**
     * Redact sensitive data from log messages
     */
    private function redact_sensitive_data($data) {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive_fields = array(
            'storekey',
            'clientid',
            'hash',
            'card',
            'cvv',
            'pan',
            'password',
            'email'
        );

        foreach ($sensitive_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
