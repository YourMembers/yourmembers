<?php

/*
* $Id: ym-dashboard.php 2175 2012-05-28 15:26:07Z bcarlyon $
* $Revision: 2175 $
* $Date: 2012-05-28 16:26:07 +0100 (Mon, 28 May 2012) $
*/

echo '<div class="wrap" id="poststuff">
	<div class="ym_full" id="ym_graphs">';

ym_box_top('&nbsp;', 300);

// new subscribers
$graphs_to_render = array(
	array('YM_PAYMENT', __('Sales over Last Month', 'ym')),
	array('YM_PPP_PURCHASED', __('Posts Purchased over Last Month', 'ym')),
	array('YM_PPP_PACK_PURCHASED', __('Packs Purchased over Last Month', 'ym')),
	array('members_graph', __('Member Figures over Last Month', 'ym'))
);
echo '<ul id="ym_graph_nav">';
foreach ($graphs_to_render as $graph) {
	echo '<li><a href="#' . $graph[0] . '">' . $graph[1] . '</a></li>';
}
echo '</ul>';

echo '<div id="ym_graph_holder" class="aligncenter">';
ym_render_graphs($graphs_to_render);
echo '</div>';
ym_box_bottom();

echo '</div>';

echo '<div class="ym_two_third alignleft">';

ym_box_top(__('Posts Purchased', 'ym'), 300);
ym_render_posts_purchased(false, true);
ym_box_bottom();

echo '<div style="clear:both;">&nbsp;</div>';

ym_box_top(__('Recent History', 'ym'), 300);

if ($limit = ym_post('ym_home_timeline_log_limit', FALSE)) {
	update_option('ym_home_timeline_log_limit', ym_post('ym_home_timeline_log_limit'));
} else {
	$limit = get_option('ym_home_timeline_log_limit', 20);
}
ym_show_timeline_log_home($limit);

echo '
<form action="" method="post" class="alignright"><fieldset>
	<input type="text" name="ym_home_timeline_log_limit" value="' . $limit . '" size="4" /> <input type="submit" value="' . __('Change Limit', 'ym') . '" />
</fieldset></form>
';

ym_box_bottom();

echo '</div>';

echo '<div class="ym_third alignright">';

do_action('ym_nm_news_box');

echo '</div>';

echo '</div>';

// advert call

// graph engine
// Google Graphs functions
function ym_get_graph_base($title) {
	$graph_base = 'https://chart.googleapis.com/chart?';
	// dimensions
	$graph_base .= 'chs=900x200&amp;';
	// graph type
	$graph_base .= 'cht=lc&amp;';
	// background color
	$graph_base .= 'chf=bg,lg,90,FFFFFF,0,EFEFEF,0.75&amp;';
	// title
	$graph_base .= 'chtt=' . urlencode($title) . '&amp;';
	
	return $graph_base;
}
function ym_get_y_axis($max) {
	// scale
	$top_limit = $max + 5;
	while (substr($top_limit, -1, 1) != 0 && substr($top_limit, -1, 1) != 5) {
		$top_limit ++;
	}
	$y_axis = 'chds=0,' . $top_limit . '&amp;';
	// grid lines
	$y = 100 / $top_limit;
	$y_axis .= 'chg=33.33333333333333,' . $y . ',5,5,0,0&amp;';
	// axis define, y gets called before x hence x,y here
	$y_axis .= 'chxt=x,y&amp;';
	$y_axis .= 'chxr=1,0,' . $top_limit . ',5&amp;';
	
	return $y_axis;
}
function ym_get_x_axis($legend) {
	// x axis label
	$start = strtotime(array_shift(array_keys($legend)));
	$end = strtotime(array_pop(array_keys($legend)));
	
	$x_axis = 'chxl=0:';
	
	$counter = $start;
	while ($counter < $end) {
		$x_axis .= '|' . date('Y-m-d', $counter);
		$counter = $counter + 604800;
		// one week
	}
	$x_axis .= '&amp;';
	
	return $x_axis;
}

function ym_render_graph($log = YM_PAYMENT, $title = '') {
	if (!$title) {
		$title = __('Sales over Last Month');
	}
	
	global $wpdb;
	$data = array();
	// generate data
	$limit = time() - 2419200;
	// 28 days
	
	// grab log type
	if ($log == 'members_graph') {
		$query = 'SELECT UNIX_TIMESTAMP(user_registered) AS unixtime FROM ' . $wpdb->users . ' WHERE UNIX_TIMESTAMP(user_registered) > ' . $limit . ' ORDER BY user_registered DESC';
	} else {
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_transaction WHERE action_id = ' . constant($log) . ' AND unixtime > ' . $limit . ' ORDER BY unixtime DESC';
	}
	$result = $wpdb->get_results($query, ARRAY_A);
	
	foreach ($result as $row) {
		$date = date('Y-m-d', $row['unixtime']);
		$data[$date] = isset($data[$date]) ? $data[$date] = $data[$date] + 1 : 1;
	}
	// fill in missing dates
	for ($x=time();$x>$limit;$x=$x-86400) {
		$date = date('Y-m-d', $x);
		$data[$date] = isset($data[$date]) ? $data[$date] = $data[$date] : 0;//rand(0,10);
	}
	// key sort so data is in the right order
	ksort($data);
	
	$cdata = 'chd=t:';
	$clabel = 'cdl=';
	$max = 0;
	foreach ($data as $date => $sales) {
		$cdata .= $sales . ',';
		$clabel .= $date . '|';
		$max = $max > $sales ? $max : $sales;
	}
	// data processing has added a extra comma or bar, remove
	$cdata = substr($cdata, 0, -1);
	$clabel = substr($clabel, 0, -1);
	
	$graph_url = ym_get_graph_base($title);
	$graph_url .= ym_get_y_axis($max);
	$graph_url .= ym_get_x_axis($data);
	// add data
	$graph_url .= $cdata . '&amp;' . $clabel;
	
	return '<img id="ym_graph_' . $log . '" src="' . $graph_url . '" alt="' . __('A Graph', 'ym') . '" />';
}

function ym_render_graphs($graphs_to_render) {
	$graphs = '';
	foreach ($graphs_to_render as $graph) {
		$graphs .= ym_render_graph($graph[0], $graph[1]);
	}
	
	echo $graphs;
}
