<?php
	$db = "jd"; #база данных
	$user = "root"; #пользователь
	$pass = ""; #пароль
	$mysqli = mysqli_connect("localhost", $user, $pass, $db) OR DIE ("Ошибка: " . mysqli_error($mysqli)); #переход по ссылке

	require_once("simplehtmldom_1_9_1/simple_html_dom.php");
	//$url = "https://www.jd.ru/600971992.html"; // v
	//$url =  "https://www.jd.ru/product/775051.html"; // на этой ссылке не работает
	//$url =  "https://www.jd.ru/600220381.html"; // v
	//$url =  "https://www.jd.ru/650508862.html"; // v
	$url =  "https://www.jd.ru/600304832.html"; // v
	 
	$html = getHtml($url);

	$json_ = getJSONFromScript($html); // это будет использоваться!!
	$spuId = getSpuId($html, $json_); // исходный товар id
	$skuId = getSkuId($html, $json_); // наш товар id 

	$currTitle = getTitle($html);
	echo "<h3>" . $currTitle ."</h3>";

	$mainImg = getMainImage($html); // $html->find('img[id="spec-img"]'); 
	echo $mainImg."<br>";
	$attrVal = $mainImg->getAttribute('src');

//----------------------------------------------------------------------------
	echo "spuId: ".$spuId."<br>";
	echo "skuId: ".$skuId."<br>";
	if (getDiscountPrice($skuId, "RUB") != 0) echo "discount price: ".getDiscountPrice($skuId, "RUB")."<br>";
	echo "price: ".getJdPrice($skuId, "RUB")."<br>";
	echo "rating: ".getRating($spuId)."<br>";

//----------------------------------------------------------------------------
//----------------------------------------------------------------------------
	$colors = getColors($html); 
	echo "<br><b> Цвета: </b><br>";
	foreach ($colors as $col)
	{
		echo $col."<br>";
	}
	echo "<br>";
//----------------------------------------------------------------------------
//----------------------------------------------------------------------------
	$allclrs = $json_['colorSize']; // 
	$opts = array();
	foreach ($allclrs as $key => $col) {
		$skuColor = 'NULL';
		$skuRam = 'NULL';
		if (array_key_exists('attributesMap', $col)) {
			// '10494' - параметр цвет
			if (array_key_exists('10494', $col['attributesMap'])) {
				$data_value = $col['attributesMap']['10494'];//	сравнить из html 
				$finded = $html->find('li[data-value="' . $data_value . '"]');
				$skuColor = $finded[0]->getAttribute('data-title');
			}
			// '2021' - параметр ram память
			if (array_key_exists('2021', $col['attributesMap'])) {
				$data_value2 = $col['attributesMap']['2021'];	
				$finded2 = $html->find('li[data-value="' . $data_value2 . '"]');
				$skuRam = $finded2[0]->getAttribute('data-title');
			}
			//echo  $col['skuId'] . " | " . $data_value . ": " . $skuColor . " | " . $data_value2 . ": " . $skuRam . "<br>";
		}
		$opts[$col['skuId']] = new Options($skuColor, $skuRam);
	}

	//foreach ($opts as $key => $value) {
	//	echo $key . "| color: " . $value->color . "; ram: " . $value->ram . "<br>";
	//}
//----------------------------------------------------------------------------
//----------------------------------------------------------------------------
	$skuIds = getOtherSkuIds($json_, $skuId);
	array_push($skuIds, $skuId);
	foreach ($skuIds as $key => $currSkuId) {
		echo $key+1 . ". skuId: $currSkuId<br>";
		$skuUrl = "https://www.jd.ru/$currSkuId.html";
		$skuHtml = getHtml($skuUrl);
		$skuTitle = getTitle($skuHtml); $skuTitle = rtrim(trim($skuTitle)); // очистка от пробелов спереди сзади
		$skuImgUrl = getMainImageUrl($skuHtml);  
		saveImage($skuImgUrl);
		$imagePath = getImgName($skuImgUrl);
		$skuPrice = getDiscountPrice($currSkuId, "RUB");
		$skuPriceUSD = getDiscountPrice($currSkuId, "USD");
		//echo "skuId: $currSkuId<br>title: $title<br>imagePath: $imagePath<br>spuId: $spuId<br>skuPrice: $skuPrice<br><br>";
		addSubProductToDB($mysqli, $currSkuId, $skuTitle, $imagePath, $spuId, $skuPrice, $skuPriceUSD);
		addOptionsToDB($mysqli, $currSkuId, $opts[$currSkuId]->color, $opts[$currSkuId]->ram);
	}
