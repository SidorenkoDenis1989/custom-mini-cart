<?php 
/*
 * Plugin Name: Custom mini-cart
 * Author:            Denys Sydorenko
 * Author URI:        https://github.com/SidorenkoDenis1989
 * Description: Use shortcode [minicart_cats_items] or use parameter "custom_id" if you want to add ID to the wrapper, for example [minicart_cats_items custom_id="123"].
 */
class Onepix_Custom_Mini_Cart {
	public function __construct () {
		$this->load();
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets') );
		if( !is_admin() ):
			add_shortcode('minicart_cats_items', array( $this,'minicart_cats_items_handler'));
		endif;
        add_action('wp_ajax_nopriv_qty_cart',  array( $this, 'ajax_qty_cart') );
        add_action('wp_ajax_qty_cart',  array( $this, 'ajax_qty_cart') );
        add_filter( 'woocommerce_add_to_cart_fragments',  array( $this, 'minicart_cats_items_shortcode_update'));
        add_filter( 'woocommerce_locate_template', array( $this, 'myplugin_woocommerce_locate_template'), 100, 3 );
	}

	public function load() {

    }

    public function register_frontend_assets(){

		wp_enqueue_style('child-styles', plugins_url('assets/css/styles.css', __FILE__));
		wp_enqueue_script('child-js', plugins_url('assets/js/scripts.js', __FILE__), array( 'jquery' ), false, true);
		wp_localize_script('child-js', 'cartQtyAjax',    array( 
			'url'		=> admin_url( 'admin-ajax.php' ),
			'home_url'	=> get_home_url(),  
		) );

        $ajax_data = array(
            'url'       => admin_url( 'admin-ajax.php' ),
        );
        if ( is_user_logged_in() ) {
            $ajax_data['user_id'] = get_current_user_id();
        }

        wp_localize_script( 'cua-scripts', 'cuaAjax', $ajax_data);
    }

    public function ajax_qty_cart() {

	    // Set item key as the hash found in input.qty name
	    $cart_item_key = $_POST['hash'];

	    // Get the array of values owned by the product we're updating
	    $threeball_product_values = WC()->cart->get_cart_item( $cart_item_key );

	    // Get the quantity of the item in the cart
	    $threeball_product_quantity = apply_filters( 'woocommerce_stock_amount_cart_item', apply_filters( 'woocommerce_stock_amount', preg_replace( "/[^0-9\.]/", '', filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT)) ), $cart_item_key );

	    // Update cart validation
	    $passed_validation  = apply_filters( 'woocommerce_update_cart_validation', true, $cart_item_key, $threeball_product_values, $threeball_product_quantity );

	    // Update the quantity of the item in the cart
	    if ( $passed_validation ) {
	        WC()->cart->set_quantity( $cart_item_key, $threeball_product_quantity, true );
	    }

	    WC()->cart->calculate_totals();
	    WC()->cart->maybe_set_cart_cookies();

		ob_start();
		include( $this->myplugin_plugin_path() . '/woocommerce/cart/mini-cart.php' );
		$mini_cart = ob_get_contents();
		ob_end_clean();

	    /*return true;*/
	    wp_send_json(array(
	    	'cart_total'		=> WC()->cart->get_cart_total(),
	    	'cart_total_items'	=> WC()->cart->get_cart_contents_count(),
	    	'caterogories'		=> $this->get_list_cats_items_html(),
	    	'minicart'			=> $mini_cart
	    ));
	}

	public function minicart_cats_items_shortcode_update($array){
		$array['.categories-cart-items'] = $this->get_list_cats_items_html();
		return $array;
	}

	public function get_list_cats_items_html(){

		$product_cats_names = array(
			'led-headlights',
			'off-road-lights',
			'led-auto-bulbs',
			'accessories',
		);

		$hl_count = 0;
		$or_count = 0;
		$bu_count = 0;
		$ac_count = 0;
			
		$product_cats_count = array(
			'led-headlights'	=> $hl_count,
			'off-road-lights'	=> $or_count,
			'led-auto-bulbs'	=> $bu_count,
			'accessories'		=> $ac_count,
		);

		global $woocommerce;
	    $items = $woocommerce->cart->get_cart();

	    foreach($items as $item => $values) { 

	        $_product =  wc_get_product( $values['data']->get_id()); 
	        $quantity = $values['quantity'];
	        $parent_id = $_product->get_parent_id();

	        if ( $parent_id > 0 ) {

		        if( is_object_in_term(  $parent_id, 'product_cat', 'led-headlights' ) ){
		    		$hl_count += $quantity;
		    	}

		    	if( is_object_in_term(  $parent_id, 'product_cat', 'off-road-lights' )){
		    		$or_count += $quantity;
		    	}

		    	if( is_object_in_term(  $parent_id, 'product_cat', 'led-auto-bulbs' )){
		    		$bu_count += $quantity;
		    	}

		    	if( is_object_in_term(  $parent_id, 'product_cat', 'accessories' )){
		    		$ac_count += $quantity;
		    	}

	        } else {

	       		if( is_object_in_term(  $values['data']->get_id(), 'product_cat', 'led-headlights' ) ){
		    		$hl_count += $quantity;
		    	}

		    	if( is_object_in_term(  $values['data']->get_id(), 'product_cat', 'off-road-lights' )){
		    		$or_count += $quantity;
		    	}

		    	if( is_object_in_term(  $values['data']->get_id(), 'product_cat', 'led-auto-bulbs' )){
		    		$bu_count += $quantity;
		    	}

		    	if( is_object_in_term(  $values['data']->get_id(), 'product_cat', 'accessories' )){
		    		$ac_count += $quantity;
		    	}

	        }
	    }
		$out = "";
			$out .= '<ul class="categories-cart-items">';
				$out .= '<li>HL <span>' . $hl_count . '</span></li>';
				$out .= '<li>OR <span>' . $or_count . '</span></li>';
				$out .= '<li>BU <span>' . $bu_count . '</span></li>';
				$out .= '<li>AC <span>' . $ac_count . '</span></li>';
				$out .= '<li class="cats-items"> = '. $woocommerce->cart->get_cart_contents_count() . '</li>';
			$out .= '</ul>';
		return $out;
	}	

	public function minicart_cats_items_handler( $attr ) {
		
		$custom_id = (object)shortcode_atts( array(
			'custom_id' => ''
		), $attr );

	    if ( $custom_id->custom_id != '' ) {
	     	$custom_id_text = 'id="' . $custom_id->custom_id . '"';
	     } 
	    $out = '<div ' . $custom_id_text . ' class="categories-cart-items-wrap">'; 
	    $out .= $this->get_list_cats_items_html();
		$out .= '</div>';

		return $out;
	}

	private function myplugin_plugin_path() {

	  // gets the absolute path to this plugin directory

	  return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public function myplugin_woocommerce_locate_template( $template, $template_name, $template_path ) {
	  global $woocommerce;

	  $_template = $template;

	  if ( ! $template_path ) $template_path = $woocommerce->template_url;

	  $plugin_path  = $this->myplugin_plugin_path() . '/woocommerce/';

	  // Look within passed path within the theme - this is priority
	  $template = locate_template(

	    array(
	      $template_path . $template_name,
	      $template_name
	    )
	  );

	  // Modification: Get the template from this plugin, if it exists
	  if ( ! $template && file_exists( $plugin_path . $template_name ) )
	    $template = $plugin_path . $template_name;

	  // Use default template
	  if ( ! $template )
	    $template = $_template;

	  // Return what we found
	  return $template;
	}
}
new Onepix_Custom_Mini_Cart();

