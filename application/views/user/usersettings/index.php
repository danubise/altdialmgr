
<form method="post">
<table class="table table-bordered table-striped">
<thead>
    <tr><th colspan=2>Network:</th></tr>
</thead>
    <tr>
        <td>Voip host name/ip</td>
        <td><input name="ipaddress" value="<?=$networkSettings['ipaddress'] ?>"></td>
    </tr>

    <tr>
        <td>Sip port</td>
        <td><input name="port" value="<?=$networkSettings['port'] ?>"></td>
    </tr>

    <tr>
        <td>A number</td>
        <td><input name="anumber" value="<?=$anumber ?>"></td>
    </tr>
    <tr>
        <td>Prefix</td>
        <td><input name="prefix" value="<?=$prefix ?>"></td>
    </tr>
<tr><td></td><td></td></tr>

<tr><td></td><td> <button>Save</button></td></tr>
<input type="hidden" name='save' value='1'>

</table>
</form>
