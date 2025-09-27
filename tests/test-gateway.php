<?php
class Test_WC_CMI_Gateway extends WP_UnitTestCase {
    private $gateway;

    public function setUp(): void {
        parent::setUp();
        $this->gateway = new WC_Gateway_CMI();
    }

    public function test_gateway_initialization() {
        $this->assertEquals('cmi_gateway', $this->gateway->id);
        $this->assertEquals('CMI (Morocco)', $this->gateway->method_title);
        $this->assertFalse($this->gateway->has_fields);
    }

    public function test_amount_validation() {
        $this->assertTrue($this->gateway->validate_amount(50));
        
        $this->expectException(Exception::class);
        $this->gateway->validate_amount(0);
    }

    public function test_currency_validation() {
        // Set supported currencies
        $this->gateway->update_option('supported_currencies', array('MAD'));
        
        // Test with supported currency
        update_option('woocommerce_currency', 'MAD');
        $this->assertTrue($this->gateway->is_valid_for_use());
        
        // Test with unsupported currency
        update_option('woocommerce_currency', 'USD');
        $this->assertFalse($this->gateway->is_valid_for_use());
    }

    public function test_settings_fields() {
        $fields = $this->gateway->init_form_fields();
        $this->assertArrayHasKey('enabled', $fields);
        $this->assertArrayHasKey('title', $fields);
        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('clientid', $fields);
        $this->assertArrayHasKey('storekey', $fields);
    }

    public function test_process_payment() {
        // Create a test order
        $order = wc_create_order();
        $order->set_total(100);
        $order->save();

        // Process payment
        $result = $this->gateway->process_payment($order->get_id());
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('redirect', $result);
    }

    public function test_language_support() {
        $this->assertEquals('fr', $this->gateway->get_cmi_language('fr_FR'));
        $this->assertEquals('ar', $this->gateway->get_cmi_language('ar'));
        $this->assertEquals('en', $this->gateway->get_cmi_language('de_DE'));
    }
}
