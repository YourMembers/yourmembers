<?php

/**
* Version 1.1.0
* MailChimp API V1.3
* Export API V1.0
* STS V1.0
* 
* $Id: mailchimp_class.php 38 2011-04-28 10:21:05Z bcarlyon $
* $Date: 2011-04-28 11:21:05 +0100 (Thu, 28 Apr 2011) $
* $Revision: 38 $
* $Author: bcarlyon $
* 
*/

/*
* 
* Unless otherwise noted
* cid = campaign ID
* start = page
* limit = items per page
* since = since date YYYY-MM-DD HH:II:SS in GMT
* 
*/

class MailChimp {
	private $apikey;
	private $zone;
	private $url;
	private $method;
	private $parameters;
	private $jsonobject;
	public $result;
	public $error;
	public $secure = 0;
	
	private $sts;
	
	private $error_codes = array(
		// general system error
		-32601	=> 'ServerError_MethodUnknown',
		-32602	=> 'ServerError_InvalidParameters',
		-99		=> 'Unknown_Exception',
		-98		=> 'Request_TimedOut',
		-92		=> 'Zend_Uri_Exception',
		-91		=> 'PDOException',// Avesta_Db_Exception
		-90		=> 'XML_RPC2_Exception',//XML_RPC2_FaultException
		-50		=> 'Too_Many_Connections',
		0		=> 'ParseException',
		
		// 100 User Related Errors
		100		=> 'User_Unknown',
		101		=> 'User_Disabled',
		102		=> 'User_DoesNotExist',
		103		=> 'User_NotApproved',
		104		=> 'Invalid_ApiKey',
		105		=> 'User_UnderMaintenance',
		106		=> 'Invalid_AppKey',
		106		=> 'Invalid_IP',
		
		// 120 Action Related Errors
		120		=> 'User_InvalidAction',
		121		=> 'User_MissingEmail',
		122		=> 'User_CannotSendCampaign',
		123		=> 'User_MissingModuleOutbox',
		124		=> 'User_ModuleAlreadyPurchased',
		125		=> 'User_ModuleNotPurchased',
		126		=> 'User_NotEnoughCredit',
		127		=> 'MC_InvalidPayment',
		
		//200 List Related errors
		200		=> 'List_DoesNotExist',
		
		// 210 List Basic Actions
		210		=> 'List_InvalidInterestFieldType',
		211		=> 'List_InvalidOption',
		212		=> 'List_InvalidUnsubMember',
		213		=> 'List_InvalidBounceMember',
		214		=> 'List_AlreadySubscribed',
		215		=> 'List_NotSubscribed',
		
		// 220 List Import Related
		220		=> 'List_InvalidImport',
		221		=> 'MC_PastedList_Duplicate',
		222		=> 'MC_PastedList_InvalidImport',
		
		// 230 List Email Related
		230		=> 'Email_AlreadySubscribed',
		231		=> 'Email_AlreadyUnsubscribed',
		232		=> 'Email_NotExists',
		233		=> 'Email_NotSubscribed',
		
		// 250 List Mwrge Realted
		250		=> 'List_MergeFieldRequired',
		251		=> 'List_CannotRemoveMailMerge',
		252		=> 'List_Merge_InvalidMergeId',
		253		=> 'List_TooManyMergeFields',
		254		=> 'List_InvalidMergeField',
		
		// 270 List Interest Group Related
		270		=> 'List_InvalidInterestGroup',
		271		=> 'List_TooManyInterestGroups',
		
		// 300 Campaign Related Errors
		300		=> 'Campaign_DoesNotExist',
		301		=> 'Campaign_StatsNotAvailable',
		
		// 310 Campaign Option Related Errors
		310		=> 'Campaign_InvalidAbsplit',
		311		=> 'Campaign_InvalidContent',
		312		=> 'Campaign_InvalidOption',
		313		=> 'Campaign_InvalidStatus',
		314		=> 'Campaign_NotSaved',
		315		=> 'Campaign_InvalidSegment',
		316		=> 'Campaign_InvalidRss',
		317		=> 'Campaign_InvalidAuto',
		318		=> 'MC_ContentImport_InvalidArchive',
		319		=> 'Campaign_BounceMissing',
		
		// 330 Campaign Ecomm Errors
		330		=> 'Invalid_EcommOrder',
		
		// 350 Campaign Absplit Related Errors
		350		=> 'Absplit_UnknownError',
		351		=> 'Absplit_UnknownSplitTest',
		352		=> 'Absplit_UnknownTestType',
		353		=> 'Absplit_UnknownWaitUnit',
		354		=> 'Absplit_UnknownWinnerType',
		355		=> 'Absplit_WinnerNotSelected',
		
		// 500 Generic Validation Error
		500		=> 'Invalid_Analytics',
		501		=> 'Invalid_DateTime',
		502		=> 'Invalid_Email',
		503		=> 'Invalid_SendType',
		504		=> 'Invalid_Template',
		505		=> 'Invalid_TrackingOptions',
		506		=> 'Invalid_Options',
		507		=> 'Invalid_Folder',
		508		=> 'Invalid_URL',
		
		// 550 Generic Unknown Errors
		550		=> 'Module_Unknown',
		551		=> 'MonthlyPlan_Unknown',
		552		=> 'Order_TypeUnknown',
		553		=> 'Invalid_PagingLimit',
		554		=> 'Invalid_PagingStart',
	);
	
