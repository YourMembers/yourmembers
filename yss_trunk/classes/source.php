	echo '<p>Test</p>
<p>
<a href="#nowhere" onclick="window.location.reload()">Reload</a>
</p>
';
	/*
	
	$base = 'https://cloudfront.amazonaws.com/';
	
	$CallerReference = 'ym_' . time();
	
	// awsaccesskey
	$userkey = get_option('yss_user_key');
	$secretkey = get_option('yss_secret_key');
	
	$date = gmdate('D, d M Y H:i:s T');
	
	$headers[] = 'Authorization' . ':' . "AWS " . $userkey . ':' . base64_encode(hash_hmac('sha1', $date, $secretkey, true));
	$headers[] = 'Date:' . $date;
	
	$ch = curl_init($base . '2010-11-01/streaming-distribution');
//	curl_setopt($ch, CURLOPT_POST, TRUE);
//	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	
	$result = xml2array($result, 1, 'nottag');
	
	echo '<pre>';
	print_r($result);
return;	
	
		$date = gmdate('D, d M Y H:i:s T');

		$headers[] = 'Authorization' . ':' . "AWS " . $userkey . ':' . base64_encode(hash_hmac('sha1', $date, $secretkey, true));
		$headers[] = 'Date:' . $date;
		$id = 'E1BIO68GAVTOT1';

		$xml = '
	<?xml version="1.0" encoding="UTF-8"?>
	<StreamingDistributionConfig xmlns="http://cloudfront.amazonaws.com/doc/2010-11-01/">
	  <S3Origin>
	     <DNSName>codingfutures.s3.amazonaws.com</DNSName>
	     <OriginAccessIdentity>origin-access-identity/cloudfront/' . $id . '</OriginAccessIdentity>
	  </S3Origin>
	  <CallerReference>' . $CallerReference . '</CallerReference>
	  <Comment>Created by YourMembers</Comment>
	  <Enabled>true</Enabled>
		<TrustedSigners>
			<Self/>
		</TrustedSigners>
	</StreamingDistributionConfig>
	';
		$ch = curl_init($base . '2010-11-01/streaming-distribution');
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		$result = xml2array($result, 1, 'nottag');

		print_r($result);
	
	return;
	exit;
	
	echo '<textarea rows="20" cols="200">';
	echo $result;
	echo '</textarea>';
	exit;
	*/
	
	/*
	$xml = '
<?xml version="1.0" encoding="UTF-8"?>
<CloudFrontOriginAccessIdentityConfig 
xmlns="http://cloudfront.amazonaws.com/doc/2010-11-01/">
  <CallerReference>' . $CallerReference . '</CallerReference>   
  <Comment>Created by YourMembers</Comment>
</CloudFrontOriginAccessIdentityConfig>
';
	
	// awsaccesskey
	$userkey = get_option('yss_user_key');
	$secretkey = get_option('yss_secret_key');
	//: Wed, 05 Apr 2006 21:12:00 GMT
	$date = gmdate('D, d M Y H:i:s T');
	
	$headers[] = 'Authorization' . ':' . "AWS " . $userkey . ':' . base64_encode(hash_hmac('sha1', $date, $secretkey, true));
	$headers[] = 'Date:' . $date;
	
	$ch = curl_init($base . '2010-11-01/origin-access-identity/cloudfront');
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	
	echo '<textarea rows="5" cols="50">';
	echo $result;
	echo '</textarea>';
	exit;
	/**/
	
	// DOWNLOAD id: EDTW4HJMEDBAY S3canonical: 7b07c3a6070fd3e0ac3c7874f5f3187abe3fa9b2038f8af450436fceb92f386de49aa3ac6299b64471e739d97be964ec
	// STREAMIN id: E1BIO68GAVTOT1 S3canonical: 42c6edc17ea1ed1014a8c25ac223b657b25563a7f4a8958a30eca7d7f9780fbe9b68333cad9d81f60e339405c592270f
	$id = 'E1BIO68GAVTOT1';
	/*
	
	//: Wed, 05 Apr 2006 21:12:00 GMT
	$date = gmdate('D, d M Y H:i:s T');
	
	$headers[] = 'Authorization' . ':' . "AWS " . $userkey . ':' . base64_encode(hash_hmac('sha1', $date, $secretkey, true));
	$headers[] = 'Date:' . $date;
	
	$xml = '
<?xml version="1.0" encoding="UTF-8"?>
<StreamingDistributionConfig xmlns="http://cloudfront.amazonaws.com/doc/2010-11-01/">
  <S3Origin>
     <DNSName>codingfutures.s3.amazonaws.com</DNSName>
     <OriginAccessIdentity>origin-access-identity/cloudfront/' . $id . '</OriginAccessIdentity>
  </S3Origin>
  <CallerReference>' . $CallerReference . '</CallerReference>
  <Comment>Created by YourMembers</Comment>
  <Enabled>true</Enabled>
	<TrustedSigners>
		<Self/>
	</TrustedSigners>
</StreamingDistributionConfig>
';
/*
		$xml = '
<?xml version="1.0" encoding="UTF-8"?>
<DistributionConfig xmlns="http://cloudfront.amazonaws.com/doc/2010-11-01/">
  <S3Origin>
     <DNSName>codingfutures.s3.amazonaws.com</DNSName>
     <OriginAccessIdentity>origin-access-identity/cloudfront/' . $id . '</OriginAccessIdentity>
  </S3Origin>
  <CallerReference>' . $CallerReference . '</CallerReference>
  <Comment>Created by YourMembers</Comment>
  <Enabled>true</Enabled>
	<TrustedSigners>
		<Self/>
	</TrustedSigners>
</DistributionConfig>
';
/**/
	/*
	$ch = curl_init($base . '2010-11-01/streaming-distribution');
//	$ch = curl_init($base . '2010-11-01/distribution');
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	
	echo '<textarea rows="5" cols="50">';
	echo $result;
	echo '</textarea>';
	exit;
	
	// ID gen: E31A65TDB3K3U3 domain s3rx6jzvy3chqv
	
	/**/
	
	
	//stdClass Object ( [Statement] => Array ( [0] => stdClass Object ( [Resource] => RESOURCE [Condition] => stdClass Object ( [DateLessThan] => stdClass Object ( [AWS:EpochTime] => EXPIRES ) ) ) ) )
