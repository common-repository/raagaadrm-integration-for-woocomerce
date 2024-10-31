<?php
/*
  Plugin Name: raagaadrm-integration-for-woocomerce
  Plugin URI: https://www.raagaatechnologies.com/woocomerce
  Description: A plugin that allows integration between your WooCommerce store and raagaaDRM
  Version: 1.1.0
  Author: RaagaaDRM Dev Team <support@raagaatechnologies.com>
  Author URI: https://www.raagaatechnologies.com
 */

//include( plugin_dir_path(__FILE__) . 'rg_drm_api.php');



//session_start();
 global $apiurl; $apiurl = 'https://raagaatechnologiesapilive.azurewebsites.net/api/V1/';
 
 
$post_url_ref = explode($_SERVER["SERVER_NAME"], site_url("/wp-admin/post.php"));
$post_new_url_ref = explode($_SERVER["SERVER_NAME"], site_url("/wp-admin/post-new.php"));

$post_url = array_pop($post_url_ref);
$post_new_url = array_pop($post_new_url_ref);

if (($_SERVER["SCRIPT_NAME"] == $post_url) && $_GET["post"]) {

$po1=sanitize_text_field($_GET["post"]);
    $post = get_post($po1);

    if ($post->post_type == "product")
        $show_raagaa_drm  = true;
}
elseif (($_SERVER["SCRIPT_NAME"] == $post_new_url) && ($_GET["post_type"] == "product"))
    $show_raagaa_drm  = true;
else
    $show_raagaa_drm  = false;



if ($show_raagaa_drm ) {
    wp_register_script('woocommerce_raagaadrm', plugins_url('/js/woocommerce_raagaadrm.js', __FILE__), array("jquery"));
    wp_enqueue_script('woocommerce_raagaadrm');
	//wp_register_script('jquery-ui', plugins_url('/js/jquery-ui.js', __FILE__), array("jquery"));
    
  wp_enqueue_script( 'jquery-ui-core');
  wp_enqueue_script('jquery-ui-tooltip');

  wp_enqueue_script( 'jquery-ui-autocomplete');
   wp_enqueue_script( 'jquery-ui-button');
 	


    wp_register_style('woocommerce_raagaadrm', plugins_url('/css/woocommerce_raagaadrm.css', __FILE__), array('jquery-ui'));
    wp_enqueue_style('woocommerce_raagaadrm');
    wp_register_style('jquery-ui', plugins_url('/css/jquery-ui.css', __FILE__));
    wp_enqueue_style('jquery-ui');
    $secret = get_option('raagaa_drm_org_secret');
    $nonce = rand(1000000, 999999999);
    $email = get_option('raagaa_drm_org_email ');
    if ($email == "")
        $email = "";
    if ($secret)
        $hash = hash_hmac("sha1", $nonce . $email, base64_decode($secret));
    else
        $hash = "";
		$postId = sanitize_text_field($_GET["post"]);
    $on = get_post_meta($postId, "_use_raagaa_drm", true);
    $p_id = get_post_meta($postId, "_rg_prod_id", true);
    $title = get_post_meta($postId, "_use_raagaa_drm_title");
    $companyID = get_post_meta($postId, "_rg_company_id");

    if (($email != "") && ($hash != "")) {
        $data = array("email" => $email, "nonce" => $nonce, "hash" => $hash);

    // $api = new rg_drm_api();

      //  $library = $api->getBookList($email,$secret);
		
			$url= $apiurl.'GetCompanyProducts?email_Id='.$email;
		$args = array(
    'headers' => array(
        'Authorization'=>$secret
    )
);
$response=wp_remote_get($url, $args);
$library=json_decode($response['body']);


    } else {
        $library = "";
    }

    $translation_array = array(
        'plugin_path' => $pluginurl = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)) . basename(__FILE__),
        'plugin_dir' => $pluginurl = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)),
        'email' => $email,
        'return_url' => base64_encode(esc_url($_SERVER["REQUEST_URI"])),
        'on' => $on,
        'p_id' => $p_id,
        'title' => $title,
        'library' => $library,
        'comapny_id' => $companyID
    );
    wp_localize_script('woocommerce_raagaadrm', 'woo_eg', $translation_array);
}

