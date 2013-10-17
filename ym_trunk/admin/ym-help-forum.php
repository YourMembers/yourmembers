<?php

echo '<div class="wrap" id="poststuff">';

ym_box_top(__('Your Members Support Forum', 'ym'));
echo __('<p>Here are the latest forum Posts</p>', 'ym');
ym_box_bottom();

echo '<div id="tabs">';
echo '<ul>
	<li><a href="#forums">' . __('Forums', 'ym') . '</a></li>
	<li><a href="#topics">' . __('Threads', 'ym') . '</a></li>
	<li><a href="#posts">' . __('Posts', 'ym') . '</a></li>
</ul>';

echo '<div id="forums">';
$url = 'http://yourmembers.co.uk/forum/feed.php?mode=forums';
if ($data = ym_remote_request($url)) {
	$data = xml2array($data, 1, 'nottag');

	foreach ($data['feed']['entry'] as $forum) {
		$title		= $forum['title']['value'];
		$url		= $forum['link']['attr']['href'];
		$content	= $forum['content']['value'];
		$updated	= $forum['updated']['value'];
		
		ym_box_top('<a href="' . $url . '">' . $title . '</a>');
		echo $content;
		ym_box_bottom();
	}
} else {
	ym_box_top(__('Error', 'ym'));
	echo __('<p>Failed to connect to the Forums</p>', 'ym');
	ym_box_bottom();
}
echo '</div>';
echo '<div id="topics">';
$url = 'http://yourmembers.co.uk/forum/feed.php?mode=topics';
if ($data = ym_remote_request($url)) {
	$data = xml2array($data, 1, 'nottag');

	foreach ($data['feed']['entry'] as $forum) {
		$title		= $forum['title']['value'];
		$url		= $forum['link']['attr']['href'];
		$content	= $forum['content']['value'];
		$updated	= $forum['updated']['value'];
		
		ym_box_top('<a href="' . $url . '">' . $title . '</a>');
		echo $content;
		ym_box_bottom();
	}
} else {
	ym_box_top(__('Error', 'ym'));
	echo __('<p>Failed to connect to the Forums</p>', 'ym');
	ym_box_bottom();
}
echo '</div>';
echo '<div id="posts">';
$url = 'http://yourmembers.co.uk/forum/feed.php?mode=posts';
if ($data = ym_remote_request($url)) {
	$data = xml2array($data, 1, 'nottag');

	foreach ($data['feed']['entry'] as $forum) {
		$title		= $forum['title']['value'];
		$url		= $forum['link']['attr']['href'];
		$content	= $forum['content']['value'];
		$updated	= $forum['updated']['value'];
		
		ym_box_top('<a href="' . $url . '">' . $title . '</a>');
		echo $content;
		ym_box_bottom();
	}
} else {
	ym_box_top(__('Error', 'ym'));
	echo __('<p>Failed to connect to the Forums</p>', 'ym');
	ym_box_bottom();
}
echo '</div>';

echo '</div>';
echo '</div>';

echo '
<script type="text/javascript">
' . "
	jQuery(document).ready(function() {
		jQuery('#tabs').tabs({
			selected: 0
		});
	});
</script>
";
