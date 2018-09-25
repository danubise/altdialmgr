<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 28.05.15
 * Time: 14:09
 */
?>
<form method="post">
<table class="table table-bordered table-striped">
<thead>
    <tr><th colspan=2>New test</th></tr>
</thead>
<tr>
<td>Name</td><td><input name="name" ></td></tr>

<tr><td>Phone numbers group</td><td><?=$poolgroup?></td></tr>
<tr><td>'A' number</td><td><input type="text" name="anumber" value="<?=$anumber?>" maxlength="25"></td></tr>
<tr><td></td><td></td></tr>

<tr><td></td><td> <button>Create</button></td></tr>
<input type="hidden" name='create' value='1'>

</table>
</form>
