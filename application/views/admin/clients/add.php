<?php if ($addfail): ?>
    <div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>
        Пользователь с указанным логином уже существует
    </div>
<?php
endif
?>
<form method="post"  enctype="multipart/form-data" action="<?=baseurl('clients/adduser')?>">
    <table class="table  table-striped" style="width: 500px">
        <tr>
            <th>Login:&nbsp;</th>
            <td><input name="login" class="form-control"></td>
        </tr>
        <tr>
            <th>Password:&nbsp;</th>
            <td><input name="password" type="password" class="form-control" ></td>
        </tr>

        <tr>
            <th>Name:&nbsp;</th>
            <td><input name="name" type="text" class="form-control"></td>
        </tr>

        <tr>
            <th>Email:&nbsp;</th>
            <td><input name="email" type="text" class="form-control"></td>
        </tr>

        <tr>
            <th>Phone:&nbsp;</th>
            <td><input name="phone" type="text" class="form-control"></td>
        </tr>

        <tr>
            <th>Skype:&nbsp;</th>
            <td><input name="skype" type="text" class="form-control"></td>
        </tr>

        <tr>
            <th>&nbsp;</th>
            <td><button class="btn btn-primary">Apply</button></td>
        </tr>
    </table>
</form>