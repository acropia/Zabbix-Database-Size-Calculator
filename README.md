#Zabbix-Database-Size-Calculator
Written in PHP, this stand-alone script calculates the required storage for Zabbix history data. Also it reports the actual used storage for history and trend data.

It uses the credentials from the official Zabbix Web installation from /etc/zabbix/web/zabbix.conf.php

##Usage
Upload the script to your Zabbix webroot (i.e. /usr/share/zabbix/) and open the size.php script in your browser.


##Requirements
###Server
- PHP PDO

###User (visiting the size.php script)
- Internet Access to URL https://maxcdn.bootstrapcdn.com/ (for better visualization by Bootstrap 3)

##Tested on:
- CentOS Linux 7.1 / Apache HTTPD 2.4.6 / PHP 5.6.9 / Zabbix Server+Web 2.4.5
- CentOS LInux 6.5 / Apache HTTPD 2.2.15 / PHP 5.3.3 / Zabbix Server+Web 2.4.1

##Todo
n/a

##Sources
The calculations done by this script are based on the official Zabbix Documentation found over here: https://www.zabbix.com/documentation/2.4/manual/installation/requirements
