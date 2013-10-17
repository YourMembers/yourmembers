<?php

/*
* $Id: ym-cron.include.php 2582 2013-01-31 16:45:45Z bcarlyon $
* $Revision: 2582 $
* $Date: 2013-01-31 16:45:45 +0000 (Thu, 31 Jan 2013) $
*/

require_once(YM_CLASSES_DIR . 'ym-user.class.php');

global $ym_crons_that_exist;
$ym_crons_that_exist = array(
	'ym_cron_email_reminder' => array(
		'task'		=> 'ym_cron_email_reminder',
		'core'		=> 1,
		'time'		=> array(23,59),
		'schedule'	=> 'daily',
	),
	'ym_cron_inactives' => array(
		'task'		=> 'ym_cron_inactives',
		'core'		=> 1,
		'time'		=> array(23,59),
		'schedule'	=> 'daily',
	),
	'ym_cron_cleanup' => array(
		'task'		=> 'ym_cron_cleanup',
		'core'		=> 1,
		'time'		=> array(23,59),
		'schedule'	=> 'daily',
	),
);
foreach ($ym_crons_that_exist as $index => $cron_job) {
	// check for alternative times
	$alt_time = get_option('ym_cron_alttime_' . $cron_job['task'], $cron_job['time']);
	$ym_crons_that_exist[$index]['time'] = $alt_time;
	$alt_schedule = get_option('ym_cron_altschedule_' . $cron_job['task'], $cron_job['schedule']);
	$ym_crons_that_exist[$index]['schedule'] = $alt_schedule;
	// spawn action
	add_action($cron_job['task'], array($cron_job['task'], 'run'));
}
// extras
$task = 'check_plugin_updates-' . basename(YM_META_BASENAME, '.php');
$ym_crons_that_exist[$task] = array('task' => $task, 'core' => 2);// update checkeer
$ym_crons_that_exist = apply_filters('ym_crons_that_exist', $ym_crons_that_exist);

class ym_cron {
	var $call_type = 'auto';

	function init() {
		global $ym_sys, $ym_crons_that_exist;
		if ($ym_sys->enable_manual_cron) {
			foreach ($ym_crons_that_exist as $cron_job) {
				if ($cron_job['core'] != 2 && wp_get_schedule($cron_job['task'])) {
					// clear
					wp_clear_scheduled_hook($cron_job['task']);
				}
			}
		} else {
			foreach ($ym_crons_that_exist as $cron_job) {
				if ($cron_job['core'] != 2 && !wp_get_schedule($cron_job['task'])) {
					// needs to be scheduled
					$now = time();
					$next = mktime($cron_job['time'][0], $cron_job['time'][1], 0, date('n', $now), date('j', $now), date('Y', $now));
					// next, schedule, action_name
					wp_schedule_event($next, $cron_job['schedule'], $cron_job['task']);
				}
			}
		}

		// check for manual call
		if (ym_get('ym_cron_do', FALSE)) {
			// has call
			$task = ym_get('ym_cron_job', FALSE);
			if ($task) {
				$tasks = array($task => 1);
			} else {
				$tasks = $ym_crons_that_exist;
			}

			foreach ($tasks as $task => $data) {
				echo 'do ' . $task . "\n";
				do_action($task, -1);
			}

			echo "\n";
			echo 1;
			die();
		}
	}

	static function reschedule() {
		global $ym_crons_that_exist;
		foreach ($ym_crons_that_exist as $cron_job) {
			if ($cron_job['core'] != 2 && wp_get_schedule($cron_job['task'])) {
				// reschedule
				wp_clear_scheduled_hook($cron_job['task']);
				$now = time();
				$next = mktime($cron_job['time'][0], $cron_job['time'][1], 0, date('n', $now), date('j', $now), date('Y', $now));
				// next, schedule, action_name
				wp_schedule_event($next, $cron_job['schedule'], $cron_job['task']);
			}
		}
	}

	protected function begin() {
		if ($this->call_type == 'auto') {
			ob_start();
		}
		echo '<pre>';
		// open log
		echo "\n" . 'Commence ' . get_called_class() . "\n\n";
	}
	protected function end() {
		global $ym_sys;
		// close log
		echo "\n" . 'Complete ' . get_called_class() . "\n\n";
		if ($this->call_type == 'auto') {
			$contents = ob_get_contents();
			// where to log to?
			if ($ym_sys->cron_notify_email) {
				ym_email($ym_sys->cron_notify_email, $ym_sys->cron_notify_subject, $contents);
			}
			ob_end_flush();
		}
	}

	private function manual() {

	}
	private function schedules() {

	}

	public function manual_run($arguement = NULL) {
		$this->call_type = 'manual';
		$this->runtask($arguement);
	}

	public function run($arguement = NULL) {
		// called from wp cron
		$cronjob = get_called_class();
		$cronjob = new $cronjob();
		$cronjob->runtask($arguement);
	}

