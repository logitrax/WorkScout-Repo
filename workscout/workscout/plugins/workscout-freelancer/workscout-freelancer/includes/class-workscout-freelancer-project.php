<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

/**
 * WorkScout_Freelancer_Task class
 */
class WorkScout_Freelancer_Project
{

    private static $_instance = null;


    private $meta_key = 'project_milestones';


    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('init', array($this, 'workscout_handle_project_comment'));

        add_action('wp_ajax_save_milestone', array($this, 'ajax_save_milestone'));
        add_action('wp_ajax_ws_delete_milestone', array($this, 'ajax_delete_milestone'));
        add_action('wp_ajax_approve_milestone', array($this, 'ajax_approve_milestone'));
        add_action('wp_ajax_complete_milestone', array($this, 'ajax_complete_milestone'));
        add_action( 'woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
        add_action('wp_ajax_get_milestone_for_edit', array($this, 'ajax_get_milestone_for_edit'));
        add_action('wp_ajax_update_milestone', array($this, 'ajax_update_milestone'));
    }

    /**
     * Get detailed milestone data by ID
     * 
     * @param string $milestone_id The unique identifier of the milestone
     * @param int $project_id Optional project ID if known (improves performance)
     * @return array|false Returns milestone data array or false if not found
     */
    public function get_milestone_details($milestone_id, $project_id = null)
    {
        global $wpdb;

        // If project_id is not provided, find it by querying post meta
        if (!$project_id) {
            $project_id = $this->find_project_by_milestone($milestone_id);
            if (!$project_id) {
                return false;
            }
        }

        // Get all milestones for the project
        $milestones = $this->get_milestones($project_id);
        if (empty($milestones)) {
            return false;
        }

        // Find the specific milestone
        $milestone_data = null;
        foreach ($milestones as $milestone) {
            if ($milestone['id'] === $milestone_id) {
                $milestone_data = $milestone;
                break;
            }
        }

        if (!$milestone_data) {
            return false;
        }

        // Get project details
        $project = get_post($project_id);
        if (!$project) {
            return false;
        }

        // Get employer and freelancer details
        $employer_id = get_post_meta($project_id, '_employer_id', true);
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);

