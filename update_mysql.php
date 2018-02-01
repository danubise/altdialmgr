<?php
$server = 'http://vladlexx.ru/rep/current.xml';
$get = simplexml_load_string(file_get_contents($server));

if(empty($get)) die('Нет соединеня с сервером');

$version = $get->mysqli->version;
$hash = $get->mysqli->hash;
$comment = $get->mysqli->comment;

$load_file = 1;
$file_info = '';
if(!empty($_POST['file_mysqli'])) {
    $file = $_POST['file_mysqli'];
    if(file_exists($file)) {
        $load_file = 0;
        echo "<!--";
        require_once($file);
        echo "!-->";
        if (class_exists('db')) {
            $current_hash = md5(file_get_contents($file));
            $db = new db();
            if($_POST['update']=='do') {
                $get_current_version = file_get_contents($get->mysqli->file);
                file_put_contents($file,$get_current_version);
                $load_file = 1;
                $file_info = '<span style="color: #008000">Файл <b>'.$file.'</b> успешно обновлён</span>';
            }
        } else {
            $load_file = 1;
            $file_info = '<span style="color: #ff0000">Файл <b>'.$file.'</b> найден, но не подходит</span>';
        }
    } else {
        $file_info = '<span style="color: #ff0000">Файл <b>'.$file.'</b> не найден</span>';
    }
}

?>
<!DOCTYPE html>
<html>
<head lang="ru">
    <meta charset="UTF-8">
    <title>Обновление библиотеки mysqli</title>
</head>
<body>
<b>Версия последнего релиза:</b> <span style="color: #008000"><?=$version?></span><br>
<?php if($load_file){?><?=$file_info?>
    <form method="post">
        <b>Расположение файла отностилельно корня сайта:</b> <input type="text" name="file_mysqli"><input type="submit" value="Применить">
    </form>
<?php } else {?>
    <?php
    $update_info = '';
    if($db->version!=$version) {
        $update_info = ' <span style="color: #ff0000">необходимо обновить!</span>';
    }
    chmod($file, 0777);
    ?>
    <hr><b style="color: #0000ff">Ваш файл</b><br>
    <b>Библиотека:</b> <?=$file?><br>
    <b>Версия:</b> <?=$db->version.$update_info?><br>
    <!--<b>Хеш файла:</b> <?=$current_hash?><br>!-->
    <?php if($update_info): ?>
        <br><form method="post">
            <input name="file_mysqli" type="hidden" value="<?=$file?>">
            <button name="update" value="do">Обновить</button></form>
    <?php endif; ?>
<?php }?>
<hr>
<? if(!empty($comment)):?><b>Комментарий разработчика:</b> <textarea style="margin: 0px; width: 405px; height: 159px;"><?=$comment?></textarea><? endif;?>
</body>
</html> 