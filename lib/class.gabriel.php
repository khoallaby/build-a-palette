<?php
if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly
if( !class_exists('base_plugin') )
    require_once dirname(__FILE__) . '/class.base.php';


class gabriel extends base_plugin {
	public $slug = 'simple';
	public $palette_metakey = '_palette_product_id';
	public $object_types = array('post', 'page');

    protected function __construct() {
        parent::__construct();
    }

    public function init() {

	    # Front end / product page
	    add_action( 'wp', array( $this, 'modify_product_page' ) );


	    # Back end / Custom product type
	    add_action( 'admin_enqueue_scripts', array( $this, 'admin_javascript' ), 10, 1 );
	    #add_action( 'plugins_loaded', array( $this, 'register_custom_palette_product_type' ) );

	    #add_filter( 'product_type_selector', array( $this, 'add_custom_palette_product' ) );
	    add_action( 'admin_footer', array( $this, 'custom_palette_custom_js' ) );
	    add_filter( 'woocommerce_product_data_tabs', array( $this, 'custom_product_tabs' ) );
	    add_action( 'woocommerce_product_data_panels', array( $this, 'custom_palette_options_product_tab_content' ) );
	    add_action( 'woocommerce_process_product_meta_' . $this->slug, array( $this, 'save_custom_palette_option_field'  ) );
	    add_filter( 'woocommerce_product_data_tabs', array( $this, 'hide_attributes_data_panel' ) );



	    wp_register_script( 'custom-palette', plugins_url( 'js/custom-palette.js', dirname(__FILE__) ), array( 'jquery-ui-core', 'jquery-ui-tabs' ), '1.0', true );
	    wp_register_style( 'custom-palette', plugins_url( 'css/custom-palette.css', dirname(__FILE__) ) );

    }





