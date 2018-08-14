<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title><?=(($this->module_name)? ' | '.$this->module_name : '')?></title>
    <link rel="stylesheet" href="<?=baseurl('pub/css/bootstrap.css')?>" media="screen">
    <link rel="stylesheet" href="<?=baseurl('pub/css/bootswatch.min.css')?>">
    <?php
    if(is_array($param['css'])) {
        foreach ($param['css'] as $value) {
            echo "<link rel=\"stylesheet\" href=\"".$value."\" >\n";
        }
    }
    ?>
    <script src="<?=baseurl('pub/js/jquery-2.1.3.js')?>"></script>
    <script src="<?=baseurl('pub/js/bootstrap.min.js')?>"></script>
    <script src="<?=baseurl('pub/js/bootswatch.js')?>"></script>
</head>
<body>
<div class="navbar navbar-default navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="navbar-collapse collapse" id="navbar-main">
            <ul class="nav navbar-nav">
                <li><a href="<?=baseurl("numberpool")?>">Группы номеров</a></li>
                <li><a href="<?=baseurl("tester")?>">Тестер маршрутов</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="<?=baseurl('home/logout')?>"><i class="fa fa-power-off"></i> Выход</a></li>
            </ul>

        </div>
    </div>
</div>


<div class="container">
    <div class="well" style="margin-top: 20px;">
        <? include($CONTENT)?>
    </div>
</div>
<center></center>
<?php
if(is_array($param['js'])) {
    foreach ($param['js'] as $value) {
        echo "<script src=\"".$value."\" ></script>\n";
    }
}
?>
</html>
