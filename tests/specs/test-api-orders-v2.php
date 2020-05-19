<?php

/**
 * Class TJ_WC_Tests_API_Orders_V2
 */
class TJ_WC_Tests_API_Orders_V2 extends TJ_WC_REST_Unit_Test_Case {

	protected $create_order_endpoint = '/wc/v2/orders';

	/**
	 * Set up fixture before each test
	 */
	function setUp() {
		parent::setUp();

		// WooCommerce REST API V2
		$this->endpoint = new WC_REST_Orders_V2_Controller();
	}

	/**
	 * Clean up after each test
	 */
	function tearDown() {
		parent::tearDown();
	}

}