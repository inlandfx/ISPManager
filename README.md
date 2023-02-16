# ISPManager 🌐🆘
A tiny script for monitoring and logging your local broadband connection speed to check true quality of service and in case of a lost connection due to a hanged router, logs in to the admin panel and restarts the router automatically.

In case your connection is acting patchy, the script can even login to your ISP's CRM tool and create a support ticket for flaky internet 😃

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

5. Setup your ISP's CRM tool url and page scheme here...

```
	$cc = new cURL(); 
	
	$password = file_get_contents('/volume1/web/ispmgr/password.txt');
	
	$loginattempt = $cc->post('http://isp.hathway.net:7406/selfcare/index.php?r=login/loginas','username=admin&password='.$password.'&servicetype=BB');
	
	//First lets check if the password has expired and needs changing
	//die(print_r($loginattempt));
	
	if(strpos($loginattempt['response'],"\"status\":\"2\"") != FALSE){ //If the password is expired, let's change the password
		
```

Dependancies:
=============
1. PHP > 5.0
2. Speedtest CLI
3. Compatible Router
