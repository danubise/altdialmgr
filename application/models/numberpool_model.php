<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 26.02.15
 * Time: 22:14
 */
class Pool{
    public $Id;
    public $Name;
    //public $NumberCount;
    public $NumberList;
    function GetPool($id){
        if($id=="") return false;
        $getpool="* from `dm_poolgroup` where `id`='".$id."'";
        $pool=$this->db->select($getpool);
        $this->Id=$pool['id'];
        $this->Name=$pool['name'];
        $nlquery="* from `dm_numberpool` where `poolgroup`='".$id."'";
        $nl=$this->db->query($nlquery);
        $this->NumberList=$nl;
    }

}
class Numberpool_model extends Core_model {
    private $filename="numberpoll_model.php ";
    public function __construct() {
        parent::__construct();
        $this->CompanyTableField=array('id','name','status','datecreate','queueid','operatormin','CallCountProcedure1',
            'CallTimeWaitProcedure1',
            'CallCountProcedure2',
            'CallTimeWaitProcedure2',
            'TimeOut',
            'CallerId'
        );
        $this->WorkTimeTableField=array('id','compid','weekday','timefrom','timeto','lunchfrom','lunchto','timestart','timeend');
        $config = array(
            'log_file' => "/var/log/httpd/webaction.log",
            'log_write' => "file",

        );
        $this->log->setConfig($config);
    }
    public function Create($data){
        $id=$this->db->insert('dm_poolgroup',array("name"=>$data['name'],"userid"=>$_SESSION['id']));
        $tdata=array(
            "poolgroup"=>$id,
            "number"=>$data['numberlist']
        );

        $this->db->multi_insert("dm_numberpool",$tdata);
    }
    public function AddNumberToPool($idpool,$data){
        $tdata=array(
            "poolgroup"=>$idpool,
            "number"=>$data
        );
        $this->db->multi_insert("dm_numberpool",$tdata);
        $this->sync($idpool);
    }
    public function Save($data){
    }
    public function DeleteNumber($idpool,$data){
        foreach($data as $key=>$value) {
            $this->db->query("DELETE FROM `dm_numberpool` WHERE `id`='$key'");
        }
        $this->sync($idpool);
    }
    private function sync($idpool){
        //echo $idpool;
        $camplist=$this->db->select("* from `Company` where `poolgroup` ='$idpool'");
        foreach($camplist as $key=>$value){
            $querydel="DELETE n FROM `Numbers` n LEFT JOIN `dm_numberpool` p ON n.`number` = p.`number` AND p.`poolgroup` = ".$idpool." WHERE p.`number` IS NULL AND n. `compid` = '".$value['id']."'";

            $queryinsert="INSERT INTO `Numbers` (  `compid`,  `number`) SELECT ".$value['id']." , p.`number` FROM `dm_numberpool` p LEFT JOIN `Numbers` n ON    n.`number` = p.`number` AND    n. `compid` = '".$value['id']."' WHERE n.`number` IS NULL  AND    p.`poolgroup` = '".$idpool."' ";
            $this->db->query($querydel);
            $this->db->query($queryinsert);
          //  echo $querydel."\n";
           // echo $queryinsert."\n";
        }
        //die;
    }

    public function Get($id){
        $getpool="* from `dm_poolgroup` where `id`='".$id."'";
        $pool=$this->db->select($getpool,0);
        //$pool=array()
        //printarray($pool);
        return $pool;
    }
    public function Delete($id){
        $check = $this->db->select ("`id` FROM `dm_poolgroup` WHERE `id`='".$id."' AND userid='".$_SESSION['id']."'",false);
        $this->log->debug($this->filename." Delete ".$this->db->query->last. " result is ".$check);
        if($check == $id) {
            $this->db->query("DELETE FROM `dm_poolgroup` WHERE `id`='" . $id . "' AND userid='" . $_SESSION['id'] . "'");
            $this->db->query("DELETE FROM `dm_numberpool` WHERE `poolgroup`='" . $id . "'");
        }
    }
    public function GetAllPools(){
        $functionname = $this->filename."GetAllPools";
        $pools= $this->db->select("* from `dm_poolgroup` WHERE userid='".$_SESSION['id']."' ORDER BY `name`");
        $this->log->debug($functionname. $this->db->query->last);
        $all=array();
        if(is_array($pools)) {
            foreach ($pools as $key => $value) {
                $c = $this->db->select("count(*) from `dm_numberpool` where `poolgroup`='" . $value['id'] . "'", 0);
                $all[] = array(
                    "name" => $value['name'],
                    "id" => $value['id'],
                    "numbercount" => $c
                );
            }
        }

        return $all;
    }
    function GetListGroup(){
        $getpool="* from `dm_poolgroup` WHERE userid='".$_SESSION['id']."' ORDER BY `name`";
        $pool=$this->db->select($getpool);
        $tpool=array();
        foreach ($pool as $key=>$value) {
            $tpool[$value['id']]=$value['name'];

        }

        return $tpool;
    }
    function GetNumbers($poolid){
        $query="* from `dm_numberpool` where `poolgroup`='$poolid'";
        $pool=$this->db->select($query);
        /*$tpool=array();
        foreach ($pool as $key=>$value) {
            $tpool[$value['id']]=$value;

        }*/
        return $pool;
    }
}