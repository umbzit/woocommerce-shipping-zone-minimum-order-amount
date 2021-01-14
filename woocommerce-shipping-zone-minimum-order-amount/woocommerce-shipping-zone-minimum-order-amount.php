<?php 
/**
 * Plugin Name:       Woocommerce Minimum Order Amount per Country & Shipping Zone 
 * Plugin URI:        https://www.cybstudio.com
 * Description:       Add a minimum order amount for each shipping zone
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Umberto De Palma
 * Author URI:        https://www.cybstudio.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wcszmamnt
 * Domain Path:       /languages
 */


 /**
 * Exit if accessed directly
 **/
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * check if woocommerce is active
 **/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}


/**
 * add a new section (minimum_order_amount_per_zone) in the shipping tab
 **/
add_filter( 'woocommerce_get_sections_shipping', 'wcszmamnt_add_section' );
function wcszmamnt_add_section( $sections ) {
	$sections['minimum_order_amount_per_zone'] = __( 'Minimum Order Amount per Shipping Zone', 'wcszmamnt' );
	return $sections;
}


/**
 * Add settings to the new section (minimum_order_amount_per_zone) and a new subsection to develop later on
 */
 
add_filter( 'woocommerce_get_settings_shipping', 'wcszmamnt_get_settings', 10, 2 );
function wcszmamnt_get_settings( $settings, $current_section ) {

	if ( $current_section == 'minimum_order_amount_per_zone' ) {

		$custom_settings = array();

		// Add Title and description to the Settings 
		$custom_settings[] = array( 
			'name' => __( 'Minimum order amount', 'wcszmamnt' ), 
			'type' => 'title', 
			'desc' => __( 'Set a minimum order amount per shipping zone', 'wcszmamnt' ), 
			'id' => 'wcszmamnt_id' 
		);

		// Add a text field for each shipping zone
		$delivery_zones = WC_Shipping_Zones::get_zones();
		foreach ((array) $delivery_zones as $key => $the_zone ) {

			$custom_settings[]  = array(
			'name'     => __( $the_zone['zone_name'], 'wcszmamnt' ),
			'desc_tip' => __( 'This will add a minimum order amount to your '. $the_zone['zone_name'] . ' shipping zone', 'wcszmamnt' ),
			'id'       => $the_zone['zone_id'],
			'type'     => 'text',
			'desc'     => __( 'Mimimum order amount for ', 'wcszmamnt' ).$the_zone['zone_name'],
			'default'  => '0',
				);
		}

		// Section end
	    $custom_settings[] = array( 'type' => 'sectionend',
	     'id' => 'wcszmamnt_id' 
	 	);

	    return $custom_settings;
  
	} else {
	    return $settings;
	}
}




/**
 * add controls to checkout
 **/

/** extract minimum order value in current shipping zone **/

add_action( 'woocommerce_before_checkout_form', 'wc_minimum_order_amount', 12 );
add_action( 'woocommerce_before_cart' , 'wc_minimum_order_amount' );

add_action( 'wp_ajax_minimum_order_amount' , 'wc_minimum_order_amount_ajax' );
add_action( 'wp_ajax_nopriv_minimum_order_amount' , 'wc_minimum_order_amount_ajax' );



function wc_minimum_order_amount() {


$myShipping_packages =  WC()->cart->get_shipping_packages();
$myShipping_zone = wc_get_shipping_zone( reset( $myShipping_packages ) );
$myZone_id   = $myShipping_zone->get_id(); // Get current  zone ID

// Get current shipping zone ID dalle shipping zones inserite nelle impostazioni di default
$shipping_zones = WC_Shipping_Zones::get_zones();

foreach ((array) $shipping_zones as $key => $the_zone ) {

	// get setted minimum order value.

	$myZoneMinimum = get_option( $the_zone["id"], true );

	if ($myZoneMinimum==""){
		$myZoneMinimum = 0;
	}
	// check, and eventually print error.
	} 
	
    if ( WC()->cart->total < intval($myZoneMinimum) ) {

        if( is_cart() ) {

	            wc_add_notice( 
	                sprintf( 'Your current order total is %s — you must have an order with a minimum of %s to place your order ' , 
	                    wc_price( WC()->cart->total ), 
	                    wc_price( $myZoneMinimum )
	                ), 'error' 
	            );


	        } else {
				wc_clear_notices();

	            wc_add_notice( 
	                sprintf( 'Your current order total is %s — you must have an order with a minimum of %s to place your order' , 
	                    wc_price( WC()->cart->total ), 
	                    wc_price( $myZoneMinimum )
	                ), 'error' 
				);
			
	        }
	    } 

}


// duplicare function - ajax reload on country change control
function wc_minimum_order_amount_ajax() {


	$myShipping_packages =  WC()->cart->get_shipping_packages();
	$myShipping_zone = wc_get_shipping_zone( reset( $myShipping_packages ) );
	$myZone_id   = $myShipping_zone->get_id(); // Get current  zone ID
	
	// Get current shipping zone ID from settings shipping zones 
	$shipping_zones = WC_Shipping_Zones::get_zones();
	
	$myZoneMinimum = 0;
	foreach ((array) $shipping_zones as $key => $the_zone ) {
	
		// get setted minimum order value.
	if($the_zone["formatted_zone_location"] == $_POST["data"]) {
		$myZoneMinimum = get_option( $the_zone["id"], true );
		if ($myZoneMinimum==""){
			$myZoneMinimum = 0;
		}
	}
	
	// check, and eventually print error.
	} 
	

	if ( WC()->cart->total < $myZoneMinimum ) {
		
		if( is_cart() ) {

				wc_add_notice( 
					sprintf( 'Your current order total is %s — you must have an order with a minimum of %s to place your order ' , 
						wc_price( WC()->cart->total ), 
						wc_price( $myZoneMinimum )
					), 'error' 
				);


			} else {


				wc_clear_notices();
				wc_add_notice( 
					sprintf( 'Your current order total is %s — you must have an order with a minimum of %s to place your order' , 
						wc_price( WC()->cart->total ), 
						wc_price( $myZoneMinimum )
					), 'error' 
				);

				echo 'true';
				die;
			}
			
	} else {
		echo 'false';
		die;
	}
}


// enqueue ajax - Update Order Review Via Ajax in checkout
add_action( 'wp_enqueue_scripts', 'custom_add_scripts_min' );

function custom_add_scripts_min() { 
wp_enqueue_script( 'custom_min_order_amount_js', plugin_dir_url( __FILE__ ) . 'js/wcszmamnt.js', array( 'jquery' ), '', false );

			
wp_localize_script('custom_min_order_amount_js', 'Amount_Js_Call',
	array(
			'ajax_url' => admin_url('admin-ajax.php'),
	)
);
}

