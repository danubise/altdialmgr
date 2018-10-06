<?php
/**
 *
 *
 * Библиотека для упрощённой работы с базой данных MySQL используя драйвер mysqli
 *
 * Набор методов:
 * select - структурированное извеление выбранных строк из одной или несколько таблиц
 * update - обновление столбцов в соответствии с их новыми значениями в строках существующей таблицы
 * insert - добавление новых строки в существующую таблицу
 * multi_insert - добавление новых строк в существующую таблицу
 * delete - удаление строки из таблицы
 * set_config - настройка конфигурации библиотеки (Доступные поля: history_limit,prg_die)
 * debug_mode - переход в режим отладчика (Снимает ограничения с настроки конфигурации)
 *
 * Набор полей:
 * (public) query - поле, содержащие информацию по текущим запросам к базе данных
 *          query->count - количество запросов, выполняемых в пределе скрипта, к базе данных
 *          query->last - последний запрос, выполненный в пределе скрипта, к базе данных
 *          query->history - история запросов, выполненных в пределе скрипта, к базе данных
 * (public) version - версия с конфигурацией запущенной библиотеки
 *
 * (private) DEBUG - режим отладчика
 * (private) history_limit - лимит истории запросов, выполненных в пределе скрипта, к базе данных
 * (private) prg_die - если значение true: убивает скрипт, если false: выводит ошибку, но не прерывает выполнение скрипта
 *
 */

class db extends mysqli {

    public $query;
    public $version = '3.0';
    private $DEBUG = false;
    private $history_limit = 50;
    private $prg_die = false;

    public function __construct($host = '', $user = '', $pass='', $db='') {
        if(!empty($host) and !empty($user) and !empty($pass)) {
            parent::__construct($host, $user, $pass, $db);
            if (mysqli_connect_error()) {
                $msg = 'Ошибка подключения: '.$this->errnoText(mysqli_connect_errno()).' (host: '.$host.'; user: '.$user.'; db: '.$db.')';
                if($this->prg_die === true) {
                    die($msg);
                } else {
                    echo "\n<br>".$msg."<br>\n";
                }
            }
            $this->query = new stdClass();
            $this->query->history = array();
            $this->query->count = 0;
        }
    }

    public function __destruct() {
        $this->close();
    }

    /**
     * Переход в режим отладчика
     * В режиме отладчика нет органичения на вывод истории запросов
     * Добавлен вывод времени обработки запроса
     */
    public function debug_mode() {
        $this->DEBUG = true;
        $this->set_config('prg_die', false);
        $clear_version = explode(";", $this->version);
        $this->version = $clear_version[0].'; DEBUG MODE ON';
    }

    /**
     * Структурированное извеление выбранных строк из одной или несколько таблиц
     * @param mixed $query Запрос SELECT к таблице (без использования начального оператора SELECT)
     * @param int $array Вернуть результат: 1 - в виде индексированного массива, 0 - в виде ассоциативного
     * @return array Структурированный массив по результатам выбранных строк из одной или несколько таблиц
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->select("* from `table`")
     * Array (
     *      [0] => Array (
     *          [id] => 1
     *          [row] => value
     *      )
     *      [1] => Array (
     *          [id] = 2
     *          [row] = value_2
     *      )
     *      ...
     *      [n] => Array (
     *          [id] => x
     *          [row] = value_x
     *      )
     * )
     *
     * ->select("`row` from `table")
     * Array (
     *      [0] => value
     *      [1] => value_2
     *      ...
     *      [n] => value_x
     * )
     *
     * ->select("* from `table` limit 1",0)
     * Array (
     *      [id] => 1
     *      [row] = value
     * )
     *
     * ->select("`row` from `table` limit 1",0)
     * value
     * ==========================================
     *
     */
    public function select($query, $array = 1) {
        $query = 'select '.$query;
        $response = false;
        if ($result = $this->logQuery($query)) {
            while ($row = $result->fetch_assoc()) {
                $response[] = ((count($row)==1)? end($row) : $row);
            }
        }
        if($array == 0) {
            $response=$response[0];
        }
        return $response;
    }

