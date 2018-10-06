<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 28.05.15
 * Time: 11:21
 */
?>
<a href="<?=baseurl('tester/create/')?>" class="btn btn-success">New test</a>
<br><br>
<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>Route</td><td>State</td><td>Completed</td><td>Action</td><td>A number</td><td>Share URL</td></tr>
        <?php
        if(is_array($userTests)) {
            foreach ($userTests as $key => $value):
                ?>
                <tr>
                    <td><a href="<?= baseurl('tester/report/' .$value['md5hash']) ?>" ><?= $value['name'] ?></a></td>
                    <td><?= $value['status'] ?> </td>
                    <td><?= $value['start'] ?>/<?= $value['total'] ?></td>
                    <td>
                        <?php
                        if($value['status'] == "stop"){
                            echo "<a href=\"".baseurl('tester/activate/'  .$value['md5hash']) ."\" class=\"btn btn-success\">Start</a>";
                        }else{
                            echo "<a href=\"". baseurl('tester/deactivate/' . $value['md5hash'])."\" class=\"btn btn-success\">Stop</a>";
                        }
                        ?>
                        <a href="<?= baseurl('tester/delete/' .$value['md5hash']) ?>" class="btn btn-danger">Del</a>
                        <a href="<?= baseurl('tester/reset/' . $value['md5hash']) ?>" class="btn btn-danger">Reset</a>
                    </td>
                    <td><?= $value['anumber'] ?></td>
                    <td><a href="<?= baseurl('home/report/' . $value['md5hash']) ?>">Open</a></td>
                </tr>
            <?php endforeach;
        }?>
</table>
</form>