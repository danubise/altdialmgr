<form method="post" action="<?=baseurl('numberpool/savenewname')?>">Edit phone group:
    <input type="text" size="50" name="poolname[<?=$idpool?>]" value="<?=$pool['name']?>">
    <button class="btn btn-primary">Save</button>
</form>
<form method="post" >
    <a href="<?=baseurl('numberpool/index/additem/'.$idpool)?>" class="btn btn-success">Add phone number</a>&nbsp;
    <button  formaction="<?=baseurl('numberpool/index/deleteitem/'.$idpool)?>" class="btn btn-danger">Delete</button> <br/><br/>
    <table class="table table-bordered table-striped">
        <tr><td>Phone</td></tr>
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