//----------------------------------------------------------------------------
//----------------------------------------------------------------------------
	$delivers = getDelivers($skuId);
	foreach ($delivers as $key => $value) {
		addDeliveryToDB($mysqli, $spuId, $value->_from, $value->_to, $value->priceAndDelivers[0]->days, $value->priceAndDelivers[0]->deliver, $value->priceAndDelivers[0]->price);	
	} 
//----------------------------------------------------------------------------
//----------------------------------------------------------------------------
	$desc = getDescription($spuId, $skuId);
	echo "<br><b>Описание</b><br>";
	echo "$desc";
	echo "<br>";
	/*$strDesc = "";
	$array = $desc->find('div');
	foreach ($array as $key => $value) {
		$strDesc .=  $value;//->plaintext;//echo $value;

	}
	echo "$strDesc <br>";*/
	addProductToDB($mysqli, $spuId, $desc, getRating($spuId));
//----------------------------------------------------------------------------
//----------------------------------------------------------------------------

//----------------------------------------------------------------------------
	//addProductToDB($mysqli, $spuId, $description, $rating);
	//addSubProductToDB($mysqli, $spuId, $skuId, $title, $imagePath, $price);
	
	//addOptionsToDB($mysqli, $skuId, $color, $memory);
    // delivery
	// INSERT INTO `delivery` (`id`, `_from`, `_to`, `days`, `deliver`, `skuId`, `price`) 
	// VALUES                 (NULL, '_from', '_to', 'days', 'deliver', 'skuId', 'price');

    // options
        // INSERT INTO `options` (`skuId`, `color`, `memory`) 
        // VALUES 		 (NULL,    'color', 'memory');

    // product 
    	// INSERT INTO `product` (`spuId`, `description`, `rating`)
    	// VALUES                ('spuId', 'description', 'rating');

    // sub_product
	// INSERT INTO `sub_product` (`skuId`, `title`, `image`, `spuId`, `price`, `price_usd`)
	// VALUES                    ('skuId', 'title', 'image', 'spuId', 'price', 'price_usd');

