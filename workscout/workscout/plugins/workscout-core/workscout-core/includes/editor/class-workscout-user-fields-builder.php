<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Workscout_User_Fields_Builder
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
     * Stores static instance of class.
     *
     * @access protected
     * @var Workscout_Submit The single instance of the class
     */
    protected static $_instance = null;

    public $_version;

    /**
     * Returns static instance of class.
     *
     * @return self
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Initiate our hooks
     * @since 0.1.0
     */
    public function __construct($file = '', $version = '1.8.0')
    {
        $this->_version = $version;
        // Load plugin environment variables
        $this->file = __FILE__;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        add_action('admin_menu', array($this, 'add_options_page')); //create tab pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_and_styles'));
        add_filter('workscout_user_employer_fields', array($this, 'add_workscout_employer_fields'));
        add_filter('workscout_user_candidate_fields', array($this, 'add_workscout_candidate_fields'));
    }


    public function enqueue_scripts_and_styles($hook)
    {
        

        if (!in_array($hook, array('workscout-core_page_workscout_fields_visibility','workscout-core_page_workscout-user-fields-builder', 'workscout-core_page_workscout-user-fields-registration'))) {
            return;
        }

        wp_enqueue_script('workscout-fafe-script', WORKSCOUT_CORE_ASSETS_URL . 'js/editor.js', array('jquery', 'jquery-ui-droppable', 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-dialog', 'jquery-ui-resizable'));
        wp_register_style('workscout-fafe-styles', WORKSCOUT_CORE_ASSETS_URL . 'css/editor.css', array(), $this->_version);

        wp_enqueue_style('workscout-fafe-styles');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }


    function add_workscout_employer_fields($fields)
    {
        $new_fields =  get_option('workscout_employer_fields');
        if (!empty($new_fields)) {
            $fields = $new_fields;
        }
        return $fields;
    }

    function add_workscout_candidate_fields($fields)
    {
        $new_fields =  get_option('workscout_candidate_fields');
        if (!empty($new_fields)) {
            $fields = $new_fields;
        }
        return $fields;
    }



    /**
     * Add menu options page
     * @since 0.1.0
     */
    public function add_options_page()
    {
        add_submenu_page('workscout_settings', 'User Fields', 'User Fields', 'manage_options', 'workscout-user-fields-builder', array($this, 'output'));
    }



    public function output()
    {

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'employer';

        $tabs = array(
            'employer'   => __('Employer  Fields', 'workscout-fafe'),
            'candidate'  => __('Candidate/Freelancer  Fields', 'workscout-fafe'),


        );

        if (!empty($_GET['reset-fields']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reset')) {
            delete_option("workscout_{$tab}_fields");
            echo '<div class="updated"><p>' . __('The fields were successfully reset.', 'workscout') . '</p></div>';
        }

        if (!empty($_POST)) { /* add nonce tu*/

            echo $this->form_editor_save($tab);
        }


        $field_types = apply_filters(
            'workscout_form_field_types',
            array(
                'text'           => __('Text', 'workscout-editor'),
                'wp-editor'       => __('Textarea', 'workscout-editor'),
                'radio'         => __('Radio', 'workscout-editor'),
                'select'         => __('Select', 'workscout-editor'),
                'select_multiple'   => __('Multi Select', 'workscout-editor'),
                'checkbox'          => __('Checkbox', 'workscout-editor'),
                'multicheck_split'        => __('Multi Checkbox', 'workscout-editor'),
                'file'              => __('File upload', 'workscout-editor'),
                'header'              => __('Header', 'workscout-editor'),

            )
        );


        switch ($tab) {
            case 'employer':
                $default_fields = WorkScout_Core_Fields::meta_boxes_user_employer();

                break;
            case 'candidate':
                $default_fields = WorkScout_Core_Fields::meta_boxes_user_candidate();
                break;


            default:
                $default_fields = WorkScout_Core_Fields::meta_boxes_user_employer();
                break;
        }


        $options = get_option("workscout_{$tab}_fields");

        $fields = (!empty($options)) ? get_option("workscout_{$tab}_fields") : $default_fields;

        if (isset($fields['fields'])) {
            $fields = $fields['fields'];
        }


?>

        <h2>User Fields Editor</h2>
        <div class="workscout-editor-wrap">
            <div class="nav-tab-container">

                <h2 class="nav-tab-wrapper form-builder">
                    <?php
                    foreach ($tabs as $key => $value) {

                        $active = ($key == $tab) ? 'nav-tab-active' : '';
                        echo '<a class="nav-tab ' . $active . '" href="' . admin_url('admin.php?page=workscout-user-fields-builder&tab=' . esc_attr($key)) . '">' . esc_html($value) . '</a>';
                    }
                    ?>
                </h2>
            </div>
            <div class="wrap workscout-form-editor workscout-forms-builder">
                <form method="post" id="mainform" action="admin.php?page=workscout-user-fields-builder&amp;tab=<?php echo esc_attr($tab); ?>">
                    <h3 class="workscout-editor-form-header">
                        <?php
                        foreach ($tabs as $key => $value) {
                            if ($active = ($key == $tab)) {
                                echo esc_html__($value);
                            }
                        } ?>
                        <input name="Submit" type="submit" class="button-primary" value="Save Settings">
                    </h3>
                    <div class="workscout-forms-builder-top">
                        <div class="form-editor-container" id="workscout-fafe-fields-editor" data-clone="<?php
                                                                                                            ob_start();
                                                                                                            $index = -2;
                                                                                                            $field_key = 'clone';
                                                                                                            $field = array(
                                                                                                                'name' => 'clone',
                                                                                                                'id' => '_clone',
                                                                                                                'type' => 'text',
                                                                                                                'invert' => '',
                                                                                                                'desc' => '',
                                                                                                                'options_source' => '',
                                                                                                                'options_cb' => '',
                                                                                                                'options' => array()
                                                                                                            ); ?>
                <div class=" form_item" data-priority="<?php echo  $index; ?>">
                            <span class="handle dashicons dashicons-editor-justify"></span>
                            <div class="element_title"><?php echo esc_attr($field['name']);  ?> <span>(<?php echo $field['type']; ?>)</span> </div>
                            <?php include(plugin_dir_path(__DIR__) . 'views/form-field-edit.php');
                            ?>
                            <div class="remove_item"> <span class="dashicons dashicons-remove"></span> </div>
                        </div>
                        <?php echo esc_attr(ob_get_clean()); ?>">

                        <?php
                        $index = 0;

                        $fields = maybe_unserialize($fields);

                        foreach ($fields as $field_key => $field) {

                            $index++;

                            if (is_array($field)) { ?>
                                <div class="form_item">
                                    <span class="handle dashicons dashicons-editor-justify"></span>
                                    <div class="element_title"><?php echo esc_attr((isset($field['name'])) ? $field['name'] : $field['label']); ?>
                                        <div class="element_title_edit"><span class="dashicons dashicons-edit"></span> Edit</div>
                                    </div>
                                    <?php include(plugin_dir_path(__DIR__) . 'views/form-field-edit.php'); ?>
                                    <div class="remove_item"> <span class="dashicons dashicons-remove"></span> </div>
                                </div>
                        <?php }
                        }  ?>
                        <div class="droppable-helper"></div>
                    </div>
                    <a class="add_new_item button-primary add-field" href="#"><?php _e('Add field', 'workscout'); ?></a>
            </div>

            <?php wp_nonce_field('save-' . $tab); ?>

            <div class="workscout-forms-builder-bottom">

                <input type="submit" class="save-fields button-primary" value="<?php _e('Save Changes', 'workscout'); ?>" />
                <p>You can use those fields now in <a href="admin.php?page=workscout-user-fields-registration&tab=<?php echo $tab; ?>">Registration Form editor</a><p>
                <a href=" <?php echo wp_nonce_url(add_query_arg('reset-fields', 1), 'reset'); ?>" class="reset button-secondary"><?php _e('Reset to defaults', 'workscout'); ?></a>
            </div>
            </form>
        </div>
        </div>

        <?php wp_nonce_field('save-fields'); ?>
<?php
    }



    private function form_editor_save($tab)
    {
        $field_name = !empty($_POST['name']) ? array_map('sanitize_text_field', $_POST['name']) : array();
        $field_id = !empty($_POST['id']) ? array_map('sanitize_text_field', $_POST['id']) : array();
        $field_type = !empty($_POST['type']) ? array_map('sanitize_text_field', $_POST['type']) : array();
        $field_desc = !empty($_POST['desc']) ? array_map('sanitize_text_field', $_POST['desc']) : array();
        $field_options_cb = !empty($_POST['options_cb']) ? array_map('sanitize_text_field', $_POST['options_cb']) : array();
        $field_options_source = !empty($_POST['options_source']) ? array_map('sanitize_text_field', $_POST['options_source']) : array();
        $field_options = !empty($_POST['options']) ? $this->sanitize_array($_POST['options']) : array();
        $new_fields = array();

        foreach ($field_name as $key => $field) {
            if (empty($field_name[$key])) {
                continue;
            }

            $name = sanitize_title($field_id[$key]);
            $options = array();

            if (!empty($field_options[$key]) && is_array($field_options[$key])) {
                foreach ($field_options[$key] as $op_value) {
                    if (isset($op_value['name']) && isset($op_value['value'])) {
                        $options[stripslashes($op_value['name'])] = stripslashes($op_value['value']);
                    }
                }
            }

            $new_field = array(
                'name' => stripslashes($field_name[$key]),
                'label' => stripslashes($field_name[$key]),
                'id' => $field_id[$key],
                'type' => $field_type[$key],
                'desc' => isset($field_desc[$key]) ? stripslashes($field_desc[$key]) : '',
                'options_source' => isset($field_options_source[$key]) ? $field_options_source[$key] : '',
                'options_cb' => isset($field_options_cb[$key]) ? $field_options_cb[$key] : '',
                'options' => !empty($field_options_cb[$key]) ? array() : $options
            );

            $new_fields[$name] = $new_field;
        }

        $result = update_option("workscout_{$tab}_fields", $new_fields);

        if (true === $result) {
            return '<div class="updated"><p>' . __('The fields were successfully saved.', 'workscout') . '</p></div>';
        }

        return '<div class="error"><p>' . __('There was an error saving the fields.', 'workscout') . '</p></div>';
    }

    /**
     * Sanitize a 2d array
     * @param  array $array
     * @return array
     */
    private function sanitize_array($input)
    {
        if (is_array($input)) {
            foreach ($input as $k => $v) {
                $input[$k] = $this->sanitize_array($v);
            }
            return $input;
        } else {
            return sanitize_text_field($input);
        }
    }
}
