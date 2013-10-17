<?php

/*
* CloudFront
* $Id: cloudfront.php 1842 2012-02-01 14:26:14Z BarryCarlyon $
* $Date: 2012-02-01 14:26:14 +0000 (Wed, 01 Feb 2012) $
* $Revision: 1842 $
* $Author: bcarlyon $
*/

class CloudFront {
	var $base_url = 'https://cloudfront.amazonaws.com/';
	var $api_version = '2010-11-01';
	var $additional_headers = '';
	
	function __construct() {
		$this->xml_head = '<' . '?xml' . ' version="1.0" encoding="UTF-8" ' . '?' . '>';
		
		$this->userkey		= get_option('yss_user_key');
		$this->secretkey	= get_option('yss_secret_key');
		
		if ($this->userkey && $this->secretkey) {
			$this->key_pair_id	= get_option('yss_cloudfront_id');
//			$this->public		= get_option('yss_cloudfront_public');
			$this->private		= get_option('yss_cloudfront_private');
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/*
	*	Helper Function
	*/
	private function build($action) {
		$url = $this->base_url . $this->api_version . '/' . $action;
		
		$callerreference = 'ym_' . microtime(true);
		
		$headers = array();
		
		$date = gmdate('D, d M Y H:i:s T');

		$headers[] = 'Authorization' . ':' . "AWS " . $this->userkey . ':' . base64_encode(hash_hmac('sha1', $date, $this->secretkey, true));
		$headers[] = 'Date: ' . $date;
		
		if ($this->additional_headers) {
			$headers = array_merge($headers, $this->additional_headers);
			$this->additional_headers = '';
		}

		return array(
			'url'		=> $url,
			'headers'	=> $headers,
			'callref'	=> $callerreference
		);
	}
	private function run($action, $xml = '') {
		$this->last_action = $action;
		$request = $this->build($action);
		
		$xml = str_replace('[CallerReference]', $request['callref'], $xml);
		$this->last_xml = $xml;
		
		// was having problems with wp http, so using staright curl
		$ch = curl_init($request['url']);
		curl_setopt($ch, CURLOPT_USERAGENT, 'YourMembers CloudFront Lib');
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'get_etag'));
		if ($this->method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		} else if ($this->method != 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
			if ($xml) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			}
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request['headers']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		
		$this->result = $result;
		
		$data = xml2array($result, 1, 'nottag');

		$this->info = $info;
		
		$r = $this->error_handle($data);
		if ($r) {
			return $r;
		}
		return $data;
	}
	private function error_handle($data) {
		if (isset($data['ErrorResponse']) && $data['ErrorResponse']) {
			// error
			return 'An Error Occured: (' . $this->last_action . ') ' . $data['ErrorResponse']['Error']['Code']['value'] . ' - ' . $data['ErrorResponse']['Error']['Message']['value'] . '<br /><textarea>' . $this->last_xml . '</textarea>';
		}
	}
	
	private function get_etag($ch, $header) {
		if (substr(strtolower($header), 0, 5) == 'etag:') {
			$this->etag = trim(substr($header, 5));
		}
		return strlen($header);
	}
	
	/*
	*	API FUNCTIONS
	*	http://docs.amazonwebservices.com/AmazonCloudFront/latest/APIReference/
	*/
	
	/*
	*	Actions on Distibutions
	*/
	function get_distribution($nice = TRUE) {
		$this->method = 'GET';
		$action = 'distribution';
		
		$data = $this->run($action);

		if ($nice) {
			if (is_array($data)) {
				$data = $data['DistributionList']['DistributionSummary'];
			}
		}
		
		return $data;
	}
	
	function get_distribution_config($id) {
		$this->method = 'GET';
		$action = 'distribution/' . $id . '/config';
		
		$data = $this->run($action);

		return $data;
	}
	
	function create_distribution($bucket, $oai, $nice = TRUE) {
		if (!$bucket || !$oai) {
			return 'Error: a bucket and OAI must be specified';
		}
		$this->method = 'POST';
		$action = 'distribution';
				
		$xml = $this->xml_head . '
<DistributionConfig xmlns="http://cloudfront.amazonaws.com/doc/' . $this->api_version . '/">
	<S3Origin>
		<DNSName>' . $bucket . '.s3.amazonaws.com</DNSName>
		<OriginAccessIdentity>origin-access-identity/cloudfront/' . $oai . '</OriginAccessIdentity>
	</S3Origin>
	<CallerReference>[CallerReference]</CallerReference>
	<Comment>Created By YourMembers YSS</Comment>
	<Enabled>true</Enabled>
	<TrustedSigners>
		<Self/>
	</TrustedSigners>
</DistributionConfig>
';
		$data = $this->run($action, $xml);
		
		if ($nice) {
			if (is_array($data)) {
				$data = $data['Distribution']['DomainName'];// deliberatly return an array ['value']
			}
		}
		return $data;
	}
	
