<?php
class List_model extends Core_model {
    private $ArrayList=array();
    public $Name='';
    public $Select='';
    public function __construct() {
        parent::__construct();

    }

    /**
     * @param $data массив вида data[id]=value
     */
    public function SetListData($data, $type=1){
        $this->ArrayList=$data;
    }

    /**
     * @param int $type - тип если 1 то value = $value 0 value= $key
     * @return string
     */
    public function GetList($data, $name , $select='',$type=0){
        $h="<select name=\"$name\">\n";
        foreach($data as $key=>$value){
            if($type==1){$key=$value;}
            if($key==$select) {
                $h.="<option value=\"$key\" selected>$value</option>\n";
            }else {
                $h .= "<option value=\"$key\">$value</option>\n";
            }
        }
        $h.="</select>\n";
        return $h;
    }
    /*
     * $announcement_conf=array(
            "listarray"=>$this->dbasterisk->select("`announcement_id`,`description` from `announcement`"),
            "listkey"=>"announcement_id",
            "listtext"=>"description",
            "name"=>"announcement",
            "select"=>"",
            "keyoption"=>1
        );
     */
    public function GetListArray($data){
        //printarray($data);
        $tdata=array();
        foreach($data['listarray'] as $key=>$value){
            //printarray($value);
            $tdata[$value[$data['listkey']]]=$value[$data['listtext']];
        }
        //printarray($tdata);
        return $this->GetList($tdata,$data['name'],$data['select'],$data['keyoption']);
    }

}




