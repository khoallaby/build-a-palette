<?php
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly
if( !class_exists('base_plugin') )
    require_once dirname(__FILE__) . '/class.base.php';


class gabriel extends base_plugin {
	public $slug = 'custom_palette';
	public $object_types = array('post', 'page');

    protected function __construct() {
        parent::__construct();
    }

    public function init() {

	    # Front end / product page
	    add_action( 'wp', array( $this, 'modify_product_page' ) );


	    # Back end / Custom product type
	    add_action( 'admin_enqueue_scripts', array( $this, 'admin_javascript' ), 10, 1 );
	    add_action( 'plugins_loaded', array( $this, 'register_custom_palette_product_type' ) );

	    add_filter( 'product_type_selector', array( $this, 'add_custom_palette_product' ) );
	    add_action( 'admin_footer', array( $this, 'custom_palette_custom_js' ) );
	    add_filter( 'woocommerce_product_data_tabs', array( $this, 'custom_product_tabs' ) );
	    add_action( 'woocommerce_product_data_panels', array( $this, 'custom_palette_options_product_tab_content' ) );

	    add_action( 'woocommerce_process_product_meta_custom_palette', array( $this, 'save_custom_palette_option_field'  ) );

	    add_filter( 'woocommerce_product_data_tabs', array( $this, 'hide_attributes_data_panel' ) );

    }





	public function admin_javascript( $hook ) {
		global $post;

		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( 'product' === $post->post_type ) {
				wp_enqueue_script( 'custom-palette-admin-js', plugins_url( 'js/admin.js', dirname(__FILE__) ) );
			}
		}
	}












	/*************************************************
	 * Modifies front end display of custom palette product
	 *************************************************/



	public function modify_product_page() {
		if( is_product() ) {
			$product = wc_get_product( );
			if( $product  && $product->product_type == $this->slug ) {
				#remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
				#add_action( 'woocommerce_before_single_product_summary', array ( $this, 'custom_palette_template' ), 30 );
				remove_all_actions( 'woocommerce_product_thumbnails' );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
				add_action( 'woocommerce_single_product_summary', array( $this, 'woocommerce_template_single_excerpt' ), 20 );
				wp_enqueue_style( 'custom-palette-css', plugins_url( 'css/custom-palette.css', dirname(__FILE__) ) );
			}
		}
	}

	public function woocommerce_template_single_excerpt() {
		$this->get_template('palette-colors');
    }












	/*************************************************
	 * Add Custom Palette functions to WC backend
	 *************************************************/



	/**
	 * Register the custom product type after init
	 */
	function register_custom_palette_product_type() {
		require_once( dirname( __FILE__ ) . '/class.gabriel-wc-custom-product-type.php' );
	}

	/**
	 * Add to product type drop down.
	 */
	function add_custom_palette_product( $types ){
		// Key should be exactly the same as in the class
		$types[ $this->slug ] = __( 'Custom Palette' );
		return $types;

	}


	/**
	 * Show pricing fields for custom_palette product.
	 */
	function custom_palette_custom_js() {
		if ( 'product' != get_post_type() ) :
			return;
		endif;

		?><script type='text/javascript'>
            jQuery( document ).ready( function() {
                jQuery( '.options_group.pricing' ).addClass( 'show_if_custom_palette' ).show();
                jQuery('.show_if_simple').addClass('show_if_<?php echo $this->slug; ?>' );
            });
		</script><?php
	}


	/**
	 * Add a custom product tab.
	 */
	function custom_product_tabs( $tabs) {
		$tabs[$this->slug] = array(
			'label'		=> __( 'Custom Palette Options', 'woocommerce' ),
			'target'	=> $this->slug . '_options',
			'class'		=> array( 'show_if_' . $this->slug ),
		);

		return $tabs;
	}


	/**
	 * Contents of the custom palette options product tab.
	 */
	function custom_palette_options_product_tab_content() {
		global $post;

		echo '<div id="' . $this->slug . '_options" class="panel woocommerce_options_panel">';
		echo '<div class="options_group">';

		woocommerce_wp_checkbox( array(
			'id' 		=> '_enable_renta_option',
			'label' 	=> __( 'Enable rental option X', 'woocommerce' ),
		) );

		woocommerce_wp_text_input( array(
			'id'			=> '_text_input_y',
			'label'			=> __( 'What is the value of Y', 'woocommerce' ),
			'desc_tip'		=> 'true',
			'description'	=> __( 'A handy description field', 'woocommerce' ),
			'type' 			=> 'text',
		) );

		echo '</div></div>';
	}


	/**
	 * Save the custom fields.
	 */
	function save_custom_palette_option_field( $post_id ) {
		#vard($_POST);
		#die();

		$rental_option = isset( $_POST['_enable_renta_option'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_enable_renta_option', $rental_option );

		if ( isset( $_POST['_text_input_y'] ) ) :
			update_post_meta( $post_id, '_text_input_y', sanitize_text_field( $_POST['_text_input_y'] ) );
		endif;

	}


	/**
	 * Hide Attributes data panel.
	 */
	function hide_attributes_data_panel( $tabs) {

		#$tabs['attribute']['class'][] = 'hide_if_' . $this->slug;
		$tabs['general']['class'][] = 'show_if_' . $this->slug;
		#$tabs['general']['class'][] = 'show_if_simple';
		$tabs['inventory']['class'][] = 'show_if_' . $this->slug;

		return $tabs;

	}



	/**
	 * Uses custom template
	 */
	function add_to_cart() {
		require_once( dirname( __FILE__ ) . '/../templates/custom-palette.php' );
		#wc_get_template( 'single-product/add-to-cart/custom-palette.php',$args = array(), $template_path = '', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
	}










	/*************************************************
	 * Misc functions
	 *************************************************/




	public function get_template( $file ) {
		$dir = dirname( __FILE__ ) . '/../templates/';
		#$filename = $dir . $file . '.php';
		if( file_exists( $dir . $file . '.php' ) )
			wc_get_template( '/' . $file . '.php',$args = array(), $template_path = '', dirname( __FILE__ ) . '/../templates/' );
		else
			get_template_part( 'templates/' . $file );
	}


}

