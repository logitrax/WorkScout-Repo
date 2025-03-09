<?php

if (! defined('ABSPATH'))
    exit;

require __DIR__ . '/../vendor/autoload.php';


use Stripe\OAuth;

class WorkscoutStripeConnect
{
    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;

    /**
     * The secret to use when verifying webhooks.
     *
     * @var string
     */
    protected $secret;
    protected $client_id;
    protected $webhook_secret;



    function __construct()
    {

        $stripe_connect_activation = get_option('workscout_stripe_connect_activation');



        if ($stripe_connect_activation) {

            $stripe_mode = get_option('workscout_stripe_connect_mode');

            $this->testmode       = (!empty($stripe_mode) && 'test' === $stripe_mode) ? true : false;

            if ($this->testmode) {
                $client_id = get_option('workscout_stripe_connect_test_client_id');
            } else {
                $client_id = get_option('workscout_stripe_connect_live_client_id');
            }

            $secret_key           = 'workscout_stripe_connect_' . ($this->testmode ? 'test_' : 'live_') . 'secret_key';

            $this->secret         = !empty(get_option($secret_key)) ? get_option($secret_key) : false;

            $webhook_secret_key   = 'workscout_stripe_connect_' . ($this->testmode ? 'test_' : 'live_') . 'webhook_secret';
            $this->webhook_secret    = !empty(get_option($webhook_secret_key)) ? get_option($webhook_secret_key) : false;

            if ($this->testmode) {
                $this->client_id = get_option('workscout_stripe_connect_test_client_id');
            } else {
                $this->client_id = get_option('workscout_stripe_connect_live_client_id');
            }

            add_action('woocommerce_api_wc_stripe', array($this, 'check_for_webhook'), 9);
            add_action('wp_ajax_workscout_disconnect_stripe', array($this, 'ajax_workscout_disconnect_stripe'));


            add_action('wp_enqueue_scripts', array($this, 'workscout_stripe_scripts'));
        }
    }

    function workscout_stripe_scripts()
    {
        $wallet_page = get_option('workscout_wallet_page');
        global $post;
        // Single JS to track listings.
        if (isset($post) && $post->ID == $wallet_page) {
            
            wp_enqueue_script('workscout-core-stripe', WORKSCOUT_CORE_URL . 'assets/js/workscout.stripe.js', array('jquery'), 1.0, true);
            
        }
    }

    function ajax_workscout_disconnect_stripe(){
        
        $secret = $this->secret;
        if($secret){
            $user_id = get_current_user_id();
            \Stripe\Stripe::setApiKey($secret);

            try {
                \Stripe\OAuth::deauthorize([
                    'client_id' => $this->client_id,
                    'stripe_user_id' => get_user_meta($user_id, 'stripe_user_id', true),
                ]);
              
                delete_user_meta($user_id, 'vendor_connected');
                delete_user_meta($user_id, 'access_token');
                delete_user_meta($user_id, 'refresh_token');
                delete_user_meta($user_id, 'stripe_publishable_key');
                delete_user_meta($user_id, 'stripe_user_id');
                wp_send_json_success();
            } catch (Exception $e) {
                
               
                delete_user_meta($user_id, 'vendor_connected');
                delete_user_meta($user_id, 'access_token');
                delete_user_meta($user_id, 'refresh_token');
                delete_user_meta($user_id, 'stripe_publishable_key');
                delete_user_meta($user_id, 'stripe_user_id');
                wp_send_json_error();
            }
        }
       
    }