add_action('save_post', 'woo_rgdrm_woocommerce_rg_product_save', 10, 2);

function woo_rgdrm_woocommerce_rg_product_save($post_id, $post) {
    if (is_int(wp_is_post_revision($post_id)))
        return;
    if (is_int(wp_is_post_autosave($post_id)))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;

    if (!current_user_can('edit_post', $post_id))
        return $post_id;
    if ($post->post_type != 'product')
        return $post_id;

    if (!empty($_REQUEST['_rg_prod_id']))
	
	$rgId = sanitize_text_field($_REQUEST['_rg_prod_id']); 
	
        update_post_meta($post_id, '_rg_prod_id', stripslashes($rgId));
    if (!empty($_REQUEST['_rg_company_id']))
	$companyId = sanitize_text_field($_REQUEST['_rg_company_id']); 
        update_post_meta($post_id, '_rg_company_id', stripslashes($companyId));
$usedrm = sanitize_text_field($_REQUEST['_use_raagaa_drm']);
    update_post_meta($post_id, '_use_raagaa_drm', stripslashes($usedrm));

    if (!empty($_REQUEST['_rg_title']))
	$rgtitile = sanitize_text_field($_REQUEST['_rg_title']);
        update_post_meta($post_id, '_use_raagaa_drm_title', stripslashes($rgtitile));
}

//add_action('woocommerce_add_order_item_meta', 'woo_rgdrm_add_file_url_to_order_item_meta', 1, 2);

function woo_rgdrm_add_file_url_to_order_item_meta($item_id, $item) {
    if (get_post_meta($item['product_id'], "_use_raagaa_drm", true)) {
        $email = get_option('raagaa_drm_org_email ');
        $secret = get_option('raagaa_drm_org_secret');
        $resourceId = get_post_meta($item['product_id'], "_rg_prod_id", true);
        $companyid = get_post_meta($item['product_id'], "_rg_company_id", true);
        $bookData = array();



 		$email = filter_input(INPUT_POST, "billing_email");
        $links = array();
       // $api = new rg_drm_api();
        for ($i = 0; $i < $item['quantity']; $i++) {
           // $transaction = $api->createTransaction($companyid, $resourceId,$email,$secret);
		  $tansdata['customer_Email']=$email;
$tansdata['product_Id']=$resourceId;
$tansdata['company_Id']=$companyid;
$transjson=wp_json_encode($tansdata);

$args = array(
    'body'        => $transjson,
    'timeout'     => '5',
    'redirection' => '5',
    'httpversion' => '1.0',
    'blocking'    => true,
    'headers'     => array('Authorization'=>$secret,'Content-Type'=>'application/json'),
    
);
$url='https://raagaatechnologiesapilive.azurewebsites.net/api/V1/SetProductsToCustomers';

$response = wp_remote_post($url, $args );
//$transaction = unserialize( wp_remote_retrieve_body( $response ) );
$transaction = json_decode(wp_remote_retrieve_body( $response ), TRUE );


//$transaction=$response;

            $links[] = $transaction['customer_Product_id'];
        }



        if (!empty($transaction)) {
            if (function_exists("wc_add_order_item_meta")) {
                wc_add_order_item_meta($item_id, '_rg_r_id', serialize($links));
            } else {
                woocommerce_add_order_item_meta($item_id, '_rg_r_id', serialize($links));
            }
        }
    }
}


add_action('admin_menu', 'woo_rgdrm_register_custom_menu_page_rgdrm', 9999);

function woo_rgdrm_register_custom_menu_page_rgdrm() {
    $res = add_submenu_page('woocommerce', 'Raagaadrm', 'Raagaadrm', 'manage_woocommerce', 
	'woo_raagaa_drm', 'woo_rgdrm_settings');
}

