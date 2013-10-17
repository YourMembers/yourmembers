<?php

/*
* $Id: sql_update_5.php 1795 2012-01-16 12:52:27Z BarryCarlyon $
* $Revision: 1795 $
* $Date: 2012-01-16 12:52:27 +0000 (Mon, 16 Jan 2012) $
*/

$queries = array();

/**
YM Res
*/
global $ym_res;
unset($ym_res->ppp_page_login);
unset($ym_res->ppp_page_start);
unset($ym_res->ppp_page_row);
unset($ym_res->ppp_page_end);

unset($ym_res->ppp_no_login_email_subject);
unset($ym_res->ppp_no_login_email_body);

unset($ym_res->ppp_pack_template);

$ym_res->all_content_not_logged_in = 'To see all Purchasable Content, you need to be logged in';
$ym_res->all_bundles_not_logged_in = 'To see all Purchasable Bundles, you need to be logged in';
$ym_res->featured_content_not_logged_in = 'To see all Featured Content, you need to be logged in';
$ym_res->featured_bundles_not_logged_in = 'To see all Featured Bundles, you need to be logged in';

update_option('ym_res', $ym_res);