	function runtask($arguement = NULL) {
		if ($this->call_type == 'auto') {
			$blocked = get_option('ym_cron_block_' . get_called_class());
			if ($blocked) {
				echo 'blocked ' . get_called_class();
				return;
			}
		}
		$this->begin();
		if (method_exists($this, 'load_config'))
			$this->load_config();
		$this->task($arguement);
		$this->end();
	}
}

// built in

/**
Cron Inactives
*/
class ym_cron_inactives extends ym_cron {
	public function description() {
		return __('Iterates through users and Performs a expiry check', 'ym');
	}

//	public function config() {}

	protected function load_config() {
		$this->limit = 300;
	}

	public function task($offset = 0) {
		$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : $offset;
		if (is_null($offset)) {
			$offset = 0;
		}

		if ($offset == -1) {
			// no pagination
			$offset = 0;
			$this->limit = null;
		}
		$this->limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : $this->limit;

		// use API Exposed Element for search
		$users = get_users(array(
			'offset'		=> $offset,
			'number'		=> $this->limit,

			'meta_key'		=> 'ym_status',
			'meta_value'	=> YM_STATUS_EXPIRED,
			'meta_compare'	=> '!='
		));

		$total = count($users);
		if ($total) {
			echo 'Loop Begin From ' . $offset . "\n";
			$counter = 0;
			foreach ($users as $user) {
				$counter++;
				echo $counter . '/' . $total . ' ';
				if (ym_superuser($user->ID)) {
					echo 'SuperUser ' . $user->ID;
				} else {
					echo 'Checking ' . $user->ID . ' ';
					$user = new YourMember_User($user->ID);
					$user->expire_check();
					echo $user->status;
				}
				echo "\n";
			}
			// loop
			echo 'Loop Complete From ' . $offset . "\n";
			if ($this->call_type == 'auto') {
				if ($this->limit != NULL) {
					echo 'Schedule Next Step' . "\n";
					wp_schedule_single_event(time(), 'ym_cron_inactives', array($offset + $this->limit));
				} else {
					echo 'Full Call Occured' . "\n";
				}
			} else {
				// reload
				echo 'Sleeping' . "\n";
				echo '<form action="" method="post"><input type="hidden" name="run_cron_job" value="ym_cron_inactives" /><input type="hidden" name="offset" value="' . ($offset + $this->limit) . '" /></form>';
				echo '<script type="text/javascript">jQuery(document).ready(function() { setTimeout(\'ym_fire()\', 5000) }); function ym_fire() { jQuery(\'form\').submit(); }</script>';
			}
		} else {
			echo 'Nothing to do Job Complete' . "\n";
			do_action('ym_cron_inactives_complete');
		}
	}
}

/**
Email Reminders
*/
class ym_cron_email_reminder extends ym_cron {
	public function description() {
		return __('Send out Email Reminders As Configured under Advanced -> Email', 'ym');
	}

	protected function load_config() {
		$this->limit = 300;
	}

