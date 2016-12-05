<?php
if( !class_exists('base_plugin') )
    require_once dirname(__FILE__) . '/class.base.php';


class gabriel extends base_plugin {
	public $object_types = array('post', 'page');

    protected function __construct() {
        parent::__construct();
    }

    public function init() {
	    add_action( 'admin_enqueue_scripts', array( $this, 'admin_javascript' ), 10, 1 );

	    add_shortcode( 'palette', array( $this, 'palette_shortcode' ) );
    }



	public function admin_javascript( $hook ) {
		global $post;

		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( 'product' === $post->post_type ) {
				wp_enqueue_script( 'bp-admin-js', plugins_url( 'js/admin.js', dirname(__FILE__) ) );
			}
		}
	}








	public function palette_shortcode( $atts ) {
		$a = shortcode_atts( array(
			'id' => '',
		), $atts );

		return "foo = {$a['id']}";
	}


}

