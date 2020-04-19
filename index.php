<?php
	$db = ""; #база данных
	$user = "root"; #пользователь
	$pass = ""; #пароль
	$link = mysqli_connect("localhost",$user,$pass,$db) OR DIE ("Ошибка: " . mysqli_error($link));#переход по ссылке
	mysqli_set_charset($link, 'utf8');
	mysqli_query ($link,"set character_set_client='utf8'");
	mysqli_query ($link,"set character_set_results='utf8'");
	mysqli_query ($link,"set collation_connection='utf8_general_ci'");
	mysqli_character_set_name($link); 
	$per_page=10; #количество строк данных из таблицы
	if(isset($_GET['database']))#если существует
	{	
		$mydb = $_GET['database']; #создаём переменную указанной базы данных
		$onDb = false; #флаг для проверки существования выбранной базы
		$query = "SHOW DATABASES"; #формируем запрос для отображения баз
		$result = mysqli_query($link, $query) or die ("Ошибка: " . mysqli_error($link)); #запрос к базам
		while ($row = mysqli_fetch_array($result)) { #проходим по списку вернувшегося результата
			#в массиве нулевым элементом является название базы данных
			if ($row[0] == $mydb) #если название базы эквивалентно нужному
			{
				$onDb = true; # выставляем флаг в true, то есть база с таким названием существует 
			}
		}
		if (!$onDb) # если название нужной база данных не было встречено
		{
			header('location: index.php');  # переходим на главную страницу index.php
			exit("Базы данных \"".$mydb."\" не существует.<br>"); # остановка программы, чтобы не исполнялся код ниже
		}
		$dbs = $_GET['database'];
		
		if(isset($_GET['table']))
		{				
			mysqli_select_db($link,$dbs);

			$mytable = $_GET['table'];
			$onTable = false; #флаг для проверки существования выбранной таблицы
			$query = "SHOW TABLES FROM ".$dbs; #формируем запрос для отображения таблиц
			$result = mysqli_query($link, $query) or die ("Ошибка: " . mysqli_error($link)); #запрос к таблице
			while ($row = mysqli_fetch_array($result)) { #проходим по списку вернувшегося результата
				#в массиве нулевым элементом является название таблицы 
				if ($row[0] == $mytable) #если название таблицы эквивалентно нужному
				{
					$onTable = true; # выставляем флаг в true, то есть таблица с таким названием существует 
				}
			}
			if (!$onTable) # если название нужной таблицы не встречено
			{
				header('location: index.php?database='.$mydb);  # переходим на страницу index.php с нужной базой
				exit("Таблицы \"".$mytable."\" не существует.<br>"); # остановка программы, чтобы не исполнялся код ниже
			}

			if (isset($_GET['page'])) 
			{
				$page=(((int)$_GET['page']) - 1);
				if ($page<0)
				{
					$page=0;
				}
			}
			else
			{
				$page=0;
			}
			$start=abs($page*$per_page);#выводит данные на странице, где page=номер страницы, а per_page = количество записей 
			
			echo "Таблица: ";
			$table = mysqli_real_escape_string($link, $_GET['table']);
			//echo $table;
			
			$res1 = mysqli_query($link,'DESCRIBE '.$_GET['table']."") or die ("Ошибка: " . mysqli_error($link));
			
			$minVal = 100000;
			$maxVal = 0;
			$myField = "";
	echo '<table border=0>';
		echo '<tr><td>';
			echo '<table bgcolor=#FFFAF4 border=1 cellpadding=5><tr>';
			$j = 0;
			while($result = mysqli_fetch_assoc($res1))
			{	
				foreach($result as $k => $value)
				{
					if($j==0){echo "<td>".$k."</td>";}											
				}
				echo '</tr>';
				foreach($result as $k => $value)
				{
					if ($k == "Field")
					{
						if (strlen($value) > $maxVal) $maxVal = strlen($value);
						if (strlen($value) < $minVal and strlen($value) != 0) $minVal = strlen($value);
					}
					if ($k == "Type")
					{
						if (stristr("varchar", $value) != false OR stristr("text", $value) != false)
						{
							$myField += "";
						}
					}	
					echo "<td>".$value."</td>";									
				}
				$j++;					
			}
			echo "</table>";
		echo '</td>';
		echo '<td>';
			echo '<table bgcolor=#FFFAF4 border=1 cellpadding=5>';
				echo "<tr><td>min</td><td>$minVal</td></tr>";
				echo "<tr><td>max</td><td>$maxVal</td></tr>";
			echo '</table>';
		echo '</td></tr>';
	echo '</table>';
			$res3 = mysqli_query($link,'SELECT * FROM '.$_GET['table']."") or die ("Ошибка: " . mysqli_error($link));
			$res2 = mysqli_query($link,'SELECT * FROM '.$_GET['table']." LIMIT ".$start.",".$per_page."") or die ("Ошибка: " . mysqli_error($link));
			
			$total_rows=mysqli_num_rows($res3);		
			$num_pages=ceil($total_rows/$per_page);	

	echo '<table bgcolor=#FFFAF4 border=1 cellpadding=5>';	
			$j = 0;
			while($Result = mysqli_fetch_assoc($res2)){
				foreach($Result as $k => $value)
					{
						if($j==0){echo "<td>".$k."</td>";}											
					}
					$j++;
					echo '</tr>';
				foreach($Result as $k => $value)
					{
						echo "<td>".$value."</td>";									
					}					
			}

	echo '</table>';
						
			if($num_pages > 0)	
			echo "<br>Страницы:";			
			for($i=1;$i<=$num_pages;$i++)
			{
				echo "<a href=\"./index.php?database=".$dbs."&table=".$_GET['table']."&page=".$i."\">".$i." </a>";
			}
					
		}
		else
		{
			$res = mysqli_query($link,"SHOW TABLES FROM `".$_GET['database']."`");
			echo "<h3> База данных:".$_GET['database'];
			echo "<h4>Количество таблиц: ".mysqli_num_rows($res);
			echo "</h4></h3>";
			echo "<h4>Список таблиц:<br></h4>";
	
			while($rows = mysqli_fetch_array($res))
			{
				mysqli_select_db($link,$dbs);
				$temp = mysqli_query($link,"SELECT * FROM `".$rows[0]."`");
				echo "<a href=\"./index.php?database=".$dbs."&table=".$rows[0]."\">".$rows[0]."(".mysqli_num_rows($temp).")</a><br>";	    
			}
		}
	} 
	else
	{
		$query = "SHOW DATABASES";
		$result = mysqli_query($link, $query) or die ("Ошибка: " . mysqli_error($link));
		
		echo "<h3>Список баз:";
		echo "</h3>";
			while ($rows = mysqli_fetch_array($result))
			{
				$temp = mysqli_query($link,"SHOW TABLES FROM `".$rows[0]."`");
				echo "<a href=\"./index.php?database=".$rows[0]."\">".$rows[0]."(".mysqli_num_rows($temp).")</a><br>";		
			} 
	}
	mysqli_close($link);
?>

