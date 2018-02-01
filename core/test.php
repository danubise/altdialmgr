<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 25.06.15
 * Time: 16:30
 */
    //echo microtime(true) - 2592000;
    $a=microtime(true);
    echo $a."\n";
    $b= $a-2592000;
    echo $b."\n";
    //$cd=date('Y-m-d H:i:s');
    //$future = strtotime('+1 month', $cd);

    die;
    $link = mysql_connect('localhost', 'test', 'test')
    or die('Не удалось соединиться: ' . mysql_error());
    echo 'Соединение успешно установлено';
    mysql_select_db('callwaytest') or die('Не удалось выбрать базу данных');
    mb_internal_encoding('utf-8');
    $query = "select `id`,`number` from `processing` where `checkstart` = 0 and `routename` like 'кирилица'";
    mb_convert_encoding($query, "UTF-8", mb_detect_encoding($query, "UTF-8, ISO-8859-1, ISO-8859-15", true));
    echo $query."\n";
    $result = mysql_query($query) or die('Запрос не удался: ' . mysql_error());

    // Выводим результаты в html
    echo "<table>\n";
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        echo "\t<tr>\n";
        foreach ($line as $col_value) {
            echo "\t\t<td>$col_value</td>\n";
        }
        echo "\t</tr>\n";
    }
    echo "</table>\n";
