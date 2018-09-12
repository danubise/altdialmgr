<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 28.05.15
 * Time: 11:21
 */
?>
<a href="<?=baseurl('tester/create/')?>" class="btn btn-success">Создать тест</a>


<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>Route</td><td>Status</td><td>Action</td></tr>
        <?php
        if(is_array($userTests)) {
            foreach ($userTests as $key => $value):
                ?>
                <tr>
                    <td><?= $value['name'] ?></td>
                    <td><?= $value['status'] ?> завершено <?= $value['stop'] ?> из <?= $value['total'] ?></td>
                    <td>
                        <a href="<?= baseurl('tester/activate/' . $value['md5hash']) ?>">Start</a>/
                        <a href="<?= baseurl('tester/deactivate/' . $value['md5hash']) ?>">Stop</a>/
                        <a href="<?= baseurl('tester/delete/' .$value['md5hash']) ?>">Del</a>/
                        <a href="<?= baseurl('tester/reset/' . $value['md5hash']) ?>">Reset</a>/
                        <a href="<?= baseurl('tester/report/' .$value['md5hash']) ?>">Report</a>
                    </td>
                </tr>
            <?php endforeach;
        }?>
</table>
</form>