//	$str = '{"Statement":[{"Resource":"RESOURCE","Condition":{"DateLessThan":{"AWS:EpochTime":"EXPIRES"}}}]}';
//	echo $str;
//	$str = json_decode($str);
//	print_r($str);
//	return;
	
	/*
	$DomainName = '';
	$resource = 'S3Test.mov';
	$resource = 'https://' . $DomainName . '/' . $resource;
	$url = $resource;
	$resource = substr($resource, 0, -4);
	// if streaming remove .ext from resource
	
	$time = time() + 3600;
	//{"Statement":[{"Resource":"RESOURCE","Condition":{"DateLessThan":{"AWS:EpochTime":EXPIRES}}}]}
	/*
	$data = new StdClass(
		'Statement' => array(
			new StdClass(
				'Resource'	=> $resource,
				'Condition'	=> new StdClass(
					'DateLessThan'	=> new StdClass(
						'AWS:EpochTime'	=> $time
					)
				)
			)
		)
	);
	*/
	/*
	$data = '{"Statement":[{"Resource":"' . $resource . '","Condition":{"DateLessThan":{"AWS:EpochTime":' . $time . '}}}]}';
	
	$data = json_encode($data);
	$data = base64_encode(hash_hmac('sha1', $data, $secretkey, true));
	
	$key_pair_id = 'APKAIWQ3QFITOJS7WKIQ';
	
	$policy = 'MIICXAIBAAKBgQCXxjVi/s24fjSNKQt5PhS/hFTp9bDLS47P5/HRM+E9byZtYCPu34CF0fJgvBrTKHdaCo9VTQRJtALEBmPbUJeEXyhwQu81ix/zwA/1LOqqNflXhBotbiKKImhVUHekTmbULd7IRt31kI/GTWP3eXPf1wy+M6tiaRlvbHeU19Pg5wIDAQABAoGAPW/1snIUkoc7/JxN0bFosrH9sYtMGq8mS1DH2XiXx8eTlZjiUtLUctcutyvN7AYociFuLgh0IOWmbPVtPJ0eB86kp1DIwpMm9/RYAo2ctj6zbllu8UYnBJvFfNwS4A+3ubpF8tECBQQneKtHjU/YgHMtZ7bbAjbcgsnS9OKq+JECQQDRF2TjyDYftfp1qtahoo/iJi9zxs3iXfZQqmqfOa4GVU+J0YHajViDsFIvERVenM8oshdCYyu2GaqM9hKmsBQ5AkEAudL1OJw4YiAm0zhYXBnkKLZrOlGhIoYvGy+Jis8zO9MqlrgMCskopJKr9GcfceSU6mWMPSvBdIdhnKAe3rjeHwJBAMo5vxlTNY27+q49efLAgDqwxepLGCtcx3NDL3YqWjbD4fagi9/uHvLW4NWmxy1HqlBo1ngd5FPuRPaqtGVFHXkCQCVAZyxsFNmG/IGHYB9GiXin6SMNzjGvmK3of7g2BV1O3dDNIIzg+qbTSGl8as9YNYovxUdWCDgmE6lkaAvaH50CQHUlDpJhbartS7WSN8RsFXw92qQlIQJan2rI3bmGbq/AcmAcrpeUc0/EqC4rpPiQpV7gI17qYuo1PBnWV3DkM8E=';
	$policy = str_replace(array('+', '=', '/'), array('-', '_', '~'), base64_encode($policy));
	
	$data = str_replace(array('+', '=', '/'), array('-', '_', '~'), $data);
	
	$url = $resource . '?Policy=' . $policy . '&Signature=' . $data . '&Key-Pair-Id=' . $key_pair_id;
	*/
	
	// id: EDTW4HJMEDBAY S3canonical: 7b07c3a6070fd3e0ac3c7874f5f3187abe3fa9b2038f8af450436fceb92f386de49aa3ac6299b64471e739d97be964ec
	
	// streaming: sw32bb3cy99jf
	// download: d3dnuzxvi88bnp
	
