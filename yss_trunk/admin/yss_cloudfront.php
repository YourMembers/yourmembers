<?php

/*
* $Id: yss_cloudfront.php 1842 2012-02-01 14:26:14Z BarryCarlyon $
* $Revision: 1842 $
* $Date: 2012-02-01 14:26:14 +0000 (Wed, 01 Feb 2012) $
*/

if (isset($yss_cloudfront)) {
	$task = ym_request('cloudtask');
	$id = ym_request('id');
	$type = ym_request('type');
	
	switch ($task) {
		case 'enable':
			if ($type == 'distribution') {
				$data = $yss_cloudfront->enable_distribution($id);
			} else {
				$data = $yss_cloudfront->enable_streaming_distribution($id);
			}
			if (is_array($data)) {
				echo '<div id="message" class="updated fade"><p>Updated, changes can take up to 15 minutes to complete</p></div>';
			} else {
				echo '<div id="message" class="error"><p>' . $data . '</p></div>';
				return;
			}
			break;
		case 'disable':
			if ($type == 'distribution') {
				$data = $yss_cloudfront->disable_distribution($id);
			} else {
				$data = $yss_cloudfront->disable_streaming_distribution($id);
			}
			if (is_array($data)) {
				echo '<div id="message" class="updated fade"><p>Updated, changes can take up to 15 minutes to complete</p></div>';
			} else {
				echo '<div id="message" class="error"><p>' . $data . '</p></div>';
				return;
			}
			break;
		case 'delete':
			if ($type == 'distribution') {
				$data = $yss_cloudfront->delete_distribution($id);
			} else {
				$data = $yss_cloudfront->delete_streaming_distribution($id);
			}

			if ($yss_cloudfront->info['http_code'] == '204') {
				echo '<div id="message" class="updated fade"><p>Deleted, changes can take up to 15 minutes to complete</p></div>';
			} else {
				echo '<div id="message" class="error"><p>Error: HTTP Code: ' . $yss_cloudfront->info['http_code'] . '</p></div>';
				return;
			}
			break;
		case 'oais':
			echo '<select name="oai" id="oai">
				<option value="">--Select--</option>
				<option value="new">Create New</option>
				';
		case 'deleteoais':
			if ($task == 'deleteoais') {
				echo '<select name="deleteoai" value="deleteoai">
					<option value="">--Select--</option>
				';
			}
				
				$oai = $yss_cloudfront->get_oai();
				
				if (is_array($oai)) {
					$test = array_keys($oai);
					if ($test[0] != '0') {
						$oai = array(
							$oai
						);
					}
				}
				
				foreach ($oai as $id) {
					echo '<option value="' . $id['Id']['value'] . '">' . $id['Id']['value'] . ' - ' . $id['Comment']['value'] . '</option>';
				}
				
				echo '
			</select>';
			break;
		case 'streaming':
			$data = $yss_cloudfront->get_streaming();
		case 'distribution':
			// cloudfront is alive
			if ($task == 'distribution') {
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
				echo '<table class="form-table">';
				foreach ($data as $distribution) {
					echo '<tr>';
					echo '<td class="';
					if ($distribution['Enabled']['value'] == 'true') {
						echo 'ym_tick';
					} else {
						echo 'ym_cross';
					}
					echo '"></td>';
					echo '<td>';
					if ($distribution['Status']['value'] == 'InProgress') {
						echo '<img src="images/loading-publish.gif" alt="Loading" />';
					} else if ($distribution['Status']['value'] == 'Deployed') {
						echo '<span class="ym_tick"></span>';
					}
					echo ' ' . $distribution['Status']['value'] . '</td>';
					echo '<td>' . $distribution['DomainName']['value'] . '</td>';
					echo '<td>' . $distribution['S3Origin']['DNSName']['value'] . '</td>';
					echo '<td>';
					if (is_array($distribution['S3Origin']['OriginAccessIdentity'])) {
						echo 'OAI Present';
					} else {
						echo 'OAI None';
					}
					echo '</td>';
					echo '<td>' . $distribution['Comment']['value'] . '</td>';
					
					// tasks
					echo '<td>';
					
					if ($distribution['Enabled']['value'] == 'true') {
						// enabled
						if ($distribution['Status']['value'] != 'InProgress') {
							// can disable
							echo '<a href="#nowhere" class="disable" myid="' . $distribution['Id']['value'] . '" mytype="' . $task . '">Disable</a>';
						}
					} else {
						echo '<a href="#nowhere" class="enable" myid="' . $distribution['Id']['value'] . '" mytype="' . $task . '">Enable</a>';
						echo ' | ';
						echo '<a href="#nowhere" class="delete" myid="' . $distribution['Id']['value'] . '" mytype="' . $task . '">Delete</a>';
					}
					
					echo '</td>';
					
					echo '</tr>';
				}
				echo '</table>';
			} else if ($data) {
				// error
				echo $data;
			} else {
				echo '<p>No Distributions Found</p>';
			}
			break;
		default:
			echo 'Nothing to do: ' . $task;
	}
	exit;
}

