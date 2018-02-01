<h3>Редактирование группы : <?=$pool['name']?></h3>
<form method="post" >
    <a href="<?=baseurl('numberpool/index/additem/'.$idpool)?>" class="btn btn-success">Добавить номера</a>&nbsp;
    <button  formaction="<?=baseurl('numberpool/index/deleteitem/'.$idpool)?>" class="btn btn-danger">Удалить</button> <br/><br/>
    <table class="table table-bordered table-striped">
        <tr><td>Номер</td></tr>
        <?php
        if(is_array($listnumber)) {
            foreach ($listnumber as $key => $value):
                ?>
                <tr>
                    <td><input type="checkbox" name="select[<?=$value['id']?>]">  <?=$value['number']?></td>
                </tr>
            <?php endforeach;
        }?>
    </table>
</form>