<?php
/**
 * Plugin Name: Bulk Pricing Calculator for Woocommerce
 * Description: A Plugin for Woocommerce that allows bulk price calculations based
 * Version: 0.5.1
 * Author: Lorenz Kopczynski
 * Author URI: http://www.superduper.ch
 * Requires at least: 3.5
 * Tested up to: 3.5
 *
 * Text Domain: bulkpricing
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Sorry, you are not allowed to access this file directly.' );
}

class bulkdiscount_price_calculator{
	
	public function __construct(){		
		add_action( 'woocommerce_init', array($this, 'init' ) );
	}
	
	public function init(){
	
		// add scripts
		add_action( 'wp_enqueue_scripts' ,										array($this, 'frontend_scripts') );
		add_action( 'admin_enqueue_scripts' ,									array($this, 'frontend_scripts') );
		
		// stylesheets
		add_action( 'admin_init', 														array( $this, 'add_stylesheet' ) );

		// Add panels
		add_action( 'woocommerce_product_write_panel_tabs', 	array( $this, 'add_panel_tab' ) );
		add_action( 'woocommerce_product_write_panels',     	array( $this, 'add_panel' ) );
		
		// Process data when product is saved
		add_action( 'woocommerce_process_product_meta',     	array( $this, 'product_save_data' ), 10, 2 );
		
		// frontend product processing
		add_filter( 'woocommerce_add_cart_item_data', 						array( $this, 'add_cart_item_data'), 10, 2 );
		add_filter( 'woocommerce_add_cart_item', 							array( $this, 'add_cart_item'), 10, 1 );
		add_filter( 'woocommerce_get_cart_item_from_session',	 			array( $this, 'get_cart_item_from_session'), 10, 2 );
		add_filter( 'woocommerce_get_item_data', 							array( $this, 'get_item_data'), 10, 2 );

		add_action( 'init', 												array( $this, 'adjust_price_for_quantity'), 10, 0 ); 

	}
	
	
  /************************************************************
	 *  Frontend item/cart/order functions
	 ************************************************************/	 
	
	/**
	 *  function adjust_price_for_quantity
	 *  loaded when woocommerce gets initiated
	 *  updates prices for products in carts, based on bulk discounts	 	 
	 */	 		
	function adjust_price_for_quantity(){
		global $woocommerce;	
		if( !isset( $woocommerce -> cart ) ) return;
		foreach( $woocommerce->cart->cart_contents as $item => $data ){
			$newprice = $this -> get_discount( $data['product_id'], $data['quantity'] );
			if( $newprice !== false ){
				$woocommerce->cart->cart_contents[$item]['data'] -> price = $newprice;
			}
			$woocommerce -> cart -> calculate_totals();			
		}
		$woocommerce -> cart -> set_session();
	}
		 	
			 
	/**
	 *  function get_item_data
	 *  @param $other_data
	 *  @param $cart_item
	 *  
	 *	add information to the item	 	 	 
	 */	
	function get_item_data( $other_data, $cart_item ) {		
		if( isset( $cart_item['bulkdiscount_data'] ) ){
			$discount = $this -> has_discount( $cart_item['product_id'], $cart_item['quantity'] );
			if( $discount ) $other_data[] = array( 'name' => 'Mengenrabat', 'value' => $cart_item['quantity'] . ' St&uuml;ck' );
		}	
		return $other_data;
	}	

	/**
	 *  function add_cart_item_data
	 *  @param $cart_item_meta
	 *  @param $product_id
	 *  
	 *	add additional meta data to the cart item	 	 
	 */		
	function add_cart_item_data( $cart_item_meta, $product_id ) {
		global $woocommerce;
		
		$bulkdiscount_data = $this -> get_bulkdiscount_data();
		$cart_item_meta['bulkdiscount_data'] = array(); 
		return $cart_item_meta;
	}
	
	/**
	 *  function add_cart_item
	 *  @param $cart_item
	 *  
	 *	add the item to the cart 	 
	 */		
	function add_cart_item( $cart_item ){
		if ( isset( $cart_item['bulkdiscount_data'] ) ){		
			$bulkdiscount_data = $this -> get_bulkdiscount_data( $cart_item['data'] -> id );
			if( !$bulkdiscount_data || $bulkdiscount_data['active'] !== 'yes'  ) return $cart_item;
			$qty = $cart_item['bulkdiscount_data']['quantity'];
			//$cart_item['bulkdiscount_data']['origin_price'] = $cart_item['data']->price;
			$cart_item['data'] -> price = $this -> get_discount( $cart_item['data'] -> id, $qty );	
		}
								
		return $cart_item;
	}
	
	/**
	 *  function get_cart_item_from_session
	 *  @param $cart_item
	 *  @param $values	 
	 *  
	 *	add the cart item and cart item data from session 
	 */	
	function get_cart_item_from_session( $cart_item, $values ){
		global $woocommerce;

		if( isset( $values['bulkdiscount_data'] ) ){
			$cart_item['bulkdiscount_data'] = $values['bulkdiscount_data'];
			$cart_item['price'] = $this -> get_discount( $cart_item['product_id'], $cart_item['quantity'] );
			$cart_item = $this->add_cart_item( $cart_item );
		}
		
		return $cart_item;
	}
	

	/************************************************************
	 *  Frontend content and assets
	 ************************************************************/	 	

	/**
	 *  function frontend_scripts
	 *  add the javascript to perform frontend actions	 
	 */	 	
	public function frontend_scripts(){
		wp_enqueue_script(
			'bulkdiscount_frontend_scripts',
			plugins_url( '/assets/js/scripts.js', __FILE__ )
		);
	} 
	
	/**
	 *  function add_to_product_page
	 *  add the field(s) to the product page if is active
	 *  hooked into 'woocommerce_process_product_meta'
	 */	
	public function add_to_product_page(){
		global $post;
		$bulkdiscount_data = $this -> get_bulkdiscount_data();

		if( $bulkdiscount_data == false ) return;

		echo '<script type="text/javascript">';
		echo 'var pbs_post_id = ' . $post -> ID . ';';
		echo '</script>'; 
		
		?>
		<p>	
			<label for="_pbs_chosen_length">Länge (cm)</label>
			<input type="number" id="_pbs_chosen_length" name="_pbs_chosen_length" class="input-text text" size="4" min="<?php echo $bulkdiscount_data['min'];?>" max="<?php echo $bulkdiscount_data['max'];?>"  value="<?php echo $bulkdiscount_data['min'];?>">
		</p>
		<p class="price">
			<span class="price" id="_pbs_price_indicator" currency="<?php echo get_woocommerce_currency(); ;?>">
				
			</span>
		</p>
		<?php
	}
	
	
	/**
	*	function add_stylesheet
	*	add the plugin stylehsset
	*/
	function add_stylesheet() {
       wp_register_style( 'bulkdiscount_styles', plugins_url('assets/css/styles.css', __FILE__) );
       wp_enqueue_style( 'bulkdiscount_styles' );
   }



	/************************************************************
	 *  Admin functions
	 ************************************************************/	 	

	/**
	 *  function add_panel_tab
	 *  add the tab for the settings-panel in the woocommerce product meta panels	 
	 */	 	 	
	public function add_panel_tab() {
		echo "<li class=\"attributes_tab attribute_options\"><a href=\"#bulkdiscount_active_tab\">" . __( 'Mengenrabatt' ) . "</a></li>";
	}
		
	/**
	 *  function add_panel
	 *  add the panel containing the settings in the woocommerce product meta panels	 
	 */	 
	public function add_panel() {
		global $post;
		// the product

		$data = get_post_meta($post->ID, '_bulkdiscount_data', true);
		$price = get_post_meta($post->ID, '_regular_price', true);
		$active = ($data['active'] == 'yes' && count($data) > 0) ? 'yes' : 'no';
		$checked = ($active == 'yes') ? 'checked' : '';
		$discounts = 0;

		if( !$data ){
			$data = array(
				'discounts' => array(
					array(
						'min' 	=> '',
						'max' 	=> '',
						'price'	=> ''
					)
				)
			);
		}
		//$result=eval("return ($formula);");
		
		?>
		<div id="bulkdiscount_active_tab" class="panel wc-metaboxes-wrapper woocommerce_options_panel">
			<p class="form-field _bulkdiscount_active ">
				<label for="_bulkdiscount_active"> Aktiv </label>
				<input type="checkbox" class="short" name="_bulkdiscount_active" id="_bulkdiscount_active" value="yes" <?php echo $checked; ?>> 
			</p>
			<?php foreach( $data['discounts'] as $discount ){ ?>
			<div class="options_group bulkdiscount">
				<p class="form-field _bulkdiscount ">
					<label for="_bulkdiscount[<?php echo $discounts; ?>]"> Min/Max</label>
					<input type="number" class="bulkdiscount_min_max bulkdiscount_min" size="6" name="_bulkdiscount[<?php echo $discounts; ?>][min]" value="<?php echo $discount['min'];?>" step="1"> 
					<input type="number" class="bulkdiscount_min_max bulkdiscount_max" size="6" name="_bulkdiscount[<?php echo $discounts; ?>][max]" value="<?php echo $discount['max'];?>" step="1">
					<span class="description">Mindest und Maximalstückzahl für den Rabatt. 0 Wenn nichts gesetzt werden soll.</span>
				</p>
				<p class="form-field _bulkdiscount">
					<label for="_bulkdiscount[<?php echo $discounts; ?>][price]"> Der Preis</label>
					<input type="number" class="short _bulkdiscount_price" name="_bulkdiscount[<?php echo $discounts; ?>][price]" value="<?php echo $discount['price'];?>" step="any" min="0"> 					
				</p>


			</div>
			<?php $discounts++;}?>			

			<button type="button" class="button button-primary add_discount" id="add_discount">Hinzufügen</button>
		</div>
		<?php
	}
	
	
	/**
	 *  function product_save_data
	 *  process plugin specific meta data when saving the product
	 *  hooked into 'woocommerce_process_product_meta'
	 *  	 
	 *  @param int 	$post_id	  
	 */	 
	public function product_save_data( $post_id ) {
		$data = array();
		$data['discounts'] = array();
		$data['active'] = ( $_POST['_bulkdiscount_active'] == 'yes' ) ? 'yes' : 'no';
		foreach( $_POST['_bulkdiscount'] as $discount ){
			if( !isset($discount['min']) && !isset($discount['max']) || !isset($discount['price']) ) continue;
			$data['discounts'][] = array(
				'min' 	=> (int) $discount['min'],
				'max' 	=> (int) $discount['max'],
				'price' => $discount['price']
			);
		}
		
		update_post_meta( $post_id, '_bulkdiscount_data', $data );
	}
	
	/**
	 *  function get_bulkdiscount_data
	 *  get the product bulkdiscount data if exists	 
	 *  	 
	 *  @param opt int 	$p	  
	 */	 

	public function get_bulkdiscount_data($p=false){
		global $post;
		$currentPost = ( !$post || isset( $p ) ) ? $p : $post;
		if( !is_int( $currentPost ) && is_int( $currentPost->ID ) ) $currentPost = $currentPost -> ID;
		$bulkdiscount_data = get_post_meta( $currentPost, '_bulkdiscount_data', true );

		if( !isset($bulkdiscount_data) || $bulkdiscount_data['active'] !== 'yes' ){
			return false;
		}

		return $bulkdiscount_data;
	}
	
	/**
	 *  function has_discount
	 *  check if product has discount for the given quantity 
	 *  	 
	 *  @param $p	  
	 *  @param $qty	 
	 */	 
	public function has_discount($p, $qty){
		$bd = $this -> get_bulkdiscount_data($p);
		if( !$bd ) return false;

		foreach( $bd['discounts'] as $d ){
			if( $d['min'] <= $qty && ( $qty <= $d['max'] || $d['max'] == 0 ) )
			{
				return $d['price']; 
			}	
		}
		return false;	
	}

	/**
	 *  function get_discount
	 *  return the discounted price
	 *  if not discounted, returns regular price	  
	 *  	 
	 *  @param $p	  
	 *  @param $qty	 
	 */	 
	public function get_discount($p, $qty){
		$bd = $this -> get_bulkdiscount_data($p);
		if( !$bd ) return false;
		foreach( $bd['discounts'] as $d ){
			if( $d['min'] <= $qty && ( $qty <= $d['max'] || $d['max'] == 0 ) )
			{
				return $d['price']; 
			}	
		}
		
		$price = get_post_meta( $p, '_sale_price', true);
		if( !$price ) $price = get_post_meta( $p, '_regular_price', true);
		return $price;
	}
}


// initiate the plugin
global $bulkdiscount;
$bulkdiscount = new bulkdiscount_price_calculator();


?>