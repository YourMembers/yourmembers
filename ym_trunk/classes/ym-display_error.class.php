<?php

class YourMember_Display_Error {
	var $disabled;
	var $msg;
	function YourMember_Display_Error($args = array('disabled' => true)) {
		global $ym_error_msg;
		extract($args);
		if ( empty( $ym_error_msg ) ) {
			return false;
		}

		$this->msg = $ym_error_msg;

		if ( ! empty( $disabled ) && true == $disabled ) {
			$this->msg .= "\n" . __('YourMember functionality will be disabled until this error is corrected.','ym');
		}
		// if in the WordPress admin area
		if ( is_admin() ) {
			add_action('admin_notices', array(&$this, 'admin_msg'));
		} else {
			add_action('wp_footer', array(&$this, 'external_msg'));
		}
	}

	function admin_msg() {
		?><div id="message" class="updated fade-ff0000">
			<p><?php echo apply_filters('the_content', $this->msg) ?></p>	
		</div><?php
	}

	function external_msg() {
		?><div class="updated fade-ff0000" style="position: absolute; left: 0px; top: 0px; background-color: yellow; color: black; padding: 1em; width: 100%">
			<p><?php echo apply_filters('the_content', $this->msg) ?></p>	
		</div><?php

	}

}
