<?php

$conf_dir = YM_PLUGIN_DIR_PATH . 'confs';

if ($_POST) {
	if ($_POST['go_import'] == 'import') {
		if ($file = $_POST['file_to_use']) {
			ym_check_for_ymconf($conf_dir . '/' . $file);
			
			echo '<div id="message" class="updated"><p>' . __('Import Processed, you should check all the settings should be what they ought to be', 'ym') . '</p></div>';
		}
	}
}

echo '<div class="wrap" id="poststuff">';
echo ym_box_top(__('Import/Export Configuration', 'ym'));

echo '<p>' . __('Here you can choose to import and/or export your configuration to share with others, or reset your settings', 'ym') . '</p>';
echo '<p><strong>' . __('Before using/uploading a configuration file, you should ensure you trust it, the source, and nothing untoward will happen when the file is executed', 'ym') . '</strong></p>';
echo '<p>' . __('Configuration files need to be manually upload to the <em>conf</em> Directory within the YM plugin', 'ym') .'</p>';

echo '
<form method="post" action="">
	<input type="hidden" name="go_import" value="import" />
	<p style="text-align: center">
	';
	
	if (is_dir($conf_dir)) {
		echo '<select name="file_to_use">
<option value="">' . __('Select a Conf File to Load', 'ym') . '</option>';
		
		$dir = opendir($conf_dir);
		while (FALSE !== ($file = readdir($dir))) {
			if ($file != ".." && $file != "." && $file != "index.html" && is_file($conf_dir . '/' . $file)) {
				echo '<option value="' . $file . '">' . $file . '</option>';
			}
		}
		closedir($dir);
		
		echo '</select>';
	} else {
		echo '<p>' . sprintf(__('The Confs directory <em>%s</em> Does not exist', 'ym'), $conf_dir) . '</p>';
	}
	echo '
	</p>
	<p style="text-align: center">
		<span class="submit">
			<input type="submit" name="submit" value="' . __('Import', 'ym') . ' &raquo;" />
		</span>
	</p>
</form>

<form method="post" action="">
	<input type="hidden" name="go_export" value="export" />
	<p class="submit" style="text-align: center;">
		<input type="submit" name="submit" value="' . __('Export', 'ym') . ' &raquo;" />
	</p>
</form>
';

echo ym_end_box();
echo '</div>';
