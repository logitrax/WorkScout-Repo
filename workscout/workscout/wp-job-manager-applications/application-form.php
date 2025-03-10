<?php

/**
 * Application form shown on job listing page.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-applications/application-form.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-applications
 * @category    Template
 * @version     3.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

global $post;

$captcha_version = (class_exists('WP_Job_Manager\WP_Job_Manager_Recaptcha') && get_option('job_application_enable_recaptcha_application_submission'))
	? WP_Job_Manager\WP_Job_Manager_Recaptcha::instance()->get_recaptcha_version()
	: null;

$show_tabs = false;
if (get_option('resume_manager_enable_application')) {
	$show_tabs = true;
}

if (get_option('resume_manager_force_application')) {
	$show_tabs = false;
}

if ($show_tabs) { ?><div id="tab1" class="tab-slider--body"><?php } ?>
	<?php global $post; ?>
	<form class="job-manager-application-form job-manager-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(get_permalink()); ?>">
		<?php do_action('job_application_form_fields_start'); ?>

		<?php foreach ($application_fields as $key => $field) : ?>
			<?php if ('output-content' === $field['type']) : ?>
				<div class="form-content">
					<h3><?php esc_html(wp_unslash($field['label'])); ?></h3>
					<?php
					if (!empty($field['description'])) :
					?>
						<?php echo wpautop(wp_kses_post($field['description'])); ?><?php endif; ?>
				</div>
			<?php else : ?>
				<fieldset class="fieldset-<?php echo esc_attr($key); ?>">
					<label for="<?php echo esc_attr($key); ?>"><?php echo wp_unslash($field['label']) . apply_filters('submit_job_form_required_label', $field['required'] ? '' : ' <small>' . __('(optional)', 'wp-job-manager-applications') . '</small>', $field); ?></label>
					<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
						<?php $class->get_field_template($key, $field); ?>
					</div>
				</fieldset>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php do_action('job_application_form_fields_end'); ?>

		<p>
			<input type="submit" class="button wp_job_manager_send_application_button" value="<?php esc_attr_e('Send application', 'wp-job-manager-applications'); ?>" />
			<input type="hidden" name="wp_job_manager_send_application" value="1" />
			<input type="hidden" name="job_id" value="<?php echo absint($post->ID); ?>" />
			<input type="hidden" name="form_id" value="<?php echo absint($form_id); ?>" />
		</p>
	</form>

	<?php if ($show_tabs) { ?>
	</div><?php } ?>