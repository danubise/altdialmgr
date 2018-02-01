<?php if ($addfail): ?>
    <div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>
        Пользователь с указанным логином уже существует
    </div>
<?php
endif
?>
<form method="post"  enctype="multipart/form-data" action="<?=baseurl('settings/index/useradd')?>">
    <table class="table  table-striped" style="width: 500px">
        <tr>
            <th>Логин:&nbsp;</th>
            <td><input name="name" class="form-control"></td>
        </tr>
        <tr>
            <th>Пароль:&nbsp;</th>
            <td><input name="password" type="password" ></td>
        </tr>
        <tr>
            <th>&nbsp;</th>
            <td><button class="btn btn-primary">Добавить</button></td>
        </tr>
    </table>
</form>