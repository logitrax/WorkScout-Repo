<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WorkScout_Freelancer_Emails class
 */
class WorkScout_Freelancer_Emails
{

    /**
     * The single instance of the class.
     *
     * @var self
     * @since  1.0
     */
    private static $_instance = null;
    private $project;
    /**
     * Allows for accessing single instance of class. Class should only be constructed once per call.
     *
     * @since  1.0
     * @static
     * @return self Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        // Hook into project/milestone events
        $this->project = WorkScout_Freelancer_Project::instance();

     
        //about new project
        add_action('workscout_freelancer_new_project_created', array($this, 'new_project'));
        
        //employer bout new milestone
        add_action('workscout_freelancer_new_milestone_created', array($this, 'new_milestone'), 10, 2);
        
        //employer milestone edited notifications
        add_action('workscout_freelancer_milestone_edited', array($this, 'milestone_edited'), 10, 2);

        //employer notifications about milestone for review
        add_action('workscout_freelancer_milestone_for_approval', array($this, 'milestone_for_approval'), 10, 2);

        //freelancer notifications about milestone completion
        add_action('workscout_freelancer_milestone_approved', array($this, 'milestone_approved'), 10, 3);

        //new milestone payment email notifications
        add_action('workscout_freelancer_order_created', array($this, 'order_created'));
        
      // add_action('workscout_freelancer_payment_complete',array('$this','payment_complete_notification'));
    }
    // 
        
    /**
     * New Order Notification
     */
    function order_created($order_id)
    {
        // get option to check if notification is enabled
        $notification_data = get_option('workscout_freelancer_new_order_notification');
        if (is_array($notification_data) && $notification_data['workscout_freelancer_new_order_notification_enable'] != 1) {
            return;
        }

        $order = wc_get_order($order_id);
        $project_id = $order->get_meta('project_id');
        $milestone_id = $order->get_meta('milestone_id');
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);
        $freelancer_data = get_userdata($freelancer_id);

        $args = array(
            'milestone_id' => $milestone_id,
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'order_id' => $order_id,
            'order_amount' => $order->get_total(),
            'project_title' => get_the_title($project_id),
            'project_url' => get_permalink($project_id),
        );

        $subject = $notification_data['workscout_freelancer_new_order_subject'];
        $subject = $this->replace_shortcode($args, $subject);

        $body = $notification_data['workscout_freelancer_new_order_content'];

