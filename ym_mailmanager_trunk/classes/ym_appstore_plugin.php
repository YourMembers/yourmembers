<?php

class ym_mailmanager {
	var $file;
	var $sane = TRUE;
	var $version_check_url;
	var $version_check;
	var $name;
	var $page;
	var $nav_pages;
	var $page_root;

	public static function activate() {
		if (!function_exists('ym_loaded')) {
				// errror
				echo '<strong>Your Members - Mailmanager</strong>';
				echo '<p>YourMembers does not appear to be installed. <a href="http://yourmembers.co.uk/">YourMembers</a> is required to use Your Members - Mailmanager Plugin, visit <a href="http://yourmembers.co.uk/">YourMembers</a> to purchase</p>';
				die();
			}
		// look for sql
		$file = str_replace('ym_appstore_plugin.php', 'create_sql.php', __FILE__);
		if (is_file($file)) {
			include($file);
		}
	}
	public static function deactivate() {
		delete_option(get_class($this));
	}
	function __construct($file, $install_url, $version_check_url, $name, $nav_pages = array(), $dir) {
		$this->meta_file = $dir . basename($file);
		if (!function_exists('ym_loaded')) {
			// ym gone self kill
			// deactivate using WP's plugin deactivation algorithm
			$current = get_option('active_plugins');
			$key = array_search($this->meta_file, $current);
			unset($current[$key]);
			update_option('active_plugins', $current);

			do_action('deactivate_' . $dir . $this->meta_file);
			$this->sane = false;
			return;
		}
		global $ym_auth;
		
		$this->file = $file;
		$this->name = $name;
		$this->page = strtolower($name);
		$this->nav_pages = $nav_pages;
		$this->page_root = 'admin.php?page=' . YM_ADMIN_DIR . 'ym-index.php&ym_page=ym-other&action=' . $this->page;

		$this->version_check_url = $version_check_url . '&key=' . $ym_auth->ym_get_key();
		if (!get_option('ym_' . get_class($this))) {
			ym_remote_request($install_url);
			// setup version check which also sets the get_option
			$this->version_check_fetch();
		}
		
		add_filter('ym_members_links' , array($this, 'admin_menu'), 5,5);
		add_action('admin_menu',  array($this, 'navadd'));
		add_filter('plugin_action_links',  array($this, 'action_links'), 10, 2);
		add_filter('ym_plugin_preappstore', array($this, 'add_ym_nav'));
	}
	
	function version_check($force = FALSE) {
		global $ym_auth;
		/*
		$this->version_check = json_decode(get_option('ym_' . get_class($this)));
		if ($this->version_check->time < (time() - 86400) || $force) {
			// check
			$this->version_check->json = $this->version_check_fetch();
		}
				
		$this->version_check = json_decode($this->version_check->json);
		*/
		
		require_once(YM_INCLUDES_DIR . 'update_checker.php');
		$url = str_replace('version_check', 'metafile', $this->version_check_url);
		$url = $url . '&key=' . $ym_auth->ym_get_key();
		$ym_update_checker = new PluginUpdateChecker($url, $this->meta_file);
		
//		if ($this->version_check->version->current_upgrade_available) {
//			add_action('admin_notices', array($this, 'update_nag_box'));
//			add_action('after_plugin_row_' . plugin_basename($this->file), array($this, 'after_plugin_row'));
//		}
	}
	// depricated
	function version_check_fetch() {
		// cron
		$json = ym_remote_request($this->version_check_url);
		$packet = array(
			'time' => time(),
			'json' => $json
		);
		update_option('ym_' . get_class($this), json_encode($packet));
		return $json;
	}
	
	function admin_menu($menu_item = FALSE) {
		$menu_item[$this->name] = 'ym-other&action=' . $this->page;
		return $menu_item;
	}
	function navadd() {
//		if (ym_admin_user_has_access()) {
//			add_submenu_page(YM_ADMIN_DIR . 'ym-index.php', $this->name, $this->name, 'activate_plugins', $this->page_root);
//		}
	}
	function action_links($links, $file){
		if ($file == plugin_basename($this->file)) {
			if ($this->nav_pages['Settings']) {
				$link = explode('&', $this->nav_pages['Settings']);
				$link = array_pop($link);
				$links[] = '<a class="ym_plugin_settings" href="' . $this->page_root . '&' . $link . '" title="Configure this plugin">Settings</a>';
			} else {
				$links[] = '<a class="ym_plugin_settings" href="' . $this->page_root . '" title="Configure this plugin">Settings</a>';	
			}
		}
		return $links;
	}
	
	function update_nag_box() {
		if (is_ym_admin()) {
			echo '<div class="update-nag">';
			echo 'Please update your Copy of Your Members - ' . $this->name . ' to Version: <a href="' . $this->version_check->version->current_download_url . '">' . $this->version_check->version->current_version_id . '</a>';
			echo '</div>';
		}
	}
	function after_plugin_row() {
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">There is a new version of YourMembers - ' . $this->name . ' Available. ';
		echo '<a href="' . $this->version_check->version->current_download_url . '">Download version ' . $this->version_check->version->current_version_id . '</a>';
		echo '</div></td></tr>';
	}
	
	function add_ym_nav($pages) {
		if ($this->nav_pages) {
			foreach ($this->nav_pages as $label => $action) {
				$this->nav_pages[$label] = 'other_' . $action;
			}
			$array = array($this->name => 'other_' . $this->page);
			$this->nav_pages = array_merge($array, $this->nav_pages);
			$pages[$this->name] = $this->nav_pages;
		}
		return $pages;
	}
}