    function missing_stripe_notice()
    {
        $class = 'notice notice-error';
        $message = __('Workscout Stripe Connect Split Payment option requires WooCommerce Stripe Payment Gateway enabled.', 'workscout_core');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function check_for_webhook()
    {
        if (
            !isset($_SERVER['REQUEST_METHOD'])
            || ('POST' !== $_SERVER['REQUEST_METHOD'])
            || !isset($_GET['wc-api'])
            || ('wc_stripe' !== $_GET['wc-api'])
        ) {
            return;
        }
        $wc_stripe_webhook_handler = new \WC_Stripe_Webhook_Handler();
       
        $request_body = file_get_contents('php://input');
        $request_headers = array_change_key_case($wc_stripe_webhook_handler->get_request_headers(), CASE_UPPER);
        // New function call:
        //SKIP IT FOR NOW
        // if (!$this->webhook_for_this_site($request_body)) {
        //     WC_Stripe_Logger::log('Workscout Stripe Connect: Incoming webhook is not for this site: ' . print_r($request_body, true));
        //     status_header(200);
        //     exit;
        // }

        // Validate it to make sure it is legit.

        if ($this->is_valid_request($request_headers, $request_body)) {
          
            $wc_stripe_webhook_handler->process_webhook($request_body);
            WC_Stripe_Logger::log('Workscout Stripe Connect: webhook is processed: ' . print_r($request_body, true));
            $this->split_payment($request_body);
            status_header(200);
            exit;
        } else {
          
            \WC_Stripe_Logger::log('Incoming webhook failed validation: ' . print_r($request_body, true));
            status_header(400);
            exit;
        }
    }


    /**
     * Verify the incoming webhook notification to make sure it is legit.
     *
     * @since 4.0.0
     * WooCommerce @version 4.0.0
     * @param string $request_headers The request headers from Stripe.
     * @param string $request_body The request body from Stripe.
     * @return bool
     *
     * @version 1.0.0
     */
    public function is_valid_request($request_headers = null, $request_body = null)
    {
       
        if (null === $request_headers || null === $request_body) {
            return false;
        }
        if (!empty($request_headers['USER-AGENT']) && !preg_match('/Stripe/', $request_headers['USER-AGENT'])) {
            return false;
        }

        if (!empty($this->webhook_secret)) {
            // Check for a valid signature.
            $signature_format = '/^t=(?P<timestamp>\d+)(?P<signatures>(,v\d+=[a-z0-9]+){1,2})$/';
            if (empty($request_headers['STRIPE-SIGNATURE']) || !preg_match($signature_format, $request_headers['STRIPE-SIGNATURE'], $matches)) {
		    return false;
            }
            // Verify the timestamp.
            $timestamp = intval($matches['timestamp']);

            if (abs($timestamp - time()) > 5 * MINUTE_IN_SECONDS) {
                return WC_Stripe_Webhook_State::VALIDATION_FAILED_TIMESTAMP_MISMATCH;
                
            }
            // Generate the expected signature.
            $signed_payload     = $timestamp . '.' . $request_body;
            $expected_signature = hash_hmac('sha256', $signed_payload, $this->webhook_secret);

            // Check if the expected signature is present.
            if (
                !preg_match('/,v\d+=' . preg_quote($expected_signature, '/') . '/', $matches['signatures'])
            ) {
                return WC_Stripe_Webhook_State::VALIDATION_FAILED_SIGNATURE_MISMATCH;
            }
        }

        return true;
    }
        

    /**
     * Check if the webhook is for this site
     */
    private function webhook_for_this_site($request_body)
    {
        // Look for source id
        $source_id = $this->get_source_id($request_body);
        // Check with Woo to see if it's a valid order
        $order = \WC_Stripe_Helper::get_order_by_source_id($source_id);
        // If yes return true
        if ($order) {
            return true;
        }
        // If no return false
        return false;
    }

    /**
     * Gets the Stripe source ID
     */
    private function get_source_id($request_body)
    {
        $event_type = $this->get_event_type($request_body);
        $source_id = NULL;
       
        if (!$event_type) {
            return NULL;
        }

        $request_body = json_decode($request_body);
        switch ($event_type) {
            case 'charge.succeeded':
                $source_id = $request_body->data->object->source->id;
                break;
            case 'payment_intent.succeeded':
                $source_id = $request_body->data->object->source;
                break;
            case 'source.chargeable':
                $source_id = $request_body->data->object->id;
                break;
        }
        return $source_id;
    }

    /**
     * Gets the Stripe event type
     */
    private function get_event_type($request_body)
    {
        $request_body = json_decode($request_body);
        $event_type = ($request_body->type ? strtolower($request_body->type) : NULL);
      
        /* Should return a value for the following event type:
                   source.chargeable
                   charge.succeeded
                   payment_intent.succeeded
               */
        return $event_type;
    }



    /**
     * Determine whether or not to initiate a transfer
     */
    private function split_payment($request_body)
    {
        $event_type = $this->get_event_type($request_body);

        if ($event_type == 'payment_intent.succeeded' ) {
            WC_Stripe_Logger::log('Workscout Stripe Connect: payment_intent.succeeded: ' . print_r($request_body, true));
            list($success, $result_message, $transfer) = $this->transfer_to_owner($request_body);
            workscout_write_log($success);
            workscout_write_log($result_message);
            workscout_write_log($transfer);
            if ($success) {
                WC_Stripe_Logger::log('Workscout Stripe Connect: Trasnfer to owner success: ' . print_r($transfer, true));
            } else {
                WC_Stripe_Logger::log('Workscout Stripe Connect: Payment error: ' . print_r($result_message, true));
                error_log("Workscout Split Payment error {$result_message}");
            }
        }
    }
    /**
     * Get event meta data (payment_intent.succeeded)
     */
    private function get_event_meta($request_body)
    {
        $data = NULL;
        $event = json_decode($request_body);
        
        try {

            if (isset($event->data->object->charges)) {
                $charge_created = $event->data->object->charges->data[0]->created;
                $charge_created = date('Y-m-d H:i:s', $charge_created);
            } else if ($event->data->object->created) {
                $charge_created = $event->data->object->created;
                $charge_created = date('Y-m-d H:i:s', $charge_created);
            } else {
                error_log("Stripe created not found");
                return NULL;
            }

            if (isset($event->data->object->charges)) {
                $order_amount = $event->data->object->charges->data[0]->amount;
                $order_amount = $order_amount / 100;
            } else if ($event->data->object->amount) {
                $order_amount = $event->data->object->amount;
                $order_amount = $order_amount / 100;
            } else {
                error_log("Stripe order amount not found");
                return NULL;
            }


            if ($event->livemode && $event->livemode == 'true') {
                $stripe_mode = 'live';
            } else {
                $stripe_mode = 'test';
            }

            $data = array(
                'charge_amount'      => $order_amount,
                'charge_created'     => $charge_created,
                'charge_description' => $event->data->object->description,
                'total_amount'       => $event->data->object->amount,
               
                'source_transaction' => $event->data->object->source,
                'stripe_mode'        => $stripe_mode,
                'wc_order_id'        => $event->data->object->metadata->order_id,
            );
            if(isset($event->data->object->latest_charge)){
                $data['source_charge_id']   = $event->data->object->latest_charge;
            }
    
        } catch (Exception $e) {
            WC_Stripe_Logger::log("Stripe Exception in get_event_meta: {$e}");
        }
        return $data;
    }


    /**
     * Transfer to connected account
     */
    private function transfer_to_owner($request_body)
    {
        $result_message = NULL;
        $success = false;
        $transfer = NULL;
        $data_keys = array(
            'secret'    => $this->secret,
            'stripeTestMode' => $this->testmode
        );

        if (!$data_keys) {
            $result_message = 'Missing WooCommerce Stripe settings';
            return array($success, $result_message, $transfer);
        }

        $event_data = $this->get_event_meta($request_body);

        if ($event_data) {
            try {
                $stripe_test_mode = $data_keys['stripeTestMode'] == 'yes';
           
                $default_percentage = $commission_rate / 100;
                // Order details

                $order_id = $event_data['wc_order_id'];
                if(isset($event_data['source_charge_id'])){
                    $source_transaction = $event_data['source_charge_id'];
                }
    
                $total_amount = $event_data['charge_amount'];
                
                if (!$order_id ) {
                    $result_message = 'Missing order data.';
                    return array($success, $result_message, $transfer);
                }



                $order = wc_get_order($order_id);
                if (!$order) {
                    $result_message = 'Invalid order ID.';
                    return array($success, $result_message, $transfer);
                }

                // Check if this is a milestone payment
                $milestone_id = $order->get_meta('milestone_id');
                if (empty($milestone_id)) {
                    $result_message = 'Order is not a milestone payment.';
                    return array($success, $result_message, $transfer);
                }

                // // Get the project ID and freelancer ID from the milestone
                // $project_id = get_post_meta($milestone_id, '_project_id', true);
                // if (empty($project_id)) {
                //     $result_message = 'Invalid project ID for milestone.';
                //     return array($success, $result_message, $transfer);
                // }

                // // Get the freelancer (owner who will receive the payment)
                // $owner_id = get_post_meta($project_id, '_freelancer_id', true);
                // if (empty($owner_id)) {
                //     $result_message = 'No freelancer assigned to the project.';
                //     return array($success, $result_message, $transfer);
                // }

                $owner_id = $order->get_meta('freelancer_id'); 
                $stripe_user_id = get_user_meta($owner_id, 'stripe_user_id', true);

                $workscout_commission_rate = get_user_meta($owner_id, 'workscout_commission_rate', true);
                if (empty($commission)) {
                    $workscout_commission_rate = get_option('workscout_commission_rate', 10);
                }
                $commission_rate = apply_filters('workscout_commission_rate', $workscout_commission_rate);
                if (empty($stripe_user_id)){
                    $result_message = esc_html_e('User is not connected to Stripe Connect.', 'workscout_core');
                    return array($success, $result_message, $transfer);
                }

                $total_price = $order->get_total();
           
                $transfer_amount = $total_price - ($default_percentage * $total_price);
                $transfer_amount = round($transfer_amount * 100); // Round to ensure integer value
                if (!$transfer_amount) {
                    $result_message = esc_html_e('Error calculating split payment transfer amount.','workscout_core');
                    return array($success, $result_message, $transfer);
                }

                
             
                \Stripe\Stripe::setApiKey($data_keys['secret']);

                $woo_currency = get_woocommerce_currency();
                $currency = ($woo_currency ? $woo_currency : 'usd');

                
                $transfer_data = array(
                    'amount'             => $transfer_amount,
                    'currency'           => $currency,
                    //   'source_transaction' => $source_transaction,
                    'destination'        => $stripe_user_id,
                    'description'        => "Split Booking Payment Order ID:" . $order_id,
                );
                if(isset($source_transaction)){
                    $transfer_data['source_transaction'] = $source_transaction;
                }
                $transfer = \Stripe\Transfer::create($transfer_data);
                
                $success = true;
                
                
                $order->update_meta_data('workscout_stripe_connect_processed', 'yes');
                    

                $order->save_meta_data();

                $pp_data = array(
                    'status' => 'paid',
                    'commission_type' => 'stripe'
                );
                    
                
                global $wpdb;
                $commission_table = $wpdb->prefix . 'workscout_core_commissions';

                $is_updated = $wpdb->update(
                    $commission_table,
                    $pp_data,
                    [
                        'order_id' => $order_id,
                     
                    ]
                );
                
                $result_message = esc_html_e('Transfer successful','workscout_core');
                return array($success, $result_message, $transfer);
            } catch (\InvalidRequestException $e) {
                // Invalid request
                $result_message = "Invalid request: {$e}";
                return array($success, $result_message, $transfer);
            } catch (Exception $e) {

                if (stristr($e, 'no such destination')) {
                    $account_number = substr($e, strpos($e, 'acct_'));
                    $account_number = explode(' ', trim($account_number));
                    $account_number = $account_number[0];
                    $result_message = "Ignored; cannot find account number: {$account_number}. Stripe replied 'No such destination.'";
                    return array($success, $result_message, $transfer);
                } else {
                    $result_message = "Exception: {$e}";
                    return array($success, $result_message, $transfer);
                }
            }
        } else {
            $result_message = __('Could not retreive order meta data.','workscout_core');
            return array($success, $result_message, $transfer);
        }
    }


}