    /**
     * Обновление столбцов в соответствии с их новыми значениями в строках существующей таблицы
     * @param mixed $table Таблица в базе данных
     * @param array|mixed $update Массив со столбцами и параметрами
     * @param mixed $where При каких параметрах обновлять
     * @return bool|mysqli_result При неудачной попытке обновить возвращает FALSE
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * Стандартное обновление одной ячейки в таблице:
     * ->update('table', 'row,new_value', 'id=0')
     * Выполнит mysql запрос: update `table` set `row`='new_value' where id=0
     *
     * Если надо обновить несколько ячеек в таблице:
     * ->update('table', array("row"=>"value", "row_2"="value_2"), 'id=1')
     * Выполнит mysql запрос: update `table` set `row`='value', `row_2`='value_2' where `id`=1
     *
     * Если необходимо выполнить обновление с командой (например: ADD DATE)
     * ->update('table', array("row"=>"value", "date"=>array("DATE_ADD(date,INTERVAL 1 MONTH)", cmd), "sum"=>array("sum+1", cmd)), 'id=2')
     * Выполнит mysql запрос: update `table` set `row`='value', `date`=DATE_ADD(date,INTERVAL 1 MONTH), `sum`=sum+1 where id=2
     * ==========================================
     *
     */
    public function update($table, $update, $where = 0) {
        if($table) {
            $query = 'update `'.$table.'` set ';
            if(is_array($update)) {
                $array_keys = $this->get_row_keys($update);
                $update = $this->structure_value($update);
                foreach($update as $param=>$value) {
                    //$value=mysql_real_escape_string($value);
                    $query .= "`".$param."`=".((count($value)>1 and $value[1]==cmd)? $value[0] : "'".$value."'").((next($array_keys))? ',' : '');
                }
            }
            if(!is_array($update)) {
                if(count($update)==1 and count($update = explode(",", $update))==2) $query .= "`".$update[0]."`='".$update[1]."'";
            }
            if($where) $query .= " where ".$where;
            return $this->logQuery($query);
        }
        return false;
    }

    /**
     * Добавление новых строки в существующую таблицу
     * @param mixed $table Таблица в базе данных
     * @param array $insert Массив состоящий из стоблбцов и параметров
     * @return bool|mixed При удачном добавлении возвращает ID добавленной строки в текущей таблицы
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert('table', array("row"=>"new_value", "date"=>"1994-04-29"))
     *  Выполнит mysql запрос: insert into `table` (`row`, `date`) values ('new_value', '1994-04-29')
     * ==========================================
     *
     */
    public function insert($table, $insert) {
        if($table) {
            $query = 'insert into `'.$table.'` (';
            $query_p = '';
            $query_v = '';
            if(is_array($insert)) {
                $array_keys = $this->get_row_keys($insert);
                $insert = $this->structure_value($insert);
                foreach($insert as $key=>$value) {
                    $next = next($array_keys);
                    $query_p .= "`".$key."`".(($next)? ',' : ') values (');
                    $query_v .= ((count($value)>1 and $value[1]==cmd)? $value[0] : "'".$value."'").(($next)? ',' : ')');
                    unset($next);
                }
                $query .= $query_p.$query_v;
            }
            if(!is_array($insert)) {
                if (count($insert) == 1 and count($insert = explode(",", $insert)) == 2) $query .= "(`" . $insert[0] . "`) values ('" . $insert[1] . "')";
            }
            if($this->logQuery($query)===true) {
                return $this->insert_id;
            }
        }
        return false;
    }

