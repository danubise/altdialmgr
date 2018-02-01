<?
//$reports = explode("\n",$reports);
//$reports = array_diff($reports, array(''));

?>
<table class="table table-striped" id="tableNum">
    <thead>
    <tr>
        <th>Время</th>
        <th>Номер назначения</th>
        <th>Ring</th>
        <th>PDD</th>
        <th>Время разговора</th>
        <th>Hangup</th>
        <th>Статус</th>
        <th>Запись</th>

    </tr>
    </thead>
    <tbody>
    <?php
    /*
 *  $report[$key]['number']=$value['number'];
            $report[$key]['timestart']=date('Y-m-d H:i:s',$value['timestart']);
            //$report[$key]['timering']=$value['timering']-$value['timestart'];
            $report[$key]['timeringing']=$value['timeringing']-$value['timering'];
            $report[$key]['timeup']=$value['timeup']-$value['timeringing'];
            $report[$key]['timehangup']=$value['timehangup']-$value['timeup'];
            $report[$key]['callstatus']=$value['callstatus'];
            $report[$key]['recordfile']=$value['recordfile'];
 */
    if(is_array($reports)):
        foreach($reports as $key=>$report):
            //$report = explode(";",$report);
            if ($report['timeringing']<10 && $report['callstatus']==16 ){
                $color="#00FF00";
            }elseif($report['timeringing']>=10 && $report['timeringing']<30 && $report['callstatus']==16 ){
                $color="#FFFF00";
            }elseif($report['timeringing']>=30 ){
                $color="#FFC0CB";
            }elseif($report['callstatus']==17 ){
                $color="#00CC00";
                }else{
                $color="#FFC0CB";
            }

            ?>
            <tr style="background: <?= $color ?>;">
                <td><?=date('H:i:s',$report['timestart'])?>&nbsp;</td>
                <td><?=$report['number']?>&nbsp;</td>

                <td><?=$report['timering']?>&nbsp;</td>
                <td><?=$report['timeringing']?>&nbsp;</td>
                <td><?=$report['timeup']?>&nbsp;</td>
                <td><?=$report['timehangup']?>&nbsp;</td>
                <td><?=$report['callstatus']?>&nbsp;</td>
                <td>
                <?php if($report['callstatus']==16) :?>
                <audio controls>
                    <source src="<?=baseurl('tester/getaudio/'.$report['id'])?>" type="audio/wav">
                    <a href="<?=baseurl('tester/getaudio/'.$report['id'])?>" class="btn btn-success">Скачать</a>
                </audio>
                <?php endif;?>
                </td>
         </tr>
        <?php
        endforeach;
    endif;
    ?>
    </tbody>
</table>
