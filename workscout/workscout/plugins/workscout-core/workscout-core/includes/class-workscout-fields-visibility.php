<?php
/**
 * Handle field visibility in forms
 */
class Workscout_Fields_Visibility {
    
    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        // Hook into form field rendering
        add_filter('submit_job_form_fields', array($this, 'filter_job_fields'), 999, 1);
        add_filter('submit_resume_form_fields', array($this, 'filter_resume_fields'), 999, 1);
        add_filter('submit_company_form_fields', array($this, 'filter_company_fields'), 999, 1);
        add_filter('submit_task_form_fields', array($this, 'filter_task_fields'), 999, 1);
    }

    /**
     * Filter fields based on visibility settings
     */
    private function filter_fields($fields, $form_type) {
        $hidden_fields = get_option('workscout_hidden_fields', array());
        
        if (!isset($hidden_fields[$form_type]) || !is_array($hidden_fields[$form_type])) {
            return $fields;
        }


        // Get the list of hidden fields for this form type
        $fields_to_hide = $hidden_fields[$form_type];
    
        // Remove non-required fields that are in the hidden list
        foreach ($fields_to_hide as $field_key) {
            //var_dump(isset($fields[$field_key]));
            if($form_type == 'job'){
                if (isset($fields[$form_type][$field_key]) && !$fields[$form_type][$field_key]['required']) {
                    unset($fields[$form_type][$field_key]);
                }
            }
            if($form_type == 'resume'){
                if (isset($fields['resume_fields'][$field_key]) && !$fields['resume_fields'][$field_key]['required']) {
                    unset($fields['resume_fields'][$field_key]);
                }
            }
            if($form_type == 'company'){
                if (isset($fields['company_fields'][$field_key]) && !$fields['company_fields'][$field_key]['required']) {
                    unset($fields['company_fields'][$field_key]);
                }
            }
            if($form_type == 'task'){
                if (isset($fields['task_fields'][$field_key]) && !$fields['task_fields'][$field_key]['required']) {
                    unset($fields['task_fields'][$field_key]);
                }
            }
        }
        // echo '<pre>';
        
        // echo '</pre>';

        return $fields;
    }

    /**
     * Filter job submission form fields
     */
    public function filter_job_fields($fields) {
        return $this->filter_fields($fields, 'job');
    }

    /**
     * Filter resume submission form fields
     */
    public function filter_resume_fields($fields) {
        return $this->filter_fields($fields, 'resume');
    }

    /**
     * Filter company submission form fields
     */
    public function filter_company_fields($fields) {
        return $this->filter_fields($fields, 'company');
    }

    /**
     * Filter task submission form fields
     */
    public function filter_task_fields($fields) {
        return $this->filter_fields($fields, 'task');
    }
}

// Initialize the class
Workscout_Fields_Visibility::instance();
