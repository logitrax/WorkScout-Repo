<?php

/**
 * File containing the class WP_Resume_Manager_Settings.
 *
 * @package wp-job-manager-resumes
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * WP_Resume_Manager_Settings class.
 */
class Workscout_Freelancer_Settings extends WP_Job_Manager_Settings
{

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->settings_group = 'workscout-freelancer';
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_action_update', [$this, 'pre_process_settings_save']);
        
    }

    /**
     * Init_settings function.
     *
     * @access protected
     * @return void
     */
    protected function init_settings()
    {
        // Prepare roles option.
        $roles         = get_editable_roles();
        $account_roles = [];

        foreach ($roles as $key => $role) {
            if ('administrator' === $key) {
                continue;
            }
            $account_roles[$key] = $role['name'];
        }

        $empty_trash_days = defined('EMPTY_TRASH_DAYS ') ? EMPTY_TRASH_DAYS : 30;
        if (empty($empty_trash_days) || $empty_trash_days < 0) {
            $trash_description = __('They will then need to be manually removed from the trash', 'workscout-freelancer');
        } else {
            // translators: Placeholder %d is the number of days before items are removed from trash.
            $trash_description = sprintf(__('They will then be permanently deleted after %d days.', 'workscout-freelancer'), $empty_trash_days);
        }

        $this->settings = apply_filters(
            'workscout_freelancer_settings',
            [
                'tasks'    => [
                    __('Task Listings', 'workscout-freelancer'),
                    [
                        // [
                        //     'name'        => 'task_per_page',
                        //     'std'         => '10',
                        //     'placeholder' => '',
                        //     'label'       => __('Task Per Page', 'workscout-freelancer'),
                        //     'cb_label'       => __('Task Per Page', 'workscout-freelancer'),
                        //     'desc'        => __('How many task should be shown per page by default?', 'workscout-freelancer'),
                        //     'attributes'  => [],
                        // ],
                        // [
                        //     'name'        => 'task_ajax_browsing',
                        //     'std'         => '10',
                        //     'placeholder' => '',
                        //     'label'       => __('Use ajax browsing', 'workscout-freelancer'),
                        //     'cb_label'       => __('Use ajax browsing', 'workscout-freelancer'),
                        //     'desc'        => __('If enabled search results will be automatically updated after every change', 'workscout-freelancer'),
                        //     'attributes'  => [],
                        //     'type'       => 'checkbox',
                        // ],
                        // [
                        //     'name'       => 'task_list_layout',
                        //     'std'        => 'list-1',
                        //     'label'      => __('Task list layout', 'workscout-freelancer'),
                        //     'cb_label'      => __('Task list layout', 'workscout-freelancer'),
                        //     'desc'       => __('Set layout for list of tasks', 'workscout-freelancer'),
                        //     'type'       => 'radio',
                        //     'options'    => [
                        //         'list-1'            => __('List layout 1', 'workscout-freelancer'),
                        //         'list-2'           => __('List layout 2', 'workscout-freelancer'),
                        //         'grid' => __('Grid', 'workscout-freelancer'),
                        //         'full' => __('Full page grid', 'workscout-freelancer'),
                        //     ],
                        //     'attributes' => [],
                        // ],
                        // [
                        //     'name'       => 'task_list_layout_sidebar',
                        //     'std'        => 'left',
                        //     'label'      => __('Sidebar side', 'workscout-freelancer'),
                        //     'cb_label'      => __('Does not apply to full page', 'workscout-freelancer'),
                        //     'desc'       => __('Set sidebar side for list of tasks', 'workscout-freelancer'),
                        //     'type'       => 'radio',
                        //     'options'    => [
                                
                        //         'left'            => __('Left sidebar', 'workscout-freelancer'),
                        //         'right'           => __('Right sidebar', 'workscout-freelancer'),
                              
                        //     ],
                        //     'attributes' => [],
                        // ],
                        [
                            'name'       => 'task_paid_listings_flow',
                            'std'        => 'before',
                            'label'      => __('Paid listings flow', 'workscout-freelancer'),
                            'cb_label'      => __('Paid listings flow', 'workscout-freelancer'),
                            'desc'       => __('Set when the package option will appear', 'workscout-freelancer'),
                            'type'       => 'radio',
                            'options'    => [
                                'before'            => __('Before submit form', 'workscout-freelancer'),
                                'after'           => __('After submit form', 'workscout-freelancer'),
                                
                            ],
                            'attributes' => [],
                        ],
                        // option to hide list of bidders
                        [
                            'name'       => 'task_hide_bidders',
                            'std'        => '0',
                            'label'      => __('Hide list of bidders', 'workscout-freelancer'),
                            'cb_label'      => __('Hide list of bidders', 'workscout-freelancer'),
                            'desc'       => __('Hide list of bidders on task page', 'workscout-freelancer'),
                            'type'       => 'checkbox',
                            'attributes' => [],
                        ],
                    ],
                ],
                // 'project_options' => [
                //     __('Project Options','workscout-freelancer'),
                //     [
                //         // add option for commission for project payments
                //         [
                //             'name'       => 'workscout_freelancer_project_commission',
                //             'std'        => '0',
                //             'label'      => __('Project Commission', 'workscout-freelancer'),
                //             'cb_label'   => __('Enable Project Commission', 'workscout-freelancer'),
                //             'desc'       => __('Enable project commission for project payments', 'workscout-freelancer'),
                //             'type'       => 'checkbox',
                //             'attributes' => [],
                //         ],
                //         [
                //             'name'       => 'workscout_commission_rate',
                //             'std'        => '10',
                //             'label'      => __('Project Commission Rate', 'workscout-freelancer'),
                //             'cb_label'   => __('Project Commission Rate', 'workscout-freelancer'),
                //             'desc'       => __('Set the project commission rate in percentage', 'workscout-freelancer'),
                //             'type'       => 'text',
                //             'attributes' => [],
                //         ],
                //         // [
                //         //     'name'       => 'workscout_freelancer_project_commission_type',
                //         //     'std'        => 'fixed',
                //         //     'label'      => __('Project Commission Type', 'workscout-freelancer'),
                //         //     'cb_label'   => __('Project Commission Type', 'workscout-freelancer'),
                //         //     'desc'       => __('Set the project commission type', 'workscout-freelancer'),
                //         //     'type'       => 'radio',
                //         //     'options'    => [
                //         //         'fixed'            => __('Fixed', 'workscout-freelancer'),
                //         //         'percentage'           => __('Percentage', 'workscout-freelancer'),
                                
                //         //     ],
                //         //     'attributes' => [],
                //         // ],
                        
                //     ]
                // ],
                'task_submission'  => [
                    __('Task Submission', 'workscout-freelancer'),
                    [
                       
                        [
                            'name'       => 'workscout_freelancer_task_submission_requires_approval',
                            'std'        => '1',
                            'label'      => __('Approval Required', 'workscout-freelancer'),
                            'cb_label'   => __('New submissions require admin approval', 'workscout-freelancer'),
                            'desc'       => __('If enabled, new submissions will be inactive, pending admin approval.', 'workscout-freelancer'),
                            'type'       => 'checkbox',
                            'attributes' => [],
                        ],
                        [
                            'name'       => 'workscout_freelancer_user_can_edit_pending_submissions',
                            'std'        => '0',
                            'label'      => __('Allow Pending Edits', 'workscout-freelancer'),
                            'cb_label'   => __('Allow editing of pending tasks', 'workscout-freelancer'),
                            'desc'       => __('Users can continue to edit pending tasks until they are approved by an admin.', 'workscout-freelancer'),
                            'type'       => 'checkbox',
                            'attributes' => [],
                        ],
                        [
                            'name'       => 'workscout_freelancer_user_edit_published_submissions',
                            'std'        => 'yes',
                            'label'      => __('Allow Published Edits', 'workscout-freelancer'),
                            'cb_label'   => __('Allow editing of published tasks', 'workscout-freelancer'),
                            'desc'       => __('Choose whether published tasks can be edited and if edits require admin approval. When moderation is required, the original resume will be unpublished while edits await admin approval.', 'workscout-freelancer'),
                            'type'       => 'radio',
                            'options'    => [
                                'no'            => __('Users cannot edit', 'workscout-freelancer'),
                                'yes'           => __('Users can edit without admin approval', 'workscout-freelancer'),
                                'yes_moderated' => __('Users can edit, but edits require admin approval', 'workscout-freelancer'),
                            ],
                            'attributes' => [],
                        ],
                        
                    ],
                ],
              
                'task_pages'       => [
                    __('Pages', 'workscout-freelancer'),
                    [
                        [
                            'name'  => 'workscout_freelancer_submit_task_form_page_id',
                            'std'   => '',
                            'label' => __('Submit Task Page', 'workscout-freelancer'),
                            'desc'  => __('Select the page where you have placed the [workscout_submit_task] shortcode. This lets the plugin know where the form is located.', 'workscout-freelancer'),
                            'type'  => 'page',
                        ],
                        [
                            'name'  => 'workscout_freelancer_task_dashboard_page_id',
                            'std'   => '',
                            'label' => __('Manage Task Page', 'workscout-freelancer'),
                            'desc'  => __('Select the page where you have placed the [workscout_task_dashboard] shortcode. This lets the plugin know where the dashboard is located.', 'workscout-freelancer'),
                            'type'  => 'page',
                        ],
               
                        [
                            'name'  => 'workscout_freelancer_manage_my_bids_page_id',
                            'std'   => '',
                            'label' => __('Manage My Bids Page', 'workscout-freelancer'),
                            'desc'  => __('Select the page where you have placed the [workscout_my_bids] shortcode. This lets the plugin know where the dashboard is located.', 'workscout-freelancer'),
                            'type'  => 'page',
                        ],

                        [
                            'name'  => 'workscout_freelancer_manage_my_project_page_id',
                            'std'   => '',
                            'label' => __('Freelancer Projects Page', 'workscout-freelancer'),
                            'desc'  => __('Select the page where you have placed the [workscout_freelancer_project_view] shortcode. This lets the plugin know where the project list is located.', 'workscout-freelancer'),
                            'type'  => 'page',
                        ],
                    ],
                ],


                'emails' => [
                    __('Email Notifications', 'workscout-freelancer'),
                    [
                        

                        // Freelancer New Project Notification
                        [
                            'name'      => 'workscout_freelancer_new_project_notification',
                            'type'      => 'multi_enable_expand',
                            'class'     => 'email-setting-row no-separator',
                            'enable_field' => [
                                'name'        => 'workscout_freelancer_new_project_notification_enable',
                                'cb_label'    => __('Enable freelancer notifications about new project ', 'workscout-freelancer'),
                                'desc'        => __('Notifies freelancer when a new project is created for him by employer. 
                                Available shortcodes: 
                                {project_title}, {project_url}, {project_budget}, {project_description} {employer_name}, {freelancer_name}', 'workscout-freelancer'),
                                'force_value' => null,
                            ],
                            'label'     => false,
                            'settings'  => [
                                [
                                    'name'    => 'workscout_freelancer_new_project_subject',
                                    'std'     => 'The project {project_title} has been created for you',
                                    'label'   => __('New Project Email Subject', 'workscout-freelancer'),
                                    'desc'    => __('The email subject for new project notifications to freelancers.', 'workscout-freelancer'),
                                    'type'    => 'text',
                                ],
                                [
                                    'name'    => 'workscout_freelancer_new_project_content',
                                    'std'     => "Hi {user_name},\n\nEmployer has created a new project for you:\n\nProject: {project_title}\nBudget: {project_budget}\nDescription: {project_description}\n\nView project: {project_url}\n\nBest regards,\n{site_name}",
                                    'label'   => __('New Project Email Content', 'workscout-freelancer'),
                                    'desc'    => __('The email content for new project notifications to freelancers.', 'workscout-freelancer'),
                                    'type'    => 'textarea',
                                ]
                            ],
                            'track'     => 'bool',
                        ],

                     

                        // Employer New Milestone Notification
                        [
                            'name'      => 'workscout_employer_new_milestone_notification',
                            'type'      => 'multi_enable_expand',
                            'class'     => 'email-setting-row no-separator',
                            'enable_field' => [
                                'name'        => 'workscout_employer_new_milestone_notification_enable',
                                'cb_label'    => __('Enable employer notifications about new milestone ', 'workscout-freelancer'),
                                'desc'        => __('Notifies employer when a new milestone is created. Available shortcodes: 
                                {project_title}, {project_url}, {project_budget}, {project_description} {employer_name}, {freelancer_name}, {milestone_title},{milestone_amount}', 'workscout-freelancer'),
                                'force_value' => null,
                            ],
                            'label'     => false,
                            'settings'  => [
                                [
                                    'name'    => 'workscout_employer_new_milestone_subject',
                                    'std'     => 'New Milestone Created: {project_title}',
                                    'label'   => __('New Milestone Email Subject', 'workscout-freelancer'),
                                    'desc'    => __('The email subject for new milestone notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'text',
                                ],
                                [
                                    'name'    => 'workscout_employer_new_milestone_content',
                                    'std'     => "Hi {user_name},\n\nA new milestone has been created for your project:\n\nProject: {project_title}\nMilestone: {milestone_title}\nAmount: {milestone_amount}\n\nView milestone: {project_url}\n\nBest regards,\n{site_name}",
                                    'label'   => __('New Milestone Email Content', 'workscout-freelancer'),
                                    'desc'    => __('The email content for new milestone notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'textarea',
                                ]
                            ],
                            'track'     => 'bool',
                        ],
                        // Employer Milestone Edited Notification
                        [
                            'name'      => 'workscout_employer_milestone_edited_notification',
                            'type'      => 'multi_enable_expand',
                            'class'     => 'email-setting-row no-separator',
                            'enable_field' => [
                                'name'        => 'workscout_employer_milestone_edited_notification_enable',
                                'cb_label'    => __('Enable employer milestone edited notifications', 'workscout-freelancer'),
                                'desc'        => __(
                                'Notifies employer when a milestone is editedAvailable shortcodes: 
                                {project_title}, {project_url}, {project_budget}, {project_description} {employer_name}, {freelancer_name}, {milestone_title},{milestone_amount}', 'workscout-freelancer'),
                                'force_value' => null,
                            ],
                            'label'     => false,
                            'settings'  => [
                                [
                                    'name'    => 'workscout_employer_milestone_edited_notification_subject',
                                    'std'     => 'Milestone Updated: {project_title}',
                                    'label'   => __('Milestone Edited Email Subject', 'workscout-freelancer'),
                                    'desc'    => __('The email subject for milestone edited notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'text',
                                ],
                                [
                                    'name'    => 'workscout_employer_milestone_edited_notification_content',
                                    'std'     => "Hi {user_name},\n\nA milestone has been updated:\n\nProject: {project_title}\nMilestone: {milestone_title}\nNew Amount: {milestone_amount}\n\nView changes: {project_url}\n\nBest regards,\n{site_name}",
                                    'label'   => __('Milestone Edited Email Content', 'workscout-freelancer'),
                                    'desc'    => __('The email content for milestone edited notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'textarea',
                                ]
                            ],
                            'track'     => 'bool',
                        ],

                        // Employer Milestone Approval Notification
                        [
                            'name'      => 'workscout_employer_milestone_approval_notification',
                            'type'      => 'multi_enable_expand',
                            'class'     => 'email-setting-row no-separator',
                            'enable_field' => [
                                'name'        => 'workscout_employer_milestone_approval_notification_enable',
                                'cb_label'    => __('Enable employer notifications about milestone for review ', 'workscout-freelancer'),
                                'desc'        => __('Notifies employer when a milestone needs approval', 'workscout-freelancer'),
                                'force_value' => null,
                            ],
                            'label'     => false,
                            'settings'  => [
                                [
                                    'name'    => 'workscout_employer_milestone_approval_subject',
                                    'std'     => 'Milestone Ready for Review: {project_title}',
                                    'label'   => __('Milestone Approval Email Subject', 'workscout-freelancer'),
                                    'desc'    => __('The email subject for milestone approval notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'text',
                                ],
                                [
                                    'name'    => 'workscout_employer_milestone_approval_content',
                                    'std'     => "Hi {user_name},\n\nA milestone is ready for your review:\n\nProject: {project_title}\nMilestone: {milestone_title}\nAmount: {milestone_amount}\n\nPlease review and approve: {project_url}\n\nBest regards,\n{site_name}",
                                    'label'   => __('Milestone Approval Email Content', 'workscout-freelancer'),
                                    'desc'    => __('The email content for milestone approval notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'textarea',
                                ]
                            ],
                            'track'     => 'bool',
                        ],

                       

                        // Employer Milestone Completion Notification
                        [
                            'name'      => 'workscout_freelancer_milestone_completion_notification',
                            'type'      => 'multi_enable_expand',
                            'class'     => 'email-setting-row no-separator',
                            'enable_field' => [
                                'name'        => 'workscout_freelancer_milestone_completion_notification_enable',
                                'cb_label'    => __('Enable freelancer notifications about milestone completion', 'workscout-freelancer'),
                                'desc'        => __('Notifies freelancer when a milestone is completed and approved by employer', 'workscout-freelancer'),
                                'force_value' => null,
                            ],
                            'label'     => false,
                            'settings'  => [
                                [
                                    'name'    => 'workscout_freelancer_milestone_completion_notification_subject',
                                    'std'     => 'Milestone Completed: {project_title}',
                                    'label'   => __('Milestone Completion Email Subject', 'workscout-freelancer'),
                                    'desc'    => __('The email subject for milestone completion notifications to employers.', 'workscout-freelancer'),
                                    'type'    => 'text',
                                ],
                                [
                                    'name'    => 'workscout_freelancer_milestone_completion_notification_content',
                                    'std'     => "Hi {user_name},\n\nA milestone has been completed:\n\nProject: {project_title}\nMilestone: {milestone_title}\nAmount: {milestone_amount}\n\nView details: {project_url}\n\nBest regards,\n{site_name}",
                                    'label'   => __('Milestone Completion Email Content', 'workscout-freelancer'),
                                    'desc'    => __('The email content for milestone completion notifications to employers.',
                                        'workscout-freelancer'
                                    ),
                                    'type'    => 'textarea',
                                ]
                            ],
                            'track'     => 'bool',
                        ],

                       
                        // Existing New Order Email
                        [
                            'name'      => 'workscout_freelancer_new_order_notification',
                            'type'      => 'multi_enable_expand',
                            'class'     => 'email-setting-row no-separator',
                            'enable_field' => [
                                'name'        => 'workscout_freelancer_new_order_notification_enable',
                                'cb_label'    => __('Enable new milestone payment email notifications', 'workscout-freelancer'),
                                'desc'        => __('Notifies employer about new order generated when milestone is mark as complete', 'workscout-freelancer'),
                                'force_value' => null,
                            ],
                            'label'     => false,
                            'settings'  => [
                                [
                                    'name'    => 'workscout_freelancer_new_order_subject',
                                    'std'     => 'New Order Created #{milestone_id} for {project_title}',
                                    'label'   => __('New Milestone Payment Email Subject', 'workscout-freelancer'),
                                    'desc'    => __('The email subject for Milestone Paymen notifications.', 'workscout-freelancer'),
                                    'type'    => 'text',
                                ],
                                [
                                    'name'    => 'workscout_freelancer_new_order_content',
                                    'std'     => "Hi {user_name},\n\nA new order #{order_id} has been created for your project {project_title}.\nAmount: {order_amount}\nView project: {project_url}\n\nBest regards,\n{site_name}",
                                    'label'   => __('New Milestone Payment Email Content', 'workscout-freelancer'),
                                    'desc'    => __('The email content for new order notifications.', 'workscout-freelancer'),
                                    'type'    => 'textarea',
                                ]
                            ],
                            'track'     => 'bool',
                        ],
                ],
            ],
            ]
    );




    }                       

    /**
     * Outputs the capabilities or roles input field.
     *
     * @param array    $option              Option arguments for settings input.
     * @param string[] $attributes          Attributes on the HTML element. Strings must already be escaped.
     * @param mixed    $value               Current value.
     * @param string   $ignored_placeholder We set the placeholder in the method. This is ignored.
     */
    protected function input_capabilities($option, $attributes, $value, $ignored_placeholder)
    {
        $value                 = self::capabilities_string_to_array($value);
        $option['options']     = self::get_capabilities_and_roles($value);
        $option['placeholder'] = esc_html__('Everyone (Public)', 'workscout-freelancer');

?>
        <select id="setting-<?php echo esc_attr($option['name']); ?>" class="regular-text settings-role-select" name="<?php echo esc_attr($option['name']); ?>[]" multiple="multiple" data-placeholder="<?php echo esc_attr($option['placeholder']); ?>" <?php
                                                                                                                                                                                                                                                                echo implode(' ', $attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                                                                                                                                                                                                                ?>>
            <?php
            foreach ($option['options'] as $key => $name) {
                echo '<option value="' . esc_attr($key) . '" ' . selected(in_array($key, $value, true) ? $key : null, $key, false) . '>' . esc_html($name) . '</option>';
            }
            ?>
        </select>
<?php

        if (!empty($option['desc'])) {
            echo ' <p class="description">' . wp_kses_post($option['desc']) . '</p>';
        }
    }

    /**
     * Role settings should be saved as a comma-separated list.
     */
    public function pre_process_settings_save()
    {
        $screen = get_current_screen();

        if (!$screen || 'options' !== $screen->id) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings save will handle the nonce check.
        if (!isset($_POST['option_page']) || 'workscout-freelancer' !== $_POST['option_page']) {
            return;
        }

        $capabilities_fields = [
            'resume_manager_view_name_capability',
            'resume_manager_browse_resume_capability',
            'resume_manager_view_resume_capability',
            'resume_manager_contact_resume_capability',
        ];
        foreach ($capabilities_fields as $capabilities_field) {
            // phpcs:disable WordPress.Security.NonceVerification.Missing -- Settings save will handle the nonce check.
            if (isset($_POST[$capabilities_field])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by `WP_Resume_Manager_Settings::capabilities_array_to_string()`
                $input_capabilities_field_value = wp_unslash($_POST[$capabilities_field]);
                if (is_array($input_capabilities_field_value)) {
                    $_POST[$capabilities_field] = self::capabilities_array_to_string($input_capabilities_field_value);
                }
            }
            // phpcs:enable WordPress.Security.NonceVerification.Missing
        }
    }

    /**
     * Convert list of capabilities and roles into array of values.
     *
     * @param string $value Comma separated list of capabilities and roles.
     * @return array
     */
    private static function capabilities_string_to_array($value)
    {
        return array_filter(
            array_map(
                function ($value) {
                    return trim(sanitize_text_field($value));
                },
                explode(',', $value)
            )
        );
    }

    /**
     * Convert array of capabilities and roles into a comma separated list.
     *
     * @param array $value Array of capabilities and roles.
     * @return string
     */
    private static function capabilities_array_to_string($value)
    {
        if (!is_array($value)) {
            return '';
        }

        $caps = array_filter(array_map('sanitize_text_field', $value));

        return implode(',', $caps);
    }

    /**
     * Get the list of roles and capabilities to use in select dropdown.
     *
     * @param array $caps Selected capabilities to ensure they show up in the list.
     * @return array
     */
    private static function get_capabilities_and_roles($caps = [])
    {
        $capabilities_and_roles = [];
        $roles                  = get_editable_roles();

        foreach ($roles as $key => $role) {
            $capabilities_and_roles[$key] = $role['name'];
        }

        // Go through custom user selected capabilities and add them to the list.
        foreach ($caps as $value) {
            if (isset($capabilities_and_roles[$value])) {
                continue;
            }
            $capabilities_and_roles[$value] = $value;
        }

        return $capabilities_and_roles;
    }
}