	function __construct($apikey, $assoc = FALSE) {
		$this->apikey = $apikey;
		$this->addParameter('apikey', $apikey);
		
		$id = explode('-', $apikey);
		$id = array_pop($id);
		$this->zone = $id;
		
		$this->url = 'http';
		if ($this->secure) {
			$this->url .= 's';
		}
		$this->url .= '://' . $id . '.api.mailchimp.com/1.3/';
		$this->sts = 'http';
		if ($this->secure) {
			$this->sts .= 's';
		}
		$this->sts .= '://' . $id . '.sts.mailchimp.com/1.0/';
		
		$this->jsonobject = $assoc;
	}

	private function addParameter($name, $value) {
		$this->parameters[$name] = $value;
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM
	// http://apidocs.mailchimp.com/1.3/
	//******************************************************************************************************************************************/
	
	private function run() {
		// construct url
		$url = $this->url . '?output=json&method=' . $this->method;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode(json_encode($this->parameters)));
		curl_setopt($ch, CURLOPT_VERBOSE, 0);

		if ($json = curl_exec($ch)) {
			curl_close($ch);

			$return = json_decode($json, $this->jsonobject);
			if ($return->error) {
				return $this->error($return);
			} else {
				$this->error = 'ok';
			}
			$this->return = $return;

			return $return;
		} else {
			return FALSE;
		}
	}
	private function error($error) {
		$str = 'Error Code: ' . $error->code . ': ' . $error->error;
		trigger_error($str, E_USER_WARNING);
		$this->error = $str;
		$this->error_code = $error->code;
		$this->error_message = $error->error;
		return false;
	}
	
	public function getZone() {
		return $this->zone;
	}

	//******************************************************************************************************************************************/
	// MailChimp RTFM: Campaign
	//******************************************************************************************************************************************/

