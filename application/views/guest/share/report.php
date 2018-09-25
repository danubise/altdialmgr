<?
//$reports = explode("\n",$reports);
//$reports = array_diff($reports, array(''));

?>
<table class="table table-striped" id="tableNum">
    <tr><h4>Report name: "<?=$routename?> <?=$numberpoolname?>"&nbsp;</h4></tr>
    <thead>

    <tr>
        <th>DateTime</th>
        <th>Dest</th>
        <th>PDD</th>
        <th>RBT</th>
        <th>ANS</th>
        <th>DUR</th>

        <th>STATUS</th>
        <th>REC</th>

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
            if ($report['PDD']<10 && $report['callstatus']==16 ){
                $color="#00FF00";
            }elseif($report['PDD']>=10 && $report['PDD']<30 && $report['callstatus']==16 ){
                $color="#FFFF00";
            }elseif($report['PDD']>=30 ){
                $color="#FFC0CB";
            }elseif($report['callstatus']==17 ){
                $color="#00CC00";
                }else{
                $color="#FFC0CB";
            }
            //<a href="http://www.ruseller.com" target="_blank">RUSELLER.COM</a>
            /*<a href="<?=baseurl('tester/logdata/'.$report['id'])?>" target="_blank"><?=$report['number']?></a>
            <td><?=$report['number']?>&nbsp;</td>
            <a href="<?=baseurl('tester/logdata/'.$report['id'])?>" target="_blank"></a>
            */
            ?>
            <tr style="background: <?= $color ?>;">
                <td><?=$report['timestart']?>&nbsp;</td>

                <td><?=$report['number']?>&nbsp;</td>

                <td><?=$report['PDD']?>&nbsp;</td>
                <td><?=$report['RBT']?>&nbsp;</td>
                <td><?=$report['DIALOG']?>&nbsp;</td>

                <td><?=$report['DUR']?>&nbsp;</td>

                <td><?=$report['callstatus']?>&nbsp;</td>
                <td  height="70">
                <?php if(($report['callstatus']!="1/Unallocated (unassigned) num" && $report['callstatus']!="38/Network out of order" && $report['callstatus']!="0/Unknown" && $report['callstatus']!="34/Circuit/channel congestion" && $report['callstatus']!="21/Call Rejected") || ($report['callstatus']=="34/Circuit/channel congestion" && $report['RBT'] >0 ) || ($report['callstatus']=="0/Unknown" && $report['RBT'] >0 ))
                :?>
                <audio controls>
                    <source src="<?=baseurl('home/getaudio/'.$report['id'])?>" type="audio/wav">
                    <a href="<?=baseurl('home/getaudio/'.$report['id'])?>" class="btn btn-success">Скачать</a>
                </audio>
                <?php endif;?>

                    <?php if($report['callstatus']=="16/Normal Clearing")
                    :?>
                    <audio controls>
                        <source src="<?=baseurl('home/getaudio2/'.$report['id'])?>" type="audio/wav">
                        <a href="<?=baseurl('home/getaudio2/'.$report['id'])?>" class="btn btn-success">Скачать</a>
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
