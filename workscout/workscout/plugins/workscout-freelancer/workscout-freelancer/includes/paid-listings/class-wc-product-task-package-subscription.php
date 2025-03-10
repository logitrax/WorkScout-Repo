<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Job Package Product Type
 */
class WC_Product_Task_Package_Subscription extends WorkScout_Freelancer_Paid_Tasks_Subscription_Product {

	/**
	 * Constructor
	 *
	 * @param int|WC_Product|object $product Product ID, post object, or product object
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
		$this->product_type = 'task_package_subscription';
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'task_package_subscription';
	}

	/**
	 * Checks the product type.
	 *
	 * Backwards compat with downloadable/virtual.
	 *
	 * @access public
	 * @param mixed $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( 'task_package_subscription' == $type || ( is_array( $type ) && in_array( 'task_package_subscription', $type ) ) ) ? true : parent::is_type( $type );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @access public
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}


	/**
	 * Return job task duration granted
	 *
	 * @return int
	 */
	public function get_duration() {
		$task_duration = $this->get_task_duration();
		if ( 'task' === $this->get_package_subscription_type() ) {
			return false;
		} elseif ( $task_duration ) {
			return $task_duration;
		} else {
			return get_option( 'task_default_duration' );
		}
	}

	/**
	 * Return job task limit
	 *
	 * @return int 0 if unlimited
	 */
	public function get_limit() {
		$task_limit = $this->get_task_limit();
		if ( $task_limit ) {
			return $task_limit;
		} else {
			return 0;
		}
	}

	/**
	 * Return if featured
	 *
	 * @return bool true if featured
	 */
	public function is_task_featured() {
		return 'yes' === $this->get_task_featured();
	}

	/**
	 * Get job task featured flag
	 *
	 * @return string
	 */
	public function get_task_featured() {
		return $this->get_product_meta( 'listing_featured' );
	}

	/**
	 * Get job task limit
	 *
	 * @return int
	 */
	public function get_task_limit() {
		return $this->get_product_meta( 'listing_limit' );
	}

	/**
	 * Get job task duration
	 *
	 * @return int
	 */
	public function get_task_duration() {
		return $this->get_product_meta( 'listing_duration' );
	}
	
		public function get_product_meta( $key ) {
			return $this->get_meta( '_' . $key );
		}

/**
		 * Return if featured
		 *
		 * @return bool true if featured
		 */
		public function has_task_booking() {
			return 'yes' === $this->get_option_booking();
		}
		/**
		 * Get option booking
		 *
		 * @return int|bool
		 */
		public function get_option_booking() {
			return $this->get_product_meta( 'package_option_booking' );
			
		}


		public function has_task_reviews() {
			return 'yes' === $this->get_option_reviews();
		}
		/**
		 * Get option reviews
		 *
		 * @return int|bool
		 */
		public function get_option_reviews() {
			return $this->get_product_meta( 'package_option_reviews' );
			
		}


		public function has_task_social_links() {
			return 'yes' === $this->get_option_social_links();
		}
		/**
		 * Get option social links
		 *
		 * @return int|bool
		 */
		public function get_option_social_links() {
			return $this->get_product_meta( 'package_option_social_links' );
		}	


		public function has_task_opening_hours() {
			return 'yes' === $this->get_option_opening_hours();
		}
		/**
		 * Get option hours
		 *
		 * @return int|bool
		 */
		public function get_option_opening_hours() {
			return $this->get_product_meta( 'package_option_opening_hours' );
		}		


		public function has_task_video() {
			return 'yes' === $this->get_option_video();
		}
		/**
		 * Get option video
		 *
		 * @return int|bool
		 */
		public function get_option_video() {
			return $this->get_product_meta( 'package_option_video' );
			
		}	


		public function has_task_coupons() {
			return 'yes' === $this->get_option_coupons();
		}
		/**
		 * Get option coupons
		 *
		 * @return int|bool
		 */
		public function get_option_coupons() {
			return $this->get_product_meta( 'package_option_coupons' );
			
		}		


		public function has_task_gallery() {
			return 'yes' === $this->get_option_gallery();
		}
		
		public function get_option_gallery() {
			return $this->get_product_meta( 'package_option_gallery' );
			
		}	

		/**
		 * Get gallery limit booking
		 *
		 * @return int|bool
		 */
		public function get_option_gallery_limit() {
			return $this->get_product_meta( 'package_option_gallery_limit' );
			
		}

	/**
	 * Get package subscription type
	 *
	 * @return string
	 */
	public function get_package_subscription_type() {
		return $this->get_product_meta( 'package_subscription_type' );
	}
}
