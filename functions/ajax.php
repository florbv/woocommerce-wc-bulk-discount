<?php

$base_dir = dirname(__FILE__);
// go up 4 directories
for($i=0; $i < 4;$i++){
	$base_dir = dirname($base_dir);
}

define( 'DOING_AJAX', true );
define( 'WP_ADMIN', true );

/require_once($base_dir . '/wp-load.php');

if( isset($_GET['action']) ){
	
	switch( $_GET['action'] ){
		case 'price_for_length':
			getPriceForLength($_GET['pid'], $_GET['l']);
		
	}
}


function getPriceForLength($productID, $length){
	global $woocommerce;

	$return = array();
	$pbs_data = get_post_meta($productID, '_pbs_data', true);
	
	if( !$pbs_data['formula'] ) return false;
	$formula = $pbs_data['formula'];


	$return['price'] = get_post_meta($productID, '_regular_price', true);
	$return['_sale_price'] = get_post_meta($productID, '_sale_price', true);
	$return['length'] = $length;
	$return['priceForLength'] = eval( "return ( $formula );" );
                        
	echo json_encode($return);
	exit;
}


?>