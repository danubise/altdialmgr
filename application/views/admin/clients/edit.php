<?php if (isset($userdata['failed']) && $userdata['failed']): ?>
    <div class="alert alert-danger" role="alert">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>
        User login '<?=$userdata['login']?>' exists, try set another user login
    </div>
<?php
endif
?>
<form method="post"  enctype="multipart/form-data" action="<?=baseurl('clients/usersave')?>">
<input name="id" type="hidden" value="<?=$userdata['id']?>">
    <table class="table  table-striped" style="width: 500px">
        <tr>
            <th>Login:&nbsp;</th>
            <td><input name="login" class="form-control" value="<?=$userdata['login']?>"></td>
        </tr>

        <tr>
            <th>Name:&nbsp;</th>
            <td><input name="name" type="text" class="form-control" value="<?=$userdata['name']?>"></td>
        </tr>

        <tr>
            <th>Email:&nbsp;</th>
            <td><input name="email" type="text" class="form-control" value="<?=$userdata['email']?>"></td>
        </tr>

        <tr>
            <th>Phone:&nbsp;</th>
            <td><input name="phone" type="text" class="form-control" value="<?=$userdata['phone']?>"></td>
        </tr>

        <tr>
            <th>Skype:&nbsp;</th>
            <td><input name="skype" type="text" class="form-control" value="<?=$userdata['skype']?>"></td>
        </tr>

        <tr>
            <th>&nbsp;</th>
            <td><button class="btn btn-primary">Save</button></td>
        </tr>
    </table>
</form>