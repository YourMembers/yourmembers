<?php

/*
* $Id: ym-data_import.include.php 2612 2013-03-13 09:30:27Z tnash $
* $Revision: 2612 $
* $Date: 2013-03-13 09:30:27 +0000 (Wed, 13 Mar 2013) $
*/

// Functions for importing users from a Exported CSV

function ym_import_users_from_csv() {
	if(ym_post('ym_start_import')) {
		if ($_FILES['upload']['error'] !=4) {
			$time = time();
			// since we don't need to keep the file, may as well leave it in tmp!
			$file = $_FILES['upload']['tmp_name'];

			$data_check = TRUE;
			$data_valid = FALSE;

			$import_array = array();
			$headers = array();

			$row = 0;

			if (($handle = fopen($file, "r")) !== FALSE) {
				$data_valid = TRUE;
				while (($data = fgetcsv($handle)) !== FALSE) {
					if ($data_check) {
						$headers = $data;
						$data_check = FALSE;
					} else {
						foreach ($data as $index => $item) {
							$import_array[$row][$headers[$index]] = $item;
						}
						$row++;
					}
				}
			}

			if (!$data_valid) {
				echo '<div id="message" class="error"><p>' . __('Not a Valid CSV File I can handle', 'ym') . '</p></div>';
				return;
			} else {
				$total_success = 0;
				$total_fail = 0;

				$messages = '';

				// user add loop
				
				foreach ($import_array as $index => $record) {
					
					$user = new YourMember_User();
					// pass it to the pre built create function
					// no password is exported by the export function
					$smflag = FALSE;
					if($record['smflag']) $smflag = $record['smflag'];

					$package = array();
					$pack_id = '';
					if(!$record['pack_id'] || !$record['package_id']){
						$package = array(
							'account_type' => $record['account_type'],
							'duration'	=> $record['duration'],
							'duration_type' => $record['duration_type'],
							);
						if($record['expire_date']){
							$package['expire_date'] = $record['expire_date'];
						}

					}
					else{
						if($record['pack_id']) $pack_id = $record['pack_id'];
						if($record['package_id']) $pack_id = $record['package_id'];
					}
					$password = false;
					if($record['password'] || $record['ym_password']){
						if($record['password']) $password = $record['password'];
						if($record['ym_password']) $password = $record['password'];
					}
					$expire_date = false;
					if($record['expire_date']){
							$expire_date = $record['expire_date'];
					}
					//Setting package expiry date outside of the package
					
					/*
					* must be true
					* export does not export the password
					* so a new one must be generated and sent to the user
					*/

					// custom fields will ignore stuff that doens't match
					// run it

					if($record['user_email']){
						$result = $user->create($record['user_email'], $record['pack_id'], $smflag, $record['user_login'], $password, $record,$package,$expire_date);
						if (is_wp_error($result)) {
							$total_fail ++;
							$messages .= $index . '-' . $record['user_login'] . ': ' . $result->get_error_message() . '<br />';
						} else {
							$total_success ++;
						}
					}
					else{
						$total_fail ++;
						$messages .= 'No Email address for user, skipping user <br />';
					}
					unset($user);
				}

				@ym_log_transaction(11,	date(YM_DATE, $time) . ' User import began. added: ' . $total_success . ', failed to add: ' . $total_fail, get_current_user_id());
				
				echo '<div id="message" class="updated"><p><strong>' . date(YM_DATE, $time) . ' User import began. added: ' . $total_success . ', failed to add: ' . $total_fail . '</strong></p></div>';
				if ($messages) {
					echo '<div id="message" class="error"><p>' . $messages . '</p></div>';
				}
			}

			// clean up
			unlink($file);
			return;
		}
	}
}
