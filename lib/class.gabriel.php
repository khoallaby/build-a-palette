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





	    #add_action( 'woocommerce_add_to_cart', array( $this, 'woocommerce_add_to_cart' ), 1, 6 );
	    #add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'woocommerce_add_to_cart_validation', 10, 5 ) );


	    add_filter( 'woocommerce_add_cart_item_data', array( $this, 'woocommerce_add_cart_item' ), 10, 3 );
	    add_filter( 'woocommerce_add_cart_item', array( $this, 'woocommerce_add_cart_item' ), 10, 3 );
	    add_filter( 'woocommerce_cart_item_name', array( $this, 'woocommerce_cart_item_name' ), 10, 2 );
	    //add_filter( 'woocommerce_get_item_data', array( $this, 'woocommerce_get_item_data'), 10, 2 );



	    # potentially add filter to change prices
        # https://github.com/woocommerce/woocommerce/issues/4135#issuecomment-28843229
        # or
        # http://sarkware.com/woocommerce-change-product-price-dynamically-while-adding-to-cart-without-using-plugins/
	    #add_filter( 'woocommerce_get_cart_item_from_session', array( $this,'woocommerce_get_cart_item_from_session', 5, 3 ) );

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


				#remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
				add_filter( 'woocommerce_single_product_image_html', array( $this, 'woocommerce_single_product_image_html' ), 10, 1 );
				add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'woocommerce_add_hidden_fields' ) );

                #add_action( 'woocommerce_add_to_cart', array( $this, 'woocommerce_add_to_cart' ), 10, 6 );
				#add_action( 'woocommerce_add_to_cart', array( &$this, 'add_product_to_cart'), 10, 6 );
			}
		}
	}



	/*************************************************
	 * WC Actions/filters, for modifying front end display
	 *************************************************/

	public function enqueue_custom_palette_scripts() {
		wp_register_script( 'fabric', plugins_url( 'js/fabric.min.js', dirname(__FILE__) ), array(), '1.7.1' );
		#wp_register_script( 'fabric', 'http://cdnjs.cloudflare.com/ajax/libs/fabric.js/1.7.1/fabric.min.js' );
		wp_register_script( 'custom-palette', plugins_url( 'js/custom-palette.js', dirname(__FILE__) ), array( 'jquery-ui-core', 'jquery-ui-tabs' ), '1.0' );
		wp_register_style( 'custom-palette', plugins_url( 'css/custom-palette.css', dirname(__FILE__) ) );

		wp_enqueue_script( 'fabric' );
		wp_enqueue_style( 'custom-palette' );
		wp_enqueue_script( 'custom-palette' );
    }


	public function woocommerce_template_single_excerpt() {
		$this->get_template('palette-main');
    }



	public function woocommerce_add_hidden_fields() {
		$max_colors = 4;

		foreach( range(1, $max_colors) as $i )
            echo '<input type="hidden" value="' . (null !== $_REQUEST["cp_color_{$i}"] ? esc_attr($_REQUEST["cp_color_{$i}"]) : '') . '" name="cp_color_' . $i . '" />';
        #$this->wc_get_template( 'single-product/product-thumbnails.php' );
	}


	public function woocommerce_single_product_image_html( $post_id ) {
        #global $post, $product;
        $product = new WC_Product( $post_id );

		if ( has_post_thumbnail() ) {
		    /*
			$attachment_count = count( $product->get_gallery_attachment_ids() );
			$gallery          = $attachment_count > 0 ? '[product-gallery]' : '';
			$props            = wc_get_product_attachment_props( get_post_thumbnail_id(), $post );
			$image            = get_the_post_thumbnail( $post->ID, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ), array(
				'title' => $props['title'],
				'alt'   => $props['alt'],
                'class' => 'custom-palette-image'
			) );
		    */


			$image_prop = wp_get_attachment_image_src( get_post_thumbnail_id(), apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) );
			//$html = '<canvas id="custom-palette-canvas" width="' . $image_prop[1] . '" height="' . $image_prop[2] . '" style="background-image: url(\'' . $image_prop[0] . '\');"></canvas>';

			$html = '<canvas id="custom-palette-canvas" width="' . $image_prop[1] . '" height="' . $image_prop[2] . '"></canvas>';
			#$html = '<canvas id="custom-palette-canvas"></canvas>';
			#$html .= $image;
			/*
			return sprintf(
				'<a href="%s" itemprop="image" class="woocommerce-main-image zoom" title="%s" data-rel="prettyPhoto%s">%s</a>',
				esc_url( $props['url'] ),
				esc_attr( $props['caption'] ),
				$gallery,
				$image
			);
			*/
		} else {
            $html = sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) );
        }

        return $html;
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
			    $selected = false;
				if ( $config->get_type() == 'term_options' ) {
					$swatch_term = new WC_Swatch_Term( $config, $term->term_id, $attribute, $selected == $term->slug, $config->get_size() );
				} elseif ( $config->get_type() == 'product_custom' ) {
					$swatch_term = new WC_Product_Swatch_Term( $config, $term->term_id, $attribute, $selected == $term->slug, $config->get_size() );
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



	function woocommerce_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = '', $variations= '' ) {

	    return $passed;

	}

	/**
	 * Logic of adding our custom product to the cart
	 */
	function woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	    return $cart_item_key;

	    /*
		if( isset($cart_item_data['fpd_data']) ) {

			//check if an old cart item exist
			if( !empty($cart_item_data['fpd_data']['fpd_remove_cart_item']) ) {

				global $woocommerce;
				$woocommerce->cart->set_quantity($cart_item_data['fpd_data']['fpd_remove_cart_item'], 0);

			}
		}
		*/
        global $woocommerce;

		die();

		$product_id = $_POST['assessories'];

		$found = false;

		//check if product already in cart
		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'];
				if ( $_product->id == $product_id )
					$found = true;
			}
			// if product not found, add it
			if ( ! $found )
				WC()->cart->add_to_cart( $product_id );
		} else {
			// if no products in cart, add it
			WC()->cart->add_to_cart( $product_id );
		}
	}









	/*************************************************
	 * Functions for dealing with displaying the new color attributes
	 *************************************************/

	/*
	 * Format for how the attributes should look
	 */
	public function woocommerce_add_cart_item( $cart_item_data, $product_id, $variation_id ) {
		#$product_palette_id = $this->is_palette_product( $product_id );

		#if( $product_palette_id ) {
        if( $_REQUEST['cp_color_1'] || $_REQUEST['cp_color_2'] || $_REQUEST['cp_color_3'] || $_REQUEST['cp_color_4'] ) {
			$max_colors = 4;
			$cart_item_data['variation'] = array();

			foreach ( range( 1, $max_colors ) as $i ) {
				if ( $variation_id = $_REQUEST[ 'cp_color_' . $i ] ) {
					$variation_data                               = $this->get_product_variation_data( $variation_id );
					$cart_item_data['variation'][ 'Color ' . $i ] = sprintf( '%s (%s)', $variation_data['term_name'], $variation_data['sku'] );
				}
			}
		}

		return $cart_item_data;
	}


	public function woocommerce_get_cart_item_from_session( $cart_item_data, $values, $key ) {
		#$cart_item_data->set_price(200);
		return $cart_item_data;
	}


	/*
	 * Shows custom attributes under each product on cart page
	 */
	public function woocommerce_cart_item_name( $cart_item, $cart_item_key ) {
		$out = $cart_item;
		$product_palette_id = $this->is_palette_product( $cart_item_key['product_id'] );

		if( $product_palette_id ) {
			if( !empty($cart_item_key['variation']) ) {
				$out .= '<dl class="variation">';
				foreach( $cart_item_key['variation'] as $attribute_slug => $attribute_name ) {
					$short_slug = str_replace(' ' , '-', strtolower($attribute_slug) );
					$out .= '
                        <dt class="variation-' . $short_slug . '">' . $attribute_slug . ':</dt>
                        <dd class="variation-' . $short_slug . '"><p>' . $attribute_name . '</p></dd>
                    ';
				}
				$out .= '</dl>';

			}
		}

		return $out;
	}




	function woocommerce_get_item_data( $other_data, $cart_item ) {


		return $other_data;

	}




	public function get_product_variation_data( $variation_id ) {
		$data = array();
		#$data['product_variation'] = new WC_Product_Variation( $variation_id );
		$data['sku'] = get_post_meta( $variation_id, '_sku', 'true' );
		$data['price'] = get_post_meta( $variation_id, '_price', 'true' );
		$data['term_slug'] = get_post_meta( $variation_id, 'attribute_pa_color', 'true' );
		$data['term'] = get_term_by( 'slug', $data['term_slug'], 'pa_color' );
		$data['term_name'] = $data['term']->name;
		return $data;
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
                jQuery( '.options_group.pricing' ).addClass( 'show_if_<?php echo $this->slug; ?>' ).show();
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


	public function is_palette_product( $product_id ) {
		return get_post_meta( $product_id, $this->palette_metakey, true );
	}


}
