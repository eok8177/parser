<?php

use DiDom\Document;

function erkadob()
{
	global $debug;
	if ($debug == 1) {
		// echo $url;
	}

	$url = 'http://erkadob2b.pl/?subpage=skrzydlo_drzwiowe';
	$login = 'submited=logowanie&log_login=&log_pass=';
	$loginUrl = 'http://erkadob2b.pl/?page=nowe_zamowienie';

	$postUrl = 'http://erkadob2b.pl/?';
	$post = 'subpage=skrzydlo_drzwiowe&submited=t1&norma_ss=1&f1s=2&f2s=230&f6s=1&f3s=1&f5s=1&f4s=6&f7s=1&f22s=-1&f8s=1&dwzs=n&drds=n&f9s=1&f40s=-1&f10s=1&f15s=27&f21s=-1&f11s=0&f12s=1&dos=n&f13s=-1&f14s=-1&bls=-1&f24s=-1&f4s_os=-1';


	$parser = new Parser;

	$html = $parser->loginCurl($login, $loginUrl, $url, $postUrl, $post);
	echo "".print_r($html, true).""; exit();

	$html = $parser->curl_post($url_post, $url_get);
	echo "".print_r($html, true).""; exit();

	$head = $parser->parse_approvedevent($url);
	echo "<pre>".print_r($head, true)."</pre>"; exit();
}




/**
* Class parser
*/
class Parser extends Document
{
	public $data = array();

	function __construct()
	{
		# code...
	}


	function loginCurl ($login, $loginUrl, $parseURL, $postUrl, $post){

		$cookie_file_path = 'cookie.txt';

		//init curl
		$ch = curl_init();

		//Set the URL to work with
		curl_setopt($ch, CURLOPT_URL, $loginUrl);

		// ENABLE HTTP POST
		curl_setopt($ch, CURLOPT_POST, 1);

		//Set the post parameters
		curl_setopt($ch, CURLOPT_POSTFIELDS, $login);

		//Handle cookies for the login
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);

		//Setting CURLOPT_RETURNTRANSFER variable to 1 will force cURL
		//not to print out the results of its query.
		//Instead, it will return the results as a string return value
		//from curl_exec() instead of the usual true/false.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		//execute the request (the login)
		$store = curl_exec($ch);

		//the login is now done and you can continue to get the
		//protected content.

		//set the URL to the protected file
		// curl_setopt($ch, CURLOPT_URL, $parseURL);


		curl_setopt($ch, CURLOPT_URL, $postUrl);
		// ENABLE HTTP POST
		curl_setopt($ch, CURLOPT_POST, 1);
		//Set the post parameters
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		//Handle cookies for the login
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);

		//execute the request
		$content = curl_exec($ch);

		return $content;

	}
}


