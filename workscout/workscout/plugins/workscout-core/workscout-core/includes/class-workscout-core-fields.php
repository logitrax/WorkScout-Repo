<?php

if (!defined('ABSPATH')) exit;

class WorkScout_Core_Fields
{

    /**
     * The single instance of WordPress_Plugin_Template_Settings.
     * @var     object
     * @access  private
     * @since   1.0.0
     */
    private static $_instance = null;

    public function __construct()
    {

        add_filter('submit_job_form_fields', array($this, 'workscout_frontend_add_extra_fields'));
        add_filter('submit_company_form_fields', array($this, 'workscout_frontend_add_company_fields'));
        add_filter('company_manager_company_fields', array($this, 'workscout_backend_add_company_fields'));
        add_filter('job_manager_job_listing_data_fields', array($this, 'workscout_admin_add_extra_fields')); /* Add rate rate | hours | salary fields for job listing*/



        if (get_option('workscout_enable_resume_filter_rate')) :
            add_filter('submit_resume_form_fields', array($this, 'workscout_frontend_add_resume_rate_field'));
            add_filter('resume_manager_resume_fields', array($this, 'workscout_admin_add_resume_rate_field'));
        endif;

        add_filter('submit_resume_form_save_resume_data', array($this, 'workscout_custom_submit_resume_form_save_resume_data'));



        add_action('job_listing_category_add_form_fields', array($this, 'wpjm_category_add_new_meta_field'), 10, 2);
        add_action('task_category_add_form_fields', array($this, 'wpjm_category_add_new_meta_field'), 10, 2);
        add_action('resume_category_add_form_fields', array($this, 'wpjm_category_add_new_meta_field'), 10, 2);

        add_action('job_listing_category_edit_form_fields', array($this, 'wpjm_category_edit_meta_field'), 10, 2);
        add_action('task_category_edit_form_fields', array($this, 'wpjm_category_edit_meta_field'), 10, 2);
        add_action('resume_category_edit_form_fields', array($this, 'wpjm_category_edit_meta_field'), 10, 2);

        add_action('edited_job_listing_category', array($this, 'workscout_save_taxonomy_custom_meta'), 10, 2);
        add_action('edited_task_category', array($this, 'workscout_save_taxonomy_custom_meta'), 10, 2);
        add_action('create_job_listing_category', array($this, 'workscout_save_taxonomy_custom_meta'), 10, 2);
        add_action('create_task_category', array($this, 'workscout_save_taxonomy_custom_meta'), 10, 2);
        add_action('edited_resume_category', array($this, 'workscout_save_taxonomy_custom_meta'), 10, 2);
        add_action('create_resume_category', array($this, 'workscout_save_taxonomy_custom_meta'), 10, 2);


        add_filter('wpjm_get_job_listing_structured_data',  array($this, 'workscout_add_salary_to_job_structured_data'), 10, 2);
    }


    // Make comments open by default for new resumes

    function workscout_custom_submit_resume_form_save_resume_data($data)
    {
        $data['comment_status'] = 'open';
        return $data;
    }

    public function workscout_backend_add_company_fields($fields)
    {
        $fields['_header_image'] = array(
            'label'       => __('Header Image', 'workscout_core'),
            'type'        => 'file',
            'required'    => false,

            'priority'    => 90,
            'ajax'        => true,
            'multiple'    => false,
            'allowed_mime_types' => array(
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'png'  => 'image/png'
            )
        );
        $fields['_geolocation_lat'] = array(
            'label'       => esc_html__('Latitude', 'workscout_core'),
            'type'        => 'text',
            'required'    => false,

            'priority'    => 16
        );
        $fields['_geolocation_long'] = array(
            'label'       => esc_html__('Longitude', 'workscout_core'),
            'type'        => 'text',
            'required'    => false,

            'priority'    => 16
        );
        $fields['_featured'] = array(
            'label'       => esc_html__('Featured', 'workscout_core'),
            'type'        => 'checkbox',
            'required'    => false,

            'priority'    => 16
        );

        return $fields;
    }

