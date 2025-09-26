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
}
