<?php

require_once 'DiDom/Document.php';
require_once 'DiDom/Element.php';
require_once 'DiDom/Query.php';

use DiDom\Document;

$document = new Document('http://novaposhta.ua/ru/office', true);

$cities = $document
    ->find('.list')[0]
    ->find('li');

echo 'Total cities: ' . count($cities) . '<br>';

foreach ($cities as $city) {
    echo $city->text() . '<br>';
}
