<?php
class minute_cron extends Core_controller {
    public function __construct() {
        parent::__construct();
        $this->checkStatus();
    }

    private function checkStatus() {
        $get_users = $this->db->select("`id`, `date_active` from `Core_manager` where `online`=1");
        if ($get_users) {
            foreach ($get_users as $value) {
                $date_check = date_create($value['date_active']);
                $date_now = date_create(date('Y-m-d G:i:s'));
                $interval = date_diff($date_check, $date_now);
                if (($interval->d > 0) or ($interval->h > 0) or ($interval->i > 4)) {
                    $this->db->update("Core_manager", "online,0", "id=" . intval($value['id']));
                }
                unset($date_check, $date_now, $interval);
            }
        }
    }
}
