<a href="<?=baseurl('clients/add')?>" class="btn btn-success">Create</a>
<br><br>
<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>User</td><td>Email</td><td>Operation</td></tr>
        <?php
        if(is_array($users)) {
            foreach ($users as $key => $value):
                ?>
                <tr>
                    <td><?= $value['login'] ?></td>
                    <td><?= $value['email'] ?></td>
                    <td>
                        <a href="<?= baseurl('clients/useredit/' . $value['login']) ?>" class="btn btn-success">Edit</a>
                        &nbsp;
                        <a href="<?= baseurl('clients/userdelete/' . $value['login']) ?>" class="btn btn-danger">Del</a>
                    </td>
                </tr>
            <?php endforeach;
        }?>
    </table>
</form>