//-----------------------------------------------------------------------------
// ------- functions ----------------------------------------------------------
// возможно понадобится $link - ссылка на базу данных для работы с ней (решение: $mysqli) 
	function addProductToDB($mysqli, $spuId, $description, $rating)
	{
		$mysqlreq = "INSERT INTO product (spuId, description, rating) VALUES ('$spuId', '$description', '$rating') ON DUPLICATE KEY UPDATE description = '$description', rating = '$rating'";
		$res = $mysqli->query($mysqlreq);
		if (!$res)
			echo "Ошибка: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	}

	function addSubProductToDB($mysqli, $skuId, $title, $imagePath, $spuId, $price, $price_usd)
	{
		$mysqlreq = "INSERT INTO sub_product (skuId, title, image, spuId, price, price_usd) VALUES ('$skuId', '$title', '$imagePath', '$spuId', '$price', '$price_usd') ON DUPLICATE KEY UPDATE title = '$title', image = '$imagePath', spuId = '$spuId', price = '$price', price_usd = '$price_usd'";
		$res = $mysqli->query($mysqlreq);
		if (!$res)
			echo "Ошибка: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	}


	function addDeliveryToDB($mysqli, $spuId, $_from, $_to, $days, $deliver, $delivery_price)
	{
		$searchReq = "SELECT id FROM delivery WHERE spuId = '$spuId' AND _from = '$_from' AND _to = '$_to' AND days = '$days' AND deliver = '$deliver'";
		$resId = $mysqli->query($searchReq);
		$row = mysqli_fetch_array($resId);
		//echo " <b>id = " . $row['id'] . "</b><br>";
		if (empty($row)) {
			$mysqlreq = "INSERT INTO delivery (id, _from, _to, days, deliver, spuId, price) VALUES (NULL, '$_from', '$_to', '$days', '$deliver', '$spuId', '$delivery_price')"; //ON DUPLICATE KEY UPDATE _from = '$_from', _to = '$_to', days = '$days', deliver = '$deliver', spuId = '$spuId', price = '$delivery_price'";
			$res = $mysqli->query($mysqlreq);
		} else {
			$updReq = "UPDATE delivery SET price = '$delivery_price' WHERE id = '$row[0]'";
			$res = $mysqli->query($updReq);
		}
		if (!$res)
			echo "Ошибка: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
		

	}

	function addOptionsToDB($mysqli, $skuId, $color, $memory)
	{	
		$mysqlreq = "INSERT INTO options (skuId, color, memory) VALUES ('$skuId', '$color', '$memory') ON DUPLICATE KEY UPDATE color='$color', memory='$memory'";
		$res = $mysqli->query($mysqlreq);
		if (!$res)
			echo "Ошибка: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	}

	function getImgName($imgUrl)
	{
		$imgName = getStringBetween($imgUrl, '/', '.jpg');
		return $imgName;	
	}

	// Сохранение изображения 
	function saveImage($imgUrl)
	{
		$imgName = getImgName($imgUrl);
		$imgPath = "./image/$imgName.jpg"; // папка image/<имя_картинки>.jpg
		$imgSrc = "http:".$imgUrl; // без http не прогружает
		copy ($imgSrc, $imgPath);
	}

	// v - поставщики 'https://async.joybuy.com/product/querySkuStock.html?callback=jQuery172012133581148460593_1584708453777&skuId=600971992&venderId=217&destCountryId=2285&deliveryCountryId=&num=1&languageId=3&price=163.99&currency=RUB&_=1584708455739'

	// v - отсюда можно достать доставщиков https://async.joybuy.com/product/querySkuStock.html?callback=jQuery172017413141781096475_1584293151358&skuId=600971992&venderId=217&destCountryId=2285&deliveryCountryId=&num=1&languageId=3&price=163.99&currency=RUB&_=1584293154377

	// x - найти скрипт в html где находится информация о товарах
	

	// div[id="choose-attrs"] /
		// div[class="summary-color"]
		// div[class="summary-size"] 
			// div[class="dt"] - название параметра
			// attributesMap 10494 - цвет div[]  
			// attributesMap 2021 - память

	function getDelivers($skuId)
	{
		// ассоциативный массив. коды стран указанных в JD
		$countries = array(
			"2285" => "Россия",
			"2456" => "USA",
			"2262" => "Brazil",
			"2444" => "United Kingdom",
			"2288" => "France",
			"2315" => "Canada" ,
			"2371" => "Mexico",
			"2427" => "Spain",
			"2424" => "Ukraine",
			"2254" => "Australia",
			"2468" => "China",
			"2281" => "Germany",
			"2428" => "Greece",
			"2455" => "HongKong,China",
			"2432" => "Hungary",
			"2443" => "Indonesia",
			"2247" => "Ireland",
			"2440" => "Israel",
			"2441" => "Italy",
			"2385" => "Japan",
			"2321" => "Kazakhstan",
			"2453" => "Macao,China",
			"2305" => "Netherlands",
			"2395" => "Saudi Arabia",
			"2304" => "South Korea",
			"2387" => "Switzerland",
			"2454" => "Taiwan,China",
			"2411" => "Thailand",
			"2447" => "Vietnam"
		);
		// тянем цену в долларах для запроса
		$priceUsd = getDiscountPrice($skuId, "USD"); 
		// если нет скидочной цены
		if ($priceUsd == 0) 
			$priceUsd = getJdPrice($skuId, "USD");

		$deliveryCountryId = 2468; // закрепим только поставщиков с китая(а будут ли другие?) 
		$url = '';
		$delivers = array();
		foreach ($countries as $key => $value) {
			// ссылка, по которой будем тягать данные о доставщиках, в key будут числа(коды выше), соответствующих стран из $countries
			$url = 'https://async.joybuy.com/product/querySkuStock.html?callback=null&skuId='.$skuId.'&venderId=217&destCountryId='.$key.'&deliveryCountryId='.$deliveryCountryId.'&num=1&languageId=3&price='.$priceUsd.'&currency=RUB';
			$curl_html = getRequest($url);
			$curl_html = getStringWithoutFunc($curl_html, "null(", ")");
			$myjson = json_decode($curl_html, true);
			if (array_key_exists(0, $myjson["storeStocks"]))
			{
				$carrier = 0;
				echo "<br>from <b>" . $countries[$deliveryCountryId] . "</b> to <b>". $value . "</b>:<br>";
				$priceAndDelivers = array();
				while (array_key_exists($carrier, $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"])) {
					echo " <b>" . $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"][$carrier]["carrierNickname"] . '</b>      ' . 
					              $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"][$carrier]["freightMap"]["RUB"]["amount"] . ' руб.      ' . 
					              $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"][$carrier]["arrivedDays"] . "<br>";

					$deliver = $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"][$carrier]["carrierNickname"];
					$priceDel = $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"][$carrier]["freightMap"]["RUB"]["amount"];
					$days = $myjson["storeStocks"][0]["skuFreight"]["model"]["carriers"][$carrier]["arrivedDays"];

					$priceAndDeliver = new Deliver($deliver, $priceDel, $days);
					array_push($priceAndDelivers, $priceAndDeliver);
					$carrier++;
				}
				$path = new Path($countries[$deliveryCountryId], $value, $priceAndDelivers);
				array_push($delivers, $path);
				
			};
		}
		return $delivers;
	} 

	// функция для нахождения подстроки между словами
	function getStringBetween($str, $from, $to) { 
		$sub = substr($str, strrpos($str, $from) + strlen($from), strlen($str)); 
		return substr($sub, 0, strpos($sub, $to)); 
	}

	function getSpuId($html, $json_)
	{
		/*$spuIdReq = $html->find('link[rel="canonical"]');
		$spuId = getStringBetween($spuIdReq[0]->href, "/", ".html"); // исходный товар id*/
		$spuId = $json_["spuId"];
		return $spuId;
	}

	// $html был нужен, чтобы тягать skuId другим методом (уже неактуален)
	function getSkuId($html, $json_)
	{
		/*$skuIdReq = $html->find('a[clstag="pageclick|keycount|chat_click_ru|ept_ru_001"]'); // не есть skuId
		$skuId = null;
		if ($skuIdReq != null)
		{
			if ($skuIdhref = $skuIdReq[0]->href != null) 
			{		
				$skuId = getStringBetween($skuIdhref, "skuId=", "&");
			}
		}

		if ($skuId == null) // если первым способом не получилось достать, пробуем вторым способом
		{
			$skuIdReq = $html->find('link[rel="alternate"]');
			$skuIdhref = $skuIdReq[0]->href;
			$skuId = getStringBetween($skuIdhref, "ru/", ".html");
		}*/
		
		$skuId = $json_["skuId"];
		return $skuId;
	}

	// здесь добываем значение ценника товара
	function getDiscountPrice($skuId, $currency)
	{	
		$priceReq = 'https://ipromo.joybuy.com/api/promoinfo/getPriceInfo.html?json={"skuId":'.$skuId.',"site":2,"channel":2,"curList":["'.$currency.'"]}';
		$myjson = file_get_html($priceReq);
		$arr = json_decode($myjson, true);
		// if not discounted then getJdPrice($skuId)
		if (array_key_exists("discountPrice", $arr["plummetInfoDto"]["priceInfos"][0]))
			return $arr["plummetInfoDto"]["priceInfos"][0]["discountPrice"];
		else
			return 0;
		 // наш ценник	
	}

	// цена без скидки
	function getJdPrice($skuId, $currency)
	{
		$priceReq = 'https://ipromo.joybuy.com/api/promoinfo/getPriceInfo.html?json={"skuId":'.$skuId.',"site":2,"channel":2,"curList":["'.$currency.'"]}';
		$myjson = file_get_html($priceReq);
		$arr = json_decode($myjson, true);
		return $arr["plummetInfoDto"]["priceInfos"][0]["jdPrice"]; // наш ценник	
	}

	// рейтинг
	function getRating($spuId)
	{
		$ratingReq = "https://async.joybuy.com/user/getStarsAvg.html?spuId=".$spuId;
		// ratingSite = "null(<rating>)"
		$response = file_get_html($ratingReq);
		$rating = getStringBetween($response, "null(", ")");
		return $rating;	
	}

	// для изъятия аргумента функции: foo(<json>) -> <json>
	function getStringWithoutFunc($str, $from, $to) { 
		$sub = substr($str, strpos($str, $from) + strlen($from), strlen($str)); 
		return substr($sub, 0, strrpos($sub, $to)); 
	}

	// выдаёт описание 
	function getDescription($spuId, $skuId)
	{
		$descriptionReq = 'https://async.joybuy.com/product/getDescription.html?wareId='.$spuId.'&skuId='.$skuId.'&languageId=3';
		$curl_html = getRequest($descriptionReq);
		$response = getStringWithoutFunc($curl_html, "null(", ")");
		$strings = json_decode($response, true);
		$strings = $strings["descriptionVO"]["description"];
		//$strings = "<html><body>".$strings."</body></html>";
		//$dom = str_get_html($strings);
		//$strings = getStringWithoutFunc($strings, "p{margin:5px;padding:3px;}</style>", "\n</div> \n<div> \n <div cssurl=");
		//$strings = $strings->find('div[class="attrDiv"]');
		//return $dom; 
		return $strings; 
	}


	function getColors($html)
	{
		$myreq = $html->find('div[id="summary-color"] div[class="dd"] ul[class="summary-attrs"] li'); 
		//$myreq = $myreq[0]->find('li[class="item"]');
		//echo "<br>"; echo var_dump($myreq); echo "<br>";
		//echo "<br>"; echo $myreq; echo "<br>";
		$arr = array();
		foreach ($myreq as $element)
       			array_push($arr, $element->getAttribute('data-title'));
		return $arr;
	}

	// выдаёт результат запроса на сервер
	function getRequest($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);     // Говорим скрипту, чтобы он следовал за редиректами которые происходят во время авторизации

		$curl_html = curl_exec($ch);
		curl_close($ch);
		return $curl_html;
	}

	// поиск всех skuIds
	function getJSONFromScript($html)
	{
		$scrs = $html->find('script');
		$str = null;
		$findme = "//商详sku信息";
		foreach ($scrs as $key => $value) {
			$pos = strpos($value, $findme);
			if ($pos !== false)
			{
				$str = $value;

				// вске скуиды от colorSize: [{
				// до }], 
				// начинаем искать от pageConfig.product = { 
				// ищем до isSelf:
				// после него найти первый входящий символ '}'
				//$str = getStringBetween($str, "colorSize: [{", "}]");
			}
			//echo  $value . "  <br>";
		}
		$str = str_replace("<script>", "", $str);
		$str = str_replace("</script>", "", $str);
		$str = str_replace("\n", "", $str);
		$str = str_replace("'", "\"", $str);

		preg_match_all('/{\s+skuId.+isSelf:\s+(true|false)\s+}/u', $str, $matches);
		preg_match_all('/[^"\s\.][a-zA-Z]+[a-zA-Z]:/', $matches[0][0], $matches2);

		foreach ($matches2[0] as $key => $value) {
			$repl = "\"" . substr($value, 0, -1) . "\":";
			$matches[0][0] = str_replace($value, $repl, $matches[0][0]);
		}

		$myjson = json_decode($matches[0][0], true); 
		
		/*$colors = $myjson["colorSize"];
		foreach ($colors as $key => $value) {
			echo "skuId: ". $value["skuId"] . "<br>";
		}*/
		return $myjson;
	}

	function getOtherSkuIds($json_, $currSkuId)
	{
		$skuIds = array();
		foreach ($json_["colorSize"] as $key => $value) {
			if ($value["skuId"] != $currSkuId)
			{
				array_push($skuIds, $value["skuId"]);	
			}
		}
		return $skuIds;
	}

	function getTitle($html)
	{
		$currTitle = $html->find('div.title h1')[0];
		return $currTitle->plaintext;;
	}

	function getMainImage($html)
	{
		$myreq = $html->find('img[id="spec-img"]');
		return $myreq[0];	
	}

	function getMainImageUrl($html)
	{
		$mainImg = getMainImage($html); // не работает из-за заполнения джаваскриптом
		$imgUrl = $mainImg->getAttribute('src');
		return $imgUrl;
	}

	// возвращает вёрстку HTML
	function getHtml($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false); 
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_COOKIE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$curl_html = curl_exec($ch);
		$html = str_get_html($curl_html);
		curl_close($ch);
		return $html;
	}
//-----------------------------------------------------------------------------
//-------- Classes ------------------------------------------------------------
	class Options
	{
		public $color; // 10494
		public $ram;   // 2021
		public function __construct($color, $ram)
		{
			$this->color = $color;
			$this->ram = $ram;
		}
	}

	class Deliver
	{
		public $deliver;
		public $price;
		public $days;
		public function __construct($deliver, $price, $days)
		{
			$this->deliver = $deliver;
			$this->price   = $price;
			$this->days   = $days;
		}
	}

	class Path
	{
		public $_from;
		public $_to;
		public $priceAndDelivers; // массив

		public function __construct($_from, $_to, $priceAndDelivers)
		{
			$this->_from = $_from;
			$this->_to   = $_to;
			$this->priceAndDelivers = $priceAndDelivers;
		}
	}
?>