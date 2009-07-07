<?php
// a plugin that will limit the number of requests an IP can make per set amount of time

$SETTINGS = array_merge(array(
									 'max-unique-permin' 	=> 30
									,'ip-timeout' 				=> 1
									,'unmetered-time' 		=> 10
									,'enable-banlist' 		=> 'true'
									,'ban-count' 				=> 10
									,'ban-time' 				=> 7
								), $SETTINGS);

$SETTINGS['max-unique-permin'] = $SETTINGS['max-unique-permin']/60;

$IP = $_SERVER['REMOTE_ADDR'];
$REQMD5 = md5(urldecode($_SERVER['REQUEST_URI']));
$IPDB = get_cache_fn(md5('ddoslimit-'.substr($IP, 0, strrpos($IP, '.'))), 'txt');
if(!file_exists($IPDB))
	file_put_contents($IPDB, serialize(array()));
$IPDATA = unserialize(file_get_contents($IPDB));

if($SETTINGS['enable-banlist'] == 'true') {
	$BANLIST = get_cache_fn(md5('ddoslimitbanlist-'.substr($IP, 0, strrpos($IP, '.'))), 'txt');
	if(!file_exists($BANLIST))
		file_put_contents($BANLIST, serialize(array()));
	$BANLISTDATA = unserialize(file_get_contents($BANLIST));
	
	if(isset($BANLISTDATA[$IP])) {
		if($BANLISTDATA[$IP]==-1)
			die('You are banned. Stop.');
		elseif(time() <= $BANLISTDATA[$IP])
			die('You are banned.');
		elseif(time() > $BANLISTDATA[$IP]) {
			unset($BANLISTDATA[$IP]); // ban lifted
			file_put_contents($BANLIST, serialize($BANLISTDATA));
		}
	}
}

if(isset($IPDATA[$IP])) {
	if($IPDATA[$IP]['lastseen'] < strtotime('-'.$SETTINGS['ip-timeout'].' days')) {
		unset($IPDATA[$IP]);
	}else {
		$IPDATA[$IP]['lastseen'] = time();

		$total_requests = count($IPDATA[$IP]['requests']);
		$elapsed = $IPDATA[$IP]['lastseen']-$IPDATA[$IP]['introduction'];
		
		if($SETTINGS['enable-banlist'] == 'true' && $total_requests > $SETTINGS['ban-count']) {
			$BANLISTDATA[$IP] = $SETTINGS['ban-time'] == -1 ? -1 : strtotime('-'.$SETTINGS['ban-time'].' days');
			file_put_contents($BANLIST, serialize($BANLISTDATA));
			die('You have been banned.');
		}

		if(!isset($IPDATA[$IP]['requests'][$REQMD5]))
			$IPDATA[$IP]['requests'][$REQMD5]=1;
		else
			$IPDATA[$IP]['requests'][$REQMD5]++;
					
		if($elapsed > $SETTINGS['unmetered-time']) {
			$avg = $total_requests/$elapsed;
			
			if($avg > $SETTINGS['max-unique-permin'])
				die('Error: Image generation limit reached.');
		}
	}
}

if(!isset($IPDATA[$IP])) {
	$IPDATA[$IP] = array(
		 'introduction' 	=> time()
		,'lastseen'			=> time()
		,'requests'			=> array($REQMD5 => 1)
	);
}

file_put_contents($IPDB, serialize($IPDATA));
?>