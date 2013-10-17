<?php

class mailmanager_wp_mail_gateway {
	function __construct($stop = FALSE) {
		$this->name			= 'WPMail';
		$this->safe_name	= 'wp_mail'; // should be the file name
		$this->description	= 'Using the inbuilt default Server functions you can Send Mail';
		$this->logo			= YM_MM_GATEWAY_URL . $this->safe_name . '/wp_mail.png';
		$this->settings		= TRUE;
		
		$this->option_name	= 'ym_other_mm_wp_mail';
		$this->options		= get_option($this->option_name);
	}
	
	function activate() {
		$settings = new StdClass();
		$settings->canspam = 0;
		$settings->broadcast_header = '';
		$settings->broadcast_footer = '';
		$settings->generic_header = '';
		$settings->generic_footer = '';
		$settings->postal_address = '';
		$settings->unsubscribe_page = get_bloginfo('wpurl');
		$this->options = $settings;
		$this->saveoptions();
	}
	function deactivate() {
		delete_option($this->option_name);
		$this->options = '';
	}
	function saveoptions() {
		update_option($this->option_name, $this->options);
	}
	
	function settings(&$break) {
		global $ym_formgen, $mm;
		
		$break = TRUE;
		
		if ($_POST) {
			$this->options->canspam = ym_post('canspam');
			$this->options->broadcast_header = ym_post('broadcast_header');
			$this->options->broadcast_footer = ym_post('broadcast_footer');
			$this->options->generic_header = ym_post('generic_header');
			$this->options->generic_footer = ym_post('generic_footer');
			$this->options->postal_address = ym_post('postal_address');
			$this->options->unsubscribe_page = ym_post('unsubscribe_page');
			
			ym_box_top('Mail Manager Settings');
			if (ym_post('canspam') == 1) {
				if (!ym_post('postal_address')) {
					$this->options->canspam = 0;
					echo '<p>You must provide you Postal Address for CAN-SPAM Act (2003) Compliance</p>';
				}
				if (FALSE === (strpos($this->options->broadcast_footer, '[unsubscribe]'))) {
					$this->options->canspam = 0;
					echo '<p>You must include the unsubscribe shortcode in your Broadcast Footer for CAN-SPAM Act (2003) Compliance</p>';
				}
				if (FALSE === (strpos($this->options->generic_footer, '[unsubscribe]'))) {
					$this->options->canspam = 0;
					echo '<p>You must include the unsubscribe shortcode in your Generic Footer for CAN-SPAM Act (2003) Compliance</p>';
				}
				if (FALSE === (strpos($this->options->broadcast_footer, '[address]'))) {
					$this->options->canspam = 0;
					echo '<p>You must include the address shortcode in your Broadcast Footer for CAN-SPAM Act (2003) Compliance</p>';
				}
				if (FALSE === (strpos($this->options->generic_footer, '[address]'))) {
					$this->options->canspam = 0;
					echo '<p>You must include the address shortcode in your Generic Footer for CAN-SPAM Act (2003) Compliance</p>';
				}
			}
			
			$this->saveoptions();
			echo '<p>Settings were updated</p>';
			ym_box_bottom();
			echo '<meta http-equiv="refresh" content="5" />';
			return;
		}
		
		echo '<form action="" method="post">';
		
		ym_box_top('CAN-SPAM Compliant');

		echo '<p>This option allows you to turn compliancy with the <a href="http://en.wikipedia.org/wiki/CAN-SPAM_Act_of_2003">CAN-SPAM Act of 2003</a> on or off. 
		<!--
		If it is on when somebody signs up to an email series then it will first send a confirmation email to the registrant to click before it will add them to the list. When this is off it will just sign them up.
		-->
		</p>';
		echo '<table class="form-table">';
		echo $ym_formgen->render_form_table_radio_row('CAN-SPAM Act (2003) Compliant?', 'canspam', $this->options->canspam);
		echo '</table>
		<p class="submit" style="text-align: right;">
			<input type="submit" name="submit" value="' . __('Save Settings','ym') . ' &raquo;" />
		</p>
		';
		ym_box_bottom();
		
		ym_box_top('Email Templates', TRUE);

		echo '<table class="form-table">';

		echo $ym_formgen->render_form_table_textarea_row('Broadcast Email Header', 'broadcast_header', $this->options->broadcast_header, 'This will be attached to the start of any email messages that are broadcasts');
		echo $ym_formgen->render_form_table_textarea_row('Broadcast Email Footer', 'broadcast_footer', $this->options->broadcast_footer, 'This will be attached to the end of any email messages that are broadcasts. You can use the following hooks:<br />
		[unsubscribe] = The unsubscribe link (required for CAN-SPAM compliance)<br />
		[address] = The address you entered on this page (required for CAN-SPAM compliance]');

		echo $ym_formgen->render_form_table_textarea_row('Generic Email Header', 'generic_header', $this->options->generic_header, 'This will be attached to the start of any non broadcast emails');
		echo $ym_formgen->render_form_table_textarea_row('Generic Email Footer', 'generic_footer', $this->options->generic_footer, 'This will be attached to the end of any non broadcast emails. You can use the following hooks:<br />
		[unsubscribe] = The unsubscribe link (required for CAN-SPAM compliance)<br />
		[address] = The address you entered on this page (required for CAN-SPAM compliance]');

		echo $ym_formgen->render_form_table_textarea_row('Postal Address', 'postal_address', $this->options->postal_address, 'A postal address which can be inserted into your emails if you use the [address] hook (required for CAN-SPAM compliance)');

		echo '</table>';
		echo '
		<p class="submit" style="text-align: right;">
			<input type="submit" name="submit" value="' . __('Save Settings','ym') . ' &raquo;" />
		</p>
		';

		ym_box_bottom();

		ym_box_top('Un/Subscribe');

		echo '<table class="form-table">';
		echo $ym_formgen->render_form_table_text_row('Series Unsubscribe Page', 'unsubscribe_page', $this->options->unsubscribe_page, 'Users will be redirect here when they unsubscribe from a series');
		echo '
		</table>
		<p class="submit" style="text-align: right;">
			<input type="submit" name="submit" value="' . __('Save Settings','ym') . ' &raquo;" />
		</p>
		';

		ym_box_bottom();

		echo '</form>';
	}
}
