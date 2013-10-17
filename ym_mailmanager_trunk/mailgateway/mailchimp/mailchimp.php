<?php

/**
* 
* $Id$
* $Revision$
* $Date$
* $Author$
* 
*/

class mailmanager_mailchimp_gateway {
	var $mailchimp;
	
	function __construct() {
		$this->ybuy_id		= 42;
		$this->name			= 'MailChimp';
		$this->safe_name	= 'mailchimp';
		$this->description	= 'You can use MailChimp to Send your Email Series!';
		$this->logo			= YM_MM_GATEWAY_URL . $this->safe_name . '/light_freddiewink.png';
		$this->settings		= TRUE;
		
		$this->option_name	= 'ym_other_mm_mailchimp';
		$this->options		= get_option($this->option_name);
		
		if (mailmanager_active_gateway() == $this->safe_name) {
			if (!$this->options->apikey) {
				// no key
				if ($_GET['mm_action'] != 'gateway') {
					add_action('mailmanager_precontent', array($this, 'noapikey'));
				}
			} else {
				if (!$this->options->list) {
					if ($_GET['mm_action'] != 'gateway') {
						add_action('mailmanager_precontent', array($this, 'nolist'));
					}
				} else if (!$this->mailchimp) {
					if ($this->class_construct()  != "Everything's Chimpy!") {
						ym_box_top('Error');
						echo '<p>MailChimp Error: ' . $this->mailchimp->return . '</p>';
						ym_box_bottom();
					}
				}
				if ($_GET['mm_action'] != 'gateway') {
					if ($this->options->list && !$this->options->default_template) {
						add_action('mailmanager_precontent', array($this, 'notemplate'));
					}
				}
				$this->associations = get_option($this->option_name . '_associations');
				// hooks
				
				add_action('mailmanager_homepage', array($this, 'promote'));
				if ($_GET['mm_action'] == 'welcome') {
					add_action('mailmanager_precontent', array($this, 'welcome_sorry'));
				}
				// broadcast
				add_action('mailmanager_broadcast_precontent', array($this, 'broadcast_pre'));
				add_action('mailmanager_broadcast_form', array($this, 'broadcast_form'));
				add_action('mailmanager_broadcast_create', array($this, 'broadcast_create'), 10, 5);
				// create
				add_action('mailmanager_create_form', array($this, 'create_form'));
				add_action('mailmanager_create_create', array($this, 'create_create'), 10, 3);
				// view
//				add_filter('mailmanager_view_emails_filter', array($this, 'view_emails_filter'), 10, 1);
				add_action('mailmanager_view_emails', array($this, 'view_emails'));
				// preview
				add_action('mailmanager_email_preview', array($this, 'email_preview'), 10, 1);
				// series
				add_action('mailmanager_series_replace', array($this, 'series_replace'));
				
				// update/downgrade
//				do_action('ym_membership_transaction_success', array("user_id"=>$user_id));
				add_action('ym_membership_transaction_success', array($this, 'change_user_list'), 10, 1);
				
				// aditional
				add_action('mailmanager_email_stats', array($this, 'email_stats'));
				
				add_action('mailmanager_cron_check', array($this, 'sync_with_gateway'));
				
				// filter
				add_filter('mailmanager_adjust_recipients', array($this, 'mm_adjust_recipients'), 10, 1);
			}
		}
	}
	