    function workscout_frontend_add_company_fields($fields)
    {
        $fields['company_fields']['header_image'] = array(
            'label'       => __('Header Image', 'workscout_core'),
            'type'        => 'file',
            'required'    => false,

            'priority'    => 90,
            'ajax'        => true,
            'multiple'    => false,
            'allowed_mime_types' => array(
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'png'  => 'image/png'
            )
        );
        $fields['company_fields']['geolocation_lat'] = array(
            'label'       => esc_html__('Latitude', 'workscout_core'),
            'type'        => 'text',
            'required'    => false,

            'priority'    => 16
        );
        $fields['company_fields']['geolocation_long'] = array(
            'label'       => esc_html__('Longitude', 'workscout_core'),
            'type'        => 'text',
            'required'    => false,

            'priority'    => 16
        );

        return $fields;
    }

    function workscout_frontend_add_extra_fields($fields)
    {
        $currency = get_workscout_currency_symbol();

        if (get_option('workscout_enable_filter_rate')) :
            $fields['job']['rate_min'] = array(
                'label'       => esc_html__('Minimum rate/h', 'workscout_core') . ' (' . $currency . ')',
                'type'        => 'text',
                'required'    => false,
                'placeholder' => esc_html__('e.g. 20', 'workscout_core'),
                'priority'    => 7
            );
            $fields['job']['rate_max'] = array(
                'label'       => esc_html__('Maximum rate/h', 'workscout_core') . ' (' . $currency . ')',
                'type'        => 'text',
                'required'    => false,
                'placeholder' => esc_html__('e.g. 50', 'workscout_core'),
                'priority'    => 7
            );
        endif;

        if (get_option('workscout_enable_filter_salary')) :
            $fields['job']['salary_min'] = array(
                'label'       => esc_html__('Minimum Salary', 'workscout_core') . ' (' . $currency . ')',
                'type'        => 'number',
                'required'    => false,
                'placeholder' => esc_html__('e.g. 20000', 'workscout_core'),
                'priority'    => 12
            );
            $fields['job']['salary_max'] = array(
                'label'       => esc_html__('Maximum Salary', 'workscout_core') . ' (' . $currency . ')',
                'type'        => 'text',
                'required'    => false,
                'placeholder' => esc_html__('e.g. 50000', 'workscout_core'),
                'priority'    => 12
            );
        endif;

        if (get_option('workscout_enable_hour_field')) :
            $fields['job']['hours'] = array(
                'label'       => esc_html__('Hours per week', 'workscout_core'),
                'type'        => 'text',
                'required'    => false,
                'placeholder' => esc_html__('e.g. 40', 'workscout_core'),
                'priority'    => 11
            );
        endif;
        // TODO make it optional
        $fields['job']['apply_link'] = array(
            'label'       => esc_html__('External "Apply for Job" link', 'workscout_core'),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => esc_html__('http://', 'workscout_core'),
            'priority'    => 12
        );
        $fields['job']['header_image'] = array(
            'label'       => __('Header Image', 'workscout_core'),
            'type'        => 'file',
            'required'    => false,

            'priority'    => 13,
            'ajax'        => true,
            'multiple'    => false,
            'allowed_mime_types' => array(
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'png'  => 'image/png'
            )
        );
        $fields['company']['company_facebook'] = array(
            'label'       => esc_html__('Facebook URL', 'workscout_core'),
            'type'        => 'text',
            'required'    => false,
            'placeholder' => esc_html__('http://', 'workscout_core'),
            'priority'    => 5
        );

        return $fields;
    }


