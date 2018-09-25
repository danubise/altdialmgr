<a href="<?=baseurl('numberpool/index/add/')?>" class="btn btn-success">New group</a>
<br><br>
<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>Name</td><td>Count of the phones</td><td>Actions</td></tr>
        <?php
        if(is_array($pools)) {
            foreach ($pools as $key => $value):
                ?>
                <tr>
                    <td><?= $value['name'] ?></td>
                    <td><?=$value['numbercount'] ?></td>
                    <td>
                        <a href="<?= baseurl('numberpool/index/edit/' . $value['id']) ?>" class="btn btn-success">Edit</a>
                        <a href="<?= baseurl('numberpool/index/delete/' . $value['id']) ?>" class="btn btn-danger">Delete</a></td>
                </tr>
            <?php endforeach;
        }?>
    </table>
</form>