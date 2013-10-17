<?php

/*
* $Id: ym-manage_access.class.php 2369 2012-09-04 14:01:53Z tnash $
* $Revision: 2369 $
* $Date: 2012-09-04 15:01:53 +0100 (Tue, 04 Sep 2012) $
*/

class YourMember_Manage_Access {
	function __construct() {
		global $ym_sys;
/**
IF COMPLETELY HIDE aka 404
*/
		if ($ym_sys->hide_pages) {
			// may overwrite all menus
			add_filter('wp_list_pages_excludes', array(&$this, 'list_pages_excludes'));
			add_filter( 'wp_nav_menu_objects', array(&$this, 'wp_nav_menu_object') , 90 , 2 );

		}
		if ($ym_sys->hide_posts) {
			add_filter('posts_where', array(&$this, 'hide_protected_where'));
		}
	}
	function exit_check() {
		global $ym_sys;
		// check for redirect
		$url = FALSE;

		global $post;
		$post->id = isset($post->ID) ? $post->ID : 0;
		if ((is_single() || is_singular()) && $post->ID) {
			if (!ym_user_has_access($post->ID)) {
				$url = TRUE;
			}

			$link = get_permalink($post->ID);
			if ($link == $_SERVER['REQUEST_URI']) {
				$url = FALSE;
			}
		} else if (is_home() && $ym_sys->redirect_on_homepage) {
			// what posts will be displayed?
			$posts = get_posts();
			foreach ($posts as $post) {
				if (!ym_user_has_access($post->ID)) {
					$url = TRUE;
					break;
				}
			}
		} else {
			// nothing to do
			// not single post and not on home
		}

		if ($url) {
			get_currentuserinfo();
			global $current_user;

			if ($current_user->ID == 0) {
				$url = $ym_sys->no_access_redirect_lo;
			} else {
				$url = $ym_sys->no_access_redirect_li;
			}

			if ($url) {
				$url = site_url($url);
				header('Location: ' . $url);
				exit;
			}
		}
	}
	
	function hide_protected_where($where) {
		global $wpdb, $user_ID;
		get_currentuserinfo();

		//this is optional code to be activated/deactivated in the settings of ym.
		if (!is_admin()) {
			$results = array();

			$sql = 'SELECT ID FROM ' . $wpdb->posts;
			$results2 = $wpdb->get_col($sql);

			if (is_array($results2) && (count($results2) > 0)) {
				foreach ($results2 as $post_id) {
					//test time scale here (number of days need to be a member mainly)
					if (!ym_user_has_access($post_id, false, true)) {
						$results[] = $post_id;
					}
				}

				if (count($results)) {
					$exc = implode(',', $results);
					$where .= " AND " . $wpdb->posts . ".ID NOT IN (" . $exc . ") ";
				}
			}
		}
		//end optional

		return $where;
	}

	// Used to hide private pages from the sidebar
	function list_pages_excludes($excluded) {
		global $wpdb, $user_ID;
		get_currentuserinfo();

		$results = array();

		//this is optional code to be activated/deactivated in the settings of ym.
		if (!is_admin()) {
			$sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = \'page\'';
			$results2 = $wpdb->get_col($sql);

			if (is_array($results2) && (count($results2) > 0)) {
				foreach ($results2 as $id=>$post_id) {
					if (!ym_user_has_access($post_id, false, true)) {
						$results[] = $post_id;
					}
				}
			}
		}

		$results = array_merge($results, $excluded);

		return $results;
	}

	function wp_nav_menu_object( $sorted_menu_items, $args = array() ) {
	$modified_menu_items = array();

	foreach($sorted_menu_items as $item){

		if(ym_user_has_access($item->object_id) || $item->object == 'custom'){
			
			$modified_menu_items[] = $item;
		}
	}
		return $modified_menu_items;
	}

}
