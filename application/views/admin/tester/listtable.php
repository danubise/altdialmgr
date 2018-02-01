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
        <tr><td>Маршрут</td><td>Состояние</td><td>Операции</td></tr>
        <?php
        if(is_array($route)) {
            foreach ($route as $key => $value):
                $count=$status[ $value]['complite'];
                $total=$count + $status[ $value]['finish'];
                if($status[ $value]['finish']>0){
                    $process="В процессе";

                }else{
                    $process="Остановлена";
                }
                ?>
                <tr>
                    <td><?= $value ?></td>
                    <td><?= $process ?> завершено <?= $count ?> из <?= $total ?></td>
                    <td><a href="<?= $href ?>"><?= $avalue ?></a>
                        <a href="<?= baseurl('tester/activate/' . $value) ?>">Запустить</a>/
                        <a href="<?= baseurl('tester/deactivate/' . $value) ?>">Остановить</a>/
                        <a href="<?= baseurl('tester/delete/' .$value) ?>">Удалить</a>/
                        <a href="<?= baseurl('tester/reset/' . $value) ?>">Обнулить</a>/
                    <a href="<?= baseurl('tester/report/' .$value) ?>">Отчет</a></td>
                </tr>
            <?php endforeach;
        }?>
</table>
</form>