	function enable_distribution($id) {
		$data = $this->get_distribution_config($id);
		
		if (is_array($data)) {
			if ($data['DistributionConfig']['Enabled']['value'] == 'true') {
				// nothing to do
				return 'Distribution is already Enabled';
			} else {
				// toggle it
				$xml = $this->result;
				$xml = str_replace('<Enabled>false</Enabled>', '<Enabled>true</Enabled>', $xml);
				
				$etag = $this->etag;
				
				$data = $this->change_distribution_config($id, $xml, $etag);
				return $data;
			}
		} else {
			// error
			return $data;
		}
	}
	function disable_distribution($id) {
		$data = $this->get_distribution_config($id);
		
		if (is_array($data)) {
			if ($data['DistributionConfig']['Enabled']['value'] == 'false') {
				// nothing to do
				return 'Distribution is already Disabled';
			} else {
				// toggle it
				$xml = $this->result;
				$xml = str_replace('<Enabled>true</Enabled>', '<Enabled>false</Enabled>', $xml);
				
				$etag = $this->etag;
				
				$data = $this->change_distribution_config($id, $xml, $etag);
				return $data;
			}
		} else {
			// error
			return $data;
		}
	}
	function change_distribution_config($id, $xml, $etag) {
		$this->method = 'PUT';
		$action = 'distribution/' . $id . '/config';
		
		$this->additional_headers = array(
			'If-Match:' . $etag,
		);
		
		$data = $this->run($action, $xml);
		return $data;
	}
	
	function delete_distribution($id) {
		$this->get_distribution_config($id);

		$this->method = 'DELETE';
		$action = 'distribution/' . $id;
		
		$this->additional_headers = array(
			'If-Match:' . $this->etag,
		);
		
		$data = $this->run($action);
		return $data;
	}
	
	/*
	*	Actions on Streaming Distibutions
	*/
	function get_streaming($nice = TRUE) {
		$this->method = 'GET';
		$action = 'streaming-distribution';
		
		$data = $this->run($action);
		
		if ($nice) {
			if (is_array($data)) {
				$data = $data['StreamingDistributionList']['StreamingDistributionSummary'];
			}
		}
		
		return $data;
	}

	function get_streaming_distribution_config($id) {
		$this->method = 'GET';
		$action = 'streaming-distribution/' . $id . '/config';
		
		$data = $this->run($action);

		return $data;
	}
	
	function create_streaming($bucket, $oai, $nice = TRUE) {
		if (!$bucket || !$oai) {
			return 'Error: a bucket and OAI must be specified';
		}
		$this->method = 'POST';
		$action = 'streaming-distribution';
				
		$xml = $this->xml_head . '
<StreamingDistributionConfig xmlns="http://cloudfront.amazonaws.com/doc/' . $this->api_version . '/">
	<S3Origin>
		<DNSName>' . $bucket . '.s3.amazonaws.com</DNSName>
		<OriginAccessIdentity>origin-access-identity/cloudfront/' . $oai . '</OriginAccessIdentity>
	</S3Origin>
	<CallerReference>[CallerReference]</CallerReference>
	<Comment>Created By YourMembers YSS</Comment>
	<Enabled>true</Enabled>
	<TrustedSigners>
		<Self/>
	</TrustedSigners>
</StreamingDistributionConfig>
';
		$data = $this->run($action, $xml);
		
		if ($nice) {
			if (is_array($data)) {
				$data = $data['StreamingDistribution']['DomainName'];// deliberatly return an array ['value']
			}
		}
		return $data;
	}
	
	function enable_streaming_distribution($id) {
		$data = $this->get_streaming_distribution_config($id);
		
		if (is_array($data)) {
			if ($data['StreamingDistributionConfig']['Enabled']['value'] == 'true') {
				// nothing to do
				return 'Streaming Distribution is already Enabled';
			} else {
				// toggle it
				$xml = $this->result;
				$xml = str_replace('<Enabled>false</Enabled>', '<Enabled>true</Enabled>', $xml);
				
				$etag = $this->etag;
				
				$data = $this->change_streaming_distribution_config($id, $xml, $etag);
				return $data;
			}
		} else {
			// error
			return $data;
		}
	}
	function disable_streaming_distribution($id) {
		$data = $this->get_streaming_distribution_config($id);
		
		if (is_array($data)) {
			if ($data['StreamingDistributionConfig']['Enabled']['value'] == 'false') {
				// nothing to do
				return 'Streaming Distribution is already Disabled';
			} else {
				// toggle it
				$xml = $this->result;
				$xml = str_replace('<Enabled>true</Enabled>', '<Enabled>false</Enabled>', $xml);
				
				$etag = $this->etag;
				
				$data = $this->change_streaming_distribution_config($id, $xml, $etag);
				return $data;
			}
		} else {
			// error
			return $data;
		}
	}
	function change_streaming_distribution_config($id, $xml, $etag) {
		$this->method = 'PUT';
		$action = 'streaming-distribution/' . $id . '/config';
		
		$this->additional_headers = array(
			'If-Match:' . $etag,
		);
		
		$data = $this->run($action, $xml);
		return $data;
	}
	