	function activate() {
		get_currentuserinfo();
		global $current_user;
		
		$settings = new StdClass();
		$settings->apikey = '';

		$settings->from_email = $current_user->user_email;
		$settings->from_name = get_bloginfo();

		$settings->double_opt_in = '0';
		$settings->welcome_message = '0';
		
		$settings->unsub_action = 'disable';
		$settings->default_template = '';
		
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
	function saveassociations() {
		update_option($this->option_name . '_associations', $this->associations);
	}
	
	function settings(&$break) {
		global $ym_formgen, $mm;
		
		$break = TRUE;
		
		// store apikey
		if ($_POST['apikey']) {
			$apikey = $_POST['apikey'];
			// saving API KEY
			if ($this->class_construct($apikey) != "Everything's Chimpy!") {
				// error
				echo '<div id="message" class="error"><p>' . __('MailChimp Returned an Error') . ': ' . $this->mailchimp->error . '</p></div>';
			} else {
				echo '<div id="message" class="updated"><p>' . __('MailChimp API Key was Accepted and has been Saved') . '</p></div>';
				$this->options->apikey = $apikey;
				$this->saveoptions();
			}
		}
		if ($_POST['list']) {
			$list = $_POST['list'];
			
			$this->options->list = $list;
			$this->saveoptions();
			
			echo '<div id="message" class="updated"><p>' . __('MailChimp List was Saved') . '</p></div>';
			if ($this->class_construct()  != "Everything's Chimpy!") {
				ym_box_top('Error');
				echo '<p>MailChimp Error: ' . $this->mailchimp->return . '</p>';
				ym_box_bottom();
			}
		}
		$spawn_light_box = FALSE;
		if ($_POST['from_email']) {
			// general settings save
			
			$this->options->double_opt_in = $_POST['double_opt_in'];
			$this->options->welcome_message = $_POST['welcome_message'];
			
			$this->options->from_email = $_POST['from_email'];
			$this->options->from_name = $_POST['from_name'];
			
			$this->options->unsub_action = $_POST['unsub_action'];
			$this->options->default_template = $_POST['default_template'];
			
			$this->saveoptions();
			
			echo '<div id="message" class="updated"><p>' . __('General MailChimp Settings were Saved') . '</p></div>';
			
			if (!$this->options->first_run_complete) {
				// call sync
				$spawn_light_box = TRUE;
				$this->options->first_run_complete = TRUE;
				$this->saveoptions();
				$_POST['manual_sync'] = 1;
			}
		}
		if ($_POST['manual_sync']) {
			// do sync
			ym_box_top('Sync');
			if ($_POST['reset']) {
				echo 'RESETTING';
				$this->resetStaticSegments();
			}
			$this->sync_with_gateway();
			ym_box_bottom();
		}
//		$spawn_light_box = true;
		if ($spawn_light_box) {
			$lists = $this->get_lists();
			$list_name = $lists[$this->options->list];
			echo '
<script type="text/javascript">' . "
	jQuery(document).ready(function() {
		jQuery('<div id=\"mailchimp_message\"><p><img src=\"" . YM_MM_GATEWAY_URL . $this->safe_name . "/light_connected_freddie.png\" style=\"width: 150px; float: left; padding: 5px;\" alt=\"Connected with MailChimp\" /<br /><br /><br />As Freddie the Mail Chimp would say \"Go have a banana\".<br /><br />You will now find all your users on: <strong>" . $list_name . "</strong> and can use the <a href=\"https://" . $this->mailchimp->getZone() . ".admin.mailchimp.com/\" target=\"blank\">standard Mail Chimp interface</a> or you can continue using the Your Members - Mail Manager interface.<br /><br />For more information on how to manage your list and segments please check out the documentation and welcome video.</p></div>').dialog({
			width		: 600,
			modal		: true,
			resizable	: false,
			title		: '" . __('That&#39;s it your done') . "',
			buttons		: { 'Done': function() { jQuery(this).dialog('close'); } }
		});
	});
</script>
";
		}
		
		// settings page
		if ($this->options->apikey) {
			if ($this->options->list) {
				if (!$this->options->first_run_complete) {
					echo '<div id="message" class="error"><p>' . __('Please check these settings are correct and click submit to complete the initial MailChimp Sync Run') . '</p></div>';
				} else if (!$this->options->default_template) {
					echo '<div id="message" class="error"><p>' . __('You need to select a Default Template') . '</p></div>';
				}
				
				echo '<form action="" method="post">';
				ym_box_top(__('List Settings'));
				
				echo '<p>' . __('Your Members MailManager will create a segment for all local lists') . '</p>';
				echo '<p>' . __('Your Members MailManager will sync all Your Members Custom Profile Fields with MailChimp') . '</p>';

				echo '<p>' . __('You can optionally change this settings to change the MailChimp behaviour with adding new people to the mailing list') . '</p>';
				echo '<p>' . __('You do not necessarily need Double Opt in as in order to register for your membership site they have completed double opt in') . '</p>';
				
				echo '<table class="form-table">';
				// check box to sync
				
				// double opt in
				echo $ym_formgen->render_form_table_radio_row(__('Enable Double Opt In'), 'double_opt_in', $this->options->double_opt_in, __('Send users a message asking if they wish to join the list'));
				// welcome message
				echo $ym_formgen->render_form_table_radio_row(__('Enable Welcome Message'), 'welcome_message', $this->options->welcome_message, __('Send users a list welcome message'));
				
				echo '</table>';
				
				ym_box_bottom();
				
				if ($this->options->first_run_complete) {
					ym_box_top(__('MailChimp List Unsubscribe options'));
				} else {
					ym_box_top(__('MailChimp List Unsubscribe options'), TRUE, TRUE);
				}
				echo '<p>' . __('When a user Unsubscribes from the MailChimp list, on the MailChimp side, you can choose what to do') . '</p>';
				
				echo '<table class="form-table">';
				
				$data = array(
					'disable'	=> __('Disable the YM Account'),
					'flag'		=> __('Notify the Admin'),
					'nothing'	=> __('Nothing'),
				);
				
				echo $ym_formgen->render_combo_from_array_row(__('What to do on Unsubscribe'), 'unsub_action', $data, $this->options->unsub_action);
				
				echo '</table>';
				
				ym_box_bottom();
				
				if ($this->options->first_run_complete) {
					ym_box_top(__('Additional Options'));
				} else {
					ym_box_top(__('Additional Options'), TRUE, TRUE);
				}
				echo '<p>' . __('Details to use when sending a Broadcast or Series Email') . '</p>';

				echo '<table class="form-table">';

				echo $ym_formgen->render_form_table_text_row(__('From Address'), 'from_email', $this->options->from_email, __('Address Emails appear to come from'));
				echo $ym_formgen->render_form_table_text_row(__('From Name'), 'from_name', $this->options->from_name, __('The Name emails appear to be from'));

				echo '</table>';
				
//				if (!$this->options->first_run_complete) {
//					echo '<p><strong>' . __('Clicking Submit will run the first MailChimp Sync, and add your Users to the Mailing List and configure Segments') . '</strong></p>';
//				}

				echo '<p style="text-align: right;"><input type="submit" value="' . __('Save Settings') . '" /></p>';

				ym_box_bottom();
				
				if ($this->options->default_template && $this->options->first_run_complete) {
					ym_box_top(__('Default Template Select'), TRUE, TRUE);
				} else {
					ym_box_top(__('Default Template Select'), TRUE);
				}
				
				$templates = $this->mailchimp->templates(array('user' => TRUE, 'gallery' => TRUE, 'base' => TRUE));
				echo '<p>' . __('Select a default theme template') . '</p>';

				$first = FALSE;
				if (!$this->options->default_template) {
					$first = TRUE;
				}
				
				echo '
<style type="text/css">
<!--
#mailmanager_template_select img {
	width: 124px;
	background: #555;
	display: block;
	margin-left: auto;
	margin-right: auto;
	padding: 5px;	
}
-->
</style>
<div id="mailmanager_template_select">
				<ul>
					<li><a href="#template_select_user">' . __('View User/Custom Templates') . '</a></li>
					<li><a href="#template_select_base">' . __('View Base Templates') . '</a></li>
					<li><a href="#template_select_gallery">' . __('View Gallery Templates') . '</a></li>
				</ul>
				';
				echo '<div id="template_select_user"><table class="form-table">';
				echo '<tr>';
				$x = 0;
				$has_user = FALSE;
				foreach ($templates->user as $template) {
					$has_user = TRUE;
					$x++;
					if ($x > 4) {
						echo '</tr><tr>';
						$x = 1;
					}
					echo '<td style="text-align: center;">';
					echo '<label for="template_' . $template->id . '">';
//					if ($template->preview_image) {
						echo '<img src="' . $template->preview_image . '" />';
						echo '<br />';
//					}
					echo $template->name;
					echo '<br /><input type="radio" name="default_template" id="template_' . $template->id . '" value="' . $template->id . '" ';
					
					if ($template->id == $this->options->default_template || $first) {
						echo ' checked="checked" ';
						$first = FALSE;
					}
					
					echo '/>';
					echo '</td>';
				}
				echo '</tr></table></div><div id="template_select_base"><table class="form-table"><tr>';
				$x=0;
				foreach ($templates->base as $template) {
					$x++;
					if ($x > 4) {
						echo '</tr><tr>';
						$x = 1;
					}
					echo '<td style="text-align: center;">';
					echo '<label for="template_' . $template->id . '">';
//					if ($template->preview_image) {
						echo '<img src="' . $template->preview_image . '" />';
						echo '<br />';
//					}
					echo $template->name;
					echo '<br /><input type="radio" name="default_template" id="template_' . $template->id . '" value="b_' . $template->id . '" ';
					
					if ('b_' . $template->id == $this->options->default_template) {
						echo ' checked="checked" ';
					}
					
					echo '/>';
					echo '</td>';
				}
				echo '</tr>';
				echo '</table>';
				echo '</div><div id="template_select_gallery"><table class="form-table"><tr>';
				$x=0;
				foreach ($templates->gallery as $template) {
					$x++;
					if ($x > 4) {
						echo '</tr><tr>';
						$x = 1;
					}
					echo '<td style="text-align: center;">';
					echo '<label for="template_' . $template->id . '">';
//					if ($template->preview_image) {
						echo '<img src="' . $template->preview_image . '" />';
						echo '<br />';
//					}
					echo $template->name;
					echo '<br /><input type="radio" name="default_template" id="template_' . $template->id . '" value="g_' . $template->id . '" ';
					
					if ('g_' . $template->id == $this->options->default_template) {
						echo ' checked="checked" ';
					}
					
					echo '/>';
					echo '</td>';
				}
				echo '</tr>';
				echo '</table>';
				echo '</div></div>';
				
				echo '<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery(\'#mailmanager_template_select\').tabs({
							selected: ';
							
							if ($has_user) {
								if (substr($this->options->default_template, 0, 2) == 'b_') {
									echo 1;
								} else {
									echo 0;
								}
							} else {
								echo 1;
							}
				echo '
						});
					});
				</script>';
				
				echo '<p style="text-align: right;"><input type="submit" value="' . __('Save Default Template Selection') . '" /></p>';
				
				ym_box_bottom();
				
				echo '</form>';
				
				if ($this->options->first_run_complete) {
					echo '<form action="" method="post">';
					ym_box_top(__('Manual Sync'), TRUE, TRUE);
					
					echo '<input type="hidden" name="manual_sync" value="true" />';
					echo '<p style="text-align: right;"><input type="submit" value="' . __('Run a manual List Sync') . '" /></p>';
					
					ym_box_bottom();
					echo '</form>';
					echo '<form action="" method="post">';
					ym_box_top(__('Reset Static Segments'), TRUE, TRUE);
					
					echo '<input type="hidden" name="manual_sync" value="true" />';
					echo '<input type="hidden" name="reset" value="true" />';
					echo '<p style="text-align: right;"><input type="submit" class="deletelink" value="' . __('Reset Static Segments') . '" /></p>';
					
					ym_box_bottom();
					echo '</form>';
				}
				
				ym_box_top(__('MailChimp List Associate'), TRUE, TRUE);
			} else {
				ym_box_top(__('MailChimp List Associate'));
			}
			
			echo '<p>' . __('We need to associate Your Members MailManager with a single list') . '</p>';
			echo '<p>' . __('We then segment the list in order to create different mailing lists') . '</p>';
			
			$lists = $this->get_lists(TRUE);
			
			echo '<form action="" method="post">';
			echo '<table class="form-table">';
			echo $ym_formgen->render_combo_from_array_row(__('MailChimp List'), 'list', $lists, $this->options->list, __('Select a List to associate with'));
			echo '</table>';
			
			echo '<p style="text-align: right;"><input type="submit" value="' . __('Save List') . '" /></p>';
			echo '</form>';
			
			// render list picker
			ym_box_bottom();
			
			ym_box_top(__('MailChimp Settings API Key'), TRUE, TRUE);
		} else {
			ym_box_top(__('MailChimp Settings API Key'));
		}
		// API Key
		
		echo '<form action="" method="post">';
		echo '<table class="form-table">';
		echo $ym_formgen->render_form_table_text_row(__('MailChimp API Key'), 'apikey', $this->options->apikey, __('Your MailChimp API Key'));
		echo '</table>';
		echo '<p>' . __('You can get your API Key <a href="http://admin.mailchimp.com/account/api-key-popup" id="mcinterrupt">here</a>') . '</p>';
		echo '<p>' . __('You do not have MailChimp yet? Please use our link to <a href="http://eepurl.com/IqI5">sign up</a>') . '</p>';
		echo '<p style="text-align: right;"><input type="submit" value="' . __('Save API Key') . '" /></p>';
		echo '</form>';
		
		echo '
