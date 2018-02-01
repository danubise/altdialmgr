<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Авторизация</title>
    <link rel="stylesheet" href="<?=baseurl('pub/css/bootstrap.css')?>">
</head>
<body>
<div style='margin-top:20px;margin-left:20px'>
    <form method='post' action='home/login'>
        <table class='table table-striped table-bordered' style='max-width:400px;'>
            <tr>
                <th style='text-align:left'>Логин:&nbsp;</th>
                <td><input name='login' value='' type='text' class='form-control'></td>
            </tr>
            <tr>
                <th style='text-align:left'>Пароль:&nbsp;</th>
                <td><input name='pass' value='' type='password' class='form-control'></td>
            </tr>
            <tr><td colspan=2 style='text-align:right'><button class='btn btn-primary'>Вход</button></td></tr>
        </table>
    </form>
</div>
</body>
</html>