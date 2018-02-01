<a href="<?=baseurl('settings/index/useraddview/')?>" class="btn btn-success">Создать пользователя</a>
<br><br>
<form method="post">
    <table class="table table-bordered table-striped">
        <tr><td>Пользователи</td><td>Операции</td></tr>
        <?php
        if(is_array($users)) {
            foreach ($users as $key => $value):
                ?>
                <tr>
                    <td><?= $value['login'] ?></td>
                    <td><a href="<?= $href ?>"><?= $avalue ?></a>/
                        <a href="<?= baseurl('settings/index/useredit/' . $value['id']) ?>">Изменить</a>/
                        <a href="<?= baseurl('settings/index/userdelete/' . $value['id']) ?>">Удалить</a></td>
                </tr>
            <?php endforeach;
        }?>
    </table>
</form>

<div id="email_form" style="display: block;">
    <form name="adminForm" class="e-form" id="e-form" enctype="multipart/form-data" method="post" action="<?= baseurl('settings/index/test/') ?>">

        <input type="hidden" name="f_CatID" value="264">
        <input type="hidden" name="nc_ctpl" value="2072">
        <input type="hidden" name="isNaked" value="1">

        <div id="ef_body">
            <div id="efb_title">E-mail</div>
            <div id="efb_text">пожалуйста, укажите ваш адрес электронной почты (e-mail) для подтверждения вашего голоса.<br>На этот адрес мы отправим ссылку с кодом для голосования.</div>
            <div id="efb_input">
                <input type="text" name="f_Email" value="Ваш e-mail" data-value="Ваш e-mail" class="">
                <div id="efb_input_warning"></div>
                <div id="efb_preloader"></div>
            </div>
            <input type="submit" id="efb_submit" value="Отправить">
        </div>
        <div id="ef_notice">
            <div id="ef_title"></div>
            <a href="#" id="ef_close">Закрыть</a>
        </div>
    </form>
</div>