	public function admin_javascript( $hook ) {
		global $post;

		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( 'product' === $post->post_type )
				wp_enqueue_script( 'custom-palette-admin-js', plugins_url( 'js/admin.js', dirname(__FILE__) ) );
		}
	}











	/*************************************************
	 * Modifies front end display of custom palette product
	 *************************************************/



	public function modify_product_page() {
		if( is_product() ) {
			$product = wc_get_product( );
			$product_palette_id = get_post_meta($product->id, $this->palette_metakey, true);
			if( $product  && $product_palette_id ) {
				#remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
				#add_action( 'woocommerce_before_single_product_summary', array ( $this, 'custom_palette_template' ), 30 );
				remove_all_actions( 'woocommerce_product_thumbnails' );

				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
				add_action( 'woocommerce_single_product_summary', array( $this, 'woocommerce_template_single_excerpt' ), 20 );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_palette_scripts' ) );


				remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
				#add_action( 'woocommerce_product_thumbnails', array( $this, 'woocommerce_show_product_thumbnails', 20 ) );
			}
		}
	}


	public function enqueue_custom_palette_scripts() {
		wp_enqueue_style( 'custom-palette' );
		wp_enqueue_script( 'custom-palette' );
    }


	public function woocommerce_template_single_excerpt() {
		$this->get_template('palette-main');
    }


    # remove
	public function woocommerce_show_product_thumbnails() {
		$this->get_wc_template('single-product/product-thumbnails.php');
		#wc_get_template( 'single-product/product-thumbnails.php' );
	}


	public function woocommerce_display_swatches( $product_id ) {

		$product_palette_id = get_post_meta($product_id, $this->palette_metakey, true);
		$product_palette = new WC_Product_Variable( $product_palette_id );

		$get_variations = sizeof( $product_palette->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product_palette );
		$attributes = $product_palette->get_variation_attributes();
		$attribute = $attribute_keys = array_shift(array_keys( $attributes ));


        // Get terms if this is a taxonomy - ordered. We need the names too.
		$terms = wc_get_product_terms( $product_palette->id, $attribute, array('fields' => 'all') );
		$config = new WC_Swatches_Attribute_Configuration_Object( $product_palette, $attribute );

		foreach ( $terms as $term ) {
			if ( in_array( $term->slug, $attributes[$attribute] ) ) {
				if ( $config->get_type() == 'term_options' ) {
					$swatch_term = new WC_Swatch_Term( $config, $term->term_id, $attribute, $args['selected'] == $term->slug, $config->get_size() );
				} elseif ( $config->get_type() == 'product_custom' ) {
					$swatch_term = new WC_Product_Swatch_Term( $config, $term->term_id, $attribute, $args['selected'] == $term->slug, $config->get_size() );
				}

				do_action( 'woocommerce_swatches_before_picker_item', $swatch_term );
				echo $this-> woocommerce_output_swatch( $swatch_term, $product_palette );
				do_action( 'woocommerce_swatches_after_picker_item', $swatch_term );
			}
		}

    }



    /**
     * Pulled from WC_Swatch_Term->get_output()
     * Outputs individual swatch div with the variation image, as a data-attribute, for hover purposes
     */
	public function woocommerce_output_swatch( $swatch_term, $product_palette ) {
		global $product;

		$picker = '';

		$href = apply_filters( 'woocommerce_swatches_get_swatch_href', '#', $swatch_term );
		$anchor_class = apply_filters( 'woocommerce_swatches_get_swatch_anchor_css_class', 'swatch-anchor', $swatch_term );
		$image_class = apply_filters( 'woocommerce_swatches_get_swatch_image_css_class', 'swatch-img', $swatch_term );
		$image_alt = apply_filters( 'woocommerce_swatches_get_swatch_image_alt', 'thumbnail', $swatch_term );

		if ( $swatch_term->type == 'photo' || $swatch_term->type == 'image' ) {
			$picker .= '<a href="' . $href . '" style="width:' . $swatch_term->width . 'px;height:' . $swatch_term->height . 'px;" title="' . esc_attr( $swatch_term->term_label ) . '" class="' . $anchor_class . '">';
			$picker .= '<img src="' . apply_filters( 'woocommerce_swatches_get_swatch_image', $swatch_term->thumbnail_src, $swatch_term->term_slug, $swatch_term->taxonomy_slug, $swatch_term ) . '" alt="' . $image_alt . '" class="wp-post-image swatch-photo' . $swatch_term->meta_key() . ' ' . $image_class . '" width="' . $swatch_term->width . '" height="' . $swatch_term->height . '"/>';
			$picker .= '</a>';
		} elseif ( $swatch_term->type == 'color' ) {
			$picker .= '<a href="' . $href . '" style="text-indent:-9999px;width:' . $swatch_term->width . 'px;height:' . $swatch_term->height . 'px;background-color:' . apply_filters( 'woocommerce_swatches_get_swatch_color', $swatch_term->color, $swatch_term->term_slug, $swatch_term->taxonomy_slug, $swatch_term ) . ';" title="' . $swatch_term->term_label . '" class="' . $anchor_class . '">' . $swatch_term->term_label . '</a>';
			$picker .= '</a>';
		} else {
            $src = apply_filters( 'woocommerce_placeholder_img_src', WC()->plugin_url() . '/assets/images/placeholder.png' );
			$picker .= '<a href="' . $href . '" style="width:' . $swatch_term->width . 'px;height:' . $swatch_term->height . 'px;" title="' . esc_attr( $swatch_term->term_label ) . '"  class="' . $anchor_class . '">';
			$picker .= '<img src="' . $src . '" alt="' . $image_alt . '" class="wp-post-image swatch-photo' . $swatch_term->meta_key() . ' ' . $image_class . '" width="' . $swatch_term->width . '" height="' . $swatch_term->height . '"/>';
			$picker .= '</a>';
		}



		# /woocommerce/includes/class-ws-ajax.php -- WC_AJAX::get_variation()
        // gets variation
		if ( empty( $product_palette->id ) || ! ( $variable_product = wc_get_product( absint( $product_palette->id ), array( 'product_type' => 'variable' ) ) ) ) {
			$variation = false;
		} else {
            $variation_data = array(
                'attribute_pa_color' => $swatch_term->term_slug,
                'product_id' => $product_palette->id
            );
            $variation_id = $variable_product->get_matching_variation( wp_unslash( $variation_data ) );

            $variation = $variation_id ? $variable_product->get_available_variation( $variation_id ) : false;
		}


		$out = sprintf('<div class="select-option swatch-wrapper %s" data-attribute="%s" data-value="%s" data-thumbnail="%s" data-variation-id="%d" data-sku="%s">',
           ($swatch_term->selected ? ' selected' : ''),
            esc_attr( $swatch_term->taxonomy_slug ),
            esc_attr( $swatch_term->term_slug ),
			$variation ? esc_attr( $variation['image_src'] ): '',
			$variation ? esc_attr( $variation['variation_id'] ): '',
			$variation ? esc_attr( $variation['sku'] ): ''
        );
		$out .= apply_filters( 'woocommerce_swatches_picker_html', $picker, $swatch_term );
		$out .= '</div>';


		return $out;
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
		$palette_product_id = get_post_meta($post->ID, $this->palette_metakey, true);
		woocommerce_wp_select( array(
			'id' 		=> $this->palette_metakey,
			'label' 	=> __( 'Product to pull colors from:', 'woocommerce' ),
            'value'     => $palette_product_id,
            'options'   => $this->get_all_wc_products()
		) );


		echo '</div></div>';
	}


	/**
	 * Save the custom fields.
	 */
	function save_custom_palette_option_field( $post_id ) {
		if ( isset( $_POST[$this->palette_metakey] ) )
            update_post_meta( $post_id, $this->palette_metakey, sanitize_text_field( $_POST[$this->palette_metakey] ) );

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
	 * WooCommerce functions
	 *************************************************/

	public function add_custom_product() {

		global $woocommerce;
		$variations = array(
			'color'     => 'green',
			'palette 1' => 'color 1',
			'palette 2' => 'color 2',
			'palette 3' => 'color 3',
		);
		#$woocommerce->cart->add_to_cart( 16661, 1, 0, $variations, array() );

    }

	public function get_all_wc_products() {

		$args = array(
			'post_type'      => array( 'product' ),
			'posts_per_page' => - 1,
			'order'          => 'ASC',
			'orderby'        => 'post_title'
		);

		$return = array( '' => '' );

		$query = new WP_Query( $args );
		foreach( $query->get_posts() as $post ) {
		    $return[$post->ID] = $post->post_title;
        }
        wp_reset_postdata();

		return $return;

    }


    public function get_product_variations( $product_id ) {
	    $product_variable = new WC_Product_Variable( $product_id );
	    return $product_variable->get_available_variations();
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


	public function get_wc_template( $file ) {

		wc_get_template( '/' . $file . '.php',$args = array(), $template_path = '', dirname( __FILE__ ) . '/../templates/woocommerce/' );
    }


}

