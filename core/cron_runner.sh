#!/bin/bash
echo "["$(date)"] start cron_runner"
for i in {1..9};
do
    /bin/php /var/www/html/dialmanager/core/iptables.php 1>>/var/log/asterisk/iptables.log 2>>/var/log/asterisk/iptables.err.log
    sleep 5
done