    function workscout_admin_add_extra_fields($fields)
    {
        $currency = get_workscout_currency_symbol();
        if (get_option('workscout_enable_hour_field')) :
            $fields['_hours'] = array(
                'label'       => esc_html__('Hours per week', 'workscout_core'),
                'type'        => 'text',
                'placeholder' => 'e.g. 40',
                'description' => '',
                'show_in_rest' => true,
            );
        endif;
        if (get_option('workscout_enable_filter_rate')) :
            $fields['_rate_min'] = array(
                'label'       => esc_html__('Rate/h (minimum)', 'workscout_core'),
                'type'        => 'text',
                'placeholder' => esc_html__('e.g. 20', 'workscout_core'),
                'description' => esc_html__('Put just a number', 'workscout_core'),
                'show_in_rest' => true,
            );
            $fields['_rate_max'] = array(
                'label'       => esc_html__('Rate/h (maximum) ', 'workscout_core'),
                'type'        => 'text',
                'placeholder' => esc_html__('e.g. 20', 'workscout_core'),
                'description' => esc_html__('Put just a number - you can leave it empty and set only minimum rate value ', 'workscout_core'),
                'show_in_rest' => true,
            );
        endif;
        if (get_option('workscout_enable_filter_salary')) :
            $fields['_salary_min'] = array(
                'label'       => esc_html__('Salary min', 'workscout_core') . ' (' . $currency . ')',
                'type'        => 'text',
                'placeholder' => esc_html__('e.g. 20.000', 'workscout_core'),
                'description' => esc_html__('Put just a number', 'workscout_core'),
                'show_in_rest' => true,
            );
            $fields['_salary_max'] = array(
                'label'       => esc_html__('Salary max', 'workscout_core') . ' (' . $currency . ')',
                'type'        => 'text',
                'placeholder' => esc_html__('e.g. 50.000', 'workscout_core'),
                'description' => esc_html__('Maximum of salary range you can offer - you can leave it empty and set only minimum salary ', 'workscout_core'),
                'show_in_rest' => true,
            );
        endif;
        $fields['_apply_link'] = array(
            'label'       => esc_html__('External "Apply for Job" link', 'workscout_core'),
            'type'        => 'text',
            'placeholder' => esc_html__('http://', 'workscout_core'),
            'description' => esc_html__('If the job applying is done on external page, here\'s the place to put link to that page - it will be used instead of standard Apply form', 'workscout_core'),
            'show_in_rest' => true,
        );
        $fields['_hide_expiration'] = array(
            'label'       => esc_html__('Hide "Expiration date"', 'workscout_core'),
            'type'        => 'checkbox',
            'std'         => 0,
            'priority'    => 12,
            'description' => esc_html__('Hide the Listing Expiry Date  from job details', 'workscout_core'),
            'show_in_rest' => true,
        );
        $fields['_company_facebook'] = array(
            'label'       => esc_html__('Company Facebook URL', 'workscout_core'),
            'type'        => 'text',
            'placeholder' => esc_html__('http://', 'workscout_core'),
            'show_in_rest' => true,

        );


        return $fields;
    }


    /**
     * Save the extra frontend fields
     *
     * @since WorkScout 1.0.2
     *
     * @return void
     */
    function workscout_job_manager_update_job_data($job_id, $values)
    {
        if (isset($values['job']['rate_min'])) {
            update_post_meta($job_id, '_rate_min', $values['job']['rate_min']);
        }
        if (isset($values['job']['rate_max'])) {
            update_post_meta($job_id, '_rate_max', $values['job']['rate_max']);
        }
        if (isset($values['job']['salary_min'])) {
            update_post_meta($job_id, '_salary_min', $values['job']['salary_min']);
        }
        if (isset($values['job']['salary_max'])) {
            update_post_meta($job_id, '_salary_max', $values['job']['salary_max']);
        }
        if (isset($values['job']['hours'])) {
            update_post_meta($job_id, '_hours', $values['job']['hours']);
        }
        if (isset($values['job']['apply_link'])) {
            update_post_meta($job_id, '_apply_link', $values['job']['apply_link']);
        }
        if (isset($values['job']['header_image'])) {
            update_post_meta($job_id, 'pp_job_header_bg', $values['job']['header_image']);
        }
        //update_post_meta( $job_id, '_hide_expiration', $values[ 'job' ][ 'hide_expiration' ] );

    }