//	84043422.flv%3FExpires%3D1310475624%26Signature%3DPHtUCE0Hcj6XYV4x1sI9tWD32N69lpOwCO6koCFY%7E02hFE1%7EF2cdXA7lwJmFV0ighaVyWs4tbZ7VeeUPx0qY%7Ey4Qi%7E0pVNtjtCRMaqSuonUjnFj13plJ2H2s4%7EwAuljcsa43oy69tPOAYcQBJLont-wU0YYChjvF6DGZF8I9cf0_%26Key-Pair-Id%3DAPKAIWQ3QFITOJS7WKIQ
	
//	$url = getSignedURL("http://d3dnuzxvi88bnp.cloudfront.net/S3Test.mov", 60 * 60);
	$url = getSignedURL("S3Test.mov", 60 * 60);
	
	echo $url;
	echo '<br /><script>
document.write(escape(\'' . $url . '\'));
</script><br />';

$url = explode('?', $url);

//'file': '" . implode('?', $url) . "',
/*
<script type='text/javascript'>
jwplayer('mediaspace').setup({
	    'flashplayer': '/jwplayer/player.swf',
	    'file': '84043422.flv',
	'streamer': 'rtmp://s3rx6jzvy3chqv.cloudfront.net/cfx/st',
	'provider': 'rtmp',
	    'controlbar': 'bottom',
	    'width': '640',
	    'height': '480',
		'autoplay': true
});
</script>
*/
echo "
	<script type='text/javascript' src='/jwplayer/jwplayer.js'></script>

	<div id='mediaspace'>This text will be replaced</div>

		<script type='text/javascript'>
		  jwplayer('mediaspace').setup({
		    'flashplayer': '/jwplayer/player.swf',
		    'file': 'S3Test.mov?" . $url[1]  . "',
		    'provider': 'rtmp',
		    'streamer': 'rtmp://s3rx6jzvy3chqv.cloudfront.net/cfx/st',
		    'controlbar': 'bottom',
		    'width': '470',
		    'height': '290'
		  });
		</script>
	<br />
	" . '
<!--
		<iframe src="' . str_replace('rtmp', 'http', implode('?', $url)) . '" style="width: 400px; height: 400px;"></iframe>
-->
	' . "
	<br />
	<a href='#nowhere' onclick='window.location.reload()'>Reload</a>
";
	
	return;
	
	
	
	
	function getSignedURL($resource, $timeout)
	{
		//This comes from key pair you generated for cloudfront
		$keyPairId = "APKAIWQ3QFITOJS7WKIQ";

		$expires = time() + $timeout; //Time out in seconds
		$json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';		

		//Read Cloudfront Private Key Pair
		$fp=fopen("/Users/barrycarlyon/WebWork/CodingFutures/yourmembers/wordpress_dev/wp-content/plugins/yss/admin/private_key.pem","r"); 
		$priv_key=fread($fp,8192); 
		fclose($fp); 
	//	$priv_key = 'MIICXAIBAAKBgQCXxjVi/s24fjSNKQt5PhS/hFTp9bDLS47P5/HRM+E9byZtYCPu34CF0fJgvBrTKHdaCo9VTQRJtALEBmPbUJeEXyhwQu81ix/zwA/1LOqqNflXhBotbiKKImhVUHekTmbULd7IRt31kI/GTWP3eXPf1wy+M6tiaRlvbHeU19Pg5wIDAQABAoGAPW/1snIUkoc7/JxN0bFosrH9sYtMGq8mS1DH2XiXx8eTlZjiUtLUctcutyvN7AYociFuLgh0IOWmbPVtPJ0eB86kp1DIwpMm9/RYAo2ctj6zbllu8UYnBJvFfNwS4A+3ubpF8tECBQQneKtHjU/YgHMtZ7bbAjbcgsnS9OKq+JECQQDRF2TjyDYftfp1qtahoo/iJi9zxs3iXfZQqmqfOa4GVU+J0YHajViDsFIvERVenM8oshdCYyu2GaqM9hKmsBQ5AkEAudL1OJw4YiAm0zhYXBnkKLZrOlGhIoYvGy+Jis8zO9MqlrgMCskopJKr9GcfceSU6mWMPSvBdIdhnKAe3rjeHwJBAMo5vxlTNY27+q49efLAgDqwxepLGCtcx3NDL3YqWjbD4fagi9/uHvLW4NWmxy1HqlBo1ngd5FPuRPaqtGVFHXkCQCVAZyxsFNmG/IGHYB9GiXin6SMNzjGvmK3of7g2BV1O3dDNIIzg+qbTSGl8as9YNYovxUdWCDgmE6lkaAvaH50CQHUlDpJhbartS7WSN8RsFXw92qQlIQJan2rI3bmGbq/AcmAcrpeUc0/EqC4rpPiQpV7gI17qYuo1PBnWV3DkM8E=';

		//Create the private key
		$key = openssl_pkey_get_private($priv_key);
		if(!$key)
		{
			echo "<p>Failed to load private key!</p>";
			return;
		}

		//Sign the policy with the private key
		if(!openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1))
		{
			echo '<p>Failed to sign policy: '.openssl_error_string().'</p>';
			return;
		}

		//Create url safe signed policy
		$base64_signed_policy = base64_encode($signed_policy);
		$signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

		//Construct the URL
		$url = $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$keyPairId;

		return $url;
	}