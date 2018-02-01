<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 27.02.15
 * Time: 10:50
 *
 * Конфигурация роутинга
 * Настраивать очень аккуратно, может привести к критическим ошибкам!
 * Обращать внимание на уровни доступа! (User,Guest,Admin...)
 *
 * Пример:
 * site.ru/{Алиас: Welcome} == site.ru/{Контолер: User}/{Вьюха: Welcome}
 * public $Welcome = ['User','Welcome'];
 *
 */
class Config_route {
    public $welcome = array('test','index');
}