global $yss_cloudfront;
	
$task = ym_request('cloudfronttask');
if ($task) {
	switch ($task) {
		case 'createdistribution':
			$type = ym_post('dist_type');
			$oai = ym_post('oai');
			$origin = ym_post('origin');//bucket
				
			if ($oai == 'new') {
				// make a new OAI
				$data = $yss_cloudfront->create_oai();
				if (is_array($data)) {
					$oai = $data['id'];
				} else {
					echo '<div id="message" class="error"><p>OIA: ' . $data . '</p></div>';
					return;
				}
			}
			
			if ($type == 'both' || $type == 'down') {
				$data = $yss_cloudfront->create_distribution($origin, $oai);
				if (is_array($data)) {
					echo '<div id="message" class="updated fade"><p>Created a new download Distribution. It will take about 15 minutes before it is ready</p></div>';
				} else {
					echo '<div id="message" class="error"><p>D: ' . $data . '</p></div>';
					return;
				}
			}
			if ($type == 'both' || $type == 'stream') {
				$data = $yss_cloudfront->create_streaming($origin, $oai);
				if (is_array($data)) {
					echo '<div id="message" class="updated fade"><p>Created a new streaming Distribution. It will take about 15 minutes before it is ready</p></div>';
				} else {
					echo '<div id="message" class="error"><p>S: ' . $data . '</p></div>';
					return;
				}
			}
			break;
		case 'deleteoai':
			$id = ym_post('deleteoai');
			
			$yss_cloudfront->delete_oai($id);
			
			if ($yss_cloudfront->info['http_code'] == '204') {
				echo '<div id="message" class="updated fade"><p>Deleted, changes can take up to 15 minutes to complete</p></div>';
			} else {
				echo '<div id="message" class="error"><p>Error: HTTP Code: ' . $yss_cloudfront->info['http_code'] . '</p></div>';
			}
			
			break;
		default:
			echo '<div id="message" class="error"><p>I do not know what to do</p><div>';
	}
}

if (!get_option('yss_cloudfront_id') || !get_option('yss_cloudfront_private')) {
	echo ym_start_box('Amazon CloudFront');
	echo '<p>You need to provide your CloudFront Key Pair ID and Key Pair Private Key</p>';
	echo ym_end_box();
	return;
}

// status
echo '<div id="yss_message"></div>';
echo ym_start_box('Amazon CloudFront');
echo '
<div id="cloudfront_tasks">
	<h4>Download Distributions</h4>
	<div class="distribution">
		Waiting....
	</div>

	<h4>Streaming Distributions</h4>
	<div class="streaming">
		Waiting....
	</div>
	
	<p><a href="#nowhere" onclick="yss_execute_cloud();">Reload</a></p>
</div>
';