<script type="text/javascript">' . "
	jQuery(document).ready(function() {
		url = jQuery('#mcinterrupt').attr('href');
		jQuery('#mcinterrupt').attr('href', '#nowhere');
		
		jQuery('#mcinterrupt').click(function() {
			jQuery('<div style=\"width: 1020px; height: 730px;\"><iframe src=\"' + url + '\" style=\"width: 990px; height: 610px;\"></iframe><form action=\"\" method=\"post\"><label for=\"api_key\" style=\"margin-left: 200px;\">" . __('MailChimp API Key') . ":<input type=\"text\" name=\"apikey\" id=\"api_key\" size=\"50\" /></label><p style=\"text-align: right; display: inline;\"><input type=\"submit\" value=" . __('Save API Key') . " /></p></form></div>').dialog({
				width		: 1020,
				modal		: true,
				resizable	: false,
				title		: '" . __('MailChimp API Key') . "'
			});
		});
	});
</script>
";
		
		ym_box_bottom();
	}
	
	// Hooks
	function noapikey() {
		global $mm;
		ym_box_top(__('MailChimp: No ApiKey'));
		echo '<p>' . __('You need to Supply your MailChimp API Key') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . __('MailChimp Settings') . '</a></p>';
		ym_box_bottom();
	}
	function nolist() {
		global $mm;
		ym_box_top(__('MailChimp: No List Associated'));
		echo '<p>' . __('You need to select a MailChimp list to associate with') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . __('MailChimp Settings') . '</a></p>';
		ym_box_bottom();
	}
	function notemplate() {
		global $mm;
		ym_box_top(__('MailChimp: No Default Template'));
		echo '<p>' . __('You need to select a Default MailChimp Template to use') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . __('MailChimp Settings') . '</a></p>';
		ym_box_bottom();
	}
	
	function promote() {
		ym_box_bottom();
		ym_box_top(__('Connected wtih MailChimp'));
		echo '<center><img src="' . YM_MM_GATEWAY_URL . $this->safe_name . '/light_connected_freddie.png" style="width: 150px;" alt="Connected with MailChimp" /></center>';
	}
	function welcome_sorry() {
		ym_box_top(__('Welcome Messages'));
		echo '<p>' . __('WordPress Welcome emails if enabled here are still sent via WP Mail') . '</p>';
		ym_box_bottom();
	}
	
	function email_stats() {
		define('STOP_EMAIL_STATS', TRUE);
		
		$options = array(
			'list_id'	=> $this->options->list,
			'type'		=> 'regular',
			'status'	=> 'sent',
		);
		$campaigns = $this->mailchimp->campaigns($options);
		if ($campaigns->total) {
			echo '<table class="form-table">';
			echo '<tr><th>Name</th><th>Sent</th><th>Open(unique)</td><td>Soft</th><th>Hard Bounce</th><th>Click Count</th></tr>';
			foreach ($campaigns->data as $campaign) {
				echo '<tr>';
				echo '<td>' . $campaign->title . '</td>';
				$cid = $campaign->id;
				
				$stats = $this->mailchimp->campaignStats($cid);

				echo '<td>' . $stats->emails_sent . '</td>';

				echo '<td>';
				echo $stats->opens . '(' . $stats->unique_opens . ')';
				echo '</td>';

				echo '<td>' . $stats->soft_bounces . '</td>';
				echo '<td>' . $stats->hard_bounces . '</td>';
				
				echo '<td>' . $stats->clicks . '</td>';
				
				echo '<td><a href="https://' . $this->mailchimp->getZone() . '.admin.mailchimp.com/reports/summary?id=' . $campaign->web_id . '">' . __('Stats') . '</a></td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<p>' . __('Nothing to see here, yet...') . '</p>';
		}
	}
	
	/*************************************************************************
	* BROADCAST
	**************************************************************************/
	function broadcast_pre() {
		// need to replace the form
		
	}
	function broadcast_form() {
		global $ym_formgen;
		
		define('MAILMANAGER_FORM_REPLACED', TRUE);
		
		if ($_POST['email_time']) {
			echo '</table></form>';
			$this->scheduling($_POST['email_id']);
			return;
		}
		
		echo '<tr><td colspan="2">
<p>' . __('A MailChimp Broadcast is called a Campaign') . '</p>
<input type="hidden" name="email_id" value="1" /></td></tr>';// Make sure we don't get stuck in the broadcast error system
		
		$this->generate_message_create_form();
		$timewarp		= $_POST['timewarp'] ? $_POST['timewarp'] : 0;
		echo $ym_formgen->render_form_table_radio_row(__('TimeWarp'), 'timewarp', $timewarp, __('If used must schedule at least 24 hours ahead. Timewarp makes the email arrive at xhours in the relevant TimeZone'));
		
//		echo $ym_formgen->render_form_table_datetime_picker_row('Send At', 'email_date', $time, __('Date/Time to send Message'));
	}
	function broadcast_create($email_id, $email_subject, $email_content, $recipient_list, $time) {
		define('STOP_BROADCAST', TRUE);
		global $mm, $ym_formgen, $time;
		
		$items = $this->generate_message_generate($email_subject, $recipient_list);
		$c_id = $this->mailchimp->campaignCreate('regular', $items['data'], $items['content'], $items['segment_opts']);

		if ($this->mailchimp->error == 'ok') {
			// schdule/test options
			echo '<div id="message" class="updated"><p>' . __('Campaign has been created') . '</p></div>';
			ym_box_top();
			
			$this->scheduling($c_id);
		} else {
			echo '<div id="message" class="error"><p>' . __('The Campaign failed to be created') . '</p><p>' . $this->mailchimp->error . '</p></div>';
		}
	}
	
	/*************************************************************************
	* CREATE
	**************************************************************************/
	function create_form($has_id = FALSE) {
		global $ym_formgen;
		define('MAILMANAGER_FORM_REPLACED', TRUE);
		
		if (!$has_id) {
			echo '<p>' . __('You can create a draft campaign to send later') . '</p>';
		}
		
		echo '<table class="form-table">';
		echo '<tr><td colspan="2">
		<p>' . __('A MailChimp Broadcast is called a Campaign') . '</p>
		<input type="hidden" name="email_name" value="1" />
		</td></tr>';// Make sure we don't get stuck in the broadcast error system
		
		$this->generate_message_create_form($has_id);
		echo $ym_formgen->render_form_table_radio_row(__('TimeWarp'), 'timewarp', $timewarp, __('If used must schedule at least 24 hours ahead. Timewarp makes the email arrive at xhours in the relevant TimeZone'));
	} 
	function create_create($email_id, $email_subject, $email_content) {
		define('STOP_CREATE', TRUE);
		
		$recipient_list = ym_post('recipient_list');
		
		$items = $this->generate_message_generate($email_subject, $recipient_list);
		$c_id = $this->mailchimp->campaignCreate('regular', $items['data'], $items['content'], $items['segment_opts']);
		
		if ($this->mailchimp->error == 'ok') {
			ym_box_bottom();
			echo '<div id="message" class="updated"><p>' . __('The Campaign has been saved as a Draft') . '</p></div>';
			unset($_POST);
			$this->view_emails();
		} else {
			echo '<div id="message" class="error"><p>' . __('The Campaign failed to be created') . '</p><p>' . $this->mailchimp->error . '</p></div>';
			// need to route back
		}
	}
	
	/*************************************************************************
	* VIEW
	**************************************************************************/
	function view_emails() {
		global $mm;
		
		if (defined('BROADCAST_FORM_OPEN')) {
			echo '</table>';
			echo '</form>';
			ym_box_bottom();
			// need to tweak the form for delete here!!
			// if from broadcast!!!
		}
		
		define('STOP_VIEW_EMAILS', TRUE);
		
		if ($_POST) {
			// tasks
			$email_id = ym_post('email_id');
			$action = ym_post('action');
			
			if ($action && $email_id) {
				switch ($action) {
					case 'schedule':
						$this->scheduling($email_id);
						return;
					case 'edit':
						$this->create_form($email_id);
						return;
					case 'delete':
						ym_box_top('Are you sure you wish to delete this Campaign?');
						echo '<form action="" method="post">
	<input type="hidden" name="email_id" value="' . $email_id . '" />
	<input type="hidden" name="action" value="deletego" />
	<p class="submit" style="margin-left: 45%;">
		<input type="button" value="' . __('No') . '" onclick="javascript:history.go(-1);" />
		<input type="submit" value="' . __('Yes') . '" />
	</p>
</form>
';
						ym_box_bottom();
						return;
					case 'deletego':
						if ($this->mailchimp->campaignDelete($email_id)) {
							echo '<div id="message" class="updated"><p>' . __('Campaign was deleted') . '</p></div>';
						} else {
							echo '<div id="message" class="error"><p>' . __('Campaign was not deleted') . '</p></div>';
						}
				}
			}
		}
		
		$options = array(
			'list_id'	=> $this->options->list,
			'type'		=> 'regular',
			'status'	=> 'save',
		);
		$emails = $this->mailchimp->campaigns($options);
		ym_box_top(__('Drafts'));
		$this->email_list($emails);
		ym_box_bottom();

		$options['status'] = 'paused';
		$emails = $this->mailchimp->campaigns($options);
		if ($emails->total) {
			ym_box_top(__('Paused'));
			$this->email_list($emails);
			ym_box_bottom();
		}
		
		$options['status'] = 'scheduled';
		$emails = $this->mailchimp->campaigns($options);
		if ($emails->total) {
			ym_box_top(__('Scheduled'));
			$this->email_list($emails);
			ym_box_bottom();
		}
		
		$options['status'] = 'sending';
		$emails = $this->mailchimp->campaigns($options);
		if ($emails->total) {
			ym_box_top(__('Active/Sending'));
			$this->email_list($emails);
			ym_box_bottom();
		}

		$options['status'] = 'sent';
		$emails = $this->mailchimp->campaigns($options);
		ym_box_top(__('Sent'));
		$this->email_list($emails);
		ym_box_bottom();
		
		return;
	}
	
	/*
	function view_emails_filter($emails) {
		// get all draft emails
		$r = $this->mailchimp->campaigns(array(
			'status'	=> 'save',
			'list_id'	=> $this->options->list
		));
		
		$emails = array();
		
		foreach ($r->data as $email) {
			$obj = new StdClass();
			
			$obj->id = $email->id;
			$obj->name = $email->title;
			$obj->subject = $email->subject;

			$emails[] = $obj;
		}
		
		return $emails;
	}
	*/
	
	function email_preview($id) {
		define('STOP_PREVIEW', TRUE);
		
		if (!$id) {
			return;
		}
		$data = $this->mailchimp->campaignContent($id);
		echo $data->html;
	}
	
	/*************************************************************************
	* SERIES
	**************************************************************************/
	function series_replace() {
		global $ym_formgen, $mm;

		define('MM_SERIES_REPLACED', TRUE);
		
		$action = $_POST['action'];
		
		$offset_units = array(
			'day',
			'week',
			'month',
			'year'
		);
		
		switch($action) {
			case 'add':
				$email_subject = ym_post('email_subject');
				$recipient_list = ym_post('recipient_list');

				$items = $this->generate_message_generate($email_subject, $recipient_list);
				
				$type_opts = array(
					'offset-units'		=> $offset_units[ym_post('offset_units')],
					'offset-time'		=> ym_post('offset_time'),
					'offset-dir'		=> 'after',
					'event'				=> 'signup',// tis the default
				);
				
				$c_id = $this->mailchimp->campaignCreate('auto', $items['data'], $items['content'], $items['segment_opts'], $type_opts);
				
				if ($this->mailchimp->error == 'ok') {
					echo '<div id="message" class="updated"><p>' . __('The Auto Responder has been saved activated') . '</p></div>';
//					echo '<meta http-equiv="refresh" content="5;' . $mm->page_root . '&mm_action=series" />';
					unset($_POST);
					$_POST['action'] = 'start';
					$_POST['email_id'] = $c_id;
					$this->series_replace();
					return;
				} else {
					echo '<div id="message" class="error"><p>' . __('The Auto Responder failed to be created') . '</p><p>' . $this->mailchimp->error . '</p></div>';
				}
				return;
			case 'new':
				ym_box_top(__('Creating a new Auto Responder'));
				echo '<form action="" method="post">';
				echo '<table class="form-table">';
				
				$this->generate_message_create_form();
				// additionals
				// offset
				echo '<tr><td><input type="hidden" name="action" value="add" /></td><td>' . __('Send this message to the user, how long after joining/being added to the list') . '</td></tr>';
				$offset_times = array();
				for ($x=0;$x<24;$x++) {
					$offset_times[] = $x;
				}
				unset($offset_times[0]);// easy fix like a boss
				$offset_dir = 'after';
				
				echo $ym_formgen->render_combo_from_array_row(__('Offset Units'), 'offset_units', $offset_units, $offset_unit, __('Time Units'));
				echo $ym_formgen->render_combo_from_array_row(__('Offset Time'), 'offset_time', $offset_times, $offset_time, __('How much of the unit'));
				
				echo '</table>';
				echo '<p class="submit" style="float: right;"><input type="submit" value="' . __('Add AutoResponder') . ' " /></p>';
				echo '</form>';
				ym_box_bottom();
				return;
			
			case 'delete':
				$this->view_emails();
				return;
			case 'start':
				$email_id = ym_post('email_id');
				
				if ($this->mailchimp->campaignResume($email_id)) {
					echo '<div id="message" class="updated"><p>' . __('Auto Responder has been enabled') . '</p></div>';
				} else {
					echo '<div id="message" class="error"><p>' . __('Failed to Start the Auto Responder') . '</p></div>';
				}
			case 'deletego':
				if ($action == 'deletego') {
					$email_id = ym_post('email_id');
					if ($this->mailchimp->campaignDelete($email_id)) {
						echo '<div id="message" class="updated"><p>' . __('Your Auto Responder was deleted') . '</p></div>';
					} else {
						echo '<div id="message" class="error"><p>' . __('Your Auto Responder was not deleted') . '</p></div>';
					}
				}
			case 'pause':
				if ($action == 'pause') {
					$email_id = ym_post('email_id');

					if ($this->mailchimp->campaignPause($email_id)) {
						echo '<div id="message" class="updated"><p>' . __('Auto Responder has been Paused') . '</p></div>';
					} else {
						echo '<div id="message" class="error"><p>' . __('Failed to Pause the Auto Responder') . '</p></div>';
					}
				}
			
			default:
				// get responders
				$options = array(
					'list_id'	=> $this->options->list,
					'type'		=> 'auto',
					'status'	=> 'save',
				);
				$emails = $this->mailchimp->campaigns($options);
				ym_box_top(__('Drafts'));
				$this->email_list($emails);
				ym_box_bottom();

				$options['status'] = 'paused';
				$emails = $this->mailchimp->campaigns($options);
				if ($emails->total) {
					ym_box_top(__('Paused'));
					$this->email_list($emails);
					ym_box_bottom();
				}

				$options['status'] = 'schedule';
				$emails = $this->mailchimp->campaigns($options);
				if ($emails->total) {
					ym_box_top(__('Scheduled'));
					$this->email_list($emails);
					ym_box_bottom();
				}

				$options['status'] = 'sending';
				$emails = $this->mailchimp->campaigns($options);
				if ($emails->total) {
					ym_box_top(__('Active/Sending'));
					$this->email_list($emails);
					ym_box_bottom();
				}

				$options['status'] = 'sent';
				$emails = $this->mailchimp->campaigns($options);
				if ($emails->total) {
					ym_box_top(__('Sent'));
					$this->email_list($emails);
					ym_box_bottom();
				}
				
				ym_box_top(__('Actions'));
				
				echo '
<form action="" method="post">
	<fieldset>
		<p class="submit">
			<input type="hidden" name="action" value="new" />
			<input type="submit" value="' . __('Create New') . '" />
		</p>
	</fieldset>
</form>';
				ym_box_bottom();
		}
	}
	// End Hooks
	
	// handy functions for this gateway
	private function class_construct($key = FALSE) {
		require_once(YM_MM_GATEWAY_DIR . $this->safe_name . '/mailchimp_class.php');
		if (!$key) {
			$key = $this->options->apikey;
		}
		$this->mailchimp = new MailChimp($key);
		return $this->mailchimp->ping();
	}
	private function get_lists($is_option = FALSE) {
		$lists = $this->mailchimp->lists();
		if (!count($lists->data)) {
			ym_box_top($this->name . ' Error');
			echo '<p>' . __('You have no lists on ' . $this->name . ' to associate with') . '</p>';
			ym_box_bottom();
			return;
		}
		$list_data = array();
		if ($is_option) {
			$list_data[] = __('--Select--');
		}
		foreach ($lists->data as $data) {
			$list_data[$data->id] = $data->name;
		}
		return $list_data;
	}
	
	private function generate_message_create_form($has_id = FALSE) {
		global $ym_formgen, $recipient_list, $email_subject, $email_message, $time;
		
		if ($has_id) {
			// preload........
			$email_data = $this->mailchimp->campaigns(array('campaign_id' => $has_id));
			// template is broke till bug fix
//			$template_data = $this->mailchimp->campaignTemplateContent($has_id);
			
			if ($email_data->total != 1) {
				echo '<div id="message" class="error"><p>' . __('Failed to find that campaign') . '</p></div>';
				return;
			}

			$email_data = $email_data->data[0];
			$recipient_list = $segment_opts->conditions[0]->value;// THERE CAN BE ONLY ONE!
			$email_subject = $email_data['subject'];
			$email_message = 'fuuuu';
			$authenticate = $email_data['authenticate'];
//			$timewarp = $email_data['timewarp'];
//			echo '<pre>';
//			print_r($email_data);
			/*
			* 
			* Due to a bug in the MailChimp API pertaining to templates
			* Can't finish the editor at this time
			* AS the template/content merge on campaign create is borked
			* They are on a code freeze so can't fix the bug currently.....
			* 
			*/
			exit;
		}
		
		//list AKA SEGMENT
		echo $ym_formgen->render_combo_from_array_row(__('Recipients'), 'recipient_list', mailmanager_get_recipients(), $recipient_list, __('Select a List to send to'));
		echo $ym_formgen->render_form_table_text_row(__('Message Subject'), 'email_subject', $email_subject, __('Keep it relevant and non Spammy to avoid Spam Filters'));
		
		// the message
		echo '<tr><td>';
		
		if ($has_id) {
			echo '<input type="hidden" name="mm_editing_email" value="' . $has_id . '" />';
		}
		
		echo '</td><td>' . __('You will only need to edit the component tagged as content, but you can edit any template section you need to') . '</td></tr>';

		$type = 'user';
		if (substr($this->options->default_template, 0, 2) == 'b_') {
			$this->options->default_template = substr($this->options->default_template, 2);
			$type = 'base';
		}
		$template = $this->mailchimp->templateInfo($this->options->default_template, $type);
		
		foreach ($template->sections as $section) {
			echo '<tr><td colspan="2"><hr /></td></tr>';
			echo $ym_formgen->render_form_table_textarea_row(__('Template Part: ') . $section, $section, $template->default_content->$section);
		}
		echo '<tr><td colspan="2"><hr /></td></tr>';
		
		// additional options
//		$timewarp		= $_POST['timewarp'] ? $_POST['timewarp'] : 0;
		$authenticate	= $_POST['authenticate'] ? $_POST['authenticate'] : 0;

		echo $ym_formgen->render_form_table_radio_row(__('Authenticate'), 'authenticate', $authenticate, __('Enable SenderID, DomainKeys and DKIM Authentication'));
//		echo $ym_formgen->render_form_table_radio_row(__('TimeWarp'), 'timewarp', $timewarp, __('If used must schedule at least 24 hours ahead. Timewarp makes the email arrive at xhours in the relevant TimeZone'));
	}
	private function generate_message_generate($email_subject, $recipient_list) {
		// build
		$type = 'user';
		if (substr($this->options->default_template, 0, 2) == 'b_') {
			$this->options->default_template = substr($this->options->default_template, 2);
			$type = 'base';
		}
		$template = $this->mailchimp->templateInfo($this->options->default_template, $type);
		
		$email_content = $template->source;
		foreach ($template->sections as $section) {
			$email_content = str_replace($template->default_content->$section, stripslashes($_POST[$section]), $email_content);
		}

		$options = array(
			'match'			=> 'all',
			'conditions'	=> array(
				array(
					'field'			=> 'static_segment',
					'op'			=> 'eq',
					'value'			=> $this->associations->$recipient_list
				)
			)
		);

		$authenticate = $_POST['authenticate'] ? $_POST['authenticate'] : 0;
		$timewarp = $_POST['timewarp'] ? $_POST['timewarp'] : 0;

		$to_name = '*|FNAME|*';
		$generate_text = TRUE;

		$data = array(
			'list_id'			=> $this->options->list,
			'subject'			=> $email_subject,
			'from_email'		=> $this->options->from_email,
			'from_name'			=> $this->options->from_name,
			'to_name'			=> $to_name,

//			'template_id'		=> $this->options->default_template,
			'auto_footer'		=> TRUE,

			'authenticate'		=> $authenticate,
			'generate_text'		=> $generate_text,
			'timewarp'			=> $timewarp,

			'inline_css'		=> FALSE,
		);
		$content = array(
			'html'				=> $email_content
		);
		$segment_opts = $options;

		return array(
			'data'			=> $data,
			'content'		=> $content,
			'segment_opts'	=> $segment_opts
		);
	}
	
	private function scheduling($c_id) {
		global $mm, $ym_formgen;
		
		$email_id = $_POST['email_id'];
		$time = $_POST['email_time'];
		
		if ($time) {
			if ($time == 'test') {
				$test_email = $_POST['test_email'];
				
				$r = $this->mailchimp->campaignSendTest($email_id, array($test_email));
				if ($this->mailchimp->error == 'ok') {
					echo '<div id="message" class="updated"><p>' . __('A Test email has been sent') . '</p></div>';
				} else {
					echo '<div id="message" class="error"><p>' . $this->mailchimp->error_message . '</p></div>';
				}
			} else if ($time == 'now') {
				$r = $this->mailchimp->campaignSendNow($email_id);
				if ($this->mailchimp->error == 'ok') {
					echo '<div id="message" class="updated"><p>' . __('Campaign has been scheduled') . '</p></div>';
					unset($_POST);
					$this->view_emails();
					return;
				} else {
					echo '<div id="message" class="error"><p>' . $this->mailchimp->error_message . '</p></div>';
				}
			} else {
				$ym_month_email_date = $_POST['month'];
				$ym_date_email_date = $_POST['date'];
				$ym_year_email_date = $_POST['year'];
				$ym_hour_email_date = $_POST['hour'];
				$ym_min_email_date = $_POST['min'];

				$time = array($ym_month_email_date, $ym_date_email_date, $ym_year_email_date, $ym_hour_email_date, $ym_min_email_date);
				$value = array();
				$value['month'] = array_shift($time);
				$value['date'] = array_shift($time);
				$value['year'] = array_shift($time);
				$value['hour'] = array_shift($time);
				$value['min'] = array_shift($time);
				$time = mktime($value['hour'], $value['min'], 0, $value['month'], $value['date'], $value['year']);

				// adjust for idiots
				if ($time < time() + 60) {
					$time = time() + 60;
				}

				$r = $this->mailchimp->campaignSchedule($email_id, $time);
				if ($this->mailchimp->error == 'ok') {
					echo '<div id="message" class="updated"><p>' . __('Campaign has been scheduled') . '</p></div>';
					unset($_POST);
					$this->view_emails();
					return;
				} else {
					echo '<div id="message" class="error"><p>' . $this->mailchimp->error_message . '</p></div>';
				}
			}
		}

		// use tab # hash as a url to ajax load
		// just css screw us up
		// os sand box
		echo '
<form action="" method="post" class="mailmanager_schedule_sendnow" style="float: right;">
	<fieldset>
		<input type="hidden" name="action" value="schedule" />
		<input type="hidden" name="email_id" value="' . $c_id . '" />
		<input type="hidden" name="email_time" value="now" />
		<p class="submit">' . __('Or you can:') . '</p>
		<input type="submit" value="' . __('Send Now!') . '" /></p>
	</fieldset>
</form>
';
		echo '<p>' . __('You can now preview, test and schedule your email now') . '</p>';

		echo '
<div id="mailmanager_post_create" style="clear: both;">
	<ul>
		<li><a href="#mailmanager_preview">Email Preview</a></li>
		<li><a href="#mailmanager_test">Send Test Email</a></li>
		<li><a href="#mailmanager_schedule">Schedule</a></li>
	</ul>
	
	<div id="mailmanager_preview">
		<iframe style="width: 950px; height: 600px;" src="' . $mm->page_root . '&mm_action=preview&iframe_preview=' . $c_id . '"></iframe>
	</div>
	<div id="mailmanager_test">
		<form action="" method="post" id="testform">
			<fieldset>
				<input type="hidden" name="action" value="schedule" />
				<input type="hidden" name="email_id" value="' . $c_id . '" />
				<input type="hidden" name="email_time" value="test" />
				<label for="test_email">' . __('Email Address to send the test to:') . '
					<input type="text" name="test_email" id="test_email" value="' . $this->options->from_email . '" style="width: 250px;" />
				</label>
				<input type="submit" value="' . __('Send Test Email') . '" />
			</fieldset>
		</form>
	</div>
	<div id="mailmanager_schedule">
		<form action="" method="post">
			<fieldset>
				<input type="hidden" name="action" value="schedule" />
				<input type="hidden" name="email_id" value="' . $c_id . '" />
				<input type="hidden" name="email_time" value="atime" />
				<table class="form-table">
					';
					$ym_formgen->render_form_table_datetime_picker_row('Send At', 'email_date', $time, __('Date/Time to send Message'));
					echo '
				</table>
				<p class="submit"><input type="submit" value="' . __('Schedule Message') . '" /><p>
			</fieldset>
		</form>
		<form action="" method="post">
			<fieldset>
				<input type="hidden" name="action" value="schedule" />
				<input type="hidden" name="email_id" value="' . $c_id . '" />
				<input type="hidden" name="email_time" value="now" />
				<p>' . __('Or you can:') . '</p>
				<p class="submit"><input type="submit" value="' . __('Send Now!') . '" /></p>
			</fieldset>
		</form>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery(\'#mailmanager_post_create\').tabs({
			selected: 0
		});
	});
