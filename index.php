<?php
header( 'Content-type: text/html; charset=utf-8' );

$debug = 1;

$parser = isset( $_GET['parser'] ) ? $_GET['parser'] : '';


if (!file_exists("{$parser}.php")) 
	{
		echo "Parser {$parser} not exist";
		exit();
	}

/*-------------*/
require_once 'DiDom/Document.php';
require_once 'DiDom/Element.php';
require_once 'DiDom/Query.php';
/*-------------*/

require_once "{$parser}.php";
$parser();
