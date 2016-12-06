<?php
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly.


class WC_Product_Custom_Palette extends WC_Product {

	public function __construct( $product ) {
		$this->product_type = 'custom_palette';
		parent::__construct( $product );
	}
}