        // Get payment status if order exists
        $payment_status = '';
        $order_id = isset($milestone_data['order_id']) ? $milestone_data['order_id'] : null;
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $payment_status = $order->get_status();
            }
        }

        // Build comprehensive milestone data array
        $complete_milestone_data = array_merge($milestone_data, array(
            'project_id' => $project_id,
            'project_title' => $project->post_title,
            'project_status' => $project->post_status,
            'employer_id' => $employer_id,
            'freelancer_id' => $freelancer_id,
            'employer_name' => get_userdata($employer_id) ? get_userdata($employer_id)->display_name : '',
            'freelancer_name' => get_userdata($freelancer_id) ? get_userdata($freelancer_id)->display_name : '',
            'payment_status' => $payment_status,
            'order_id' => $order_id,
            'creation_date' => isset($milestone_data['creation_date']) ? $milestone_data['creation_date'] : '',
            'last_modified' => isset($milestone_data['last_modified']) ? $milestone_data['last_modified'] : '',
            'completion_percentage' => $this->calculate_milestone_completion_percentage($project_id, $milestone_id),
        ));

        return apply_filters('workscout_freelancer_milestone_details', $complete_milestone_data, $milestone_id, $project_id);
    }

    /**
     * Helper function to find project ID by milestone ID
     * 
     * @param string $milestone_id The milestone ID to search for
     * @return int|false Project ID if found, false otherwise
     */
    private function find_project_by_milestone($milestone_id)
    {
        $args = array(
            'post_type' => 'project',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => $this->meta_key,
                    'value' => $milestone_id,
                    'compare' => 'LIKE'
                )
            )
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }

        return false;
    }

    /**
     * Calculate the completion percentage of a milestone
     * 
     * @param int $project_id The project ID
     * @param string $milestone_id The milestone ID
     * @return float The completion percentage
     */
    private function calculate_milestone_completion_percentage($project_id, $milestone_id)
    {
        $milestone = $this->get_milestone($project_id, $milestone_id);

        if (!$milestone) {
            return 0;
        }

        $total = 0;

        // Calculate based on approval status
        if ($milestone['client_approval']) {
            $total += 50;
        }
        if ($milestone['freelancer_approval']) {
            $total += 50;
        }

        return $total;
    }

    function workscout_handle_project_comment()
    {
        if (!isset($_POST['submit_project_comment'])) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['project_comment_nonce']) || !wp_verify_nonce($_POST['project_comment_nonce'], 'project_comment_action')) {
            return;
        }

        $project_id = absint($_POST['project_id']);
        $comment_content = wp_kses_post($_POST['comment_content']);
        $is_milestone = isset($_POST['is_milestone']) ? 1 : 0;

        // Check if user is allowed to comment (project owner or assigned freelancer)
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);
        $employer_id = get_post_meta($project_id, '_employer_id', true);
        $current_user_id = get_current_user_id();

        if ($current_user_id != $freelancer_id && $current_user_id != $employer_id) {
            return;
        }

        // Prepare comment data
        $comment_data = array(
            'comment_post_ID' => $project_id,
            'comment_content' => $comment_content,
            'user_id' => $current_user_id,
            'comment_type' => 'project_comment',
            'comment_approved' => 1
        );

        // Insert comment
        $comment_id = wp_insert_comment($comment_data);

        if ($comment_id) {
            // Handle milestone
            if ($is_milestone) {
                add_comment_meta($comment_id, '_is_milestone', '1');

                // Add milestone status
                add_comment_meta($comment_id, '_milestone_status', 'pending');
            }

            // Handle file attachments
            if (!empty($_FILES['comment_files'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $files = $_FILES['comment_files'];
                $files_array = array();

                foreach ($files['name'] as $key => $value) {
                    if ($files['name'][$key]) {
                        $file = array(
                            'name' => $files['name'][$key],
                            'type' => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error' => $files['error'][$key],
                            'size' => $files['size'][$key]
                        );

                        $_FILES = array('upload_file' => $file);
                        $attachment_id = media_handle_upload('upload_file', $project_id);

                        if (!is_wp_error($attachment_id)) {
                            $files_array[] = $attachment_id;
                        }
                    }
                }

                if (!empty($files_array)) {
                    add_comment_meta($comment_id, '_comment_files', $files_array);
                }
            }
            wp_redirect(add_query_arg('comment_posted', 'true', wp_get_referer()));
            exit;
        }

        exit;
    }

    /**
     * Get all files attached to project comments
     *
     * @param int $project_id The ID of the project
     * @return array Array of attachment objects with file details
     */
    public function get_project_files($project_id)
    {
        // Get all comments for this project
        $comments = get_comments(array(
            'post_id' => $project_id,
            'type' => 'project_comment',
            'status' => 'approve'
        ));

        $files = array();

        foreach ($comments as $comment) {
            // Get files attached to this comment
            $comment_files = get_comment_meta($comment->comment_ID, '_comment_files', true);

            if (!empty($comment_files) && is_array($comment_files)) {
                foreach ($comment_files as $attachment_id) {
                    $attachment = get_post($attachment_id);

                    if ($attachment) {
                        $file_url = wp_get_attachment_url($attachment_id);
                        $file_type = wp_check_filetype(get_attached_file($attachment_id));
                        $file_size = filesize(get_attached_file($attachment_id));

                        $files[] = array(
                            'id' => $attachment_id,
                            'name' => $attachment->post_title,
                            'url' => $file_url,
                            'type' => $file_type['ext'],
                            'size' => size_format($file_size),
                            'date' => $attachment->post_date,
                            'comment_id' => $comment->comment_ID,
                            'comment_author' => $comment->comment_author,
                            'comment_date' => $comment->comment_date
                        );
                    }
                }
            }
        }

        return $files;
    }

    public function display_project_files($project_id)
    {
        $files = $this->get_project_files($project_id);

        if (empty($files)) {
            echo '<p>No files attached to this project.</p>';
            return;
        }

      

        foreach ($files as $file) {
?>
            <li class="project-file">
                <div class="file-info">
                    <a href="<?php echo esc_url($file['url']); ?>" target="_blank" class="file-name">
                        <?php echo esc_html($file['name']); ?>
                    </a>
                    <span class="file-meta">
                        <?php
                        echo esc_html($file['type']) . ' | ' .
                            esc_html($file['size']) . ' | ' .
                            'Uploaded by ' . esc_html($file['comment_author']) . ' on ' .
                            date('M j, Y', strtotime($file['comment_date']));
                        ?>
                    </span>
                </div>
            </li>
<?php
        }

      
    }

    // Get milestones for a project
    public function get_milestones($project_id)
    {
        $milestones = get_post_meta($project_id, $this->meta_key, true);
        return !empty($milestones) ? $milestones : array();
    }

    // I need a function that calculates project completion based on done milestones:
    

    /**
     * Calculate the overall project completion percentage based on completed milestones
     * 
     * @param int $project_id The ID of the project
     * @return float Completion percentage between 0 and 100
     */
    public function calculate_project_completion($project_id) {
        $milestones = $this->get_milestones($project_id);
        if (empty($milestones)) {
            return 0;
        }

        $total_percentage = 0;
        $completed_percentage = 0;

        foreach ($milestones as $milestone) {
            
            $milestone_percentage = floatval($milestone['percentage']);
            $total_percentage += $milestone_percentage;
            
            if ($milestone['status'] === 'approved') {
                $completed_percentage += $milestone_percentage;
            }

        }
        return $completed_percentage;
        // Normalize to ensure we don't exceed 100%
        if ($total_percentage > 0) {
            return min(100, ($completed_percentage / $total_percentage) * 100);
        }

        return 0;
    }

    // Save a new milestone or update existing one
    public function save_milestone($project_id, $milestone_data)
    {
        $milestones = $this->get_milestones($project_id);

        if (isset($milestone_data['id'])) {
            // Update existing milestone
            foreach ($milestones as $key => $milestone) {
                if ($milestone['id'] === $milestone_data['id']) {
                    $milestones[$key] = array_merge($milestone, $milestone_data);
                    do_action('workscout_freelancer_milestone_edited', $project_id, $milestone_data);
                    break;
                }
            }
        } else {
            // Add new milestone
            $milestone_data['id'] = uniqid();
            $milestone_data['status'] = 'pending';
            $milestone_data['client_approval'] = false;
            $milestone_data['freelancer_approval'] = false;
            $milestones[] = $milestone_data;
            // Trigger email notification
            do_action('workscout_freelancer_new_milestone_created', $project_id, $milestone_data);
        }

        return update_post_meta($project_id, $this->meta_key, $milestones);
    }

    // Handle milestone approval
    public function approve_milestone($project_id, $milestone_id, $user_type)
    {
        $milestones = $this->get_milestones($project_id);
        $completion = 0;
        foreach ($milestones as &$milestone) {
            $completion = $milestone['percentage'];
            if ($milestone['id'] === $milestone_id) {
                if ($user_type === 'client') {
                    $milestone['client_approval'] = true;
                } else {
                    $milestone['freelancer_approval'] = true;
                    do_action('workscout_freelancer_milestone_for_approval', $project_id, $milestone_id);
                }

                // Check if both parties approved
                if ($milestone['client_approval'] && $milestone['freelancer_approval']) {
                    $milestone['status'] = 'approved';

                    // Create WooCommerce order
                    $order_id = $this->create_milestone_order($project_id, $milestone);
                    if ($order_id) {
                        $milestone['order_id'] = $order_id;
                    }
                    
                    // Trigger milestone approved notification
                    do_action('workscout_freelancer_milestone_approved', $project_id, $milestone_id,$order_id);
                }
                break;
            }
        }
        update_post_meta($project_id, $this->meta_key, $milestones);
        $data = array(
            'remaining_percentage' => 100-$completion
        );
        return $data;
    }

    // In the WorkScout_Project_Milestones class
    public function ajax_approve_milestone()
    {
        // Verify nonce
        check_ajax_referer('workscout_milestone_nonce', 'nonce');

        $project_id = intval($_POST['project_id']);
        $milestone_id = sanitize_text_field($_POST['milestone_id']);

        // Check if user has permission
        if (!$this->can_approve_milestone($project_id)) {
            wp_send_json_error('Permission denied');
            return;
        }

        // Determine if current user is client or freelancer
        $user_type = $this->get_user_type($project_id);

        $result = $this->approve_milestone($project_id, $milestone_id, $user_type);
        
        if ($result) {
           
            wp_send_json_success( array(
                'remaining_percentage' => $result['remaining_percentage']
                )
            );
        } else {
            wp_send_json_error();
        }
    }

    /**
     * AJAX handler for getting milestone data for editing
     */
    public function ajax_get_milestone_for_edit()
    {
       // check_ajax_referer('workscout_milestone_nonce', 'nonce');

        $project_id = intval($_POST['project_id']);
        $milestone_id = sanitize_text_field($_POST['milestone_id']);

        // if (!$this->can_edit_milestone($project_id, $milestone_id)) {
        //     wp_send_json_error(['message' => __('Permission denied', 'workscout-freelancer')]);
        //     return;
        // }

        $milestone = $this->get_milestone($project_id, $milestone_id);

        if ($milestone) {
            // Get project value for percentage calculations
            $project_value = $this->get_project_value($project_id);
            
            // Calculate remaining percentage excluding current milestone
            $remaining_percentage = 100 - $this->get_total_milestone_percentage($project_id, $milestone_id);
            $remaining_percentage += floatval($milestone['percentage']); // Add back current milestone percentage
            
            wp_send_json_success([
                'milestone' => $milestone,
                'project_value' => $project_value,
                'remaining_percentage' => $remaining_percentage
            ]);
        } else {
            wp_send_json_error(['message' => __('Milestone not found', 'workscout-freelancer')]);
        }
    }

    /**
     * AJAX handler for updating milestone
     */
    public function ajax_update_milestone()
    {
      //  check_ajax_referer('workscout_milestone_nonce', 'nonce');

        $project_id = intval($_POST['project_id']);
        $milestone_id = sanitize_text_field($_POST['milestone_id']);
        $percentage = floatval($_POST['percentage']);

        if (!$this->can_edit_milestone($project_id, $milestone_id)) {
            wp_send_json_error(['message' => __('Permission denied', 'workscout-freelancer')]);
            return;
        }

        // Validate percentage
        if (!$this->validate_milestone_percentage($project_id, $percentage, $milestone_id)) {
            wp_send_json_error([
                'message' => __('Total milestone percentages cannot exceed 100%', 'workscout-freelancer'),
                'current_total' => $this->get_total_milestone_percentage($project_id, $milestone_id)
            ]);
            return;
        }

        $amount = $this->calculate_amount_from_percentage($project_id, $percentage);

        $milestone_data = [
            'id' => $milestone_id,
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'percentage' => $percentage,
            'amount' => $amount,
        ];

        $result = $this->update_milestone($project_id, $milestone_data);

        if ($result) {
            wp_send_json_success([
                'milestone' => $milestone_data,
                'remaining_percentage' => 100 - $this->get_total_milestone_percentage($project_id)
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to update milestone', 'workscout-freelancer')]);
        }
    }

    private function create_milestone_order($project_id, $milestone)
    {
        WC()->cart->empty_cart();
        // Get project details
        $project = get_post($project_id);
        $client_id = $project->post_author;
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);

        try {
            // Create the order
            // Create or get the product
            $product_id = $this->get_or_create_milestone_product();
            if (!$product_id) {
                error_log('Failed to create milestone product');
                return false;
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                error_log('Failed to get milestone product');
                return false;
            }

            // Set the price
            $product->set_price($milestone['amount']);
            $product->set_regular_price($milestone['amount']);
            $product->save();

            // Create the order
            $order = wc_create_order(array(
                'status' => 'pending',
                'customer_id' => get_current_user_id()
            ));

            // Add the product to the cart first
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), array(
                'milestone_id' => $milestone['id'],
                'project_id' => $project_id
            ));

            if (!$cart_item_key) {
                error_log('Failed to add product to cart');
                return false;
            }
            $item_id = $order->add_product($product, 1, array(
                'subtotal' => $milestone['amount'],
                'total' => $milestone['amount']
            ));
            if (!$item_id) {
                error_log('Failed to add product to order');
                return false;
            }
            // Add meta data
            $order->update_meta_data('project_id', $project_id);
            $order->update_meta_data('project_title', get_the_title($project_id));
            $order->update_meta_data('milestone_id', $milestone['id']);
            $order->update_meta_data('milestone_title', $milestone['title']);
            $order->update_meta_data('freelancer_id', get_post_meta($project_id, '_freelancer_id', true));
            $order->update_meta_data('employer_id', get_post_meta($project_id, '_employer_id', true));

            // Set address
            $this->set_order_address($order, get_current_user_id());

            // Calculate totals
            $order->calculate_totals();

            // Add note
            $order->add_order_note(sprintf(
                'Order created for project milestone: %s (Project: %s)',
                $milestone['title'],
                get_the_title($project_id)
            ));

            $order->save();

            // Fire the actions
            do_action('workscout_milestone_order_created', $order->get_id(), $project_id,
                $milestone
            );
            do_action('workscout_freelancer_order_created', $order->get_id());

            return $order->get_id();
        } catch (Exception $e) {
            error_log('Failed to create milestone order: ' . $e->getMessage());
            return false;
        }
    }


    // Helper function to set order address
    private function set_order_address($order, $client_id)
    {
        $client = get_userdata($client_id);
        if ($client) {
            $address = array(
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => $client->user_email,
                'phone'      => get_user_meta($client_id, 'billing_phone', true),
                'address_1'  => get_user_meta($client_id, 'billing_address_1', true),
                'address_2'  => get_user_meta($client_id, 'billing_address_2', true),
                'city'       => get_user_meta($client_id, 'billing_city', true),
                'state'      => get_user_meta($client_id, 'billing_state', true),
                'postcode'   => get_user_meta($client_id, 'billing_postcode', true),
                'country'    => get_user_meta($client_id, 'billing_country', true),
            );

            // Set both billing and shipping address
            $order->set_address($address, 'billing');
            $order->set_address($address, 'shipping');
        }
    }


    private function get_or_create_milestone_product()
    {
        try {
        // Try to get existing milestone product
        $product_id = get_option('workscout_milestone_product_id');

        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                // Update existing product settings
                $product->set_status('publish');
                $product->set_catalog_visibility('hidden');
                $product->set_virtual(true);
                $product->set_downloadable(false);
                
                $product->set_sold_individually(true);
                $product->set_stock_status('instock');
                $product->save();
                return $product_id;
            }
        }


        // Create new product
        $product = new WC_Product_Simple();
        $product->set_name('Project Milestone Payment');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_sold_individually(true);
        $product->set_stock_status('instock');
        $product->set_manage_stock(false);
        $product->set_reviews_allowed(false);

            // Set initial prices
            $product->set_regular_price('0');
            $product->set_price('0');

        // Set the SKU
        $product->set_sku('milestone-payment-' . wp_generate_password(6, false));

        // Save the product
        $product->save();

        // Save product ID for future use
        update_option('workscout_milestone_product_id', $product->get_id());

        return $product->get_id();
        } catch (Exception $e) {
            error_log('Error creating milestone product: ' . $e->getMessage());
            return false;
        }
    }


    // Hook for order status changes
    public function handle_order_status_change($order_id, $old_status, $new_status)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Get milestone info from order
        $project_id = $order->get_meta('project_id');
        $milestone_id = $order->get_meta('milestone_id');

        if (!$project_id || !$milestone_id) return;

        // Update milestone status based on order status
        $milestones = $this->get_milestones($project_id);

        foreach ($milestones as &$milestone) {
            if ($milestone['id'] === $milestone_id) {
                switch ($new_status) {
                    case 'completed':
                    case 'processing':
                        $milestone['payment_status'] = 'paid';
                        break;

                    case 'refunded':
                        $milestone['payment_status'] = 'refunded';
                        break;

                    case 'failed':
                        $milestone['payment_status'] = 'failed';
                        break;
                }
                break;
            }
        }
        if ($new_status === 'completed') {
            do_action('workscout_freelancer_payment_complete',$order_id, $project_id, $milestone_id);
            
            // Check if all milestones are paid
            $total_progress = 0;
            $all_paid = true;
            
            foreach ($milestones as $milestone) {
                $total_progress += floatval($milestone['percentage']);
                if ($milestone['payment_status'] !== 'paid') {
                    $all_paid = false;
                    break;
                }
            }
            
            // If all milestones are paid and total progress is 100%, mark project as completed
            if ($all_paid && abs($total_progress - 100) < 0.01) {
                wp_update_post(array(
                    'ID' => $project_id,
                    'post_status' => 'completed'
                ));
                // update post meta _project_status to completed
                update_post_meta($project_id, '_project_status', 'completed');
            }
        }

        update_post_meta($project_id, $this->meta_key, $milestones);
    }

    // Get total project value from custom field
    private function get_project_value($project_id)
    {
        return floatval(get_post_meta($project_id, '_budget', true));
    }

    // Calculate total percentage of existing milestones
    public function get_total_milestone_percentage($project_id, $exclude_milestone_id = null)
    {
        $milestones = $this->get_milestones($project_id);
        $total = 0;

        foreach ($milestones as $milestone) {
            // Skip the milestone we're updating if provided
            // check if milestone has percentage
            if (!isset($milestone['percentage'])) {
                continue;
                // check if milestone has amount
            }
            if ($exclude_milestone_id && $milestone['id'] === $exclude_milestone_id) {
                continue;
            }
            $total += floatval($milestone['percentage']);
        }

        return $total;
    }

    // Validate milestone percentage
    private function validate_milestone_percentage($project_id, $new_percentage, $milestone_id = null)
    {
        $current_total = $this->get_total_milestone_percentage($project_id, $milestone_id);
        $total_with_new = $current_total + floatval($new_percentage);

        return $total_with_new <= 100;
    }

    // Calculate amount based on percentage
    private function calculate_amount_from_percentage($project_id, $percentage)
    {
        $project_value = $this->get_project_value($project_id);
       
        return ($project_value * floatval($percentage)) / 100;
    }

    public function get_milestone_payment_link($milestone)
    {
        if (!isset($milestone['order_id'])) {
            return '';
        }

        $order = wc_get_order($milestone['order_id']);
        if (!$order) {
            return '';
        }

        // Check if current user is the client
        if (get_current_user_id() !== $order->get_customer_id()) {
            return '';
        }

        switch ($order->get_status()) {
            case 'pending':
                return sprintf(
                    '<a href="%s" class="button pay-milestone">Pay Now</a>',
                    esc_url($order->get_checkout_payment_url())
                );

            case 'processing':
            case 'completed':
                return '<span class="milestone-paid">Payment Complete</span>';

            default:
                return sprintf(
                    '<span class="milestone-status">Order Status: %s</span>',
                    esc_html($order->get_status())
                );
        }
    }

    // Helper function to determine user type
    public function get_user_type($project_id)
    {
        $current_user_id = get_current_user_id();
        $project = get_post($project_id);
        $freelancer = get_post_meta($project_id, '_freelancer_id', true);
        $employer = get_post_meta($project_id, '_employer_id', true);

        if ($current_user_id === intval($employer)) {
            return 'client';
        } elseif ($current_user_id === intval($freelancer)) {
            return 'freelancer';
        }

        return false;
    }

    // Check if user can approve milestone
    private function can_approve_milestone($project_id)
    {
        $user_type = $this->get_user_type($project_id);
        return $user_type !== false;
    }

    /**
     * Check if user can edit milestone
     * 
     * @param int $project_id Project ID
     * @param string $milestone_id Milestone ID
     * @param int $user_id User ID (optional)
     * @return bool
     */
    public function can_edit_milestone($project_id, $milestone_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $milestone = $this->get_milestone($project_id, $milestone_id);
        if (!$milestone) {
            return false;
        }

        // Get project owner and freelancer
        $employer_id = get_post_meta($project_id, '_employer_id', true);
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);

        // Check if milestone is already approved
        if (isset($milestone['status']) && $milestone['status'] === 'approved') {
            return false;
        }
        if ($user_id == $employer_id) {
            return false;
        }
        if ($user_id == $freelancer_id) {
            return true;
        }
        // Only employer can edit milestones
        return ($user_id == $employer_id);
    }
    /**
     * Check if user can edit milestones for a project
     * 
     * @param int $project_id Project ID
     * @param int $user_id Optional user ID, defaults to current user
     * @return bool
     */
    private function can_edit_milestones($project_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Get project owner and freelancer
        $employer_id = get_post_meta($project_id, '_employer_id', true);
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);

        // Only freelancers can add/edit milestones 
        return ($user_id == $freelancer_id);
    }
    // AJAX handler for saving milestone
    public function ajax_save_milestone()
    {
        check_ajax_referer('workscout_milestone_nonce', 'nonce');

        $project_id = intval($_POST['project_id']);
        $percentage = floatval($_POST['percentage']);
        $milestone_id = isset($_POST['milestone_id']) ? sanitize_text_field($_POST['milestone_id']) : null;
        
        // Check if user has permission
        if (!$this->can_edit_milestones($project_id)) {
            wp_send_json_error('Permission denied');
            return;
        }
        // Validate percentage
        if (!$this->validate_milestone_percentage($project_id, $percentage, $milestone_id)) {
            wp_send_json_error([
                'message' => 'Total milestone percentages cannot exceed 100%',
                'current_total' => $this->get_total_milestone_percentage($project_id, $milestone_id)
            ]);
            return;
        }

        $amount = $this->calculate_amount_from_percentage($project_id, $percentage);
        
        $milestone_data = [
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'percentage' => $percentage,
            'amount' => $amount,
            
        ];
  
        if (isset($_POST['milestone_id'])) {
            $milestone_data['id'] = sanitize_text_field($_POST['milestone_id']);
        }
        $result = $this->save_milestone($project_id, $milestone_data);

        if ($result) {
            wp_send_json_success([
                'milestone' => $milestone_data,
                'remaining_percentage' => 100 - $this->get_total_milestone_percentage($project_id)
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to save milestone']);
        }
    }

    // AJAX handler to get remaining percentage
    public function ajax_get_remaining_percentage()
    {
        check_ajax_referer('workscout_milestone_nonce', 'nonce');

        $project_id = intval($_POST['project_id']);
        $milestone_id = isset($_POST['milestone_id']) ? sanitize_text_field($_POST['milestone_id']) : null;

        $remaining = 100 - $this->get_total_milestone_percentage($project_id, $milestone_id);

        wp_send_json_success(['remaining' => $remaining]);
    }


    /**
     * Delete a milestone
     * 
     * @param int $project_id Project ID
     * @param string $milestone_id Milestone ID
     * @return bool Success status
     */
    public function delete_milestone($project_id, $milestone_id)
    {
        
        $milestones = $this->get_milestones($project_id);
        $updated_milestones = array();

        foreach ($milestones as $milestone) {
            if ($milestone['id'] !== $milestone_id) {
                $updated_milestones[] = $milestone;
            } else {
                // Check if milestone can be deleted
                if (isset($milestone['status']) && $milestone['status'] === 'approved') {
                    return false; // Cannot delete approved milestone
                }
            }
        }

        return update_post_meta($project_id, $this->meta_key, $updated_milestones);
    }

    /**
     * Update existing milestone
     * 
     * @param int $project_id Project ID
     * @param array $milestone_data Updated milestone data
     * @return bool Success status
     */
    public function update_milestone($project_id, $milestone_data)
    {
        if (!isset($milestone_data['id'])) {
            return false;
        }

        $milestones = $this->get_milestones($project_id);
        $milestone_updated = false;
       
        
        foreach ($milestones as $key => $milestone) {
            if ($milestone['id'] === $milestone_data['id']) {
                // Check if milestone can be edited
                if (isset($milestone['status']) && $milestone['status'] === 'approved') {
                    return false; // Cannot edit approved milestone
                }

                // Update milestone data while preserving status and approval flags
                $milestones[$key] = array_merge($milestone, $milestone_data);
                // unset freelancer_approval flag
                $milestones[$key]['freelancer_approval'] = false;
                $milestones[$key]['client_approval'] = false;
                $milestone_updated = true;
                break;
            }
        }

        if ($milestone_updated) {
            return update_post_meta($project_id, $this->meta_key, $milestones);
        }

        return false;
    }


    

    /**
     * Get single milestone by ID
     * 
     * @param int $project_id Project ID
     * @param string $milestone_id Milestone ID
     * @return array|bool Milestone data or false if not found
     */
    public function get_milestone($project_id, $milestone_id)
    {
        $milestones = $this->get_milestones($project_id);

        foreach ($milestones as $milestone) {
            if ($milestone['id'] === $milestone_id) {
                return $milestone;
            }
        }

        return false;
    }


    /**
     * AJAX handler for deleting milestone
     */
    public function ajax_delete_milestone()
    {
       
        //check_ajax_referer('workscout_milestone_nonce', 'nonce');

        $project_id = intval($_POST['project_id']);
        $milestone_id = sanitize_text_field($_POST['milestone_id']);

        // if (!$this->can_edit_milestone($project_id, $milestone_id)) {
        //     wp_send_json_error(['message' => 'Permission denied']);
        //     return;
        // }

        $result = $this->delete_milestone($project_id, $milestone_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Milestone deleted successfully',
                'remaining_percentage' => 100 - $this->get_total_milestone_percentage($project_id)
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete milestone']);
        }
    }

    // Get milestone status badge HTML
    public function get_status_badge($status)
    {
        $badges = array(
            'pending' => '<span class="badge badge-warning">Pending Approval</span>',
            'approved' => '<span class="badge badge-success">Approved</span>',
            'completed' => '<span class="badge badge-primary">Completed</span>'
        );

        return isset($badges[$status]) ? $badges[$status] : '';
    }


    
}
