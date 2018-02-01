<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 06.10.15
 * Time: 14:44
 *
 */

class Billing {
    public $server = '95.141.192.21'; // old 95.141.193.39
    public $billing = 'card:dust123@95.141.192.5:8080';
    public $max_count_lines = 5;
    private $bg_server = '95.141.192.5:8080';
    private $bg_login = 'aconn';
    private $bg_psw = 'AhW2po1c';

    public function getBalance()
    {
        global $_owner, $db, $Corp;
        if ($Corp->entity) {
            $__table = 'corp';
            $__account = $_owner['contract'];
        } elseif ($_owner['group']['id'] == 2) {
            $__table = 'user';
            $__account = $_owner['login'];
        } else {
            return 1;
        }
        $url = "http://" . $this->billing . "/bgbilling/mpsexecuter/3/3/?command=check&txn_id=29&account=" . $__account . "&sum=1";
        //echo $url;
        $result = simplexml_load_string(file_get_contents($url));
        //printArray($url);
        //printArray($result);
        if ($result->account_balance) {
            $db->update("`" . $__table . "` set `balance`='" . $result->account_balance . "' where `id`=" . $_owner['id']);
        }
    }

    public function getHours($cid, $date1, $date2)
    {
        //http://95.141.192.5:8080/bgbilling/executer?user=aconn&pswd=AhW2po1c&module=voiceip&pageSize=100&mask=&contentType=xml&cid={CID в системе}&pageIndex=1&unit=1&action=LoginsAmount&date2=28.02.2015&mid=4&date1=01.02.2015&
        $date1 = date("d.m.Y", strtotime($date1));
        $date2 = date("d.m.Y", strtotime($date2));
        $url = "http://" . $this->bg_server . "/bgbilling/executer?user=aconn&pswd=AhW2po1c&module=voiceip&pageSize=100&mask=&contentType=xml&cid=" . $cid . "&pageIndex=1&unit=1&action=LoginsAmount&date2=" . $date2 . "&mid=4&date1=" . $date1 . "&";
        $result = simplexml_load_string(file_get_contents($url));
        return $result->table->data->attributes()->money_amount;
    }

    private function getXMLDetalization($date1, $date2)
    {
        global $_owner;
        $id = $this->getBGID();
        $url = "http://" . $this->bg_server . "/bgbilling/executer?user=" . $this->bg_login . "&pswd=" . $this->bg_psw . "&module=voiceip&pageSize=10000&mask=&contentType=xml&cid=" . $_owner['cid'] . "&pageIndex=1&id=" . $id . "&unit=1&action=LoginSessions&date2=" . $date2 . "&mid=4&date1=" . $date1;
        //echo $url;
        $result = file_get_contents($url);
        return $result;
    }

    public function getDetalization($date_start, $date_end)
    {
        if (strtotime($date_start) and strtotime($date_end)) {
            $date_start = date('d.m.Y', strtotime($date_start));
            $date_end = date('d.m.Y', strtotime($date_end));

            if (date('m', strtotime($date_start)) != date('m', strtotime($date_end))) {
                $month_s = date('m', strtotime($date_start));
                $month_e = date('m', strtotime($date_end));
                $count = abs($month_s - $month_e);

                $date1 = $date_start;
                $date2 = date('t.m.Y', strtotime($date_start));

                $result = $this->getXMLDetalization($date1, $date2);
                $result .= "[this_end]";
                $result = str_replace("</data></table></data>[this_end]", "", $result);
                $result = str_replace("/></table></data>[this_end]", ">", $result);

                for ($i = 1; $i <= $count; $i++) {

                    $current_date = date('d.m.Y', strtotime($date_start . '+ ' . $i . ' month'));
                    $date1 = date('01.m.Y', strtotime($current_date));
                    $date2 = (($i < $count) ? date('t.m.Y', strtotime($current_date)) : $date_end);

                    $detalization[$i]['xml_obj'] = simplexml_load_string($this->getXMLDetalization($date1, $date2));
                    $detalization[$i]['xml_obj'] = $detalization[$i]['xml_obj']->table->data;
                    $detalization[$i]['head'] = [
                        'count_amount' => end($detalization[$i]['xml_obj']->attributes()->count_amount),
                        'money_amount' => end($detalization[$i]['xml_obj']->attributes()->money_amount)
                    ];

                    $array = $detalization[$i]['xml_obj']->row;

                    foreach ($array as $value) {
                        $attributes = $value->attributes();
                        $attr_str = "<row ";
                        foreach ($attributes as $key => $values) {
                            $attr_str .= $key . "=\"" . htmlspecialchars($values) . "\" ";
                        }
                        $attr_str .= "/>";
                        $result .= utf8_to_cp1251($attr_str); //Из нормальной системы в Андреевскую
                        unset($attr_str);
                    }
                }

                $result .= "</data></table></data>";
                $result = str_replace("</table></data></data></table></data>", "</table></data>", $result);

                $result = simplexml_load_string($result);

                $result = cp1251_to_utf8($result); //из Андреевской системы в нормальную
                $result = $result->table->data;

                $count_amount = $result->attributes()->count_amount;
                $money_amount = $result->attributes()->money_amount;
                foreach ($detalization as $value) {
                    $count_amount += $value['head']['count_amount'];
                    $money_amount += $value['head']['money_amount'];
                }
                $result->attributes()->count_amount = $count_amount;
                $result->attributes()->money_amount = $money_amount;
                return $result;

            }

            $result = $this->getXMLDetalization($date_start, $date_end);
            $result = simplexml_load_string($result);
            $result = cp1251_to_utf8($result); //из Андреевской системы в нормальную
            $result = $result->table->data;
            return $result;

        }
    }

