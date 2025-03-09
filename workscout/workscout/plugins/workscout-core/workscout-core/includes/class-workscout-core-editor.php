<?php
class Workscout_Forms_And_Fields_Editor
{


    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    public $users;
    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * Initiate our hooks
     * @since 0.1.0
     */
    public function __construct($file = '', $version = '1.0.0')
    {
        $this->_version = $version;
        add_action('admin_menu', array($this, 'add_options_page')); //create tab pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_and_styles'));

        // Load plugin environment variables
        $this->file = __FILE__;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        // Load plugin environment variables



        add_action('admin_init', array($this, 'workscout_process_settings_export'));
        add_action('admin_init', array($this, 'workscout_process_settings_import'));
    }


    public function enqueue_scripts_and_styles($hook)
    {


        if (!in_array($hook, array('workscout-editor_page_workscout-submit-builder', 'workscout-editor_page_workscout-forms-builder', 'workscout-editor_page_workscout-fields-builder', 'workscout-editor_page_workscout-reviews-criteria'))) {
            return;
        }

        wp_enqueue_script('workscout-fafe-script', WORKSCOUT_PLUGIN_DIR . 'assets/js/editor.js', array('jquery', 'jquery-ui-droppable', 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-dialog', 'jquery-ui-resizable'));

        wp_register_style('workscout-fafe-styles', WORKSCOUT_PLUGIN_DIR . 'assets/css/editor.css', array(), $this->_version);

        wp_enqueue_style('workscout-fafe-styles');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }

    /**
     * Add menu options page
     * @since 0.1.0
     */
    public function add_options_page()
    {

        //add_menu_page('Workscout Forms', 'Workscout Editor', 'manage_options', 'workscout_settings',array( $this, 'output' ));


        // Add new submenu for field visibility
        add_submenu_page('workscout_settings', 'Form Fields Visibility', 'Form Fields Visibility', 'manage_options', 'workscout_fields_visibility', array($this, 'render_visibility_page'));
    }

    /**
     * Get all available form fields for a specific form type
     * 
     * @param string $form_type Type of form (job, resume, company, task)
     * @return array Array of field data
     */
    private function get_form_fields($form_type)
    {
        switch ($form_type) {
            case 'job':
                return $this->get_job_form_fields();
            case 'resume':
                return $this->get_resume_form_fields();
            case 'company':
                return $this->get_company_form_fields();
            case 'task':
                return $this->get_task_form_fields();
            default:
                return array();
        }
    }

    /**
     * Get job form fields
     */
    /**
     * Get resume form fields
     */
    private function get_resume_form_fields()
    {
        $fields = array(
            'candidate_name' => array(
                'label' => __('Full Name', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'candidate_email' => array(
                'label' => __('Email', 'workscout'),
                'type' => 'email',
                'required' => true
            ),
            'candidate_title' => array(
                'label' => __('Professional Title', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'candidate_location' => array(
                'label' => __('Location', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'candidate_photo' => array(
                'label' => __('Photo', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
            'candidate_video' => array(
                'label' => __('Video', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'resume_category' => array(
                'label' => __('Category', 'workscout'),
                'type' => 'select',
                'required' => false
            ),
            'resume_content' => array(
                'label' => __('Resume Content', 'workscout'),
                'type' => 'wp-editor',
                'required' => true
            ),
            'resume_skills' => array(
                'label' => __('Skills', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'links' => array(
                'label' => __('Social Sites', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'candidate_education' => array(
                'label' => __('Education', 'workscout'),
                'type' => 'repeater',
                'required' => false
            ),
            'candidate_experience' => array(
                'label' => __('Experience', 'workscout'),
                'type' => 'repeater',
                'required' => false
            ),
            'resume_file' => array(
                'label' => __('Resume File', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
            'resume_region' => array(
                'label' => __('Region', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'rate_min' => array(
                'label' => __('Minimum Rate', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'competencies' => array(
                'label' => __('Competencies', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'country' => array(
                'label' => __('Country', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'gallery' => array(
                'label' => __('Gallery', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
            'header_image' => array(
                'label' => __('Header Image', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
        );

        return apply_filters('workscout_resume_form_fields', $fields);
    }

    /**
     * Get company form fields
     */
    private function get_company_form_fields()
    {
        $fields = array(
            'company_name' => array(
                'label' => __('Company Name', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'company_website' => array(
                'label' => __('Website', 'workscout'),
                'type' => 'url',
                'required' => false
            ),
            'company_tagline' => array(
                'label' => __('Tagline', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_video' => array(
                'label' => __('Video', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_twitter' => array(
                'label' => __('Twitter Username', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_logo' => array(
                'label' => __('Logo', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
            'company_description' => array(
                'label' => __('Description', 'workscout'),
                'type' => 'wp-editor',
                'required' => true
            ),
            'company_location' => array(
                'label' => __('Location', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_email' => array(
                'label' => __('Email', 'workscout'),
                'type' => 'email',
                'required' => true
            ),
            'company_since' => array(
                'label' => __('Since', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_phone' => array(
                'label' => __('Phone', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_twitter' => array(
                'label' => __('Twitter', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_facebook' => array(
                'label' => __('Facebook', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_category' => array(
                'label' => __('Category', 'workscout'),
                'type' => 'select',
                'required' => false
            ),
            'company_strength' => array(
                'label' => __('Strength', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_average_salary' => array(
                'label' => __('Average Salary', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_revenue' => array(
                'label' => __('Revenue', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_excerpt' => array(
                'label' => __('Excerpt', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'company_content' => array(
                'label' => __('Content', 'workscout'),
                'type' => 'wp-editor',
                'required' => false
            ),
            'header_image' => array(
                'label' => __('Header Image', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
            'geolocation_lat' => array(
                'label' => __('Latitude', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'geolocation_long' => array(
                'label' => __('Longitude', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'country' => array(
                'label' => __('Country', 'workscout'),
                'type' => 'text',
                'required' => false
            ),

        );

        return apply_filters('workscout_company_form_fields', $fields);
    }

    /**
     * Get task form fields
     */
    private function get_task_form_fields()
    {
        $fields = array(
            'task_title' => array(
                'label' => __('Task Title', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'task_category' => array(
                'label' => __('Category', 'workscout'),
                'type' => 'select',
                'required' => false
            ),
            'task_location' => array(
                'label' => __('Location', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'remote_position' => array(
                'label' => __('Remote Position', 'workscout'),
                'type' => 'checkbox',
                'required' => false
            ),
            'task_type' => array(
                'label' => __('Billing Type', 'workscout'),
                'type' => 'select',
                'required' => true
            ),
            'task_description' => array(
                'label' => __('Description', 'workscout'),
                'type' => 'wp-editor',
                'required' => true
            ),

            'budget_min' => array(
                'label' => __('Minimum Budget', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'budget_max' => array(
                'label' => __('Maximum Budget', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'hourly_min' => array(
                'label' => __('Minimum Hourly Rate', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'hourly_max' => array(
                'label' => __('Maximum Hourly Rate', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'task_deadline' => array(
                'label' => __('Deadline', 'workscout'),
                'type' => 'date',
                'required' => false
            ),
            
         
            'task_skill' => array(
                'label' => __('Required Skills', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'task_file' => array(
                'label' => __('Attachments', 'workscout'),
                'type' => 'file',
                'required' => false
            )
        );

        return apply_filters('workscout_task_form_fields', $fields);
    }

    /**
     * Get job form fields
     */
    private function get_job_form_fields()
    {
        $fields = array(
            'job_title' => array(
                'label' => __('Job Title', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'job_location' => array(
                'label' => __('Location', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'job_type' => array(
                'label' => __('Job Type', 'workscout'),
                'type' => 'select',
                'required' => true
            ),
            'job_category' => array(
                'label' => __('Job Category', 'workscout'),
                'type' => 'select',
                'required' => false
            ),
            'job_description' => array(
                'label' => __('Description', 'workscout'),
                'type' => 'wp-editor',
                'required' => true
            ),
            'remote_position' => array(
                'label' => __('Remote Position', 'workscout'),
                'type' => 'checkbox',
                'required' => false
            ),
            'job_salary' => array(
                'label' => __('Salary', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'job_salary_currency' => array(
                'label' => __('Salary Currency', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'job_salary_unit' => array(
                'label' => __('Salary Unit', 'workscout'),
                'type' => 'select',
                'required' => false
            ),
            'rate_min' => array(
                'label' => __('Minimum Rate', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'rate_max' => array(
                'label' => __('Maximum Rate', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'salary_min' => array(
                'label' => __('Minimum Salary', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'salary_max' => array(
                'label' => __('Maximum Salary', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'application' => array(
                'label' => __('Application Email/URL', 'workscout'),
                'type' => 'text',
                'required' => true
            ),
            'apply_link' => array(
                'label' => __('Application Link', 'workscout'),
                'type' => 'text',
                'required' => false
            ),
            'header_image' => array(
                'label' => __('Header Image', 'workscout'),
                'type' => 'file',
                'required' => false
            ),
            
            // 'job_tags' => array(
            //     'label' => __('Tags', 'workscout'),
            //     'type' => 'text',
            //     'required' => false
            // ),
            // 'company_name' => array(
            //     'label' => __('Company Name', 'workscout'),
            //     'type' => 'text',
            //     'required' => false
            // ),
            // 'company_website' => array(
            //     'label' => __('Website', 'workscout'),
            //     'type' => 'url',
            //     'required' => false
            // ),
            // 'company_tagline' => array(
            //     'label' => __('Tagline', 'workscout'),
            //     'type' => 'text',
            //     'required' => false
            // ),
            // 'company_video' => array(
            //     'label' => __('Video', 'workscout'),
            //     'type' => 'text',
            //     'required' => false
            // ),
            // 'company_twitter' => array(
            //     'label' => __('Twitter Username', 'workscout'),
            //     'type' => 'text',
            //     'required' => false
            // ),
            // 'company_logo' => array(
            //     'label' => __('Logo', 'workscout'),
            //     'type' => 'file',
            //     'required' => false
            // )
        );

        return apply_filters('workscout_job_form_fields', $fields);
    }

    /**
     * Render the field visibility settings page
     */
    public function render_visibility_page()
    {
        // Get saved settings
        $hidden_fields = get_option('workscout_hidden_fields', array());

        if (isset($_POST['workscout_save_visibility'])) {
            check_admin_referer('workscout_visibility_nonce');

            $new_hidden_fields = array();
            foreach (['job', 'resume', 'company', 'task'] as $form_type) {
                $get_fields_method = "get_{$form_type}_form_fields";
                $all_fields = array_keys($this->$get_fields_method());
                $checked_fields = isset($_POST['hidden_fields'][$form_type]) ? array_map('sanitize_text_field', $_POST['hidden_fields'][$form_type]) : array();
                $new_hidden_fields[$form_type] = array_values(array_diff($all_fields, $checked_fields));
            }

            update_option('workscout_hidden_fields', $new_hidden_fields);
            $hidden_fields = $new_hidden_fields;
            echo '<div class="updated"><p>' . __('Settings saved successfully!', 'workscout') . '</p></div>';
        }

?>

        <h2><?php _e('Form Fields Visibility Settings', 'workscout'); ?></h2>
        <div class="workscout-editor-wrap">

            <div class="nav-tab-container">
                <h2 class="nav-tab-wrapper form-builder">
                    <a href="#job-fields" class="nav-tab nav-tab-active"><?php _e('Job Fields', 'workscout'); ?></a>
                    <a href="#resume-fields" class="nav-tab"><?php _e('Resume Fields', 'workscout'); ?></a>
                    <a href="#company-fields" class="nav-tab"><?php _e('Company Fields', 'workscout'); ?></a>
                    <a href="#task-fields" class="nav-tab"><?php _e('Task Fields', 'workscout'); ?></a>
                </h2>
            </div>

            <div class="workscout-forms-builder wrap">
                <div class="wrap workscout-form-editor  workscout-fields-visibility-form-builder">
                    <form method="post" id="mainform" action="">
                        <?php wp_nonce_field('workscout_visibility_nonce'); ?>
                        <!-- 
                        <div class="nav-tab-wrapper">
                            <a href="#job-fields" class="nav-tab nav-tab-active"><?php _e('Job Fields', 'workscout'); ?></a>
                            <a href="#resume-fields" class="nav-tab"><?php _e('Resume Fields', 'workscout'); ?></a>
                            <a href="#company-fields" class="nav-tab"><?php _e('Company Fields', 'workscout'); ?></a>
                            <a href="#task-fields" class="nav-tab"><?php _e('Task Fields', 'workscout'); ?></a>
                        </div> -->
                        <span class="description"><span class="noticebox">Uncheck fields you want to hide from job/resume/company/task forms and click Save</span></span>
                        <?php foreach (['job' => 'Job', 'resume' => 'Resume', 'company' => 'Company', 'task' => 'Task'] as $form_type => $form_label) : ?>
                            <div id="<?php echo $form_type; ?>-fields" class="tab-content" <?php echo $form_type !== 'job' ? 'style="display:none;"' : ''; ?>>
                                <h3><?php printf(__('%s Submission Form Fields', 'workscout'), $form_label); ?></h3>
                                <?php
                                $get_fields_method = "get_{$form_type}_form_fields";
                                $fields = $this->$get_fields_method();
                                foreach ($fields as $field_key => $field) :
                                    $is_hidden = isset($hidden_fields[$form_type]) && in_array($field_key, $hidden_fields[$form_type]);
                                ?>
                                    <label>
                                        <input type="checkbox"
                                            name="hidden_fields[<?php echo $form_type; ?>][]"
                                            value="<?php echo esc_attr($field_key); ?>"
                                            <?php checked(!$is_hidden, true); ?>
                                            <?php disabled($field['required'], true); ?>>
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if ($field['required']) : ?>
                                            <span class="required-badge"><?php _e('Required', 'workscout'); ?></span>
                                        <?php endif; ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <p class="submit">
                            <input type="submit" name="workscout_save_visibility" class="button-primary" value="<?php _e('Save Changes', 'workscout'); ?>">
                        </p>
                    </form>
                </div>
            </div>

        </div>


        <style>
            /* .nav-tab-wrapper {
                margin-bottom: 20px;
            }

            .tab-content {
                padding: 20px;
                background: #fff;
                border: 1px solid #ccc;
            } */

            .required-badge {
                background: #dc3232;
                color: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 11px;
                margin-left: 5px;
            }

            label {
                display: block;
                margin-bottom: 10px;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Tab functionality
                $('.nav-tab').click(function(e) {
                    e.preventDefault();
                    var target = $(this).attr('href');

                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');

                    $('.tab-content').hide();
                    $(target).show();
                });
            });
        </script>
    <?php
    }

    public function output()
    {
    ?>
<?php
    }


    /**
     * Process a settings export that generates a .json file of the shop settings
     */
    function workscout_process_settings_export()
    {

        if (empty($_POST['workscout_action']) || 'export_settings' != $_POST['workscout_action'])
            return;

        if (! wp_verify_nonce($_POST['workscout_export_nonce'], 'workscout_export_nonce'))
            return;

        if (! current_user_can('manage_options'))
            return;

        $settings = array();
        $settings['property_types']         = get_option('workscout_property_types_fields');
        $settings['property_rental']        = get_option('workscout_rental_periods_fields');
        $settings['property_offer_types']   = get_option('workscout_offer_types_fields');

        $settings['submit']                 = get_option('workscout_submit_form_fields');

        $settings['price_tab']              = get_option('workscout_price_tab_fields');
        $settings['main_details_tab']       = get_option('workscout_main_details_tab_fields');
        $settings['details_tab']            = get_option('workscout_details_tab_fields');
        $settings['location_tab']           = get_option('workscout_locations_tab_fields');

        $settings['sidebar_search']         = get_option('workscout_sidebar_search_form_fields');
        $settings['full_width_search']      = get_option('workscout_full_width_search_form_fields');
        $settings['half_map_search']        = get_option('workscout_search_on_half_map_form_fields');
        $settings['home_page_search']       = get_option('workscout_search_on_home_page_form_fields');
        $settings['home_page_alt_search']   = get_option('workscout_search_on_home_page_alt_form_fields');

        ignore_user_abort(true);

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=workscout-settings-export-' . date('m-d-Y') . '.json');
        header("Expires: 0");

        echo json_encode($settings);
        exit;
    }

    /**
     * Process a settings import from a json file
     */
    function workscout_process_settings_import()
    {

        if (empty($_POST['workscout_action']) || 'import_settings' != $_POST['workscout_action'])
            return;

        if (! wp_verify_nonce($_POST['workscout_import_nonce'], 'workscout_import_nonce'))
            return;

        if (! current_user_can('manage_options'))
            return;

        $extension = end(explode('.', $_FILES['import_file']['name']));

        if ($extension != 'json') {
            wp_die(__('Please upload a valid .json file'));
        }

        $import_file = $_FILES['import_file']['tmp_name'];

        if (empty($import_file)) {
            wp_die(__('Please upload a file to import'));
        }

        // Retrieve the settings from the file and convert the json object to an array.
        $settings = json_decode(file_get_contents($import_file), true);

        update_option('workscout_property_types_fields', $settings['property_types']);
        update_option('workscout_rental_periods_fields', $settings['property_rental']);
        update_option('workscout_offer_types_fields', $settings['property_offer_types']);

        update_option('workscout_submit_form_fields', $settings['submit']);

        update_option('workscout_price_tab_fields', $settings['price_tab']);
        update_option('workscout_main_details_tab_fields', $settings['main_details_tab']);
        update_option('workscout_details_tab_fields', $settings['details_tab']);
        update_option('workscout_locations_tab_fields', $settings['location_tab']);

        update_option('workscout_sidebar_search_form_fields', $settings['sidebar_search']);
        update_option('workscout_full_width_search_form_fields', $settings['full_width_search']);
        update_option('workscout_search_on_half_map_form_fields', $settings['half_map_search']);
        update_option('workscout_search_on_home_page_form_fields', $settings['home_page_search']);
        update_option('workscout_search_on_home_page_alt_form_fields', $settings['home_page_alt_search']);


        wp_safe_redirect(admin_url('admin.php?page=workscout-fields-and-form&import=success'));
        exit;
    }
}