</script>';
			
		ym_box_bottom();
	}
	
	private function email_list($emails) {
		global $mm;
		
		if ($emails->total == 0) {
			echo '<p>' . __('There are no emails to display') . '</p>';
			return;
		}
		echo '<table class="form-table">';
		echo '<tr><th>Email Name</th><th>Email Subject</th><th>Preview</th><th>Opens(Unqiue Opens)/Sent</th></tr>';

		foreach ($emails->data AS $email) {
			echo '<tr>';
			echo '<td>' . $email->title . '</td>';//' (' . $email->type . '/' . $email->status . ')</td>';
			echo '<td>' . $email->subject . '</td>';
			echo '<td><a href="' . $mm->page_root . '&mm_action=preview&iframe_preview=' . $email->id . '" class="previewemail">Email Preview</a></td>';
			echo '<td>';

			$do_stats = FALSE;
			if ($email->status == 'sent') {
				// get additional statistics
				$do_stats = TRUE;
			} else if ($email->status == 'save') {
				if ($email->send_time) {
					echo __('Scheduled:') . ' ' . $email->send_time;
				} else {
					if ($email->type == 'auto') {
						$do_stats = TRUE;
					} else {
						echo __('Draft');
						// schedule
					}
				}
//			} else if ($email->status == 'sending') {
//				$do_stats = TRUE;
			} else if ($email->type == 'auto') {
				$do_stats = TRUE;
			}

			if ($do_stats) {
				$stats = $this->mailchimp->campaignStats($email->id);
				echo $stats->opens . '(' . $stats->unique_opens . ')/' . $stats->emails_sent;
				echo '</td><td>';
				echo '<a href="https://' . $this->mailchimp->getZone() . '.admin.mailchimp.com/reports/summary?id=' . $email->web_id . '">' . __('Stats') . '</a>';
			} else {
				echo '</td><td>';
			}
			
			echo '</td><td nowrap="nowrap">';
			
			// auto schedule
			if ($email->type == 'auto') {
				$type_opts = (array)$email->type_opts;
				echo $type_opts['offset-time'] . ' ' . $type_opts['offset-units'] . ' ' . __('after joining the list');
			} else if ($email->status == 'schedule') {
				echo __('Scheduled: ') . $email->send_time . ' ' . __('GMT');
			}

			echo '</td><td nowrap="nowrap">';
			
			echo '
<form action="" method="post" class="deletecheck">
	<input type="hidden" name="email_id" value="' . $email->id . '" />
	<select name="action">
		<option value="">' . __('Actions') . '</option>';
		
		if (!$email->send_time) {
			if ($email->type != 'auto' && $email->status != 'sending') {
				echo '<option value="schedule">' . __('Schedule') . '</option>';
			}
		}
		if ($email->type == 'auto') {
			if ($email->status != 'sending') {
				echo '<option value="start">' . __('Start') . '</option>';
			} else {
				echo '<option value="pause">' . __('Pause') . '</option>';
			}
		}
		
//		<option value="edit">' . __('Edit') . '</option>
		echo '
		<option value="delete">' . __('Delete') . '</option>
	</select>
	<input type="submit" value="->" />
</form>
';
			echo '</td>';
			echo '</tr>';
		}

		echo '</table>';
	}
	
	function change_user_list($user) {
		$id = $user['user_id'];
		
		// get user email
		$data = get_userdata($id);
		$email = $data->user_email;

		// get list/segment user is on
		$info = $this->mailchimp->listMemberInfo($this->options->list, $email);
		if (!$info->success) {
			return;
		}

		// what should the user be on?
		// data
		$data = new YourMember_User($id);
		if (!$data) {
			return;
		}

		$account_type = $data->account_type;
		$pack_id = $data->pack_id;
		$status = $data->status;
		
		// new data
		$segment_account_type = 'ym_ac_' . strtolower($account_type);
		$segment_pack_id = 'ym_pack_' . $pack_id;
		$segment_all = 'ym_all_' . strtolower($status);

		$should_be_on = array();
		if ($this->associations->$segment_account_type) {
			$should_be_on[] = $this->associations->$segment_account_type;
		}
		if ($this->associations->$segment_pack_id) {
			$should_be_on[] = $this->associations->$segment_pack_id;
		}
		if ($this->associations->$segment_all) {
			$should_be_on[] = $this->associations->$segment_all;
		}
		foreach ($info->data[0]->static_segments as $segment) {
			if (!in_array($segment->id, $should_be_on)) {
				
				// remove
				$r = $this->mailchimp->listStaticSegmentMembersDel($this->options->list, $segment->id, array($email));
				
			}
		}
		
		foreach ($should_be_on as $segment) {
			$r = $this->mailchimp->listStaticSegmentMembersAdd($this->options->list, $segment, array($email));
		}
	}
	// end handy
	
	function resetStaticSegments() {
		foreach ($this->associations as $name => $segment_id) {
			echo $segment_id . '-' . $this->mailchimp->listStaticSegmentReset($this->options->list, $segment_id) . '<br />';
		}
	}

	// cron
	// sync sync reboot
	// sync sync
	function sync_with_gateway() {
		global $wpdb;
		if ($_GET['mm_action']) {
			if (!strpos($_SERVER['SERVER_SOFTWARE'], 'Debian')) {
				ob_end_flush();
				// echo as we go
				ob_implicit_flush(true);
			}
			
			echo '<pre>';
			echo 'Syncing with Gateway' . "\n";
		}

		if ($_GET['mm_action']) {
			echo 'Syncing YM Custom Fields with MailChimp Merge Vars' . "\n";
			
		}
		$local_fields = $live_fields = $fields_to_add = array();
		$fields = get_option('ym_custom_fields');

		$order = explode(';', $fields->order);

		$words = $custom_field_data = array();
		foreach ($order as $index) {
			$field = ym_get_custom_field_by_id($index);

			if (
				$field['name'] != 'subscription_introduction' &&
				$field['name'] != 'subscription_options' &&
				strlen($field['name']) <= 10 &&
				$field['name'] != 'first_name' &&//replaced
				$field['name'] != 'last_name' &&//replaced
				$field['name'] != 'user_email' &&
				$field['name'] != 'ym_password' &&//sec
				$field['name'] != 'user_url'//reserved
			) {
				$word = strtoupper($field['name']);
				$local_fields[] = $word;
			
				$words[$word] = $field['name'];
				if ($field['name'] == 'birthdate') {
					$field['type'] = 'birthday';
				}
				$custom_field_data[$word] = $field;
			}
		}

		$live_fieldsd = $this->mailchimp->listMergeVars($this->options->list);
		if ($live_fieldsd->error) {
			echo 'An Error Occured';
			return;
		}
		// process
		foreach ($live_fieldsd as $field) {
			$live_fields[] = $field->tag;
		}
		
		$fields_to_add = array_diff($local_fields, $live_fields);

		foreach ($fields_to_add as $field) {
			switch ($custom_field_data[$field]['type']) {
				case 'birthday':
					$type = 'birthday';
					break;
				case 'text':
				default:
					$type = 'text';
			}
			$r = $this->mailchimp->listMergeVarAdd($this->options->list, $field, $words[$field], array('field_type' => $type));
			if ($_GET['mm_action']) {
				echo 'Result for: ' . $field . ' - ' . $r . "\n";
			}
		}
		
		if ($_GET['mm_action']) {
			echo 'Syncing All Users to list' . "\n";
		}
		
		$all_local_users = $all_live_users_subscribed = $all_live_users_unsubscribed = array();
		
		$list = 'wordpress_users';
		
		$subd = $this->mailchimp->listMembers($this->options->list, 'subscribed', FALSE, FALSE, 15000);
		$unsubd = $this->mailchimp->listMembers($this->options->list, 'unsubscribed', FALSE, FALSE, 15000);

		if ($subd->error || $unsubd->error) {
			// error
			echo 'An Error Occured';
			return;
		}
		foreach ($subd->data as $user) {
			$all_live_users_subscribed[] = $user->email;
		}
		foreach ($unsubd->data as $user) {
			$all_live_users_unsubscribed[] = $user->email;
		}
		
		foreach ($wpdb->get_results(mailmanager_get_sql($list)) AS $row) {
			$all_local_users[] = strtolower($row->email);
		}
		
		if ($_GET['mm_action']) {
			echo 'There are locally: ' . sizeof($all_local_users) . "\n";
			echo 'There are live: ' . sizeof($all_live_users_subscribed) . " Subscribed\n";
			echo 'There are live: ' . sizeof($all_live_users_unsubscribed) . " Unsubcribed\n";
		}
		
		// sync delete
		// umsubed
		// clean all in order to handle resubscribe.....
		$sql = 'DELETE FROM ' . $wpdb->prefix . 'mm_list_unsubscribe WHERE list_name = \'' . $this->options->list . '\'';
		$wpdb->query($sql);
		foreach ($all_live_users_unsubscribed as $user_email) {
			$user_id = get_user_by_email($user_email);
			$user_id = $user_id->ID;
			
			// log unsub
			mailmanager_unsubscribe_user($this->options->list, $user_id);
		}
		
		$users_to_add = array_diff($all_local_users, $all_live_users_subscribed, $all_live_users_unsubscribed);
		if ($_GET['mm_action']) {
			echo 'There are ' . sizeof($users_to_add) . ' Users to add' . "\n";
		}
		
		include(YM_MM_INCLUDES_DIR . 'countries.inc.php');
		
		foreach ($users_to_add as $user_email) {
			$data = get_user_by_email($user_email);
			
			$merge = array();
			$merge = array(
				'FNAME'	=> $data->first_name,
				'LNAME'	=> $data->last_name
			);
			
			$fields = ym_get_custom_field_array($data->ID);
			foreach ((array) $fields as $field => $value) {
				$merge[strtoupper(str_replace(' ', '_', $field))] = $value;
			}
			if ($merge['COUNTRY']) {
				$merge['COUNTRY'] = $countries[$merge['COUNTRY']];
			}
			if (isset($merge['BIRTHDATE']) && $merge['BIRTHDATE']) {
				$merge['BIRTHDATE'] = explode('-', $merge['BIRTHDATE']);
				// m d y
//				$merge['BIRTHDATE'] = $merge['BIRTHDATE'][2] . '-' . $merge['BIRTHDATE'][0] . '-' . $merge['BIRTHDATE'][1];
				$merge['BIRTHDATE'] = $merge['BIRTHDATE'][0] . '/' . $merge['BIRTHDATE'][1];
			}

			if ($_GET['mm_action']) {
				echo 'Adding ' . $user_email . "\n";
			}
			$this->mailchimp->listSubscribe($this->options->list, $user_email, $merge, $this->options->double_opt_in, $this->options->welcome_message);
		}
		// for exitinst subscribed update fields
		if ($_GET['mm_action']) {
			echo 'Updating existing users' . "\n";
		}
		$counter = 0;
		foreach ($all_live_users_subscribed as $index => $user_email) {
			$data = get_user_by_email($user_email);
			
			$merge = array();
			$merge = array(
				'FNAME'	=> $data->first_name,
				'LNAME'	=> $data->last_name
			);
			
			$fields = ym_get_custom_field_array($data->ID);
			foreach ((array) $fields as $field => $value) {
				$merge[strtoupper($field)] = $value;
			}
			if (isset($merge['COUNTRY'])) {
				$merge['COUNTRY'] = $countries[$merge['COUNTRY']];
			}
			if (isset($merge['BIRTHDATE']) && $merge['BIRTHDATE']) {
				$merge['BIRTHDATE'] = explode('-', $merge['BIRTHDATE']);
				// m d y
//				$merge['BIRTHDATE'] = $merge['BIRTHDATE'][2] . '-' . $merge['BIRTHDATE'][0] . '-' . $merge['BIRTHDATE'][1];
				$merge['BIRTHDATE'] = $merge['BIRTHDATE'][0] . '/' . $merge['BIRTHDATE'][1];
			}
			echo $this->mailchimp->listUpdateMember($this->options->list, $user_email, $merge);
			$counter++;
			if ($counter >= 20) {
				$counter = 0;
				echo ' ' . ($index + 1) . '/' . count($all_live_users_subscribed);
				echo "\n";
			}
		}
		echo "\n";

		// hadnle lists and segments
		if ($_GET['mm_action']) {
			echo 'General Sync Complete - Moving on to YM MM List/Segmentation' . "\n";
		}
		// what segments exists
		// if list associations are to a segment.....

		// validate segemetns
		// in case deleted server side

		// server side
		$segments = $this->mailchimp->listSegments($this->options->list);
		$this->associations = new StdClass();
		foreach ($segments as $segment) {
			$name = $segment->name;
			$this->associations->$name = $segment->id;
		}
		$this->saveassociations();
		// any segments left to make?
		foreach (mailmanager_get_recipients() as $nice_key => $word_name) {
			$assoc = $this->associations->$nice_key;
			if (!$assoc) {
				if ($_GET['mm_action']) {
					echo 'Creating Segment: ' . $nice_key . "\n";
				}
				// the ym list needs a segment
				if ($id = $this->mailchimp->listStaticSegmentAdd($this->options->list, $nice_key)) {
					$this->associations->$nice_key = $id;
					$assoc = $id;
					// just to be safe in case of crash
					$this->saveassociations();
				} else {
					if ($_GET['mm_action']) {
						echo 'There was an error, creating a list segment' . "\n";
					}
					return;
				}
				// ought to error catch.....
			}
		}
		// just in case
		$this->saveassociations();
		
		$emails = array();
		foreach ($this->associations as $nice_key => $word_name) {
			foreach ($wpdb->get_results(mailmanager_get_sql($nice_key)) AS $row) {
				if ($row->email) {
					$emails[$row->email][] = $this->associations->$nice_key;
				}
			}
		}
		
		// member iterate
		$subd = $this->mailchimp->listMembers($this->options->list, 'subscribed', FALSE, FALSE, 15000);

		$counter = 0;
		foreach ($subd->data as $index => $user) {
			$email = $user->email;
			
			// ping
			// should generally do nothing
			// but in case admin switch the level
			// or the ipn was missed
			// do this first to stop multiple existings
			$user_data = get_user_by_email($email);
			if ($user_data) {
				$this->change_user_list(array('user_id' => $user_data->ID));
			}
			// obeslete below now?
			
			$data = $this->mailchimp->listMemberInfo($this->options->list, $email);
			$segments = array();
			foreach ((array)$data->data[0]->static_segments as $segment) {
				$segments[] = $segment->id;
			}
			$diff = array_diff((array) $emails[$email], $segments);

			// add or remove a user from a segment
			foreach ($diff as $item) {
				$r = $this->mailchimp->listStaticSegmentMembersAdd($this->options->list, $item, array($email));
				if ($_GET['mm_action']) {
					echo 'a';
//					echo 'Adding ' . $email . ' to ' . $item . "\n";
					if ($r->success) {
						echo $r->success;
					} else {
						echo $r->error;
					}
				}
			}

			$counter++;
			if ($counter >= 20) {
				$counter = 0;
				echo ' ' . ($index + 1) . '/' . count($subd->data);
				echo "\n";
			}
		}
		// complete
		if ($_GET['mm_action']) {
			echo 'Segmentation Complete' . "\n";
			echo 'WebHook check' . "\n";
		}

		$url = site_url() . '/?mm_webhook=1';
		if (FALSE === (stripos($url, 'localhost'))) {
			$hooks = $this->mailchimp->listWebHooks($this->options->list);
			
			$found = FALSE;
			foreach ($hooks as $hook) {
				if ($hook->url == $url) {
					$found = TRUE;
				}
			}
			if (!$found) {
				// can't handle upemail properly
				$this->mailchimp->listWebhookAdd($this->options->list, $url, '11111');
			}
		} else if ($_GET['mm_action']) {
			echo 'On Localhost' . "\n";
		}
		
		if ($_GET['mm_action']) {
			echo 'Checking for abuse/spam reports' . "\n";
		}
		// cron job, runs daily....
		// so get the last day only
		$reports = $this->mailchimp->listAbuseReports($this->options->list, 0, 1000, time() - 86400);
		if ($reports->total) {
			foreach ($reports->data as $report) {
				$email = $report['email'];
				$user = get_user_by_email($email);
				$user_id = $user->ID;
				@ym_log_transaction(YM_USER_STATUS_UPDATE, 'Inactive', $user_id);
				update_user_option($user_id, 'ym_status', 'Inactive', true);
				
				if ($_GET['mm_action']) {
					echo $email . ' reported spam removing/inactivising' . "\n";
				}
			}
		}
		
		if ($_GET['mm_action']) {
			echo 'All Done' . "\n";
			echo '</pre>';
			
			if (!strpos($_SERVER['SERVER_SOFTWARE'], 'Debian')) {
				ob_flush();
				// restart wordpress ob
				ob_start();
			}
		}
	}
	
	function webhook($post) {
		if ($post['data']['list_id'] == $this->options->list) {
			// list id matches
			$email = $post['data']['email'];
			//$action = $post['type'];
			
			$user = get_user_by_email($email);
			$user_id = $user->ID;
			
			if (!$user_id) {
				// unable to ID
				exit;
			}
			
			// actions
			// subscribe unsubscribe profile cleaned upemail
			$task = $this->options->unsub_action;
			switch ($task) {
				case 'disable':
					// disblae the account
					@ym_log_transaction(YM_USER_STATUS_UPDATE, 'Inactive', $user_id);
					update_user_option($user_id, 'ym_status', 'Inactive', true);
					break;
				case 'flag':
					// mail admin
					$sys = get_option('ym_sys');
					$from_email = ($sys->from_email != '' ? $sys->from_email:get_option('admin_email'));
					@wp_mail($from_email, __('YM MailManager: User Unsubcribed MailChimp'), 'The User ' . $email . ' User ID: ' . $user_id . ' has unsubscribed from MailChimp');
					break;
				case 'nothing':
				default:
					break;
			}
			// complete
			echo 'OK';
			exit;
		}
	}
	
	function mm_adjust_recipients($r) {
		unset($r['wordpress_commenters']);
		unset($r['wordpress_guest_commenters']);
		unset($r['wordpress_registered_commenters']);
		return $r;
	}
}

