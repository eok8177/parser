<html>
<head>
	<meta charset="utf8">
</head>

<?php

require_once 'DiDom/Document.php';
require_once 'DiDom/Element.php';
require_once 'DiDom/Query.php';

use DiDom\Document;

// $url = 'http://www...';
$url = '01.html';

$document = new Document($url, true);

$head = parse_head($document);
file_put_contents('test.txt', $head);
echo "<pre>".print_r($head, true)."</pre>";

$content = parse($document, $head);
echo "<pre>".print_r($content, true)."</pre>";


function parse_head($doc)
{
	$head = array();
	//Two lines: rowspan=2 -> Select <td> with colspan =2 from last <tr>
	$second_line = array();
	$i = 1;
	foreach ($doc->find('thead tr')[1]->find('td') as $element) {
		$el = $element->text();
		$el = trim(preg_replace('/\s{2,}/', '', $el)); //remove whitespaces
		$second_line[$i] = preg_replace("/\s/",'',$el);
		$i++;
	}

	//Parse First line <tr>
	$i = 1;
	foreach ($doc->find('thead tr')[0]->find('td') as $element) {
		$el = $element->text();
		$el = trim(preg_replace('/\s{2,}/', '', $el)); 
		$colspan = ($element->attr('colspan')) ? ($element->attr('colspan') - 1) : 0;

		if ($colspan > 0) {
			foreach ($second_line as $key => $value) {
				$i+=($key - 1);
				$el = $value;
				
				$head[$i] = preg_replace("/\s/",'',$el);
			}
		}
		$head[$i] = $el;
		$i++;
	}
	return $head;
}

function parse($doc = false, $head = false)
	{
		end($head);
		$num_last_td = key($head);

		$i = 1;
		$list = array();
		$data = array();

		foreach ($doc->find('tbody tr.radek td.bunka') as $element) {

			switch ((string)$head[$i]) {
				case 'Состояние': //condition
					$data['active'] = 1;
					$element = $element->find("div.ikony1 img");
					for ($j = 0; $j < 3; $j++) {
						$el = $element[$j]->getAttribute('onmouseover');
						if ($el) {
							$el = preg_replace("/.*CAPTION, '/", "", $el);
							$el = preg_replace("/', FGCOLOR.*/", "", $el);
							if ((string)$el == 'Резервация (ведутся переговоры с другим клиентом)'){
								//Reserved
								$data['active'] = 0;
							}
						}
					}
					break;

				case 'Населенный пункт – РайонНазвание проекта': //Locality
					$el = $element->find('div')[0]->text();
					$el = trim(preg_replace('/\s{2,}/', ' ', $el));
					$data['locality'] = $el;
					break;

				case 'Корпус, этаж': //floor
					$el = $element->find('span.vetsi-pismo')[0]->text();
					$data['floor'] = (int)preg_replace("/\D/", "", $el); //only numbers

					$el = $element->find('b')[0]->text(); //num of house
					$data['number'] = $el . $data['floor'];
					break;

				case 'Номерквартиры': //Apartment number
					$data['number'] .= "B".(int)$element;
					break;

				case 'Планировка, дополнительные удобства': //planing
					$el = $element->find('div.typ-bytu')[0]->text();
					$el = preg_replace("/\D/", "", $el); //only numbers
					$data['planning'] = $el;

					$others = false;
					$element = $element->find("div.ikony2 img");
					for ($j = 0; $j < 6; $j++) {
						$el = $element[$j]->getAttribute('onmouseover');
						if ($el) {
							$el = preg_replace("/.*CAPTION, '/", "", $el);
							$el = preg_replace("/', FGCOLOR.*/", "", $el);
							if ($others) {
								$others .= ', ' . $el;
							} else {
								$others = $el;
							}
						}
					}
					if ($others) {
						$data['others'] .= $others;
					}
					break;

				case 'Общаяжилаяплощадь(м²)': //Living area
					$el = $element->text();
					$data['area_living'] = (float)str_replace(',', '.', $el);
					break;

				case 'Общаяплощадь(по контуруобъекта)(м²)': //Total area
					$el = $element->text();
					$data['area_total'] = (float)str_replace(',', '.', $el);
					break;

				case 'Ценажилой площадиквартиры(в чеш.кронах/м²)': //price of m2
					$el = $element->text();
					$data['price_m'] = (int)str_replace('.', '', $el);
					break;

				case 'Ценадополнительныхэлементов отделки(чеш.крон без НДС)': //price
					$el = $element->text();
					$data['price_plus'] = (int)str_replace('.', '', $el);
					break;

				case '(без НДС)': //full price without DPH
					$el = $element->text();
					$data['price'] = (int)str_replace('.', '', $el);
					break;

				case '(с НДС)': //full price with DPH
					$el = $element->text();
					$data['price_inc_dph'] = (int)str_replace('.', '', $el);
					break;

				case 'Сроксдачи': //date
					$el = $element->text();
					$data['date'] = trim(preg_replace('/\s{2,}/', ' ', $el));
					break;

				case 'Сторонысвета,кудавыходяткомнаты': //orientation
					$others = '';
					$element = $element->find("img");
					for ($j = 0; $j < 4; $j++) {
						$el = $element[$j]->getAttribute('onmouseover');
						if ($el) {
							$el = preg_replace("/.*CAPTION, '/", "", $el);
							$el = preg_replace("/', FGCOLOR.*/", "", $el);
							if ($others) {
								$others .= ', ' . $el;
							} else {
								$others = $el;
							}
						}
					}
					if ($others) {
						$data['others'] .= ', ' . $others;
					}
					break;

				case 'ФотогалереяИнформацияо квартире': //Foto
					$el = $element->text();
					$data['foto'] = $el;
					break;
			}

			++$i;

			if ($i == ($num_last_td + 1)) {
				$i = 1;
				$list[] = $data;
				$data = array();
			}
		}

		return $list;
	}