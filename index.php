<html>
<head>
	<meta charset="utf8">
</head>

<?php

require_once 'DiDom/Document.php';
require_once 'DiDom/Element.php';
require_once 'DiDom/Query.php';

use DiDom\Document;

$url = 'http://www.central-group.ru/vyhledavani-byty.aspx?formular=rozsirene&cisloStranky=1&nic=on&nic=on&f1cena1=&f1cena2=&f1Dokoncene1=on&f1Dokoncene2=2017-06-30&f1NastaveniVypisu=1&f1NastaveniVypisuPocet=50&f2Lokalita_h1=9&f2Lokalita_h1_o9=10&f2Lokalita_h1_o9_c10=3281&f2Lokalita_h1_o9=27&f2Lokalita_h1_o9_c27=3315&f2Lokalita_h1_o9=46&f2Lokalita_h1_o9_c46=3322&nic=on&nic=on&nic=1&nic=0&f2patro1=x&f2patro2=x&nic=on&f2cena1=&f2cena2=&f2Dokoncene1=on&f2Dokoncene2=2017-06-30&f2NastaveniVypisu=1&f2NastaveniVypisu=0&f2NastaveniVypisu=0&f2NastaveniVypisuPocet=90';

$document = new Document($url, true);

// $cities = $document
//     ->find('thead tr')[0]
//     ->find('td');
$head = parse_head($document);
echo "<pre>".print_r($head, true)."</pre>";
// echo 'Total cities: ' . count($cities) . '<br>';

// echo "test: <pre>".print_r($cities, true);

// foreach ($cities as $city) {
//     echo $city->text() . '<br>';
// }



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
	// echo "<pre>".print_r($second_line, true)."</pre>";

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

public function parse($parser_id, $insert = false, $head = false)
	{
		end($head);
		$num_last_td = key($head);

		$i = 1;
		$data = $this->initData();

		foreach ($this->doc->find('tbody tr.radek td.bunka') as $_el) {
			$el = pq($_el)->html();

			switch ((string)$head[$i]) {
				case 'Stav': //Состояние
					// echo "$i = $head[$i]";
					$data['active_parser'] = 1;
					$el = '';
					for ($j = 0; $j < 3; $j++) {
						$element = pq($_el)->find("div.ikony1 img:eq($j)")->attr('onmouseover');
						if ($element) {
							$element = preg_replace("/.*CAPTION, '/", "", $element);
							$element = preg_replace("/', FGCOLOR.*/", "", $element);
							if ($el) {
								$el .= ', ' . $element;
							} else {
								$el = $element;
							}
							if ((string)$element == 'V jednání o prodeji s jiným zájemcem'){
								//Зарезервировано
								$data['active_parser'] = 0;
								// echo "Status: $element <br>";
							}
						}
					}
					$data['memo_admin'] = $el;
					break;

				case 'Obec – Část obceNázev lokalita': //Населенный пункт – Район (Название проекта)
					// echo "$i = $head[$i]";
					$el = pq($_el)->find('div')->text();
					$el = trim(preg_replace('/\s{2,}/', ' ', $el)); //убрать пробелы
					break;

			//!!! $data['property_number']= корпус.этаж.В.квартира
				case 'Byt. dům,podlaží': //Корпус, этаж
					// echo "$i = $head[$i]";
					$el = pq($_el)->find('span.vetsi-pismo')->text();
					$el = preg_replace("/\D/", "", $el); //Вытянуть только цифры
					$data['floor_num'] = (int)$el;

					$el = pq($_el)->find('b')->text(); //Номер дома (корпус)
					$data['property_number'] = $el . $data['floor_num'];
					break;

				case 'Číslobytu': //Номер квартиры
					// echo "$i = $head[$i]";
					$el = (int)$el;
					$data['property_number'] .= "B".$el;
					// $el = $data['property_number'];
					break;

				case 'Dispozice, příslušenství': //Планировка, дополнительные удобства
					// echo "$i = $head[$i]";
					$el = pq($_el)->find('div.typ-bytu')->text();
					$el = preg_replace("/\D/", "", $el); //Вытянуть только цифры
					if (($rooms = substr("$el", -1)) !== false) {
						$el = $rooms;
					}
					$data['planning_id'] = $el;

					// ---- Атрибуты вытянуть ---
					$el = false;
					for ($j = 0; $j < 6; $j++) {
						$element = pq($_el)->find("div.ikony2 img:eq($j)")->attr('onmouseover');
						if ($element) {
							$element = preg_replace("/.*CAPTION, '/", "", $element);
							$element = preg_replace("/', FGCOLOR.*/", "", $element);
							if ($el) {
								$el .= ', ' . $element;
							} else {
								$el = $element;
							}
						}
					}
					if ($el) {
						$data['memo_admin'] .= $el;
					}
					break;

				case 'Vnitřníužitnáplocha(m2)': //Жилая площадь
					// echo "$i = $head[$i]";
					$el = (float)str_replace(',', '.', $el);
					$data['area_living'] = $el;
					break;

				case 'Celkovápodlahováplocha(m2)': //Общая площадь
					// echo "$i = $head[$i]";
					$el = pq($_el)->find('span')->text();
					$el = (float)str_replace(',', '.', $el);
					$data['area_total'] = $el;
					break;

				case 'Cenavnitřní užitnéplochy bytu(Kč/m2)': //Цена за метр
					// echo "$i = $head[$i]";
					$el = (int)str_replace('.', '', $el);
					break;

				case 'Cenaurčenýchpříplatkovýchpoložek(Kč)': //Цена дополнительных элементов отделки (чеш.крон без НДС)
					// echo "$i = $head[$i]";
					break;

				case '(Kčbez DPH)': //Полная цена Без НДС
					// echo "$i = $head[$i]";
					$el = (int)str_replace('.', '', $el);
					$data['price'] = $el;
					break;

				case '(Kč s DPH)': //Полная цена с НДС
					// echo "$i = $head[$i]";
					$el = (int)str_replace('.', '', $el);
					$data['price_inc_dph'] = $el;
					break;

				case 'Termíndokončení': //Срок сдачи
					// echo "$i = $head[$i]";
					$el = pq($_el)->text();
					$el = trim(preg_replace('/\s{2,}/', ' ', $el)); //убрать пробелы
					break;

				case 'Orientaceobytnýchmístností': //Ориентация
					// echo "$i = $head[$i]";
					$el = '';
					for ($j = 0; $j < 4; $j++) {
						$element = pq($_el)->find("img:eq($j)")->attr('onmouseover');
						if ($element) {
							$element = preg_replace("/.*CAPTION, '/", "", $element);
							$element = preg_replace("/', FGCOLOR.*/", "", $element);
							if ($el) {
								$el .= ', ' . $element;
							} else {
								$el = $element;
							}
						}
					}
					if ($el) {
						$data['memo_admin'] .= ', ' . $el;
					}
					break;

				case 'FotogalerieDetail bytu': //Фото
					// echo "$i = $head[$i]";
					$el = '';
					break;
			}

			// Тут всегда активные объявления
			// $data['active_parser'] = 1;

			// Тип недвижимости (2 - квартира, 3 - дом, 4 - коммерческая недвижимость)
			$data['type_id'] = 2;

			// Номер парсера
			$data['parser_id'] = $parser_id;

			// Номер проекта
			$data['project_id'] = 41;

			// Формируем уникальное поле для записи
			$data['unique_id'] = $data['parser_id']
				. '-' . $data['property_number'];

			++$i;

			if ($i == ($num_last_td + 1)) {
				$i = 1;
				$this->list[] = $data;
				$data = $this->initData();
			}
		}

		return $this->list;
	}