if ($_GET['mm_webhook']) {
	$mc = new mailmanager_mailchimp_gateway();
	$mc->webhook($_POST);
	exit;
}
if ($_GET['mm_preview']) {
	$message = $_GET['message'];
	echo $message;
	exit;
}

if ($_GET['template_test']) {
	$mc = new mailmanager_mailchimp_gateway();
	
	if ($_POST) {
		$type = 'user';
		
echo '<pre>';
		$tpl = $_POST['default_template'];
		if (substr($tpl, 0, 2) == 'b_') {
			$tpl = str_replace('b_', '', $tpl);
			$type = 'base';
		} else if (substr($tpl, 0, 2) == 'g_') {
			$tpl = str_replace('g_', '', $tpl);
			$type = 'gallery';
		}
		
		$info = $mc->mailchimp->templateInfo($tpl, $type);
		print_r($info->sections);
	
/*				$options = array(
					'match'			=> 'all',
					'conditions'	=> array(
						array(
							'field'			=> 'static_segment',
							'op'			=> 'eq',
							'value'			=> $mc->associations->wordpress_users
						)
					)
				);
*/
				$authenticate = 0;
				$timewarp = 0;

				$to_name = '*|FNAME|*';
				$generate_text = TRUE;
				
				$email_content = 'I AM TESTINGS';

				$data = array(
					'list_id'			=> $mc->options->list,
					'subject'			=> 'testing',
					'from_email'		=> $mc->options->from_email,
					'from_name'			=> $mc->options->from_name,
					'to_name'			=> $to_name,

		//			'template_id'		=> $this->options->default_template,
					'auto_footer'		=> TRUE,

					'authenticate'		=> $authenticate,
					'generate_text'		=> $generate_text,
					'timewarp'			=> $timewarp,

					'inline_css'		=> FALSE,
				);
				$content = array(
'html_std_content00'		=> array($email_content),
'html_main'			=> $email_content,


//					'html_std_utility'				=> $email_content,
//					'html_utility'			=> $email_content,
					/*
					'std_content00'			=> $email_content,
					'html_content00'		=> $email_content,
					'html_std_content00'	=> $email_content,

					'html_content'	=> $email_content,
					'html_std_content'	=> $email_content,
					*/
				);
//				$content = array();
//				$segment_opts = $options;
				
				$tpl = $_POST['default_template'];
				if (substr($tpl, 0, 2) == 'b_') {
					$tpl = str_replace('b_', '', $tpl);
//					$type = 'base';
					$data['base_template_id'] = $tpl;
				} else if (substr($tpl, 0, 2) == 'g_') {
					$tpl = str_replace('g_', '', $tpl);
					$data['gallery_template_id'] = $tpl;
				} else {
					$data['template_id'] = $tpl;
				}
				echo '<hr />';
print_r($data);
print_r($content);
//$content = array('html' => 'test');
				$c = $mc->mailchimp->campaignCreate('regular', $data, $content);//, $segment_opts);
				echo '<hr />';
				print_r($c);
				
				$r = $mc->mailchimp->campaignContent($c);
				echo '<hr />';
				print_r($r);
				echo '<hr />';
				$r = $mc->mailchimp->campaignTemplateContent($c);
				print_r($r);
				echo '<hr />';
				$mc->mailchimp->campaignDelete($c);
echo '</pre>';
	}
	
	$templates = $mc->mailchimp->templates(array('user' => TRUE, 'gallery' => true, 'base' => TRUE));
//				echo '<p>' . __('Showing Custom and Base templates only') . '</p>';
echo '<form action="" method="post">';
echo '<p>' . __('Showing Custom templates only') . '</p>';
echo '<input type="submit" />';
	echo '<table class="form-table">';
	echo '<tr>';
	$x = 0;
	foreach ($templates->user as $template) {
		$x++;
		if ($x > 4) {
			echo '</tr><tr>';
			$x = 1;
		}
		echo '<td style="text-align: center;">';
		echo '<label for="template_' . $template->id . '">';
//					if ($template->preview_image) {
			echo '<img src="' . $template->preview_image . '" style="width: 124px; height: 240px; background: #555; display: block; margin-left: auto; margin-right: auto;" />';
			echo '<br />';
//					}
		echo $template->name;
		echo '<br /><input type="radio" name="default_template" id="template_' . $template->id . '" value="' . $template->id . '" ';
		
		if ($template->id == $mc->options->default_template) {
			echo ' checked="checked" ';
		}
		
		echo '/>';
		echo '</td>';
	}
	foreach ($templates->base as $template) {
		$x++;
		if ($x > 4) {
			echo '</tr><tr>';
			$x = 1;
		}
		echo '<td style="text-align: center;">';
		echo '<label for="template_' . $template->id . '">';
//					if ($template->preview_image) {
			echo '<img src="' . $template->preview_image . '" style="width: 124px; height: 240px; background: #000;" />';
			echo '<br />';
//					}
		echo $template->name;
		echo '<br /><input type="radio" name="default_template" id="template_' . $template->id . '" value="b_' . $template->id . '" ';
		
		if ('b_' . $template->id == $mc->options->default_template) {
			echo ' checked="checked" ';
		}
		
		echo '/>';
		echo '</td>';
	}
	foreach ($templates->gallery as $template) {
		$x++;
		if ($x > 4) {
			echo '</tr><tr>';
			$x = 1;
		}
		echo '<td style="text-align: center;">';
		echo '<label for="template_' . $template->id . '">';
//					if ($template->preview_image) {
			echo '<img src="' . $template->preview_image . '" style="width: 124px; height: 240px; background: #000;" />';
			echo '<br />';
//					}
		echo $template->name;
		echo '<br /><input type="radio" name="default_template" id="template_' . $template->id . '" value="g_' . $template->id . '" ';
		
		if ('g_' . $template->id == $mc->options->default_template) {
			echo ' checked="checked" ';
		}
		
		echo '/>';
		echo '</td>';
	}
	echo '</tr>';
	echo '</table>';
	echo '</form>';
}
