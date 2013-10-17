<?php

/*
* $Id: yss_home.php 1754 2012-01-03 16:45:50Z BarryCarlyon $
* $Revision: 1754 $
* $Date: 2012-01-03 16:45:50 +0000 (Tue, 03 Jan 2012) $
*/

echo '<div id="yss_guide">';
echo ym_start_box('Welcome to Your Secure Stream!');
echo '
<p>This plugin (YSS) works in conjunction with <a href="http://www.yourmembers.co.uk" target="_blank">Your Members</a>, allows you to embed Video content uploaded to the <a href="http://aws.amazon.com/s3/" target="_blank">Amazon Simple Storage Server (S3)</a>, as well as protect it.</p>
<p>Use the settings page to add your S3 information then use the Videos page to create a new video link for the site.</p>
<p><strong>Note</strong></p>
<p>You can select which player you wish to use to play your content within as long as you have the relevant (and supported) plugin installed.</p>
<p><strong>Usage Instructions</strong></p>
<p>Videos can be added to posts and pages on your site using <strong>[yss_player id=1234]</strong> as the shortcode. The video size defaults to 640x380, however, you can set these using width and height arguments within the shortcode as follows: <strong>[yss_player id=1234 width=1234 height=1234]</strong></p>';

echo ym_end_box();
    
echo '</div>';
