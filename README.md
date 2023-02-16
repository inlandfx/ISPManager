# ISPManager 🌐🆘
A tiny script for monitoring and logging your local broadband connection speed to check true quality of service and in case of a lost connection due to a hanged router, logs in to the admin panel and restarts the router automatically.

## Setup: 
1. Setup a CRON job to run the connection monitoring script at a set frequency. For e.g. I had set mine to run every 5 minutes

```
(printf "\r[" && printf "$(date +"%d-%b-%Y %I:%M %p %A")" && printf "] " &&  php  -f /path/to/script/index.php action=checknet restartok=1 mail=yourname@gmail.com) >> /path/to/script/logs/net-$(date +"%Y-%m").log
```

2. Setup another job to run the spedtest CLI script and pipe all output to the designated logging path

```
/path/to/script/speedtest-cli >> /path/to/script/speedlogs/speed-$(date +"%Y-%m-%d %k.%M").log
```

3. Update your router admin panel login password in password.txt, username defaults to 'admin' but you can change this in index.php

4. Setup the speed reports to mail you the details. You can provide multiple email id's with commas

```
php -f /path/to/script/index.php mail=yourname@gmail.com,secondname@gmail.com
```

Dependancies:
=============
1. PHP > 5.0
2. Speedtest CLI
3. Compatible Router