    public static function meta_boxes_user_employer()
    {

        $fields = array(
            'phone' => array(
                'id'                => 'phone',
                'name'              => __('Phone', 'workscout_core'),
                'label'             => __('Phone', 'workscout_core'),
                'type'              => 'text',

            ),
            // 'header_social' => array(
            //     'label'       => __('Social', 'workscout_core'),
            //     'type'        => 'header',
            //     'id'          => 'header_social',
            //     'name'        => __('Social', 'workscout_core'),
            // ),
            // 'twitter' => array(
            //     'id'                => 'twitter',
            //     'name'              => __('<i class="fa-brands fa-x-twitter"></i> Twitter', 'workscout_core'),
            //     'label'             => __('<i class="fa-brands fa-x-twitter"></i> Twitter', 'workscout_core'),
            //     'type'              => 'text',
            // ),
            // 'facebook' => array(
            //     'id'                => 'facebook',
            //     'name'              => __('<i class="fa fa-facebook-square"></i> Facebook', 'workscout_core'),
            //     'label'             => __('<i class="fa fa-facebook-square"></i> Facebook', 'workscout_core'),
            //     'type'              => 'text',
            // ),

            // 'linkedin' => array(
            //     'id'                => 'linkedin',
            //     'name'              => __('<i class="fa fa-linkedin"></i> Linkedin', 'workscout_core'),
            //     'label'             => __('<i class="fa fa-linkedin"></i> Linkedin', 'workscout_core'),
            //     'type'              => 'text',

            // ),
            // 'instagram' => array(
            //     'id'                => 'instagram',
            //     'name'              => __('<i class="fa fa-instagram"></i> Instagram', 'workscout_core'),
            //     'label'             => __('<i class="fa fa-instagram"></i> Instagram', 'workscout_core'),
            //     'type'              => 'text',
            // ),
            // 'youtube' => array(
            //     'id'                => 'youtube',
            //     'name'              => __('<i class="fa fa-youtube"></i> YouTube', 'workscout_core'),
            //     'label'             => __('<i class="fa fa-youtube"></i> YouTube', 'workscout_core'),
            //     'type'              => 'text',
            // ),
            // 'skype' => array(
            //     'id'                => 'skype',
            //     'name'              => __('<i class="fa fa-skype"></i> Skype', 'workscout_core'),
            //     'label'             => __('<i class="fa fa-skype"></i> Skype', 'workscout_core'),
            //     'type'              => 'text',
            // ),
            // 'whatsapp' => array(
            //     'id'                => 'whatsapp',
            //     'name'              => __('<i class="fa fa-whatsapp"></i> Whatsapp', 'workscout_core'),
            //     'label'             => __('<i class="fa fa-whatsapp"></i> Whatsapp', 'workscout_core'),
            //     'type'              => 'text',
            // ),
        );
        $fields = apply_filters('workscout_user_employer_fields', $fields);

        // Set meta box
        return $fields;
    }

    public static function meta_boxes_user_candidate()
    {

        $fields = array(
            'phone' => array(
                'id'                => 'phone',
                'name'              => __('Phone', 'workscout_core'),
                'label'             => __('Phone', 'workscout_core'),
                'type'              => 'text',

            ),
            
        );
        $fields = apply_filters('workscout_user_candidate_fields', $fields);

        // Set meta box
        return $fields;
    }

    /*
     * adding rate field for jobs edit/submit
     */

    function workscout_frontend_add_resume_rate_field($fields)
    {
        $currency = get_workscout_currency_symbol();
        $fields['resume_fields']['rate_min'] = array(
            'label'       => esc_html__('Minimum rate/h', 'workscout_core') . ' (' . $currency . ')',
            'type'        => 'text',
            'required'    => false,
            'placeholder' => esc_html__('e.g. 20', 'workscout_core'),
            'priority'    => 7
        );

        return $fields;
    }





    function workscout_admin_add_resume_rate_field($fields)
    {
        $currency = get_workscout_currency_symbol();
        $fields['_rate_min'] = array(
            'label'       => esc_html__('Rate/h (minimum)', 'workscout_core') . ' (' . $currency . ')',
            'type'        => 'text',
            'placeholder' => esc_html__('e.g. 20', 'workscout_core'),
            'description' => 'Put just a number'
        );
        return $fields;
    }


    /*
     * Custom Icon field for Job Categories taxonomy 
     **/

