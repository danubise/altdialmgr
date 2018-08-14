<form method="post"  enctype="multipart/form-data" action="<?=baseurl('numberpool/index/addnumbertopool/'.$idpool)?>">
    <table class="table  table-striped" style="width: 500px">
        <tr>
            <th>Добавление номеров в:&nbsp;</th>
            <td><?=$poolname?><input type="hidden" name="idpool" class="form-control" value="<?=$idpool?>"></td>
        </tr>
        <tr>
        <td colspan=2>
        <textarea style='height: 320px;' name='numbers' class='form-control'></textarea>
        </td>
        </tr>
        <!--<tr>
            <th>Файл:&nbsp;</th>
            <td><input name="file" type="file" ></td>
        </tr>!-->
        <tr>
            <th>&nbsp;</th>
            <td><button class="btn btn-primary">Добавить</button></td>
        </tr>
     </table>
</form>