function woo_rgdrm_settings() {
    global $wpdb;
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    if ($_POST['submit']) {
		
		
        foreach ($_POST as $v => $k) {
            if ($v == 'submit')
                continue;
            update_option($v, $k);
        }
        $_SESSION["options_updated_settings_drm"] = 1;
    }
    if ($_SESSION["options_updated_settings_drm"]) {
        unset($_SESSION["options_updated_settings_drm"]);
        ?><div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Settings saved.</strong></p></div><?php if ($_REQUEST["return_url"]) { ?><script>jQuery(document).ready(function () {
                            if (confirm("Do you want to get back to editing your product?"))
                                window.location.href = '<?php echo esc_url($_REQUEST["return_url"]) ?>';
                        })</script> <?php
                    }
                }
                ?><style>
        .wrap label {line-height: 24px;margin-right:10px}
        .wrap input {}
        .wrap li {display: table-cell; vertical-align: top;}
    </style>

    <div class="wrap">
        <div id="icon-options-general" class="icon32">
            <br>
        </div>
        <h2>RaagaaDRM for WooCommerce Settings</h2>

        <form method="POST" action="">
            <ul>
                <li>
                    <label for="raagaa_drm_org_email">Email</label><br />
                    <label for="raagaa_drm_org_secret">Shared Secret</label><br />
                </li>
                <li>

                    <input type="email" required="required" name="raagaa_drm_org_email" <?php if (get_option('raagaa_drm_org_email')) echo 'value="' . get_option('raagaa_drm_org_email') . '"' ?> /><br />
                    <input type="text" required="required" name="raagaa_drm_org_secret" <?php if (get_option('raagaa_drm_org_secret')) echo 'value="' . get_option('raagaa_drm_org_secret') . '"' ?> /><br />
                </li>
            </ul>
            <a href="http://www.raagaatechnologies.com">Don't have an RaagaaDRM account? Get started </a><br/><br/>
            <input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes">
        </form>
    </div>
    <?php
}






add_filter( 'woocommerce_product_data_tabs', 'woo_rgdrm_raagaa_drm_tab' );

function woo_rgdrm_raagaa_drm_tab( $tabs) {
	// Key should be exactly the same as in the class product_type
	$tabs['gift_card'] = array(
		'label'	 => __( 'RaagaaDRM', 'wcpt' ),
		'target' => 'raagaa_drm_options',
		'class'  => array('show_if_virtual'),
	);
	return $tabs;
}

 
add_action( 'woocommerce_product_data_panels', 'woo_rgdrm_wk_custom_tab_data' );
 
function woo_rgdrm_wk_custom_tab_data() {
   echo '<div id="raagaa_drm_options" class="panel woocommerce_options_panel"><div class="show_if_raagaadrm show_if_virtual"></div></div>';
}






