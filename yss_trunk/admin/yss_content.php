<?php

/*
* $Id: yss_content.php 1842 2012-02-01 14:26:14Z BarryCarlyon $
* $Revision: 1842 $
* $Date: 2012-02-01 14:26:14 +0000 (Wed, 01 Feb 2012) $
*/

$action = ym_request('task');
$date_format = get_option('date_format');

if (!get_option('yss_user_key') || !get_option('yss_secret_key')) {
	echo ym_start_box('Error');
	echo '<p>You need to provide your S3 User and Secret Keys, please visit the the Settings tab to do so.</p>';
	echo ym_end_box();
} else {
	if (in_array($action, array('stream', 'dload'))) {
		// distro select
		yss_s3_distribution($action, ym_request('id'));
	} else if (in_array($action, array('add', 'edit'))) {
		yss_s3_edit($_REQUEST['id']);
	} else {
		if ($action == 'delete') {
			yss_s3_delete();
		}
			
		if (ym_post('submit_edit_s3')) {
			yss_s3_save();
		}
		
		yss_s3_list();
	}
}

function yss_s3_distribution($type, $id) {
	global $ym_formgen, $yss_cloudfront, $yss_db, $wpdb;
	
	// file details
	$s3file = yss_get($id);
	
	if ($_POST) {
		// here we go
		$distro = $_POST['distro'];
		list($can, $oai, $bucket, $file, $domain, $type) = explode('|', $distro);
		
		$packet = array(
			'type'			=> 'CanonicalUser',
			'id'			=> $can,
			'name'			=> 'CloudFront Origin Access Identity ' . $oai,
			'permission'	=> 'READ'
		);
		$acp = array();

		require_once(YSS_CLASSES_DIR . 'S3.php');
		$s3 = new S3();
		$s3->setAuth(get_option('yss_user_key'), get_option('yss_secret_key'));
		
		//get existing and merge
		$acp = $s3->getAccessControlPolicy($bucket, $file);
		$acp['acl'][] = $packet;
		
		if ($s3->setAccessControlPolicy($bucket, $file, $acp)) {
			$acp = $s3->getAccessControlPolicy($bucket, $file);
			
			// store
			$distribution = json_decode($s3file->distribution);
			
			if ($type == 'stream') {
				$distribution->streaming = $domain;
			} else {
				$distribution->download = $domain;
			}
			$distribution = json_encode($distribution);
			
			$sql = 'UPDATE ' . $yss_db . ' SET
						distribution = \'' . $distribution . '\'
					WHERE id = ' . $id;
			$wpdb->query($sql);
			
			echo '<div id="message" class="updated"><p>Permissions updated</p></div>';
			yss_s3_list();
			return;
		} else {
			echo '<div id="message" class="error"><p>Permissions update failed</p></div>';
		}
	}
	
	if ($type == 'stream') {
		$data = $yss_cloudfront->get_streaming();
	} else {
		$data = $yss_cloudfront->get_distribution();
	}

	if (is_array($data)) {
		$test = array_keys($data);
		if ($test[0] != '0') {
			$data = array(
				$data
			);
		}
	}
	
	if (is_array($data)) {
		
		echo ym_box_top('Deploy');
		echo '
<form action="" method="post">
	<fieldset>
		<p>You can select a distribution to expose the file, ' . $s3file->bucket . '/' . $s3file->resource_path . ' onto</p>
		<table class="form-table">
			';
			
			$items = array(
				'blank' => 'Select'
			);
			foreach ($data as $item) {
				$bucket = $item['S3Origin']['DNSName']['value'];
				list($bucket, $null) = explode('.', $bucket, 2);
				$enabled = $item['Enabled']['value'];
				
				if ($enabled == 'true' && $s3file->bucket == $bucket) {
					// Distribution is enabled and is for this bucket matches
					$status = $item['Status']['value'];
					$domain = $item['DomainName']['value'];
					$oai = $item['S3Origin']['OriginAccessIdentity']['value'];
					list($null, $nulm, $oai) = explode('/', $oai);
					
					// oai needs canonical
					$canonical = $yss_cloudfront->get_oai_canonical($oai);
					
					$value = $canonical . '|' . $oai . '|' . $bucket . '|' . $s3file->resource_path . '|' . $domain . '|' . $type;
					//echo '<option value="' . $value . '">' . $domain . '</option>';
					$items[$value] = $domain;
				}
			}
			$ym_formgen->render_combo_from_array_row('Distribution', 'distro', $items, '', 'Which Distribution to expose this file on');

			echo '
		</table>
		<p class="submit">
			<input type="submit" value="Deploy!" />
		</p>
	</fieldset>
</form>
';
		echo ym_box_bottom();

	} else {
		echo '<div id="message" class="error"><p>Failed to load Distributions or none available</p></div>';
	}
}