	function delete_streaming_distribution($id) {
		$this->get_streaming_distribution_config($id);

		$this->method = 'DELETE';
		$action = 'streaming-distribution/' . $id;
		
		$this->additional_headers = array(
			'If-Match:' . $this->etag,
		);
		
		$data = $this->run($action);
		return $data;
	}
	
	/*
	*	Actions on origin access identity
	*/
	function get_oai($id = '', $nice = TRUE) {
		$this->method = 'GET';
		$action = 'origin-access-identity/cloudfront';
		
		if ($id) {
			$action .= '/'.  $id;
		}
		
		$data = $this->run($action);
		
		if ($nice) {
			if (is_array($data)) {
				if ($id) {
					$etag = $this->etag;

					$data = $data['CloudFrontOriginAccessIdentity'];
					$data = array(
						'data' => $data,
						'etag' => $etag,
					);
				} else {
					$data = $data['CloudFrontOriginAccessIdentityList']['CloudFrontOriginAccessIdentitySummary'];
				}
			}
		}
		
		return $data;
	}
	
	// helpder
	function get_oai_canonical($id) {
		if (!$id) {
			return '';
		}
		
		if (isset($this->oai_cache) && $this->oai_cache) {
			$data = $this->oai_cache;
		} else {
			$data = $this->get_oai();
			$this->oai_cache = $data;
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
			foreach($data as $item) {
				$thisid = $item['Id']['value'];
				if ($thisid == $id) {
					return $item['S3CanonicalUserId']['value'];
				}
			}
		}
		return '';
	}
	
	function create_oai($nice = TRUE) {
		$this->method = 'POST';
		$action = 'origin-access-identity/cloudfront';
		
		$xml = $this->xml_head . '
<CloudFrontOriginAccessIdentityConfig xmlns="http://cloudfront.amazonaws.com/doc/' . $this->api_version . '/">
	<CallerReference>[CallerReference]</CallerReference>
	<Comment>Created By YourMembers YSS</Comment>
</CloudFrontOriginAccessIdentityConfig>
';
		$data = $this->run($action, $xml);
		
		if ($nice) {
			if (is_array($data)) {
				$data = array(
					'id'		=> $data['CloudFrontOriginAccessIdentity']['Id']['value'],
					'Canonical'	=> $data['CloudFrontOriginAccessIdentity']['S3CanonicalUserId']['value']
				);
			}
		}
		return $data;
	}
	
	function delete_oai($id) {
		$data = $this->get_oai($id);
		
		$this->method = 'DELETE';
		$action = 'origin-access-identity/cloudfront/' . $id;
		
		$this->additional_headers = array(
			'If-Match:' . $this->etag,
		);
		
		$data = $this->run($action);
		
		return $data;
	}
	
	/*
	* Secure URL
	*/
	function generateSecureUrl($resource, $valid_before, $valid_after = 0, $ip = '') {
		if ($this->key_pair_id && $this->private) {
			$condition = '"DateLessThan":{"AWS:EpochTime":' . $valid_before . '}';
			if ($valid_after) {
				$condition .= '"DateMoreThan":{"AWS:EpochTime":' . $valid_after . '}';
			}
			if ($ip) {
				$condition .= '"IpAddress":{"AWS:SourceIp":"' . $ip . '"}';
			}
			
			$json = '{"Statement":[{"Resource":"' . $resource . '","Condition":{' . $condition . '}}]}';

			// load
			$key = openssl_pkey_get_private($this->private);
			if (!$key) {
				echo 'failed key';
				return '';
			}
			// sign request
			if(!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1)) {
				echo 'failed sign';
				return '';
			}
			
			// generate and clean for amazon ness
			$signature = str_replace(array('+','=','/'), array('-','_','~'), base64_encode($signed_policy));
			
			//Construct the URL
			$url = $resource . '?Expires=' . $valid_before . '&Signature=' . $signature . '&Key-Pair-Id=' . $this->key_pair_id;
			
			return $url;
		} else {
			echo 'instatn';
			return '';
		}
	}
}
