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
    <tr><th colspan=2>Новый тест</th></tr>
</thead>
<tr>
<td>Название</td><td><input name="name"></td></tr>

<tr><td>Группа номеров</td><td><?=$poolgroup?></td></tr>
<tr><td></td><td></td></tr>

<tr><td></td><td> <button>Сохранить</button></td></tr>
<input type="hidden" name='add' value='1'>

</table>
</form>