echo ym_end_box();
echo ym_start_box('New Distribution');
echo '
<div id="cloudfront_new">
	<h4>Create New Distribution(s)</h4>
	<p>This will create distribution(s) for Your Amazon S3 Bucket, and secured them for secured streaming/downloading.</p>
	<p>You will be able to select which files are available pre distribution type.</p>
	
	
	<form action="" method="post">
		<fieldset>
			<input type="hidden" name="cloudfronttask" value="createdistribution" />
			
			<table class="form-table">
			<tr><td><label for="dist_type">Distribution Type</label></td><td>
				<select name="dist_type" id="dist_type">
					<option value="">--Select--</option>
					<option value="both">Both</option>
					<option value="stream">Streaming</option>
					<option value="down">Download</option>
				</select>
			</td></tr>
			<tr><td></td><td>To make a secured Distribution, you need to Select A Origin Access Identity(OAI)</td></tr>
			<tr><td><label for="oai">Origin Access Identity:</label></td><td id="oais">Waiting....</td></tr>
			<tr><td><label for="origin">Amazon S3 Bucket to deploy:</label></td><td id="origin">Waiting...</td></tr>
			</table>
			
			<p class="submit">
				<input type="submit" value="Create Distribution(s)" />
			</p>
			
		</fieldset>
	</form>
</div>
';

echo ym_end_box();
echo ym_start_box('OAI - Origin Access Identity');
		
echo '
		<form action="" method="post">
			<fieldset>
				<input type="hidden" name="cloudfronttask" value="deleteoai" />
				
				<label for="deleteoai">Delete Origin Access Identity:</label> <div id="deleteoais">Waiting....</div>
				
				<p class="submit">
					<input type="submit" value="Delete OAI" />
				</p>

			</fieldset>
		</form>
		';
echo ym_end_box();
		
// cloud front js driver
// disable enable delete
echo '
<script type="text/javascript">' . "
	jQuery(document).ready(function() {
		yss_execute_cloud();
		
		jQuery('.enable').live('click', function() {
			var theid = jQuery(this).attr('myid');
			var thetype = jQuery(this).attr('mytype');
			
			jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_cloudfront&cloudtask=enable&id=' + theid + '&type=' + thetype, function(data) {
				jQuery('#yss_message').html(data);
				yss_execute_tasks();
			});
		});
		jQuery('.disable').live('click', function() {
			var theid = jQuery(this).attr('myid');
			var thetype = jQuery(this).attr('mytype');
			
			jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_cloudfront&cloudtask=disable&id=' + theid + '&type=' + thetype, function(data) {
				jQuery('#yss_message').html(data);
				yss_execute_tasks();
			});
		});
		jQuery('.delete').live('click', function() {
			var theid = jQuery(this).attr('myid');
			var thetype = jQuery(this).attr('mytype');
			
			if (confirm('Are you sure?')) {
				jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_cloudfront&cloudtask=delete&id=' + theid + '&type=' + thetype, function(data) {
					jQuery('#yss_message').html(data);
					yss_execute_tasks();
				});
			}
		});
	});
	function yss_execute_cloud() {
		yss_execute_tasks();
		yss_execute_add_new();
		jQuery('#yss_message').html('');
	}
	function yss_execute_tasks() {
		jQuery('#cloudfront_tasks div').each(function() {
			var task = jQuery(this).attr('class');
			var target = this;
			jQuery(this).html('Loading.... Communicating with the Cloud...');
			jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_cloudfront&cloudtask=' + task, function(data) {
				jQuery(target).slideUp(function() {
					jQuery(target).html(data);
					jQuery(target).slideDown();
				});
			});
		});
	}
	function yss_execute_add_new() {
		jQuery('#oais').each(function() {
			jQuery(this).html('Loading.... Communicating with the Cloud...');
			jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_cloudfront&cloudtask=oais', function(data) {
				jQuery('#oais').slideUp(function() {
					jQuery(this).html(data);
					jQuery(this).slideDown();
				})
			})
		});
		
		jQuery('#origin').each(function() {
			jQuery(this).html('Loading.... Communicating with the Buckets...');
			jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_s3&buckettask=buckets', function(data) {
				jQuery('#origin').slideUp(function() {
					jQuery(this).html(data);
					jQuery(this).slideDown();
				})
			})
		});
		
		jQuery('#deleteoais').each(function() {
			jQuery(this).html('Loading.... Communicating with the Cloud...');
			jQuery.get('" . YM_ADMIN_INDEX_URL . "&ym_page=ym-other&action=ymyss_cloudfront&cloudtask=deleteoais', function(data) {
				jQuery('#deleteoais').slideUp(function() {
					jQuery(this).html(data);
					jQuery(this).slideDown();
				})
			})
		});
	}
</script>
";