/**
 * @snippet       WooCommerce Add New Tab @ My Account
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 3.5.7
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
  
// ------------------
// 1. Register new endpoint to use for My Account page
// Note: Resave Permalinks or it will give 404 error
  
function rg_add_raagaa_drm_endpoint() {
    add_rewrite_endpoint( 'raagaa-drm', EP_ROOT | EP_PAGES );
}
  
add_action( 'init', 'rg_add_raagaa_drm_endpoint' );
  
  
// ------------------
// 2. Add new query var
  
function rg_raagaa_drm_query_vars( $vars ) {
    $vars[] = 'raagaa-drm';
    return $vars;
}
  
add_filter( 'query_vars', 'rg_raagaa_drm_query_vars', 0 );
  
  
// ------------------
// 3. Insert the new endpoint into the My Account menu
  
function woo_rgdrm_rg_add_raagaa_drm_link_my_account( $items ) {
    $items['raagaa-drm'] = 'RaagaaDRM Products';
    return $items;
}
  
add_filter( 'woocommerce_account_menu_items', 'woo_rgdrm_rg_add_raagaa_drm_link_my_account' );
  
  
// ------------------
// 4. Add content to the new endpoint
  
function rg_raagaa_drm_content() {

/*
  wp_register_script('woocommerce_raagaadrm', plugins_url('/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
    wp_enqueue_script('woocommerce_raagaadrm');*/
	
  		wp_register_script('woocommerce_raagaadrm', plugins_url('/js/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
        wp_enqueue_script('woocommerce_raagaadrm');
        wp_localize_script( 'woocommerce_raagaadrm', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php') ) ); 
		
		
   
	
global $wpdb;
$current_user = wp_get_current_user(); 
$user_id= $current_user->ID ;
 $secret = get_option('raagaa_drm_org_secret');

$customer_orders = get_posts( array(
    'numberposts' => -1,
    'meta_key'    => '_customer_user',
   
    'post_type'   => wc_get_order_types(),
    'post_status' => 'wc-completed',
) );

?>
        <table>
        <thead>
        <th></th>
         <th>Name</th>
          <th>Action</th>
          </thead>
          <tbody>
         

<?php
  
foreach ( $customer_orders as $customer_order ) {
   //echo  $customer_order->ID;
    $order = wc_get_order( $customer_order->ID );
   $items = $order->get_items();
   $email= $order->get_billing_email();
    foreach ( $items as $item ) {

if (get_post_meta($item['product_id'], "_use_raagaa_drm", true)) {
       // array_push($broughtid,$item->get_product_id());
      // echo  $orderid['pid']=$item->get_product_id();
	  // echo ',';
	    // echo $downloadUrls = unserialize($item['item_meta']['_rg_r_id'][0]);
		 //$a=(unserialize($item->get_meta('_rg_r_id')));
		 //$rid= $a[0];
		 $rid=$item->get_meta('_rg_r_id');

        $orderid['orderno']=$customer_order->ID;
		   
$product = wc_get_product($item['product_id']);
$attachment_ids = $product->get_gallery_attachment_ids();
		?>
          <tr>
         
          <td> 
          <?php //echo wp_get_attachment_image($attachment_ids[0], 'full');   $image_id  = $product->get_image_id(); echo $image_url = wp_get_attachment_image_url( $image_id, 'full' );
		  
		  
		  if ( has_post_thumbnail( $product->id ) ) {
                        $attachment_ids[0] = get_post_thumbnail_id( $product->id );
                         $attachment = wp_get_attachment_image_src($attachment_ids[0], 'thumbnail'); ?>    
                        <img src="<?php echo $attachment[0] ; ?>" style="height: 100px;"  class="drm-prod-image"  />
                    <?php } 
 ?>
          </td>
           <td>
           <?php echo $product->get_title(); ?>
          </td>
           <td>
          <button data-rid="<?php echo $rid;?>"  data-email="<?php echo $email;?>" class="woocommerce-button view-book-drm  p-histry-btn"> View Ebook</button>  
          </td>
           </tr>
         
	<!--	<div class="product-list-purchased">
    <div class="pr-left">
<div class="pr-image"> <?php ///echo wp_get_attachment_image($attachment_ids[0], 'full'); ?></div>
    </div>
    <div class="pr-right"> 
<div class="pr-title"> <?php //echo $product->get_title(); ?></div>
<div class=""> <?php //echo $product->get_price_html(); ?></div>
<div class="pr-desc"> <?php //echo $product->get_description(); ?></div>
  <button data-rid="<?php //echo $rid;?>"  data-email="<?php echo $email;?>" class="woocommerce-button view-book  p-histry-btn"> View Ebook</button>  
</div></div>-->
<?php 
     // echo   array_push($orderids,$orderid);
	}
}	
}

?>
    
	
          </tbody>
          
        </table>
        
        <?php

wp_reset_query();


		
       
}


function my_scripts_methods() {

    if (is_page('user-dashboard')) {
    wp_register_script('woocommerce_raagaadrm', plugins_url('/js/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
    wp_enqueue_script('woocommerce_raagaadrm');
    wp_localize_script( 'woocommerce_raagaadrm', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php') ) ); 
     }
 }
 add_action('wp_enqueue_scripts', 'my_scripts_methods');
add_action( 'wp_ajax_woo_rgdrm_ebook_request', 'woo_rgdrm_ebook_request' );
add_action( 'wp_ajax_woo_rgdrm_nopriv_ebook_request', 'woo_rgdrm_ebook_request' );  
add_action( 'woocommerce_account_raagaa-drm_endpoint', 'rg_raagaa_drm_content' );
// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format



function woo_rgdrm_ebook_request() {
	
	$rid = sanitize_text_field($_REQUEST['r_id']); 
	$email = sanitize_email($_REQUEST['email']); 
	 // $rid = $_REQUEST['r_id'];
          
             $secret = get_option('raagaa_drm_org_secret');
			
	//$api = new rg_drm_api();
	// $transaction = $api->getDownloadUrl($rid,$email,$secret);
 // echo   $transaction->product_Url;
  
  $url="https://raagaatechnologiesapilive.azurewebsites.net/api/V1/GetCustomerProducts?customer_Product_Id=".$rid."&customer_Email=".$email;
		$args = array(
    'headers' => array(
        'Authorization'=>$secret
    )
);
$response=wp_remote_get($url, $args);
$transaction=json_decode($response['body']);

echo $transaction->product_Url;

   exit;
	
	
}



// Rename, re-order my account menu items
function woo_rgdrm_fwuk_reorder_my_account_menu() {
    $neworder = array(
        'dashboard'          => __( 'Dashboard', 'woocommerce' ),
        'orders'             => __( 'Orders', 'woocommerce' ),
		 'raagaa-drm'             => __( 'RaagaaDRM Products', 'woocommerce' ),
        'edit-address'       => __( 'Addresses', 'woocommerce' ),
        'edit-account'       => __( 'Account Details', 'woocommerce' ),
        'customer-logout'    => __( 'Logout', 'woocommerce' ),
    );
    return $neworder;
}
add_filter ( 'woocommerce_account_menu_items', 'woo_rgdrm_fwuk_reorder_my_account_menu' );


add_shortcode( 'woo_rgdrm_prods', 'woo_rgdrm_product_shortcode' );

function woo_rgdrm_product_shortcode( $atts) {
	
	/*
  wp_register_script('woocommerce_raagaadrm', plugins_url('/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
    wp_enqueue_script('woocommerce_raagaadrm');*/
	
  		wp_register_script('woocommerce_raagaadrm', plugins_url('/js/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
        wp_enqueue_script('woocommerce_raagaadrm');
        wp_localize_script( 'woocommerce_raagaadrm', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php') ) ); 
		
		
  if ( is_user_logged_in() ) {
 
	
global $wpdb;
$current_user = wp_get_current_user(); 
$user_id= $current_user->ID ;

$customer_orders = get_posts( array(
    'numberposts' => -1,
    'meta_key'    => '_customer_user',
    'meta_value'  => $user_id,
    'post_type'   => wc_get_order_types(),
    'post_status' => 'wc-completed',
) );


       $cont.='<table>
        <thead>
        <th></th>
         <th>Name</th>
          <th>Action</th>
          </thead>
          <tbody>';
         


  
foreach ( $customer_orders as $customer_order ) {
    $order = wc_get_order( $customer_order->ID );
   $items = $order->get_items();
   $email= $order->get_billing_email();
    foreach ( $items as $item ) {

if (get_post_meta($item['product_id'], "_use_raagaa_drm", true)) {

		// $a=(unserialize($item->get_meta('_rg_r_id')));
         //$rid= $a[0];
         $rid=$item->get_meta('_rg_r_id');
}
        $orderid['orderno']=$customer_order->ID;
		   
$product = wc_get_product($item['product_id']);
$attachment_ids = $product->get_gallery_attachment_ids();
		
         
		 
		 
		 
		 
		 $cont.="<tr><td>"; 
         
		  if ( has_post_thumbnail( $product->id ) ) {
                        $attachment_ids[0] = get_post_thumbnail_id( $product->id );
                         $attachment = wp_get_attachment_image_src($attachment_ids[0], 'thumbnail');   
                     $cont.='<img src="'.$attachment[0].'" style="height: 100px;"  class="drm-prod-image"/>';
                   } 
 
      $cont.= '</td>
           <td>'
          . $product->get_title().
         '</td>
           <td>
          <button data-rid="'.$rid.'"  data-email="'.$email.'" class="woocommerce-button view-book  p-histry-btn"> View Ebook</button>  
          </td>
           </tr>' ; 


	}
	
}
	
      $cont.= "</tbody></table>";
        
wp_reset_query();		
 
 return $cont;   
}
 else{
	return 'Only for login user';
	}
}
   
   
   
   add_shortcode( 'woo_rgdrm_prod_link', 'woo_rgdrm_product_link_shortcode' );

function woo_rgdrm_product_link_shortcode( $atts) {
	
	/*
  wp_register_script('woocommerce_raagaadrm', plugins_url('/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
    wp_enqueue_script('woocommerce_raagaadrm');*/
	
  		wp_register_script('woocommerce_raagaadrm', plugins_url('/js/raagaa-drm-myaccount.js', __FILE__), array("jquery"));
        wp_enqueue_script('woocommerce_raagaadrm');
        wp_localize_script( 'woocommerce_raagaadrm', 'ajax_object', array( 'ajax_url' => admin_url('admin-ajax.php') ) ); 
		
$shortodeid=$atts['prodid'];

  if ( is_user_logged_in() ) {
 
	
global $wpdb;
$current_user = wp_get_current_user(); 
$user_id= $current_user->ID ;

$customer_orders = get_posts( array(
    'numberposts' => -1,
    'meta_key'    => '_customer_user',
    'meta_value'  => $user_id,
    'post_type'   => wc_get_order_types(),
    'post_status' => 'wc-completed',
) );
  
foreach ( $customer_orders as $customer_order ) {
    $order = wc_get_order( $customer_order->ID );
   $items = $order->get_items();
   $email= $order->get_billing_email();
    foreach ( $items as $item ) {

if ($shortodeid==$item['product_id']) {
if (get_post_meta($item['product_id'], "_use_raagaa_drm", true)) {

       
		 //$a=(unserialize($item->get_meta('_rg_r_id')));
       //  $rid= $a[0];
         $rid=$item->get_meta('_rg_r_id');
}
        $orderid['orderno']=$customer_order->ID;
		   
$product = wc_get_product($item['product_id']);

		
         
		 

 
      $cont.= '
          <button data-rid="'.$rid.'"  data-email="'.$email.'" class="woocommerce-button view-book  p-histry-btn"> View Ebook</button>  
         ' ; 


	}
	
}
}
        
wp_reset_query();		
 
 return $cont;   
}
 else{
	return 'Only for login user';
	}




}


add_action( 'woocommerce_order_status_changed', 'add_meta_order_data' ,99, 4);

function add_meta_order_data($order_id, $old_status, $new_status,$order ) { 

// file_put_contents('12.txt',$order_id);
   
if( $new_status == "completed" ) {
        //your code here
		
		$order = wc_get_order( $order_id );
$items = $order->get_items();
$order_data = $order->get_data(); 
 //$links = array();
foreach ( $items as $item ) {
	
	 if (get_post_meta($item->get_product_id(), "_use_raagaa_drm", true)) {
       // if($prodid==$item->get_product_id())
                {
    $email = get_option('raagaa_drm_org_email ');
        $secret = get_option('raagaa_drm_org_secret');
        $resourceId = get_post_meta($item->get_product_id(), "_rg_prod_id", true);
        $companyid = get_post_meta($item->get_product_id(), "_rg_company_id", true);
		 $order_customer_email = $order_data['billing']['email'];
		 
		  $tansdata['customer_Email']=$order_customer_email;
$tansdata['product_Id']=$resourceId;
$tansdata['company_Id']=$companyid;
$transjson=wp_json_encode($tansdata);

$args = array(
    'body'        => $transjson,
    'timeout'     => '5',
    'redirection' => '5',
    'httpversion' => '1.0',
    'blocking'    => true,
    'headers'     => array('Authorization'=>$secret,'Content-Type'=>'application/json'),
    
);
$url='https://raagaatechnologiesapilive.azurewebsites.net/api/V1/SetProductsToCustomers';

$response = wp_remote_post($url, $args );
//$transaction = unserialize( wp_remote_retrieve_body( $response ) );
$transaction = json_decode(wp_remote_retrieve_body( $response ), TRUE );


//$transaction=$response;

           // $links[]= $transaction['customer_Product_id'];
			$links= $transaction['customer_Product_id'];
 
  if (!empty($transaction)) {
            if (function_exists("wc_add_order_item_meta")) {
                wc_add_order_item_meta($item->get_id(), '_rg_r_id',$links);
            } else {
                woocommerce_add_order_item_meta($item->get_id(), '_rg_r_id', $links);
            }
        }
}
}
    }
}
}


 ?>
