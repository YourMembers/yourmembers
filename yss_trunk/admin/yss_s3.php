<?php

/*
* $Id: yss_s3.php 1754 2012-01-03 16:45:50Z BarryCarlyon $
* $Revision: 1754 $
* $Date: 2012-01-03 16:45:50 +0000 (Tue, 03 Jan 2012) $
*/

if ($task = @$_REQUEST['buckettask']) {
	
	$s3 = new S3();
	$s3->setAuth(get_option('yss_user_key'), get_option('yss_secret_key'));

	switch ($task) {
		case 'buckets':
			echo '<select name="origin" id="origin">
				<option value="">--Select--</option>
				';
				foreach ($s3->listBuckets() as $bucket) {
					echo '<option value="' . $bucket . '">' . $bucket . '</option>';
				}
				echo '
			</select>';
			break;
		default:
			echo 'Nothing to do: ' . $task;
	}

	exit;
}

echo '<p>' . __('There is currently nothing to see here', 'yss') . '</p>';
