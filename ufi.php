<?php

use DiDom\Document;

function ufi()
{
	$url = 'http://www.ufi.org/Public/ufimembers/Fiche.aspx?Clef_APPROVED_EVENTS=747&Clef_COMPANIES=190&Rub=UFIApprovedEventsDetails';
	// $url = 'ufi.html';

	$url_post = 'http://www.ufi.org/Public/ufimembers/UFIApprovedEvents.aspx?Clef_SITESMAPS=81&Clef_SITESMAPS=87';
	$url_get = 'http://www.ufi.org/Public/ufimembers/UFIApprovedEvents.aspx';


	global $debug;
	if ($debug == 1) {
		echo $url;
	}

	$parse = new Parser;

echo "<br>Parse_______________________________________<br>";
	$head = $parse->parse_approvedevent($url);
	echo "<pre>".print_r($head, true)."</pre>";

echo "<br>Get links<br>_______________________________________";
	$html = $parse->curl_post($url_post, $url_get);
	echo "".print_r($html, true).""; 
}




/**
* UFI parser
*/
class Parser extends Document
{
	public $data = array();

	function __construct()
	{
		# code...
	}

	/**
	 * @param  string $url
	 * @return array $this->data
	 */
	function parse_approvedevent($url)
	{
		$doc = new Document($url, true);

		$title = $doc->find('.descr_approvedevent')[0]->text();
		$this->data['title'] = trim(preg_replace('/\s{2,}/', '', $title)); //remove whitespaces

		foreach ($doc->find('div.caracs_approvedevent div') as $element) {
			$div_id = $element->attr('id');

			switch ($div_id) {
				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_OrganizingCompany_Panel':
					$el = $element->find('a')[0]->text();
					$this->data['orginizing_company'] = trim(preg_replace('/\s{2,}/', '', $el));
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_OrganiserLocation_Panel':
					$el = $element->find('span')[0]->text();
					$this->data['orginiser_location'] = $el;
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_BusinessSectors_Panel':
					$el = $element->find('span')[0]->text();
					$this->data['business_sectors'] = $el;
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_Frequency_Panel':
					$el = $element->find('span')[0]->text();
					$this->data['frequency'] = $el;
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_EventOpenTo_Panel':
					$el = $element->find('span')[0]->text();
					$this->data['event_open_to'] = $el;
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_Email_Panel':
					$el = $element->find('a')[0]->text();
					$this->data['email'] = $el;
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_Website_Panel':
					$el = $element->find('a')[0]->attr('href');
					$this->data['website'] = $el;
					break;

				case 'ctl00_ContentPlaceHolder1_UFIApprovedEventsDetails1_Links_Panel':
					foreach ($element->find('a') as $key=>$href) {
						$this->data['links'][$key] = $href->attr('href');
					}
					break;

			}
		}
		$this->data['session'] = $this->parse_session($doc);

		return $this->data;
	}

	/**
	 * Not End
	 * @param  object $doc
	 * @return array $this->data
	 */
	function parse_session($doc)
	{
		$session = array();
		$years = array();

		$table = $doc->find('table.tbl1 tr');
		// echo "<pre>".print_r($table, true)."</pre>";

		foreach ($table[0]->find('td') as $key => $value) {
			$val = $value->text();
			if (!empty($val)){
				$years[$key] = $val;
			}
			
		}
		echo "<pre>".print_r($years, true)."</pre>";

		return $session;
	}


	/** 
	* Send a POST requst using cURL 
	* @param string $url to request 
	* @param array $post values to send 
	* @param string $url to get after request
	* @return string 
	*/ 
	function curl_post($url, $post = false, $url_get = false){

		$ckfile = tempnam("/tmp", "CURLCOOKIE");
		$useragent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0';
		$f = fopen('log.txt', 'w'); // file to write request header for debug purpose

		// regular expressions to parse out the special ASP.NET
		// values for __VIEWSTATE and __EVENTVALIDATION
		$regexViewstate = '/__VIEWSTATE\" value=\"(.*)\"/i';
		$regexEventVal  = '/__EVENTVALIDATION\" value=\"(.*)\"/i';
		$regexViewstateGenerator  = '/__VIEWSTATEGENERATOR\" value=\"(.*)\"/i';
		$regexToolkitScriptManager  = '/ctl00_ToolkitScriptManager_HiddenField&amp;_TSM_CombinedScripts_=(.*)\" type/i';

		//init curl
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("application/x-www-form-urlencoded"));

		$html = curl_exec($ch);
		curl_close($ch);

		$viewstate = $this->regexExtract($html, $regexViewstate);
		$eventval = $this->regexExtract($html, $regexEventVal);
		$viewstategenerator = $this->regexExtract($html, $regexViewstateGenerator);
		$toolkitscriptmanager = $this->regexExtract($html, $regexToolkitScriptManager);
		$toolkitscriptmanager = urldecode($toolkitscriptmanager);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_STDERR, $f);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8", "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3"));

		// Collecting all POST fields
		$postfields = array();
		$postfields['ctl00_ToolkitScriptManager_HiddenField'] = $toolkitscriptmanager;
		$postfields['__VIEWSTATEGENERATOR'] = $viewstategenerator;
		$postfields['__EVENTTARGET'] = "";
		$postfields['__EVENTARGUMENT'] = "";
		$postfields['__VIEWSTATE'] = $viewstate;
		$postfields['__EVENTVALIDATION'] = $eventval;
		$postfields['ctl00$BlocIdentification1$Login'] = "Login";
		$postfields['ctl00$BlocIdentification1$Password'] = "Password";
		$postfields['ctl00$Search'] = "SEARCH";
		$postfields['ctl00$ContentPlaceHolder1$DDL_CountryRegionOrganiser'] = '44';
		$postfields['ctl00$ctl00$cplhMain$cplhContent$hdnPasswordDefault'] = 'Password';
		$postfields['ctl00$ContentPlaceHolder1$GridView_Sort_Hidden'] = "Libelle_APPROVED_EVENTS";
		$postfields['ctl00$ContentPlaceHolder1$ButtonSearch'] = "Search";
		$postfields['ctl00$ContentPlaceHolder1$DDL_BusinessSector'] = "2147483647";
		$postfields['ctl00$ContentPlaceHolder1$DDL_CountryRegionEvent'] = "2147483647";
		$postfields['ctl00$ContentPlaceHolder1$DDL_CityEvent'] = "2147483647";
		$postfields['ctl00$ContentPlaceHolder1$DDL_CityOrganiser'] = "2147483647";
		$postfields['ctl00$ContentPlaceHolder1$DDL_Company'] = "2147483647";

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

		$data = curl_exec($ch);
		curl_close($ch);
echo "".print_r($data, true).""; exit();



		//show information regarding the request
		print_r(curl_getinfo($ch));
		echo curl_errno($ch) . '-' . curl_error($ch);

		return $store;

	}

	/************************************************
	* utility function: regexExtract
	*    use the given regular expression to extract
	*    a value from the given text;  $regs will
	*    be set to an array of all group values
	*    (assuming a match) and the nthValue item
	*    from the array is returned as a string
	************************************************/
	private function regexExtract($text, $regex, $nthValue = 1)
	{
		if (preg_match($regex, $text, $regs)) {
			$result = $regs[$nthValue];
		}
		else {
			$result = "";
		}
		return $result;
	}
}


