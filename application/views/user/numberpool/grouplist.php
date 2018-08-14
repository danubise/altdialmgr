<a href="<?=baseurl('numberpool/index/add/')?>" class="btn btn-success">Создать группу</a>
<br><br>
<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>Группа</td><td>Количество номеров</td><td>Операции</td></tr>
        <?php
        if(is_array($pools)) {
            foreach ($pools as $key => $value):
                ?>
                <tr>
                    <td><?= $value['name'] ?></td>
                    <td><?=$value['numbercount'] ?></td>
                    <td><a href="<?= $href ?>"><?= $avalue ?></a>/
                        <a href="<?= baseurl('numberpool/index/edit/' . $value['id']) ?>">Изменить</a>/
                        <a href="<?= baseurl('numberpool/index/delete/' . $value['id']) ?>">Удалить</a></td>
                </tr>
            <?php endforeach;
        }?>
    </table>
</form>