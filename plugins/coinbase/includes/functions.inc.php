<?php

function coinbaseRequest($directory, $getOrPost, $apiKey, $apiSecret, $parameters)
{
	$ch = curl_init();
	$nonce = sprintf('%0.0f',round(microtime(true) * 1000000));
	$url = "https://coinbase.com/api/v1/" . $directory;
	$parameters = @http_build_query(json_decode($parameters), true);

	if($getOrPost == "post")
	{
		curl_setopt_array($ch, array(CURLOPT_POSTFIELDS => $parameters, CURLOPT_POST => true));
	}
	elseif($parameters != "")
	{
		$url .= "?" . $parameters;
		$parameters = "";
	}

	curl_setopt_array($ch, array(
								CURLOPT_URL => $url,
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_HTTPHEADER => array(
															"ACCESS_KEY: " . $apiKey,
															"ACCESS_NONCE: " . $nonce,
															"ACCESS_SIGNATURE: " . hash_hmac("sha256", $nonce . $url . $parameters, $apiSecret)
															)
								)
	);

	$results = curl_exec($ch);
	curl_close($ch);
	return $results;
}