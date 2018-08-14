<a href="<?=baseurl('newcamp/index/')?>" class="btn btn-success">Создать тест</a>



<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>Тест</td><td>Состояние</td><td>Операции</td></tr>
        <?php
        if(is_array($campany)) {
            foreach ($campany as $key => $value):
                //$value['status'] = (())
                //echo "<tr><td>".$value['name']." " .$value['status']."</td><td>работает/остановлена</td><td>активировать/изменить/удалить</td></tr>";
                if ($value['status'] == '0') {
                    $href = baseurl('campanylist/activate/' . $value['id']);
                    $avalue = "Активировать";
                    $status = "<span class='text-danger'>Выключена</span>";
                } else {
                    $href = baseurl('campanylist/deactivate/' . $value['id']);
                    $avalue = "Деактивировать";
                    $status = "<span class='text-success'>Работает</span>";
                }
                ?>
                <tr>
                    <td><?= $value['name'] ?> <?= $value['id'] ?></td>
                    <td><?= $status ?></td>
                    <td><a href="<?= $href ?>"><?= $avalue ?></a>/
                        <a href="<?= baseurl('campanyedit/index/' . $value['id']) ?>">Изменить</a>/
                        <a href="<?= baseurl('campanylist/campdelete/' . $value['id']) ?>">Удалить</a></td>


                </tr>
            <?php endforeach;
        }?>
    </table>
</form>