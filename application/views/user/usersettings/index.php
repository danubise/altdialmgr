
<form method="post">
<table class="table table-bordered table-striped">
<thead>
    <tr><th colspan=2>Network:</th></tr>
</thead>
    <tr>
        <td>Voip host name/ip</td>
        <td><input name="ipaddress" value="<?=$networkSettings['ipaddress'] ?>" maxlength="25"></td>
    </tr>

    <tr>
        <td>Sip port</td>
        <td><input name="port" value="<?=$networkSettings['port'] ?>" maxlength="5"></td>
    </tr>

    <tr>
        <td>A number</td>
        <td><input name="anumber" value="<?=$anumber ?>" maxlength="25"></td>
    </tr>
    <tr>
        <td>Prefix</td>
        <td><input name="prefix" value="<?=$prefix ?>" maxlength="25"></td>
    </tr>
    <tr>
        <td>Codec</td>
        <td>
            <select name="codec">
                <option <? if( $networkSettings['codec'] == "alaw") echo "selected"?> value="alaw">g711</option>
                <option <? if( $networkSettings['codec'] == "g729") echo "selected"?> value="g729">g729</option>
            </select>
        </td>
    </tr>
<tr><td></td><td></td></tr>

<tr><td></td><td> <button>Save</button></td></tr>
<input type="hidden" name='save' value='1'>

</table>
</form>