    // Add term page
    function wpjm_category_add_new_meta_field()
    {
        // this will add the custom meta field to the add new term page
?>
        <div class="form-field">
            <label for="term_meta[fa_icon]"><?php esc_html_e('Category Icon', 'workscout_core'); ?></label>
            <select class="workscout-icon-select" name="term_meta[fa_icon]" id="term_meta[fa_icon]" id="">

                <?php
                $faicons = workscout_icons_list();
                foreach ($faicons as $key => $value) {
                    echo '<option value="fa fa-' . $key . '">' . $value . '</option>';
                }
                if (get_option('workscout_linear_icons_status') != 'hide') {
                    $imicons = workscout_line_icons_list();
                    foreach ($imicons as $key) {
                        echo '<option value="ln ln-' . $key . '">' . $key . '</option>';
                    }
                }
                $materialicons = workscout_material_icons();
                foreach ($materialicons as $key) {
                    echo '<option value="' . $key . '">' . $key . '</option>';
                }

                ?>
            </select>
            <p class="description"><?php esc_html_e('Icon will be displayed in categories grid view', 'workscout_core'); ?></p>
        </div>
        <div class="form-field">
            <label for="term_meta[upload_icon]"><?php esc_html_e('Custom image icon for category', 'workscout_core'); ?></label>
            <input type="text" name="term_meta[upload_icon]" id="term_meta[upload_icon]" value="">
            <p class="description"><?php esc_html_e('This is alternative for font based icons', 'workscout_core'); ?></p>
        </div>
        <div class="form-field">
            <label for="_icon_svg"><?php esc_html_e('Custom Icon (SVG files only)', 'workscout_core'); ?></label>

            <?php wp_enqueue_media(); ?>
            <input style="width:100px" type="text" name="_icon_svg" id="_icon_svg" value="">
            <input type='button' class="listeo-custom-image-upload button-primary" value="<?php _e('Upload SVG Icon', 'workscout_core'); ?>" id="uploadimage" /><br />
        </div>
        <div class="form-field">
            <label for="term_meta[upload_icon]"><?php esc_html_e('Background image for category header', 'workscout_core'); ?></label>
            <input type="text" name="term_meta[upload_header]" id="term_meta[upload_header]" value="">
            <p class="description"><?php esc_html_e('Similar to the single jobs you can add image to the category header. It should be 1920px wide', 'workscout_core'); ?></p>
        </div>
    <?php
    }


