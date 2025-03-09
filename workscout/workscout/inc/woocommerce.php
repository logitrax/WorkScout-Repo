<?php 
/**
 * Change the Shop archive page title.
 * @param  string $title
 * @return string
 */

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

add_filter('loop_shop_columns', 'loop_columns');
if (!function_exists('loop_columns')) {
	function loop_columns() {
		$layout = Kirki::get_option( 'workscout', 'pp_shop_layout' ); 
		if($layout=='full-width'){ 
			return 3;
		} else {
			return 2;
		}
	}
}

add_filter('woocommerce_valid_order_statuses_for_payment', 'allow_milestone_order_payment', 10, 2);
function allow_milestone_order_payment($statuses, $order)
{
	// Check if this is a milestone order
	if ($order && $order->get_meta('milestone_id')) {
		if (!in_array('pending', $statuses)) {
			$statuses[] = 'pending';
		}
		if (!in_array('pending_payment', $statuses)) {
			$statuses[] = 'pending_payment';
		}
	}
	return $statuses;
}

add_filter('woocommerce_is_purchasable', 'prevent_direct_milestone_product_purchase', 10, 2);

function prevent_direct_milestone_product_purchase($purchasable, $product)
{
	if ($product->get_meta('_is_milestone_product') === 'yes') {
		// Only allow purchase if it's part of a milestone order
		if (!isset($_GET['pay_for_order']) && !isset($_POST['payment_method'])) {
			return false;
		}
	}
	return $purchasable;
}

add_filter('woocommerce_order_can_be_paid', 'enable_milestone_order_payment', 10, 2);
function enable_milestone_order_payment($can_be_paid, $order)
{
	if ($order && $order->get_meta('milestone_id')) {
		if ($order->has_status(['pending', 'pending_payment'])) {
			return true;
		}
	}
	return $can_be_paid;
}

//add_filter('woocommerce_short_description', 'workscout_woocommerce_short_description', 10, 1);
function workscout_woocommerce_short_description($post_excerpt){
	global $product;
	if($product->get_type() == "job_package" || $product->get_type() == "resume_package") {
       		if($product->get_type() == "job_package" ) { 
				$output = '<ul>';
					
					$jobslimit = $product->get_limit();
					if(!$jobslimit){
						$output .= "<li>";
						$output .= esc_html__('Unlimited number of jobs','workscout'); 
						$output .=  "</li>";
					} else { 
						$output .= '<li>';
						$output .= esc_html__('This plan includes ','workscout'); $output .= sprintf( _n( '%d job', '%s jobs', $jobslimit, 'workscout' ) . ' ', $jobslimit ); 
						$output .= '</li>';

						$jobduration =  $product->get_duration();
						if(!empty($jobduration)){ 
						$output .= '<li>';
						$output .= esc_html__('Jobs are posted ','workscout'); $output .= sprintf( _n( 'for %s day', 'for %s days', $product->get_duration(), 'workscout' ), $product->get_duration() ); 
						$output .= '</li>';
					 } 
				$output .= "</ul>";
				} 
			}
			if($product->get_type() == "resume_package" ) { 
				$output = '<ul>';
					 
					$jobslimit = $product->get_limit();
					if(!$jobslimit){
						$output .= '<li>';
						$output .= esc_html__('Unlimited number of Resumes','workscout'); 
						$output .= '</li>';
					} else { 
						$output .= '<li>';
						$output .= esc_html__('This plan includes ','workscout'); $output .= sprintf( _n( '%d resume', '%s resumes', $jobslimit, 'workscout' ) . ' ', $jobslimit ); 
						$output .= '</li>';
					} 

					$jobduration =  $product->get_duration();
					if(!empty($jobduration)){ 
						$output .= '<li>';
						$output .= esc_html__('Resumes are posted ','workscout'); $output .= sprintf( _n( 'for %s day', 'for %s days', $product->get_duration(), 'workscout' ), $product->get_duration() ); 
						$output .= '</li>';
					 } 

				$output .= "</ul>";
			}

        $post_excerpt = $output . $post_excerpt;
    }
    return $post_excerpt;
}


remove_action( 'woocommerce_after_shop_loop_item_title','woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item','woocommerce_template_loop_add_to_cart', 10 );
add_action( 'woocommerce_before_shop_loop_item_title','woocommerce_template_loop_add_to_cart', 10 );

add_filter( 'woocommerce_show_page_title', 'workscout_hide_shop_title' );
function workscout_hide_shop_title() { return false; }



remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_upsells', 15 );

if ( ! function_exists( 'woocommerce_output_upsells' ) ) {
	function woocommerce_output_upsells() {
	    woocommerce_upsell_display( 3,3 ); // Display 3 products in rows of 3
	}
}

add_filter( 'woocommerce_output_related_products_args', 'workscout_related_woo_per_page' );

function workscout_related_woo_per_page( $args ) { 
    $args = wp_parse_args( array( 'posts_per_page' => 3 ), $args );
    return $args;
}


add_filter('workscout_userpage', 'workscout_woo_redirect_candidate_to_dashboard');
add_filter('workscout_woo_userpage', 'workscout_woo_redirect_candidate_to_dashboard');
function workscout_woo_redirect_candidate_to_dashboard($loginlink){
	
	$login_system = Kirki::get_option( 'workscout', 'pp_login_form_system' );
	if($login_system==='woocommerce' || $login_system==='workscout' ) {
		$redirect_can =  Kirki::get_option( 'workscout', 'pp_woo_redirect_user_page_candidate');
		$redirect_emp =  Kirki::get_option( 'workscout', 'pp_woo_redirect_user_page_employer');
		$user = wp_get_current_user();
		if($redirect_can){
			if ( in_array( 'candidate', (array) $user->roles )   ) {
				$candidate_dashboard_page_id = get_option( 'resume_manager_candidate_dashboard_page_id' ); 
				$loginlink = get_permalink($candidate_dashboard_page_id);
			}
		}		
		if($redirect_emp){
			if ( in_array( 'employer', (array) $user->roles )   ) {
				$employer_dashboard_page_id = get_option( 'job_manager_job_dashboard_page_id' );
				$loginlink = get_permalink($employer_dashboard_page_id);
			}
			
		}
	}
	
	return $loginlink;
}

// function exclude_widget_categories($args){
// 	 // $category = get_term_by( 'slug', 'listeo-booking', 'product_cat' );
// 	 // var_dump($category);
//     $exclude = "20";
//     $args["exclude"] = $exclude;
//     return $args;
// }
// add_filter("widget_categories_args","exclude_widget_categories");


// add_action('template_redirect', 'skip_checkout_for_free_products');

// function skip_checkout_for_free_products()
// {

	
// 	// Only proceed if we're on the cart or checkout page, and not the order-received page
// 	if ((is_cart() || is_checkout()) && !is_wc_endpoint_url('order-received')) {
// 		// Get the cart total
// 		$cart_total = WC()->cart->get_total('edit');
// workscout_write_log($cart_total);
// 		// Check if the cart contains only free products (total is 0)
// 		if ($cart_total == 0) {
// 			// Create an order with free products
// 			$order = wc_create_order();

// 			// Add the products in the cart to the order
// 			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			
// 				$job = get_post(absint($cart_item['job_id']));
			
// 				$order->add_product($cart_item['data'], $cart_item['quantity']);
// 				$order->update_meta_data(__('Job Listing', 'wp-job-manager-wc-paid-listings'), $job->post_title);
// 				$order->update_meta_data('_job_id', $cart_item['job_id']);
// 			}

// 			// Calculate the order totals
// 			$order->calculate_totals();

// 			// Mark the order as completed
// 			$order->set_status('completed');
// 			$order->save();

// 			// Empty the cart
// 			WC()->cart->empty_cart();

// 			// Redirect to the order received page (thank you page)
// 			wp_redirect($order->get_checkout_order_received_url());
// 			exit;
// 		}
// 	}
// }

// // Automatically mark orders containing only free products as "completed"
// add_action('woocommerce_checkout_order_processed', 'auto_complete_order_for_free_products', 10, 1);

// function auto_complete_order_for_free_products($order_id)
// {
// 	// Get the order object
// 	$order = wc_get_order($order_id);

// 	// Flag to check if all items are free
// 	$all_items_free = true;

// 	// Loop through each item in the order
// 	foreach ($order->get_items() as $item) {
// 		if ($item->get_total() > 0) {
// 			// If any item has a price greater than 0, set the flag to false
// 			$all_items_free = false;
// 			break;
// 		}
// 	}

// 	// If all products are free, mark the order as completed
// 	if ($all_items_free) {
// 		$order->update_status('completed');
// 		// can I run here action woocommerce_order_status_completed?
// 		do_action('woocommerce_order_status_completed', $order_id);
// 	}
// }
?>