	/*
	* Get the content (both html and text) for a campaign either as it would appear in the campaign archive or as the raw, original content
	*/
	function campaignContent($cid, $for_archive = FALSE) {
		$this->method = 'campaignContent';
		
		$this->addParameter('cid', $cid);
		$this->addParameter('for_archive', $for_archive);
		
		return $this->run();
	}
	/*
	* Create a new draft campaign to send.
	*/
	function campaignCreate($type, $options, $content, $segment_opts = '', $type_opts = '') {
		$this->method = 'campaignCreate';
		
		$this->addParameter('type', $type);
		$this->addParameter('options', $options);
		$this->addParameter('content', $content);
		if (is_array($segment_opts)) {
			$this->addParameter('segment_opts', $segment_opts);
		}
		if (is_array($type_opts)) {
			$this->addParameter('type_opts', $type_opts);
		}
		
		return $this->run();
	}
	/*
	* Delete a campaign.
	*/
	function campaignDelete($cid) {
		$this->method = 'campaignDelete';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/*
	* Attach Ecommerce Order Information to a Campaign. 
	* 
	* For use with ecommerece package plugins either 3rd party of MC
	* $order is an array of information
	*/
	function campaignEcommOrderAdd($order) {
		$this->method = 'campaignEcommOrderAdd';
		
		$this->addParameter('order', $order);
		
		return $this->run();
	}
	
	/*
	* Pause an AutoResponder or RSS campaign from sending 
	*/
	function campaignPause($cid) {
		$this->method = 'campaignPause';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/*
	* Replicate a campaign.
	* returns new id
	*/
	function campaignReplicate($cid) {
		$this->method = 'campaignReplicate';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/*
	* Resume sending an AutoResponder or RSS campaign 
	*/
	function campaignResume($cid) {
		$this->method = 'campaignResume';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/*
	* Schedule a campaign to be sent in the future
	*/
	function campaignSchedule($cid, $time, $time_b = FALSE) {
		$this->method = 'campaignSchedule';
		
		$this->addParameter('cid', $cid);
		if (is_int($time)) {
			$this->addParameter('schedule_time', date('Y-m-d H:i:s', $time));
		} else {
			$this->addParameter('schedule_time', $time);
		}
		if ($time_b) {
			if (is_int($time_b)) {
				$this->addParameter('schedule_time_b', date('Y-m-d H:i:s', $time));
			} else {
				$this->addParameter('schedule_time_b', $time);
			}
		}
		
		return $this->run();
	}
	
	/*
	* Allows one to test their segmentation rules before creating a campaign using them
	* options array(match, conditions);
	*/
	function campaignSegmentTest($list_id, $options) {
		$this->method = 'campaignSegmentTest';
		
		$this->addParameter('list_id', $list_id);
		$this->addParameter('options', $options);
		
		return $this->run();
	}
	
	/*
	* Send a campaign NOW
	*/
	function campaignSendNow($cid) {
		$this->method = 'campaignSendNow';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	/*
	* Send type string
	* test emails is array of emails
	* Null for both
	* html/text for that
	* test_emails is array of emails to send the test to
	*/
	function campaignSendTest($cid, $test_emails, $send_type = '') {
		$this->method = 'campaignSendTest';
		
		$this->addParameter('cid', $cid);
		
		$this->addParameter('test_emails', $test_emails);
		
		if ($send_type) {
			$this->addParameter('send_type', $send_type);
		}
		
		return $this->run();
	}
	
	/*
	* Get the URL to a customized VIP Report for the specified campaign and optionally send an email to someone with links to it. 
	*/
	function campaignShareReport($cid, $opts = '') {
		$this->method = 'campaignShareReport';
		
		$this->addParameter('cid', $cid);
		
		if ($opts) {
			$this->addParameter('opts', $opts);
		}
	}
	
	/**
	* Get the HTML template content sections for a campaign.
	*/
	function campaignTemplateContent($cid) {
		$this->method = 'campaignTemplateContent';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	function campaignUnschedule($cid) {
		$this->method = 'campaignUnschedule';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/*
	* Change any campaignCreate item in the options array
	*/
	function campaignUpdate($cid, $name, $value) {
		$this->method = 'campaignUpdate';
		
		$this->addParameter('cid', $cid);
		$this->addParameter('name', $name);
		$this->addParameter('value', $value);
		
		return $this->run();
	}
	
	/**
	* filters => (optional) array of filters o.0
	* start => (optional) page to start
	* limit => (optional) number on a page default = 25 max 1000
	*/
	function campaigns($filters = '', $start = '', $limit = '') {
		$this->method = 'campaigns';
		
		if ($filters) {
			$this->addParameter('filters', $filters);
		}
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM: Campaign Stats
	//******************************************************************************************************************************************/
	
	/*
	* Start = page number default 0
	* Limit = items per page default 500 max is 1000
	* since = from YYYY-MM-DD HH:II:SS GMT
	*/
	function campaignAbuseReports($cid, $since = '', $start = '', $limit = '') {
		$this->method = 'campaignAbuseReports';
		
		$this->addParameter('cid', $cid);
		if ($since) {
			$this->addParameter('since', $since);
		}
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	/**
	* return advice from mailchimp
	*/
	function campaignAdvice($cid) {
		$this->method = 'campaignAdvice';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/*
	* Retrieve the Google Analytics data we've collected for this campaign.
	*/
	function campaignAnalytics($cid) {
		$this->method = 'campaignAnalytics';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	
	/**
	* Get bounce message for email
	* email - the email address or unique id of the member to pull a bounce message for.
	*/
	function campaignBounceMessage($cid, $email) {
		$this->method = 'campaignBounceMessage';
		
		$this->addParameter('cid', $cid);
		
		$this->addParameter('email', $email);
		
		return $this->run();
	}
	// since YYYY-MM-DD GMT
	function campaignBounceMessages($cid, $start = '', $limit = '', $since = '') {
		$this->method = 'campaignBounceMessages';
		
		$this->addParameter('cid', $cid);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		if ($since) {
			$this->addParameter('since', $since);
		}
		
		return $this->run();
	}
	/*
	* Retuns an array of the urls in the message and their click counts
	*/
	function campaignClickStats($cid) {
		$this->method = 'campaignClickStats';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	// since YYYY-MM-DD HH:II:SS GMT
	function campaignEcommOrders($cid, $start = '', $limit = '', $since = '') {
		$this->method = 'campaignEcommOrders';
		
		$this->addParameter('cid', $cid);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		if ($since) {
			$this->addParameter('since', $since);
		}
		
		return $this->run();
	}
	
	// twitter mentions
	function campaignEepUrlStats($cid) {
		$this->method = 'campaignEepUrlStats';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	// get the top 5 email domains for campaign
	function campaignEmailDomainPerformance($cid) {
		$this->method = 'campaignEmailDomainPerformace';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	// opemns by country
	function campaignGeoOpens($cid) {
		$this->method = 'campaignGeoOpens';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	// opens for a sepcific country
	function campaignGeoOpensForCountry($cid, $code) {
		$this->method = 'campaignGoOpensForCountry';
		
		$this->addParameter('cid', $cid);
		
		$this->addParameter('code', $code);
		
		return $this->run();
	}
	
	
	/**
	* Get all email addresses the campaign was successfully sent to (ie, no bounces) 
	* cid => Campaign ID
	* status => (optional) get results that match sent/hard/soft (if none returns all)
	* start => (optional) page number to start at
	* limit => (optional) number results to return default to 1000, max is 15000
	*/
	function campaignMembers($cid, $status = '', $start = '', $limit = '') {
		$this->method = 'campaignMembers';
		
		$this->addParameter('cid', $cid);
		if ($status) {
			$this->addParameter('status', $status);
		}
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	// get stats
	function campaignStats($cid) {
		$this->method = 'campaignStats';
		
		$this->addParameter('cid', $cid);
		
		return $this->run();
	}
	/**
	* Get all unsubscribed email addresses for a given campaign 
	*/
	function campaignUnsubscribes($cid, $start = '', $limit = '') {
		$this->method = 'campaignUnsubscribes';
		
		$this->addParameter('cid', $cid);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM: Campaign Report Data Methods
	//******************************************************************************************************************************************/
	
	// Return the list of email addresses that clicked on a given url, and how many times they clicked 
	function campaignClickDetailAIM($cid, $url, $start = '', $limit = '') {
		$this->method = 'campaignClickDetailAIM';
		
		$this->addParameter('cid', $cid);
		$this->addParameter('url', $url);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	/*
	* Given a campaign and email address, return the entire click and open history with timestamps, ordered by time
	* cid => Campaign ID
	* email_address => Array of up to 50 email address
	*/
	function campaignEmailStatsAIM($cid, $email_address) {
		$this->method = 'campaignEmailStatsAIM';
		
		$this->addParameter('cid', $cid);
		$this->addParameter('email_address', $email_address);
		
		return $this->run();
	}
	
	/*
	* Given a campaign and correct paging limits, return the entire click and open history with timestamps, ordered by time, for every user a campaign was delivered to. 
	*/
	function campaignEmailStatsAIMAll($cid, $start = '', $limit = '') {
		$this->method = 'campaignEmailStatsAIMAll';
		
		$this->addParameter('cid', $cid);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	/*
	* Retrieve the list of email addresses that did not open a given campaign 
	*/
	function campaignNotOpenedAIM($cid, $start = '', $limit = '') {
		$this->method = 'campaignNotOpenedAIM';
		
		$this->addParameter('cid', $cid);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	function campaignOpenedAIM($cid, $start = '', $limit = '') {
		$this->method = 'campaignOpenedAIM';
		
		$this->addParameter('cid', $cid);
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM: Ecommerce
	//******************************************************************************************************************************************/
	
	// not used checked docs
	function ecommOrderAdd($order) {
		$this->method = 'ecommOrderAdd';
		
		$this->addParameter('order', $order);
		
		return $this->run();
	}
	function ecommOrderDel($store_id, $order_id) {
		$this->method = 'ecommOrderDel';
		
		$this->addParameter('store_id', $store_id);
		$this->addParameter('order_id', $order_id);
		
		return $this->run();
	}
	function ecommOrders($start = '', $limit = '', $since = '') {
		$this->method = 'ecommOrders';
		
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		if ($since) {
			$this->addParameter('sice', $since);
		}
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM: Folder
	//******************************************************************************************************************************************/
	
	// type = campaign or autoresponder, default is campaign
	function folderAdd($name, $type = 'campaign') {
		$this->method = 'folderAdd';
		
		$this->addParameter('name', $name);
		$this->addParameter('type', $type);
		
		return $this->run();
	}
	//Delete a campaign or autoresponder folder. Note that this will simply make campaigns in the folder appear unfiled, they are not removed. 
	function folderDel($fid, $type = 'campaign') {
		$this->method = 'folderDel';
		
		$this->addParameter('fid', $fid);
		$this->addParameter('type', $type);
		
		return $this->run();
	}
	function folderUpdate($fid, $name, $type = 'campaign') {
		$this->method = 'folderDel';
		
		$this->addParameter('fid', $fid);
		$this->addParameter('name', $name);
		$this->addParameter('type', $type);
		
		return $this->run();
	}
	function folders($type = 'campaign') {
		$this->method = 'folders';
		
		$this->addParameter('type', $type);
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM: Helper
	//******************************************************************************************************************************************/
	
	function campaignsForEmail($email_address) {
		$this->method = 'campaignsForEmail';
		
		$this->addParameter('email_address', $email_address);
		
		return $this->run();
	}
	function chimpChatter() {
		$this->method = 'chimpChatter';
		
		return $this->run();
	}
	/*
	* Method does not save content, its just a generator
	* Generate text from text
	* Content
	* - plain html
	* - array of template content
	* - campaign ID
	* - templateID
	* The type of content to parse. Must be one of: "html", "template", "url", "cid" (Campaign Id), or "tid" (Template Id)
	*/
	function generateText($type, $content) {
		$this->method = 'generateText';
		
		$this->addParameter('type', $type);
		$this->addParameter('content', $content);
		
		return $this->run();
	}
	
	function getAccountDetails() {
		$this->method = 'getAccountDetails';
		
		return $this->run();
	}
	
	/*
	* Send your HTML content to have the CSS inlined and optionally remove the original styles.
	* strip css optional defailts to false
	*/
	function inlineCss($html, $strip_css = FALSE) {
		$this->method = 'inlineCss';
		
		$this->addParameter('html', $html);
		$this->addParameter('strip_css', $strip_css);
		
		return $this->run();
	}
	
	// get the lists that Email is on
	function listsForEmail($email_address) {
		$this->method = 'listsForEmail';
		
		$this->addParameter('email_address', $email_address);
		
		return @$this->run();
	}
	
	/*
	* returns "Everything's Chimpy!" if everything is chimpy, otherwise returns an error message
	*/
	function ping() {
		$this->method = 'ping';
		
		return $this->run();
	}
	
	/*
	* Custom
	*/
	function isSubscribed($listId, $email_address) {
		$r = $this->listsForEmail($email_address);
		if ($r) {
			if (FALSE !== array_search($listId, $r)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	//******************************************************************************************************************************************/
	// MailChimp RTFM: List
	//******************************************************************************************************************************************/
	
	function listAbuseReports($id, $start = '', $limit = '', $since = '') {
		$this->method = 'listAbuseReports';
		
		$this->addParameter('id', $id);
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		if ($since) {
			if (is_int($since)) {
				$since = date('Y-m-d H:i:s', $since);
			}
			$this->addParameter('since', $since);
		}
		
		return $this->run();
	}
	
	function listActivity($id) {
		$this->method = 'listActivity';
		
		$this->addParameter('id', $id);
		
		return $this->run();
	}
	
	/*
	* Update existing set to false with return error on attempt to
	*/
	/*
	function listBatchSubscribe($id, $batch, $double_optin = FALSE, $update_existing = FALSE, $replace_interests = FALSE) {
		$this->method = 'listBatchSubscribe';
		
		$this->addParameter('id', $id);
		$this->addParameter('batch', $batch);
		$this->addParameter('double_optin', $double_optin);
		$this->addParameter('update_existing', $update_existing);
		$this->addParameter('replace_interested', $replace_interests);
		
		return $this->run();
	}
	function listBatchUnsubscribe($id, $emails, $delete_member = FALSE, $send_goodbye = FALSE, $send_notify = FALSE) {
		$this->method = 'listBatchUnsubscribe';
		
		$this->addParameter('emails', $emails);
		$this->addParameter('delete_member', $delete_member);
		$this->addParameter('send_goodbye', $send_goodbye);
		$this->addParameter('send_notify', $send_notify);
		
		return $this->run();
	}
	*/
	/*
	* user agent data for a given list
	*/
	function listClients($id) {
		$this->method = 'listClients';
		
		$this->addParameters('id', $id);
		
		return $this->run();
	}
	function listGrowthHistory($id) {
		$this->method = 'listGrowthHistory';
		
		$this->addParmeter('id', $id);
		
		return $this->run();
	}
	
	// Add a single Interest Group - if interest groups for the List are not yet enabled, adding the first group will automatically turn them on.
	//The grouping to add the new group to - get using listInterestGrouping() . If not supplied, the first grouping on the list is used.
	function listInterestGroupAdd($id, $group_name, $grouping_id = '') {
		$this->method = 'listInterestGroupAdd';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('group_name', $group_name);
		if ($grouping_id) {
			$this->addParameter('grouping_id', $grouping_id);
		}
		
		return $this->run();
	}
	
	function listInterestGroupDel($id, $group_name, $grouping_id) {
		$this->method = 'listInterestGroupDel';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('group_name', $group_name);
		$this->addParameter('grouping_id', $grouping_id);
		
		return $this->run();
	}
	
	// change name of interest group
	// $optional $grouping_id The grouping to delete the group from - get using listInterestGrouping() . If not supplied, the first grouping on the list is used.
	function listInterestGroupUpdate($id, $old_name, $new_name, $grouping_id, $optional) {
		$this->method = 'listInterestGroupUpdate';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('old_name', $old_name);
		$this->addParameter('new_name', $new_name);
		$this->addParameter('grouping_id', $grouping_id);
		$this->addParameter('optional', $optional);
		
		return $this->run();
	}
	
	function listInterestGroupingAdd($id, $name, $type, $groups) {
		$this->method = 'listInterestGroupingAdd';
		
		$this->addParameter('id', $id);
		$this->addParameter('name', $name);
		$this->addParameter('type', $type);
		$this->addParameter('groups', $groups);
		
		return $this->run();
	}
	
	function listInterestGroupingDel($grouping_id) {
		$this->method = 'listInterestGroupingDel';
		
		$this->addParameter('grouping_id', $grouping_id);
		
		return $this->run();
	}
	
	function listInterestGroupingUpdate($grouping_id, $name, $value) {
		$this->method = 'listInterestGroupingUpdate';
		
		$this->addParameter('grouping_id', $grouping_id);
		$this->addParameter('name', $name);
		$this->addParameter('value', $value);
		
		return $this->run();
	}
	
	function listInterestGroupings($id) {
		$this->method = 'listInterestGroupings';
		
		$this->addParameter('id', $id);
		
		return $this->run();
	}
	
	// Retrieve the locations (countries) that the list's subscribers have been tagged to based on geocoding their IP address
	function listLocations($id) {
		$this->method = 'listLocations';
		
		$this->addParameter('id', $id);
		
		return $this->run();
	}
	
	function listMemberActivity($id, $email_address) {
		$this->method = 'listMemberActivity';
		
		$this->addParameter('id', $id);
		$this->addParameter('email_address', $email_address);
		
		return $this->run();
	}
	
	function listMemberInfo($listId, $email_address) {
		$this->method = 'listMemberInfo';
		
		$this->addParameter('id', $listId);
		if (is_array($email_address)) {
			$this->addParameter('email_address', implode(',', $email_address));
		} else {
			$this->addParameter('email_address', $email_address);
		}
		
		return $this->run();
	}
	
	function listMembers($listId, $status = 'subscribed', $since = FALSE, $start = FALSE, $limit = FALSE) {
		$this->method = 'listMembers';
		
		$this->addParameter('id', $listId);
		$this->addParameter('status', $status);
		if ($since) {
			$this->addParameter('since', $since);
		}
		if ($start) {
			$this->addParameter('start', $start);
		}
		if ($limit) {
			$this->addParameter('limit', $limit);
		}
		
		return $this->run();
	}
	
	// aka custom field
	function listMergeVarAdd($id, $tag, $name, $options = '') {
		$this->method = 'listMergeVarAdd';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('tag', $tag);
		$this->addParameter('name', $name);
		if (is_array($options)) {
			$this->addParameter('options', $options);
		}
		
		return $this->run();
	}
	
	function listMergeVarDel($id, $tag) {
		$this->method = 'listMergeVarDel';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('tag', $tag);
		
		return $this->run();
	}
	
	function listMergeVarUpdate($id, $tag, $options) {
		$this->method = 'listMergeVarUpdate';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('tag', $tag);
		$this->addParameter('options', $options);
		
		return $this->run();
	}
	
	// retru merge vars on list aka custom fields
	function listMergeVars($id) {
		$this->method = 'listMergeVars';
		
		$this->addParameter('id', $id);
		
		return $this->run();
	}
	
	// SEGMENTS
	
	function listStaticSegmentAdd($listId, $name) {
		$this->method = 'listStaticSegmentAdd';

		$this->addParameter('id', $listId);
		$this->addParameter('name', $name);

		return $this->run();
	}
	function listStaticSegmentDel($listId,$segmentId) {
		$this->method = 'listStaticSegmentDel';

		$this->addParameter('id',$listId);
		$this->addParameter('seg_id',$segmentId);

		return $this->run();
	}
	// Note: this takes an array of users emails or is array('email1','email2');
	function listStaticSegmentMembersAdd($listId,$segmentId,$users) {
		$this->method = 'listStaticSegmentMembersAdd';

		$this->addParameter('id', $listId);
		$this->addParameter('seg_id',$segmentId);
		$this->addParameter('batch',$users);

		return $this->run();
	}
	// Note: this takes an array of users emails or is array('email1','email2');
	function listStaticSegmentMembersDel($listId,$segmentId,$users) {
		$this->method = 'listStaticSegmentMembersDel';

		$this->addParameter('id', $listId);
		$this->addParameter('seg_id',$segmentId);
		$this->addParameter('batch',$users);

		return $this->run();
	}
	/*
	* Resets a static segment - removes all members from the static segment.
	*/
	function listStaticSegmentReset($id, $seg_id) {
		$this->method = 'listStaticSegmentReset';
		
		$this->addParameter('id', $id);
		
		$this->addParameter('seg_id', $seg_id);
		
		return $this->run();
	}
	function listSegments($listId) {
		$this->method = 'listStaticSegments';

		$this->addParameter('id', $listId);

		return $this->run();
	}
	// END SEGMENT
	
	function listSubscribe($listId, $email_address, $merge_vars = array(), $double_optin = FALSE, $welcome = FALSE) {
		if ($this->isSubscribed($listId, $email_address)) {
			// already subscribed
			return TRUE;
		}
		$this->method = 'listSubscribe';
		
		$this->addParameter('id', $listId);
		$this->addParameter('email_address', $email_address);
		$this->addParameter('double_optin', $double_optin);
		$this->addParameter('welcome', $welcome);
		$this->addParameter('merge_vars', $merge_vars);

		return $this->run();
	}
	function listUnsubscribe($listId, $email_address, $complete_delete = FALSE, $send_goodbye = FALSE, $send_notify = FALSE) {
		if (!$this->isSubscribed($listId, $email_address)) {
			// already unsubscribed
			return TRUE;
		}
		
		$this->method = 'listUnsubscribe';
		
		$this->addParameter('id', $listId);
		$this->addParameter('email_address', $email_address);
		$this->addParameter('delete_member', $complete_delete);
		$this->addParameter('send_goodbye', $send_goodbye);
		$this->addParameter('send_notify', $send_notify);
		
		return $this->run();
	}
	function listUpdateMember($listId, $email_address, $merge_vars = array()) {
		$this->method = 'listUpdateMember';
		
		$this->addParameter('id', $listId);
		$this->addParameter('email_address', $email_address);
		$this->addParameter('merge_vars', $merge_vars);
		
		return $this->run();
	}
	
	// WEBHOOKS
	// when a user does action direct to mailchimp, ping to hook
	// actions = hash sources = fash
	function listWebhookAdd($listId, $url, $actions = '', $sources = '') {
		$this->method = 'listWebhookAdd';
		
		$this->addParameter('id', $listId);
		$this->addParameter('url', $url);

		if ($actions) {
			$this->addParameter('actions', $actions);
		}
		if ($sources) {
			$this->addParameter('sources', $sources);
		}
		
		return $this->run();
	}
	function listWebhookDel($listId, $url) {
		$this->method = 'listWebhookDel';
		
		$this->addParameter('id', $listId);
		
		$this->addParameter('url', $url);
		
		return $this->run();
	}
	
	function listWebhooks($listId) {
		$this->method = 'listWebhooks';
		
		$this->addParameter('id', $listId);
		
		return $this->run();
	}
	
	function lists() {
		// return all lists
		$this->method = 'lists';
		$lists = $this->run();
		return $lists;
	}
	
	//******************************************************************************************************************************************/
	// MailChimp Security
	//******************************************************************************************************************************************/	
	
	// api key control
	
	//******************************************************************************************************************************************/
	// MailChimp Template
	//******************************************************************************************************************************************/	
	function templateAdd($name, $html) {
		$this->method = 'templateAdd';
		
		$this->addParameter('name', $name);
		$this->addParameter('html', $html);
		
		return $this->run();
	}
	// delete/deactiavate a template
	function templateDel($id) {
		$this->method = 'templateDel';
		
		$this->addParameter('id', $id);
		
		return $this->run();
	}
	// $type = user gallery base
	function templateInfo($tid, $type = FALSE) {
		$this->method = 'templateInfo';
		
		$this->addParameter('tid', $tid);
		if ($type) {
			$this->addParameter('type', $type);
		}
		
		return $this->run();
	}
	
	// undelete/reactivate a template
	function templateUndel($id) {
		$this->method = 'templateUndel';
		
		$this->addParameter('id', $id);
		
		return $this->run();
	}
	
	// change the user content
	function templateUpdate($id, $values) {
		$this->method = 'templateUpdate';
		
		$this->addParameter('id', $id);
		$this->addParameter('values', $values);
		
		return $this->run();
	}
	function templates($types = '', $inactives = '', $category = '') {
		$this->method = 'templates';
		
		if (is_array($types)) {
			$this->addParameter('types', $types);
		}
		if (is_array($inactives)) {
			$this->addParameter('inactives', $inactives);
		}
		if (is_array($category)) {
			$this->addParameter('category', $category);
		}
		
		return $this->run();
	}
	
		
	//******************************************************************************************************************************************/
	// MailChimp STS
	// http://apidocs.mailchimp.com/sts/1.0/
	//******************************************************************************************************************************************/
	
	private function sts() {
		$url = $this->sts . '/' . $this->method . '.json?';
		$url .= http_build_query($this->parameters);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, FALSE);
		$json = curl_exec($ch);
		curl_close($ch);

		$return = json_decode($json, $this->jsonobject);
		if ($return->http_code != '200') {
			//error
			return $this->stserror($return);
		} else {
			$this->error = 'ok';
		}
		return $return;
	}
	private function stserror($error) {
		$str = 'Error Code: ' . $error->http_code . ': ' . $error->message;
		trigger_error($str, E_USER_WARNING);
		$this->error = $str;
		return false;
	}
	
	// Email Verification Methods
	function DeleteVerifiedEmailAddress($email) {
		$this->method = 'DeleteVerifiedEmailAddress';
		
		$this->addParameter('email', $email);
		
		return $this->sts();
	}
	// verify
	function ListVerifiedEmailAddresses() {
		$this->method = 'ListVerifiedEmailAddresses';
		
		return $this->sts();
	}
	function VerifyEmailAddress($email) {
		$this->method = 'VerifyEmailAddress';
		
		$this->addParameter('email', $email);
		
		return $this->sts();
	}
	
	// sending
	function SendEmail($message, $track_opens = false, $track_clicks = false, $tags = array()) {
		$this->method = 'SendEmail';

		$this->addParameter('message', $message);
		$this->addParameter('track_opens', $track_opens);
		$this->addParameter('track_clicks', $track_clicks);
		$this->addParameter('tags', $tags);

		return $this->sts();
	}
	
	// stats
	function getSendQuota() {
		$this->method = 'GetSendQuota';
		return $this->sts();
	}
	function getSendStatistics() {
		$this->method = 'GetSendStatistics';
		return $this->sts();
	}
}