    /**
     * Добавление новых строк в существующую таблицу
     * @param mixed $table Таблица в базе данных
     * @param array $insert Массив состоящий из стоблбцов и параметров
     * @return bool|mixed При удачном добавлении возвращает ID добавленной строки в текущей таблицы
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->insert('table', array("row"=>("new_value","new_value2"), "date"=>array("1994-04-29","2015-01-01")))
     *  Выполнит mysql запрос: insert into `table` (`row`, `date`) values ('new_value', '1994-04-29'), ('new_value2', '2015-01-01')
     * ==========================================
     *
     */
    public function multi_insert($table, $insert) {
        if($table) {
            $query = 'insert into `'.$table.'` (';
            $query_p = '';
            $query_v = '';
            if(is_array($insert)) {
                $count_row = 0;
                $array_keys = array();
                foreach($insert as $key=>$value) {
                    $array_keys[] = $key;
                    if(is_array($value)) {
                        if(count($value)>$count_row) $count_row = count($value);
                    }
                }
                $i=0;
                foreach($insert as $key=>$value) {
                    $next = next($array_keys);
                    $query_p .= "`".$key."`".(($next)? ',' : ') values ');
                    $query_params[$i] = (!is_array($value)? array($value):$value);
                    $i++;
                    unset($next);
                }
                for($i=0;$i<$count_row;$i++) {
                    if (count($query_params[$i]) != $count_row) {
                        if (count($query_params[$i]) == 1) {
                            for ($j = 0; $j < $count_row; $j++) {
                                $query_params[$i][$j] = $query_params[$i][0];
                            }
                        } else {
                            for ($j = 0; $j < count($array_keys); $j++) {
                                if (!isset($query_params[$i][$j])) $query_params[$i][$j] = "";
                            }
                        }
                    }
                    $query_uv = '';
                    for($j=0;$j<count($array_keys);$j++) {
                        $query_uv .= "'".$query_params[$j][$i].($j<(count($array_keys)-1)?'\', ':'\'');
                    }
                    $query_v .= '('.$query_uv.')'.($i<($count_row-1)?',':'').' ';
                }
            }
            $query .= $query_p.$query_v;
            if($this->logQuery($query)===true) {
                return $this->insert_id;
            }
        }
        return false;
    }

    /**
     * Удаление строки из таблицы
     * @param mixed $query Запрос DELETE к таблице (без использования начального оператора DELETE)
     * @return bool|mysqli_result
     *
     *
     * ==========================================
     * Примеры запросов и ответов:
     *
     * ->delete(" from `table` where `id`<2")
     * Выполнит mysql запрос: delete from `table` where `id`<2
     * ==========================================
     *
     */
    public function delete($query) {
        $query = 'delete '.$query;
        return $this->logQuery($query);
    }

    /**
     * Настройка конфигурации библиотеки
     * @param $param mixed поле, которое надо обновить
     * @param $value mixed новое значение поля
     */
    public function set_config($param, $value) {
        $params = array('history_limit','prg_die');
        if(in_array($param,$params)) {
            $this->$param = $value;
            if($this->DEBUG===false) {
                $this->version .= '; '.$param.': '.(($value===false)? 0 : $value);
            }
        }
    }

    /* Приватные методы */

    /**
     * Возвращение текста ошибки по ID
     * Публичные методы: __construct
     * @param $id int ID mysql-ошибки
     * @return mixed текст ошибки
     */
    private function errnoText($id) {
        $error[1044] = "у пользователя нет доступа к базе";
        $error[1045] = "ошибка авторизации (логин или пароль неверен)";
        $error[2003] = "сервер не найден";
        return $error[$id];
    }

    /**
     * Логирование и выполнение mysql запросов
     * Публичные методы: select, update, insert, delete
     * @param $query mixed полный query запрос к mysql
     * @return bool|mysqli_result отмет от mysql
     */
    private function logQuery($query) {
        $this->query->count++;
        $this->query->last = $query;
        if(count($this->query->history)>($this->history_limit-1) and $this->DEBUG===false) {
            unset($this->query->history[0]);
            $this->query->history = array_values($this->query->history);
        }
        if($this->DEBUG===true) {
            unset($start_time,$end_time,$time);
            $time_start = microtime(true);
        }
        $result = $this->query($query);
        if($this->DEBUG===false) {
            $this->query->history[] = $query;
        } else {
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $this->query->history[] = array(
                'query'=> $query,
                'time' => number_format($time,4)
            );
        }
        return $result;
    }

    /**
     * Запись ключей массива как элементы
     * Публичные методы: update, insert
     * @param $data array массив данных
     * @return array ключи массива как отдельный массив
     */
    private function get_row_keys($data) {
        $array_keys = array();
        foreach($data as $key=>$value) {
            $array_keys[] = $key;
        }
        return $array_keys;
    }

    /**
     * Обработка элементов массива для корректной работы библиотеки
     * Публичные методы: update, insert
     * @param $data array массив данных
     * @return array обработанный массив данных
     */
    private function structure_value($data) {
        $structure = array();
        foreach($data as $key=>$value) {
            if(is_array($value) and $value[1]!==cmd) {
                $structure[$key] = $value[0];
            } elseif (is_object($value)) {
                $structure[$key] = ((end($value))? end($value) : "");
            } else {
                $structure[$key] = $value;

            }
        }
        return $structure;
    }

} 