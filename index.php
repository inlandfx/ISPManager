<?php
//Testing ISP Manager
//
// Functions:
// 1) Enable monitoring of data usage quota
// 2) Internet router auto restarter in case of connection drop out

require_once '/volume1/web/phpmailer/PHPMailerAutoload.php';

//initiate logging
	date_default_timezone_set('Asia/Calcutta');

// Initialize basic parameters
if(isset($argv)) parse_str(implode('&', array_slice($argv, 1)), $_GET);
$datalimit = 10.0; //GB
$datalock = "/volume1/web/ispmgr/datalimitsent";

class cURL { 
	var $headers; 
	var $user_agent; 
	var $compression; 
	var $cookie_file; 
	var $proxy; 

	function cURL($cookies=TRUE,$cookie='cookies.txt',$compression='gzip',$proxy='') { 
		$this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg'; 
		$this->headers[] = 'Connection: Keep-Alive'; 
		$this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8'; 
		$this->user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)'; 
		$this->compression=$compression; 
		$this->proxy=$proxy; 
		$this->cookies=$cookies; 
		if ($this->cookies == TRUE) $this->cookie($cookie); 
	} 
	
	function cookie($cookie_file) { 
		if (file_exists($cookie_file)) { 
		$this->cookie_file=$cookie_file; 
		} else { 
		fopen($cookie_file,'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions'); 
		$this->cookie_file=$cookie_file; 
		fclose($this->cookie_file); 
		} 
	} 
	function get($url) { 
		$process = curl_init($url); 
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers); 
		curl_setopt($process, CURLOPT_HEADER, 0); 
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file); 
		curl_setopt($process,CURLOPT_ENCODING , $this->compression); 
		curl_setopt($process, CURLOPT_TIMEOUT, 30); 
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy); 
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1); 
		$return['response'] = curl_exec($process); 
		$return['redirect'] = curl_getinfo($process, CURLINFO_REDIRECT_URL);
		
		curl_close($process); 
		return $return; 
	} 
	
	function post($url,$data) { 
		$process = curl_init($url); 
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers); 
		curl_setopt($process, CURLOPT_HEADER, 1); 
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file); 
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file); 
		curl_setopt($process, CURLOPT_ENCODING , $this->compression); 
		curl_setopt($process, CURLOPT_TIMEOUT, 30); 
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy); 
		curl_setopt($process, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt($process, CURLOPT_POST, 1); 
		$return['response'] = curl_exec($process); 
		$return['redirect'] = curl_getinfo($process, CURLINFO_REDIRECT_URL);
		
		curl_close($process); 
		return $return; 
	} 
	
	function error($error) { 
		echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>"; 
		die; 
	} 
} 