    // Edit term page
    function wpjm_category_edit_meta_field($term)
    {

        // put the term ID into a variable
        $t_id = $term->term_id;

        // retrieve the existing value(s) for this meta field. This returns an array
        $term_meta = get_option("taxonomy_$t_id");
    ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="term_meta[fa_icon]"><?php esc_html_e('Category Icon', 'workscout_core'); ?></label>

            <td>
                <select class="workscout-icon-select" name="term_meta[fa_icon]" id="term_meta[fa_icon]">

                    <?php

                    $faicons = workscout_icons_list();

                    foreach ($faicons as $key => $value) {

                        echo '<option value="fa fa-' . $key . '" ';
                        if (isset($term_meta['fa_icon']) && $term_meta['fa_icon'] == 'fa fa-' . $key) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $value . '</option>';
                    }
                        if (get_option('workscout_linear_icons_status') != 'hide') {
                    $imicons = workscout_line_icons_list();

                    foreach ($imicons as $key) {
                        echo '<option value="ln ln-' . $key . '" ';
                        if (isset($term_meta['fa_icon']) && $term_meta['fa_icon'] == 'ln ln-' . $key) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $key . '</option>';
                    }
                }
                        $materialicons = workscout_material_icons();
                        foreach ($materialicons as $key) {
                        echo '<option value="' . $key . '" ';
                        if (isset($term_meta['fa_icon']) && $term_meta['fa_icon'] == '' . $key) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $key . '</option>';
                        }

                    ?>

                </select>
                <p class="description"><?php esc_html_e('Icon will be displayed in categories grid view', 'workscout_core'); ?></p>
            </td>
        </tr>
        <?php wp_enqueue_media(); ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="term_meta[upload_icon]"><?php esc_html_e('Custom image icon for category', 'workscout_core'); ?></label></th>
            <td>
                <input type="text" name="term_meta[upload_icon]" id="term_meta[upload_icon]" value="<?php echo isset($term_meta['upload_icon']) ? esc_attr($term_meta['upload_icon']) : ''; ?>">
                <p class="description"><?php esc_html_e('This is alternative for font based icons', 'workscout_core'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="_cover"><?php esc_html_e('Custom Icon (SVG files only)', 'workscout_core'); ?></label></th>
            <td>
                <?php
                $_icon_svg = get_term_meta($t_id, '_icon_svg', true);

                if ($_icon_svg) :
                    $_icon_svg_image = wp_get_attachment_image_src($_icon_svg, 'medium');

                    if ($_icon_svg_image) {
                        echo '<img src="' . $_icon_svg_image[0] . '" style="width:300px;height: auto;"/><br>';
                    }
                endif;
                ?>
                <input style="width:100px" type="text" name="_icon_svg" id="_icon_svg" value="<?php echo $_icon_svg; ?>">
                <input type='button' class="workscout-custom-image-upload button-primary" value="<?php _e('Upload SVG Icon', 'workscout_core'); ?>" id="uploadimage" /><br />
                <p>We recommend using outline icons from <a href="https://www.iconfinder.com/search/?price=free&style=outline">iconfinder.com</a></p>
            </td>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="term_meta[upload_header]"><?php esc_html_e('Background image for category header', 'workscout_core'); ?></label></th>
            <td>
                <input type="text" name="term_meta[upload_header]" id="term_meta[upload_header]" value="<?php echo isset($term_meta['upload_header']) ? esc_attr($term_meta['upload_header']) : ''; ?>">
                <p class="description"><?php esc_html_e('Similar to the single jobs you can add image to the category header. Put here direct link to the image. It should be 1920px wide', 'workscout_core'); ?></p>
            </td>
        </tr>
<?php
    }



    // Save extra taxonomy fields callback function.
    function workscout_save_taxonomy_custom_meta($term_id)
    {
        if (isset($_POST['term_meta'])) {
            $t_id = $term_id;
            $term_meta = get_option("taxonomy_$t_id");
            $cat_keys = array_keys($_POST['term_meta']);
            foreach ($cat_keys as $key) {
                if (isset($_POST['term_meta'][$key])) {
                    $term_meta[$key] = $_POST['term_meta'][$key];
                }
            }
            // Save the option array.
            update_option("taxonomy_$t_id", $term_meta);
        }
        if (isset($_POST['_icon_svg'])) {
            $_icon_svg = sanitize_title($_POST['_icon_svg']);
            update_term_meta($term_id, '_icon_svg', $_icon_svg);
        }
    }


    function workscout_add_salary_to_job_structured_data($data, $post)
    {

        if ($post && $post->ID) {
            $salary = get_post_meta($post->ID, '_salary_min', true);
            //_salary_min, _salary_max, _rate_min, _rate_max, _hours
            // Here you can add values that would be considered "not a salary" to skip output for
            $no_salary_values = array('Not Disclosed', 'N/A', 'TBD');

            // Don't add anything if empty value, or value equals something above in no salary values
            if (empty($salary) || in_array(strtolower($salary), array_map('strtolower', $no_salary_values))) {
                return $data;
            }

            // Determine float value, stripping all non-alphanumeric characters
            $salary_float_val = (float) filter_var($salary, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if (!empty($salary_float_val)) {
                // @see https://schema.org/JobPosting
                // Simple value:
                //$data['baseSalary'] = $salary_float_val;

                // Or using Google's Structured Data format
                // @see https://developers.google.com/search/docs/data-types/job-posting
                // This is the format Google really wants it in, so you should customize this yourself
                // to match your setup and configuration
                $data['baseSalary'] = array(
                    '@type' => 'MonetaryAmount',
                    'currency' => get_option('workscout_currency_setting'),
                    'value' => array(
                        '@type' => 'QuantitativeValue',
                        'value' => $salary_float_val,
                        // HOUR, DAY, WEEK, MONTH, or YEAR
                        'unitText' => 'YEAR'
                    )
                );
            }
        }

        return $data;
    }
}