        $body = $this->replace_shortcode($args, $body);
        self::send($args['user_mail'], $subject, $body);
    }
    /**
     * New Project Notification
     */
    function new_project($project_id)
    {

        $notification_data = get_option('workscout_freelancer_new_project_notification');
        if (is_array($notification_data) && $notification_data['workscout_freelancer_new_project_notification_enable'] != 1) {
            return;
        }
        $project = get_post($project_id);
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);
        $employer_id = get_post_meta($project_id, '_employer_id', true);

        $freelancer_data = get_userdata($freelancer_id);
        $employer_data = get_userdata($employer_id);

        $args = array(
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'employer_name' => $employer_data->display_name,
            'freelancer_name' => $freelancer_data->display_name,
            'project_title' => $project->post_title,
            'project_url' => get_permalink($project_id),
            'project_budget' => floatval(get_post_meta($project_id, '_budget', true)),
            'project_description' => get_post_field('post_content', $project_id),
            
        );

        $subject = $notification_data['workscout_freelancer_new_project_subject'];
        $subject = $this->replace_shortcode($args, $subject);

        $body = $notification_data['workscout_freelancer_new_project_content'];

        $body = $this->replace_shortcode($args, $body);
        self::send($args['user_mail'], $subject, $body);
    }

    /**
     * New Milestone Notification
     */
    function new_milestone($project_id,$milestone_data)
    {
        $notification_data = get_option('workscout_employer_new_milestone_notification');
        if (is_array($notification_data) && $notification_data['workscout_employer_new_milestone_notification_enable'] != 1) {
            return;
        }
        $project_id = $project_id;
        $project = get_post($project_id);
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);
        $employer_id = get_post_meta($project_id, '_employer_id', true);

        $freelancer_data = get_userdata($freelancer_id);
        $employer_data = get_userdata($employer_id);
        $milestone_details = $this->project->get_milestone_details($milestone_data['id'], $project_id);
        $args = array(
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'employer_name' => $employer_data->display_name,
            'freelancer_name' => $freelancer_data->display_name,
            'project_title' => $project->post_title,
            'project_url' => get_permalink($project_id),
            'milestone_title' => $milestone_details['title'],
            'milestone_amount' => $milestone_details['amount'],
            
        );

        $subject = $notification_data['workscout_employer_new_milestone_subject'];
        $subject = $this->replace_shortcode($args, $subject);

        $body = $notification_data['workscout_employer_new_milestone_content'];


        $body = $this->replace_shortcode($args, $body);
        self::send($employer_data->user_email, $subject, $body);
    }

    function milestone_edited($project_id, $milestone_id)
    {
        $notification_data = get_option('workscout_employer_milestone_edited_notification');
        if (is_array($notification_data) && $notification_data['workscout_employer_milestone_edited_notification_enable'] != 1) {
            return;
        }
        $project_id = $project_id;
        $project = get_post($project_id);
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);
        $employer_id = get_post_meta($project_id, '_employer_id', true);

        $freelancer_data = get_userdata($freelancer_id);
        $employer_data = get_userdata($employer_id);
        $milestone_details = $this->project->get_milestone_details($milestone_id, $project_id);
        $args = array(
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'employer_name' => $employer_data->display_name,
            'freelancer_name' => $freelancer_data->display_name,
            'project_title' => $project->post_title,
            'project_url' => get_permalink($project_id),
            'milestone_title' => $milestone_details['title'],
            'milestone_amount' => $milestone_details['amount'],

        );
        
        $subject = $this->replace_shortcode($args, $notification_data['workscout_employer_milestone_edited_notification_subject']);
        $subject = $this->replace_shortcode($args, $subject);

        $body = $notification_data['workscout_employer_milestone_edited_notification_content'];

        $body = $this->replace_shortcode($args, $body);
        self::send($employer_data->user_email, $subject, $body);
    }
    /**
     * Milestone Approved Notification
     */
    function milestone_approved($project_id, $milestone_id, $order_id)
    {
        $notification_data = get_option('workscout_employer_milestone_approval_notification');
        
        if (is_array($notification_data) && $notification_data['workscout_employer_milestone_approval_notification_enable'] != 1) {
            return;
        }
        $milestone_details = $this->project->get_milestone_details($milestone_id, $project_id);
        
        $freelancer_id = get_post_meta($project_id, '_employer_id', true);
        $freelancer_data = get_userdata($freelancer_id);

        // get payment link from order id
        if(!$order_id){
            return;
        }

        $order = wc_get_order($order_id);
        if(!$order){
            return;
        }
        $payment_link = $order->get_checkout_payment_url();

        //$milestone_details = $this->project->get_milestone_details($milestone_id, $project_id);
        $args = array(
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'milestone_title' => $milestone_details['title'],
            'milestone_amount' => $milestone_details['amount'],
            'project_title' => get_the_title($project_id),
            'project_url' => get_permalink($project_id),
            'payment_link' => $payment_link,
        );

        $subject = $this->replace_shortcode($args, $notification_data['workscout_employer_milestone_approval_subject']);
        $subject = $this->replace_shortcode($args, $subject);

        $body = $notification_data['workscout_employer_milestone_approval_content'];
   

        $body = $this->replace_shortcode($args, $body);
        self::send($args['user_mail'], $subject, $body);
    }
    /**
     * Milestone Approved Notification
     */
    function milestone_for_approval($project_id, $milestone_id)
    {
        $notification_data = get_option('workscout_employer_milestone_approval_notification');
        if (is_array($notification_data) && $notification_data['workscout_employer_milestone_approval_notification_enable'] != 1) {
            return;
        }
        $project_id = $project_id;
        $freelancer_id = get_post_meta($project_id, '_employer_id', true);
        $freelancer_data = get_userdata($freelancer_id);
        $milestone_data = $this->project->get_milestone_details($milestone_id, $project_id);
        $args = array(
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'milestone_title' => $milestone_data['title'],
            'milestone_amount' => $milestone_data['amount'],
            'project_title' => get_the_title($project_id),
            'project_url' => get_permalink($project_id),
        );

        $subject = $this->replace_shortcode($args, $notification_data['workscout_employer_milestone_approval_subject']);
        $subject = $this->replace_shortcode($args, $subject);

        $body = $notification_data['workscout_employer_milestone_approval_content'];


        $body = $this->replace_shortcode($args, $body);
        self::send($args['user_mail'], $subject, $body);
    }

   

    /**
     * Payment Complete Notification
     */
    function payment_complete_notification($payment_data)
    {
        $project_id = $payment_data['project_id'];
        $freelancer_id = get_post_meta($project_id, '_freelancer_id', true);
        $freelancer_data = get_userdata($freelancer_id);

        $args = array(
            'user_mail' => $freelancer_data->user_email,
            'user_name' => $freelancer_data->display_name,
            'payment_amount' => $payment_data['amount'],
            'project_title' => get_the_title($project_id),
            'project_url' => get_permalink($project_id),
        );

        $subject = get_option('workscout_freelancer_payment_complete_subject', 'Payment Received: {payment_amount}');
        $subject = $this->replace_shortcode($args, $subject);

        $body = get_option(
            'workscout_freelancer_payment_complete_content',
            "Hi {user_name},<br><br>
            A payment of {payment_amount} has been completed for your project {project_title}.<br>
            View project: {project_url}<br><br>
            Best regards,<br>
            {site_name}"
        );

        $body = $this->replace_shortcode($args, $body);
        self::send($args['user_mail'], $subject, $body);
    }

    /**
     * Send email
     */
    public static function send($emailto, $subject, $body)
    {
        $from_name = get_option('workscout_freelancer_emails_name', get_bloginfo('name'));
        $from_email = get_option('workscout_freelancer_emails_from_email', get_bloginfo('admin_email'));
        $headers = sprintf("From: %s <%s>\r\n Content-type: text/html", $from_name, $from_email);

        if (empty($emailto) || empty($subject) || empty($body)) {
            return;
        }

        $template_loader = new WorkScout_Freelancer_Template_Loader;
        ob_start();
        $template_loader->get_template_part('emails/header');
?>
        <tr>
            <td align="left" valign="top" style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; padding-left: 25px; padding-right: 25px; padding-bottom: 28px; width: 87.5%; font-size: 16px; font-weight: 400; padding-top: 28px; color: #666; font-family: sans-serif;" class="paragraph">
                <?php echo $body; ?>
            </td>
        </tr>
<?php
        $template_loader->get_template_part('emails/footer');
        $content = ob_get_clean();

        wp_mail($emailto, $subject, $content, $headers);
    }

    /**
     * Replace email template shortcodes
     */
    public function replace_shortcode($args, $body)
    {
        $tags = array(
            'user_mail' => "",
            'user_name' => "",
            'project_title' => "",
            'project_url' => "",
            'milestone_title' => "",
            'milestone_amount' => "",
            'order_id' => "",
            'order_amount' => "",
            'payment_amount' => "",
            'employer_name' => "",
            'site_name' => get_bloginfo('name'),
            'site_url' => get_home_url(),
        );

        $tags = array_merge($tags, $args);
        extract($tags);

        $search = array(
            '{user_mail}',
            '{user_name}',
            '{project_title}',
            '{project_url}',
            '{milestone_title}',
            '{milestone_amount}',
            '{order_id}',
            '{order_amount}',
            '{payment_amount}',
            '{employer_name}',
            '{site_name}',
            '{site_url}',
        );

        $replace = array(
            $user_mail,
            $user_name,
            $project_title,
            $project_url,
            $milestone_title,
            $milestone_amount,
            $order_id,
            $order_amount,
            $payment_amount,
            $employer_name,
            get_bloginfo('name'),
            get_home_url(),
        );

        $message = str_replace($search, $replace, $body);
        $message = nl2br($message);

        return $message;
    }
}