    private function getBGID()
    {
        global $_owner, $db, $Corp;
        if (!$_owner['bg_id']) {
            $url = "http://" . $this->bg_server . "/bgbilling/executer?user=" . $this->bg_login . "&pswd=" . $this->bg_psw . "&module=voiceip&action=GetLogins&mid=4&cid=" . $_owner['cid'];
            $result = simplexml_load_string(file_get_contents($url));
            $id = intval($result->logins->item->attributes()->id);
            $table = 'user';
            if ($Corp->entity) {
                $table = 'corp';
            }
            $db->update("`" . $table . "` set `bg_id`=" . $id . " where `id`=" . intval($_owner['id']));
            return $id;
        }
        return $_owner['bg_id'];
    }

    public function entityBalacne($uid)
    {
        global $db;
        $contract = $db->select("`contract` from `corp` where `uid`=" . intval($uid), 0)['conract'];
        if ($contract) {
            $url = "http://" . $this->billing . "/bgbilling/mpsexecuter/3/3/?command=check&txn_id=29&account=" . $contract . "&sum=1";
            $result = simplexml_load_string(file_get_contents($url));
            if ($result->account_balance) {
                return $result->account_balance;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    public function payTo($uid, $user_type, $tid, $summ)
    {
        global $db, $DEBUG;
        if ($user_type) {
            $contract = $db->select("`contract` from `corp` where `id`=" . intval($uid), 0)['contract'];
        } else {
            $contract = $db->select("`login` from `user` where `id`=" . intval($uid), 0)['login'];
        }
        if ($contract) {
            $url = "http://" . $this->billing . "/bgbilling/mpsexecuter/3/3/?command=pay&txn_id=" . $tid . "&account=" . $contract . "&txn_date=" . date('YmdHis') . "&sum=" . $summ;
            $result = simplexml_load_string(file_get_contents($url));
            if ($DEBUG === TRUE) {
                print_r($url);
                print_r($result);
            }
            if ($result->account_balance) {
                return true;
            }

        }
        return false;
    }

    public function slava_balans()
    {
        /*global $db, $_owner;
        if($_owner['id']==6)
            file_get_contents("http://{$this->billing}/bgbilling/mpsexecuter/3/3/?command=pay&txn_id=");7000000003*/
    }

    /*
     * Старая версия без проверки до 3 февряля 2015 16:41
    public function payTo($uid, $user_type, $tid, $summ) {
        global $db;
        if($user_type) {
            $contract = $db->select("`contract` from `corp` where `id`=".intval($uid),0)['contract'];
        } else {
            $contract = $db->select("`login` from `user` where `id`=".intval($uid),0)['login'];
        }
        if ($contract) {
            $url = "http://".$this->billing."/bgbilling/mpsexecuter/3/3/?command=pay&txn_id=".$tid."&account=".$contract."&txn_date=".date('YmdGis')."&sum=".$summ;
            //echo $url;
            if(file_get_contents($url)) {
                return true;
            }
        }
        return false;
    }
    */

    public function payOut($uid, $summ, $comment = '')
    {
        global $db;
        $cid = $db->select("`cid` from `corp` where `id`=" . intval($uid), 0)['cid'];
        if ($cid) {
            $comment = utf8_to_cp1251($comment);
            $url = "http://" . $this->bg_server . "/bgbilling/executer?user=" . $this->bg_login . "&pswd=" . $this->bg_psw . "&id=new&module=contract&summa=" . $summ . "&pt=2&action=UpdateContractCharge&comment=" . urlencode($comment) . "&date=" . date('d.m.Y') . "&cid=" . $cid . "&";
            $result = simplexml_load_string(file_get_contents($url));
            if ($result->attributes()->status == 'ok') {
                return true;
            }
        }
        return false;
    }

    /*
     * тарая версия без проверки до 3 февряля 2015 16:56
    public function payOut($uid, $summ, $comment='') {
        global $db;
        $cid = $db->select("`cid` from `corp` where `id`=".intval($uid),0)['cid'];
        if($cid) {
            $comment =  utf8_to_cp1251($comment);
            $url = "http://".$this->bg_server."/bgbilling/executer?user=".$this->bg_login."&pswd=".$this->bg_psw."&id=new&module=contract&summa=".$summ."&pt=2&action=UpdateContractCharge&comment=".urlencode($comment)."&date=".date('d.m.Y')."&cid=".$cid."&";
            //echo $url;
            if(file_get_contents($url)) {
                return true;
            }
        }
        return false;
    }*/

    //public function addBalance()

    public function constructClient($number, $uid = '')
    {
        global $_owner, $db;
        if (!empty($uid)) {
            $numID = $db->select("`numID` from `corp` where `id`=" . $uid, 0)['numID'];
        } else {
            $numID = $_owner['numID'];
        }
        $result = explode(";", $db->select("`block` from `outNumbers` where `id`=" . $numID, 0)['block']);
        $in = 200;
        $j = 0;
        for ($i = $result[0]; $i <= $result[1]; $i++) {
            if (strlen($number) == 4) {
                if ($i != $number) {
                    $in++;
                } else {
                    return $in;
                }
            } elseif (strlen($number) == 3) {
                if ($j == ($number - 200)) {
                    return $i;
                } else {
                    $j++;
                }
            } else {
                return 0;
            }
        }
        return 0;
    }

    public function constructNumber($number)
    {
        if (strlen($number) == 12 and $number[0] == '+') {
            $number = str_replace("+7", "", $number);
            return $number;
        } elseif (strlen($number) == 11 and $number[0] == '8') {
            $number = $number - 80000000000;
            return $number;
        } elseif (strlen($number) == 10) {
            return $number;
        } else {
            return false;
        }
    }

    public function extNumber($id)
    {
        global $db;
        $numID = $db->select("`numID` from `corp` where `id`=" . intval($id), 0)['numID'];
        $ext_numer = $db->select("`code`, `telephone` from `outNumbers` where `id`=" . intval($numID), 0);
        return "7" . $ext_numer['code'] . $ext_numer['telephone'];
    }

    public function clientName($num)
    {
        global $db;
        if (strlen($num) == 3) {
            $num = $this->constructClient($num);
        }
        if ($name = $db->select("`name` from `inNumbers` where `num`=" . intval($num), 0)['name']) {
            return $name;
        } else {
            return false;
        }
    }

    public function clientInIsset($num)
    {
        global $db;
        if (strlen($num) == 3) {
            $num = $this->constructClient($num);
        }
        $count = $db->select("COUNT(*) from `inNumbers` where `num`=" . intval($num), 0)['COUNT(*)'];
        if ($count) {
            return true;
        } else {
            return false;
        }
    }

    public function clientWrite($num, $name)
    {
        global $db, $_config, $_owner;
        if (strlen($num) == 3) {
            $num = $this->constructClient($num);
        }
        if ($this->clientInIsset($num)) {
            $db->update("`inNumbers` set name='" . $name . "' where `num`=" . intval($num));
        } else {
            $db->insert("`inNumbers` (`name`,`num`) values ('" . $name . "', " . intval($num) . ")");
        }
        $url = "http://" . $this->server . "/peer_update.php?key=" . $_config['keys']['api'] . "&name=" . urlencode($name) . "&protocol=" . $_owner['protocol'] . "&num=" . intval($num) . "&numIN=" . intval($this->constructClient($num, $_owner['id']));
        file_get_contents($url);
    }

    public function createContract($id, $num = 0)
    {
        global $Corp;
        if ($Corp->entity) {
            $type = 7;
        } else {
            $type = 9;
        }
        $url = "http://" . $this->bg_server . "/bgbilling/executer?user=" . $this->bg_login . "&pswd=" . $this->bg_psw . "&custom_title=" . $id . "&module=contract&sub_mode=0&action=NewContract&pattern_id=9&date=" . date('d.m.Y');
        $result = simplexml_load_string(file_get_contents($url));
        $cid = 0;
        $cid = $result->contract->attributes()->id;
        if ($cid) {
            if (!$num) {
                $num = $id;
            }
            $url = "http://" . $this->bg_server . "/bgbilling/executer?user=" . $this->bg_login . "&pswd=" . $this->bg_psw . "&module=voiceip&alias=" . $num . "&object_id=0&login_pswd=12345&type=" . $type . "&access=0&cid=" . $cid . "&lid=0&session=0&action=UpdateLoginInfo&date2=&mid=4&comment=&date1=" . date('d.m.Y');
            file_get_contents($url);
            return $cid;
        } else {
            return FALSE;
        }
    }

    public function costs($id, $date) {
        //$url = "http://" . $this->billing . "/bgbilling/mpsexecuter/3/3/?command=check&txn_id=29&account=" . $contract . "&sum=1";
        //$result = simplexml_load_string(file_get_contents($url));
    }

}