	public function task($offset = 0) {
		global $ym_sys;
		if (!$ym_sys->email_reminder_enable) {
			echo 'Not Enabled in YM SYS';
			return;
		}

		$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : $offset;
		if (is_null($offset)) {
			$offset = 0;
		}

		if ($offset == -1) {
			// no pagination
			$offset = 0;
			$this->limit = null;
		}
		$this->limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : $this->limit;

		// use API Exposed Element for search
		$users = get_users(array(
			'offset'		=> $offset,
			'number'		=> $this->limit,

			'meta_key'		=> 'ym_status',
			'meta_value'	=> YM_STATUS_EXPIRED,
			'meta_compare'	=> '!='
		));

		$current_time = time();
		// set to now + days so a future
		$limit_date = time() + ($ym_sys->email_reminder_limit * 86400);

		$postarray = array();
		//Drip Feed Email
		if($ym_sys->email_drip_reminder_enable){
			global $wpdb;
			//Get all posts
			$args = array('meta_key' =>'_ym_account_min_duration','post_status' => 'publish');
				$posts = get_posts($args);
				foreach ($posts as $post) {
					$drip = get_post_meta($post->ID,'_ym_account_min_duration',true);
					$new_array = array();
					if($drip){
						$drip = explode(';', $drip);
						if($drip){
							foreach($drip as $d){
								$array = explode('=', $d);
								$new_array[$array[0]] = $array[1];
							}
						}
					}
					$postarray[$post->ID] = array_filter($new_array);
				}
				$postarray = array_filter($postarray);	
		}

		$total = count($users);
		if ($total) {
			$counter = 0;
			foreach ($users as $user) {
				$counter++;

				$user = new YourMember_User($user->ID);
				$expire_date = $user->expire_date;
				// user has expire date
				// user has not been sent a reminder
				// expire_date is less that the limit date
				// expire date is in the future
				if (
					$user->expire_date &&
					!$user->reminder_email_sent &&
					$user->expire_date < $limit_date &&
					$user->expire_date > $current_time
				) {
					// lock
					$user->update(array('reminder_email_sent' => true), true);
					// send
					$subject = $ym_sys->email_reminder_subject;
					$message = $ym_sys->email_reminder_message;

					$pack = ym_get_pack_by_id($user->pack_id);
					if ($pack['num_cycles'] != 1) {
						// so 0 or many ie recurring
						$subject = $ym_sys->email_reminder_subject_recur;
						$message = $ym_sys->email_reminder_message_recur;
					}

					$subject = str_replace('[site_name]', get_bloginfo(), $subject);
					$message = ym_apply_filter_the_content($message);

					ym_email($user->data->user_email, $subject, $message);
					@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Email Reminder Sent', 'ym'), $user->ID);

					do_action('ym_cron_email_reminder_sent', $user->ID);

					echo '1';
				} else {
					echo '.';
				}
				if (substr($counter, -1, 1) == '0') {
					echo ' ' . $counter . '/' . $total . "\n";
				}

				$reminders = array();
				foreach ($postarray as $post => $type) {
					foreach ($type as $ac_type => $days){
						if($ac_type == $act){
							$reminders[$post] = array('post_id'=> $post, 'days'=>$days);
						}
					}
				}
				if($reminders){
					$users_reminders = unserialize(get_user_meta($user->ID,'drip_email_reminders', true));
					if(!$users_reminders || !is_array($users_reminders)) $users_reminders = array();
					foreach ($reminders as $reminder) {
						if(!in_array($reminder['post_id'], $users_reminders)){
							//The post ID is not marked as already sent so we may need to send it
							
							//need to determine if we should send it.
							$reg = $user->data->user_registered;
								if ($sys->post_delay_start == 'pack_join') {
									if ($pack_join = $user->account_type_join_date) {
										$reg = date('Y-m-d', $pack_join);
									}
								}
								$reg = mktime(0,0,0,substr($reg, 5, 2), substr($reg, 8, 2), substr($reg, 0, 4));
								$user_at = $reg + (86400*$reminder['days']);

								if($user_at <= time() && $user_at >= time()-(86400*7)){
									//If the time is not in the future, and no older then 10 days, we should send an email

									//send email
									$subject = $ym_sys->email_drip_subject;
									$message = $ym_sys->email_drip_message;

									$subject = str_replace('[site_name]', get_bloginfo(), $subject);
									$message = ym_apply_filter_the_content($message);

									ym_email($target, $subject, $message);

									$users_reminders[] = $reminder['post_id'];
									@ym_log_transaction(USER_STATUS_UPDATE, __('Drip Content Email for post'.$reminder['post_id'], 'ym'), $user->ID);

									do_action('ym_cron_email_drip_sent', $user->ID, $reminder['post_id']);
								}
						}
					}
					update_user_meta($user->ID,'drip_email_reminders',serialize($users_reminders));
				}
			}

			echo ' ' . $counter . '/' . $total . "\n";
			// loop
			echo 'Loop Complete From ' . $offset . "\n";
			if ($this->call_type == 'auto') {
				if ($this->limit != NULL) {
					echo 'Schedule Next Step' . "\n";
					wp_schedule_single_event(time(), 'ym_cron_email_reminder', array($offset + $this->limit));
				} else {
					echo 'Full Call Occured' . "\n";
				}
			} else {
				// reload
				echo 'Sleeping' . "\n";
				echo '<form action="" method="post"><input type="hidden" name="run_cron_job" value="ym_cron_email_reminder" /><input type="hidden" name="offset" value="' . ($offset + $this->limit) . '" /></form>';
				echo '<script type="text/javascript">jQuery(document).ready(function() { setTimeout(\'ym_fire()\', 5000) }); function ym_fire() { jQuery(\'form\').submit(); }</script>';
			}
		} else {
			echo 'Nothing to do Job Complete' . "\n";
			do_action('ym_cron_email_reminder_complete');
		}
	}
}

/**
Cron Flag CleanUp
*/
class ym_cron_cleanup extends ym_cron {
	public function description() {
		return __('Performs Flag Clean Up', 'ym');
	}

	public function task() {
		$args = array(
			'numberposts'	=> -1,
			'post_type'		=> 'any',

			'meta_key'		=> '_ym_post_purchasable_featured',
			'meta_value'	=> '1',
		);
		$posts = get_posts($args);
		foreach ($posts as $post) {
			$meta = get_post_meta($post->ID, '_ym_post_purchasable', TRUE);
			if ($meta == '0') {
				echo 'Clean Up: ' . $post->ID . "\n";
				update_post_meta($post->ID, '_ym_post_purchasable_featured', 0);
			}
		}

		echo 'Job Complete' . "\n";
		do_action('ym_cron_cleanup_complete');
	}
}