if(isset($_GET['action']) && $_GET['action']=="datausage"){
	
	$cc = new cURL(); 
	
	$password = file_get_contents('/volume1/web/ispmgr/password.txt');
	
	$loginattempt = $cc->post('http://isp.hathway.net:7406/selfcare/index.php?r=login/loginas','username=admin&password='.$password.'&servicetype=BB');
	
	//First lets check if the password has expired and needs changing
	//die(print_r($loginattempt));
	
	if(strpos($loginattempt['response'],"\"status\":\"2\"") != FALSE){ //If the password is expired, let's change the password
		
		$oldpassword = $password;
		
		while($oldpassword == $password){
			$password = 'Hathway123'.rand(0,9).'!'; //Create a new password
		}
		
		echo "Password expired\n";
		//echo "$loginattempt['response'] = ".$loginattempt['response']."\n\n";
		echo "New random generated password is '".$password."'\n";
		
		$changeattempt = $cc->post('http://isp.hathway.net:7406/selfcare/index.php?r=login/updatepassword','pwdguid=&account_no=accno&user_name=Sugato.B&newpassword='.$password.'&retypenewpassword='.$password);
		
		//Save the new password to file
		file_put_contents('/volume1/web/ispmgr/password.txt',$password);
		
		//Send mail to user notifying the new password
		//Create a new PHPMailer instance
		$mail = new PHPMailer;
		
		$mailid = array("yourname@gmail.com");
		
		//Set who the message is to be sent from
		$mail->setFrom('yourname@gmail.com', 'YourAppName');
		//Set an alternative reply-to address
		$mail->addReplyTo('yourname@gmail.com', 'Jane Doe');
		//Set who the message is to be sent to
		foreach($mailid as $id) { $mail->addAddress(urldecode($id), ''); }
		
		//Set the subject line
		$mail->Subject = 'Broadband Data - Hathway Password changed';

		$mail->msgHTML("Hi <br><br> Your Hathway password had expired so I've reset it to <strong>".$password."</strong><br><br> Cheers,<br>Calcium YourAppName");
		
		if($mail->send())
			echo '\r\nPassword change notification mailed successfully mailed to '.urldecode($_GET['mail']);
		else
			echo '\r\nFailed to generate email. Error: '.($mail->ErrorInfo);
	
	
		//Now redo the login attempt
		$loginattempt = $cc->post('http://isp.hathway.net:7607/selfcare/index.php?r=login/loginas','username=admin&password='.$password.'&servicetype=BB');
	}
	
	if(strpos($loginattempt['response'],"\"statustext\":\"success\"") != FALSE){
		$dashboard = $cc->get('http://isp.hathway.net:7406/selfcare/index.php?r=dashboard\/index');

		$dataline = explode("Remaning:", $dashboard['response']);
		$datausage = explode(" GB",$dataline[1]);
		
		$freedata = explode("CF/EU:", $dashboard['response']);
		$freedata = explode(" GB",$freedata[1]);
		$freedata = trim($freedata[0]); // This is the free data quota if any
		
		$cycledate = explode("<h5>Expiry Date</h5>", $dashboard['response']);
		$cycledate = substr($cycledate[1],strpos($cycledate[1],'<em>')+4,2);
		
		if(strlen($datausage[0]) > 1){ //since the correct strings have been found this means the correct page has been loaded
			
			$datausage = explode("/", trim($datausage[0]));
			$datausage[0] = $datausage[0] + $freedata;
			$datausage[1] = $datausage[1] + $freedata;
			
			file_put_contents("/volume1/web/ispmgr/datatrend.csv", date("d-M-Y").",".date("g:iA").",".$datausage[0].",".$datausage[1].",GB\n",FILE_APPEND); //Append the data balance to a trend file
			
			if(floatval($datausage[0])== 0){ //which means usage is complete exhausted
				$usagetext = 'Broadband Data Completely Exhausted - '.($datausage[0]).' of '.($datausage[1]).' GB remaining (Cycle renews on '.(($cycledate < date("d")) ? $cycledate.date("-M-Y",time()+30*24*60*60):$cycledate.date("-M-Y")).')';
				
				$maintext = "Hi <br><br> You're internet quota is exhausted little one and now I'm sad to admit your the life's speed will now be reduced to 1 Mbps.<br>This situation will continue for the next ".round((strtotime($cycledate.date("-M-Y"))-time())/(60*60*24),0)." days until your billing cycle renews <br><br> Cheers,<br>Calcium YourAppName";
			}
			else{
				$usagetext = 'Broadband Data Usage Warning - only '.($datausage[0]).' of '.($datausage[1]).' GB remaining (Cycle renews on '.(($cycledate < date("d")) ? $cycledate.date("-M-Y",time()+30*24*60*60):$cycledate.date("-M-Y")).')';
				
				$maintext = "Hi <br><br> Be wary little one for soon your the connection speed will be reduced to 1 Mbps.<br>Try to use the internet wisely for the next ".round((strtotime($cycledate.date("-M-Y"))-time())/(60*60*24),0)." days :-) <br><br> Cheers,<br>Calcium YourAppName";
			}
			
			if(date("j",time()-24*60*60)."" >= $cycledate && file_exists($datalock) && date('j',filemtime($datalock)) <= $cycledate) //If the date lock file warning was created prior to the cycle date then lets delete it
				unlink($datalock);
			
			echo $usagetext; //Output the text for web users
			
			if(floatval($datausage[0]) <= $datalimit && isset($_GET['mail']) && !file_exists($datalock)){
								
				//Action warning mail
				//Create a new PHPMailer instance
				$mail = new PHPMailer;
				
				$mailid = explode(",",$_GET['mail']);
				
				//Set who the message is to be sent from
				$mail->setFrom('yourname@gmail.com', 'YourAppName');
				//Set an alternative reply-to address
				$mail->addReplyTo('yourname@gmail.com', 'Jane Doe');
				//Set who the message is to be sent to
				foreach($mailid as $id) { $mail->addAddress(urldecode($id), ''); }
				
				//Set the subject line
				$mail->Subject = $usagetext;

				$mail->msgHTML($maintext);
				
				if($mail->send()){
					echo '\r\nPassword change successfully mailed to '.urldecode($_GET['mail']);
					
					if($datausage[0] == 0) file_put_contents($datalock,"Data exhaustion mail sent on ".date("j-M-Y",time()));
				}
				else
					echo '\r\nFailed to generate email. Error: '.($mail->ErrorInfo);
			}	
		}
		else
			echo "Error unable fetch dashboard page or page scheme changed\r\n\r\nDegug dump ==================\r\n".$dashboard['response'];
	}
	else
		echo 'System error. Unable to login';
}
else if(isset($_GET['action']) && $_GET['action']=="checknet"){
	
	$test = file_get_contents("http://www.google.com");	
	
	if(strlen($test) > 0){
		echo 'Net is working fine';
	
		//check if a reboot log file exists in which case we need to send an email alerting the previous reboot
		if(file_exists("/volume1/web/ispmgr/reboot.init") && isset($_GET['mail'])){
			
			//Get restart timestamp
			$rebootlog = file_get_contents("/volume1/web/ispmgr/reboot.init");
			
			//Send router restart mail
			//Create a new PHPMailer instance
			$mail = new PHPMailer;
			
			$mailid = explode(",",$_GET['mail']);
			
			//Set who the message is to be sent from
			$mail->setFrom('yourname@gmail.com', 'YourAppName');
			
			//Set an alternative reply-to address
			$mail->addReplyTo('yourname@gmail.com', 'Jane Doe');
			
			//Set who the message is to be sent to
			foreach($mailid as $id) { $mail->addAddress(urldecode($id), ''); }
			
			//Set the subject line
			$mail->Subject = 'Internet Connection Disrupted - Router Restart Initiated at '.(($rebootlog != "") ? date('g.ia d-M-Y',$rebootlog):'Unknown Event Time');

			$mail->msgHTML("Regards,<br>Calcium YourAppName");
			
			if($mail->send()){
				//Now that the user has been mailed, delete the log file
				unlink("/volume1/web/ispmgr/reboot.init");
				
				echo 'Warning successfully mailed to '.urldecode($_GET['mail']).'\n\n';
			}
			else
				echo 'Failed to generate email. Error: '.($mail->ErrorInfo).'\n\n';
		}
	}
	else if(isset($_GET['restartok'])){
		echo 'Net seems to be down. Initiating router reboot...';
		
		//Create reboot log file so that an email can be initiated the next time the process runs
		if(!file_exists("/volume1/web/ispmgr/reboot.init")){
			
			$log = fopen("/volume1/web/ispmgr/reboot.init",'w');
			
			if($log){
				fputs($log,time());
				echo '\r\nReboot log file created';
				
				fclose($log);
			}
			else
				echo '\r\nFailed to create reboot log file';
		}
			
		//Action router restart for Router Model D-Link DIR-615, Firmware ver: 20.09
		
		$cc = new cURL(); 
		$cc->post('http://192.168.0.1/login.cgi','username=YourAppName&password=calcium&submit.htm?login.htm=Send');
		$cc->post('http://192.168.0.1/form2reboot.cgi','reboot=Reboot&submit.htm?reboot.htm=Send');
	}	
	else
		echo 'Net seems to be down';
		
} else if(isset($_GET['action']) && isset($_GET['trendperiod']) && $_GET['action']=="datatrend"){
	
	//Generate a trend chart of the period selected
	
	require_once '/volume1/web/jpgraph/src/jpgraph.php';
	require_once '/volume1/web/jpgraph/src/jpgraph_bar.php';
	
	$datatrend = array();
	$datachartX = array();
	$datachartY1 = array(); //Used data
	$datachartY2 = array(); //Remaining data
	
	if(file_exists("/volume1/web/ispmgr/datatrend.csv")){
		
		$trendfile = file("/volume1/web/ispmgr/datatrend.csv");
		
		foreach($trendfile as $line){
			
			$fields = explode(',',$line);
			
			$datatrend[] = array(
									'date' => strtotime($fields[0]),
									'time' => $fields[1],
									'remainingdata' => $fields[2],
									'quota' => $fields[3],
									'uom' => $fields[4]
								); //Populate the data trend array 
		}
		
		foreach($datatrend as $indexnum=>$point){
		
			if($indexnum > 0) // Which means a prior date point exists, for estimating
				$point_data = $datatrend[$indexnum-1]['remainingdata']-$point['remainingdata']; //Calculate the data consumed as current remaingin - prev remaining data
			else //No prior date point will exist since the entry is the last entry in the log
				$point_data = 0; //Since no prior data point exists, the data consumption cannot be estimated.  
			
			if( //Lets check if the data point falls within the specified time frame
				$point['date'] >= time() - $_GET['trendperiod']*24*60*60 && 
				$point['date'] <= time()
			){	
				//data point selected is within the time frame,lets build the array to be passed to the Chart builder API
				
				if(count($datachartX) >= 1 && $datachartX[count($datachartX)-1] == date('d-M',$point['date'])){
					
					//Which means the date in this current data point and the previous data point are the same
					//Let's skip this data point as we want the earliest measurement available on that date
					
				}
				else{
					//No repetition, this is a fresh date point baby!!!
					$datachartX[] = date('d-M',$point['date']); 
					$datachartY2[] = $point['remainingdata'];
					
					if($point_data >= 0) 
						$datachartY1[] = $point_data;
					else //Wait! If the date consumed is negative, it must mean the billing cycle has renewed
						$datachartY1[] = $point['quota']+$point_data; 
				}
			}
		}
		
		if(count($datachartX)>0){
			
			//Generate the chart
			setlocale (LC_ALL, 'et_EE.ISO-8859-1');
			$graph = new Graph(950,200);    
			$graph->SetScale("textlin");
			
			$graph->img->SetMargin(40,30,20,40);
			
			$bplot1 = new BarPlot($datachartY1);
			$bplot1->SetFillColor('orange');
			$bplot2 = new BarPlot($datachartY2);
			$bplot2->SetFillColor('blue');
			
			$gplot = new GroupBarPlot(array($bplot2,$bplot1));
			
			$graph->Add($gplot);
			
			$bplot1->value->SetAlign('center');
			$bplot1->value->Show();
			$bplot1->SetLegend ("Consumed data");
			$bplot2->value->SetAlign('center');
			$bplot2->value->Show();
			$bplot2->SetLegend ("Remaining Quota");
			
			$graph->title->Set('Broadband Usage Summary');
			$graph->xaxis->title->Set('Date');
			$graph->yaxis->title->Set('Data Usage (GB)');
			
			$graph->xaxis->SetTickLabels($datachartX);
			
			$graph->Stroke('/volume1/web/ispmgr/logs/summarychart-'.date('d-M-Y').'.png');
			
			$maintext = "
			Hi <br><br> I've prepared the broadband consumption report for the last ".count($datachartX)." days. See the chart below <br><br> 
			<img src='cid:trendchart_1'>
			<br><br> Cheers,
			<br>Calcium YourAppName";
			
			//Action the trend mail
			//Create a new PHPMailer instance
			$mail = new PHPMailer;
			//$mail->isSMTP();
			//$mail->SMTPDebug = 3;
			
			$mailid = explode(",",$_GET['mail']);
			
			//Set who the message is to be sent from
			$mail->setFrom('yourname@gmail.com', 'YourAppName');
			//Set an alternative reply-to address
			$mail->addReplyTo('yourname@gmail.com', 'Jane Doe');
			//Set who the message is to be sent to
			foreach($mailid as $id) { $mail->addAddress(urldecode($id)); }
			
			//Set the subject line
			$mail->Subject = "Broadband Usage Summary Report";
			$mail->AddEmbeddedImage('/volume1/web/ispmgr/logs/summarychart-'.date('d-M-Y').'.png', 'trendchart_1');

			$mail->msgHTML($maintext);
			
			if($mail->send()){
				echo '\r\nSummary report successfully mailed to '.urldecode($_GET['mail']);
			}
			else
				echo '\r\nFailed to generate email. Error: '.($mail->ErrorInfo);
		}
	}
	else
		echo 'Trend file does not exist';
}
else echo "No options selected. Terminating";


?>