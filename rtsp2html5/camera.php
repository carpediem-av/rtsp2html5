<?php
// Copyright (c) 2020-2021 Carpe Diem Software Developing by Alex Versetty.
// http://carpediem.0fees.us/
// License: MIT
// Version 1.2

// Программа получает ссылку на камеру и отображает видео/фото, при необходимости - делает перекодировку через ffmpeg.
// The program receives a link to the camera and displays video/photo. If necessary, makes a transcoding via ffmpeg.

// Формат запроса видео: camera.php?b={ключ}&a={ссылка rtsp в base64}&c={ссылка второго потока rtsp в base64}
// Формат запроса фото: camera.php?b={ключ}&a={ссылка rtsp в base64}&c={ссылка второго потока rtsp в base64}&get=jpeg
// Фото в полном размере: camera.php?b={ключ}&a={ссылка rtsp в base64}&c={ссылка второго потока rtsp в base64}&get=jpeg-hq
// Параметр "с" необязательный

// Video request format: camera.php?b={key}&a={base64-coded rtsp uri}&c={base64-coded rtsp 2nd stream uri}
// Still image request format: camera.php?b={key}&a={base64-coded rtsp uri}&c={base64-coded rtsp 2nd stream uri}&get=jpeg
// Full size still image: camera.php?b={key}&a={base64-coded rtsp uri}&c={base64-coded rtsp 2nd stream uri}&get=jpeg-hq
// The "c" parameter is optional

//////////////////////// КОНФИГУРАЦИЯ / CONFIGURATION ////////////////////////////////////////////////////////////////////////////////
$key = "придумайте свой / come up with your own";		//ключ безопасности (минимум 10 символов)
														//security key (minimum 10 characters)
$duration_limit = 600; 									//ограничение на просмотр, секунд (потом картинка остановится)
														//limit duration of video streaming, seconds
$jpeg_quality = 5;  									//для jpeg: качество
$jpeg_resolution = "360x202";  							//для jpeg: разрешение
$jpeg_cachetime = 60;  									//для jpeg: время хранения кадра в кэше браузера, секунд
														//jpeg: limit duration of thumbnail browser caching
$webm_ogv_fps = 6; 										//для webm & ogv: кадров в секунду
$webm_ogv_bitrate = "512k"; 							//для webm & ogv: битрейт
$webm_ogv_resolution = "640x360"; 						//для webm & ogv: разрешение
$mjpeg_fps = 6; 										//для mjpeg: кадров в секунду
$mjpeg_bitrate = "1200k"; 								//для mjpeg: битрейт
$mjpeg_resolution = "640x360"; 							//для mjpeg: разрешение
$redirectToIfBackground = 'http://'; 					//сайт для переадресации на него, если мы в фоновой вкладке (экономия трафика)
														//redirect to this url if tab running background (for trafic saving)
$ffmpeg_path = "ffmpeg";								//путь к программе ffmpeg
														//ffmpeg program path
$timeout = '5000000';									//таймаут установления соединения в микросекундах
														//connection establishment timeout in microseconds
$rtsp_transport = 'tcp';								// выберите между 'tcp' или 'udp'
														// choose between 'tcp' or 'udp'
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function getRAMUsage() {
    $free = shell_exec('free');
    $free = (string) trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2] / $mem[1] * 100;
    return $memory_usage;
}

function ffmpeg_getVersion()
{
	static $nums = null;
	if ($nums != null) return $nums;
	
	$info = shell_exec('ffmpeg -version');
	$pattern = '/ffmpeg\s+version\s+(\d+.\d+.\d+)/';
	
	if (preg_match($pattern, $info, $matches)) {
		$versionStr = $matches[1];
		$nums = explode('.', $versionStr);
		return $nums;
	}
	else return null;
}

function mpjpeg_getBoundary()
{
	if (ffmpeg_getVersion()[0] >= 4) {
		return 'ffmpeg';
	}
	else {
		return 'ffserver';
	}
}

function disableBrowserCaching()
{
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

function passthruErrorImg()
{
	$errorImg = file_get_contents('error.png');
	echo $errorImg;
}

// запрет доступа без указания ключа
if (!isset($_REQUEST["b"]) || $_REQUEST["b"] !== $key) { 
	echo "forbidden";
	die;
}

if (isset($_REQUEST["get"])) {
	header('Accept-Ranges:bytes');
	header('Connection:keep-alive');

	if (ffmpeg_getVersion()[0] >= 5) {
		$timeout_opt = "-timeout {$timeout}";
	}
	else {
		$timeout_opt = "-stimeout {$timeout}";
	}

	$rtsp = str_replace("'", '', str_replace("\"", '', base64_decode($_REQUEST["a"])));
	if (substr($rtsp, 0, 7) !== "rtsp://") die('RTSP URL is invalid!');
	$ffmpeg_base = "{$ffmpeg_path} -rtsp_transport {$rtsp_transport} -probesize 32 {$timeout_opt} -i \"{$rtsp}\" -loglevel quiet";
	
	if (isset($_REQUEST["c"])) {
		$rtsp_lq = str_replace("'", '', str_replace("\"", '', base64_decode($_REQUEST["c"])));
		if (substr($rtsp_lq, 0, 7) !== "rtsp://") die('RTSP URL (sub/second stream) is invalid!');
		$ffmpeg_base_lq = "{$ffmpeg_path} -rtsp_transport {$rtsp_transport} -probesize 32 {$timeout_opt} -i \"{$rtsp_lq}\" -loglevel quiet";
	}
	else {
		$ffmpeg_base_lq = $ffmpeg_base;
	}

	switch ($_REQUEST["get"]) {
		case "jpeg":
			header("Cache-Control: public, max-age=60");
			header('Content-type: image/jpeg');
			if (getRAMUsage() > 80) passthruErrorImg();
			else passthru("{$ffmpeg_base_lq} -vframes 1 -s {$jpeg_resolution} -q:v {$jpeg_quality} -f singlejpeg pipe:");
			break;
		case "jpeg-hq":
			header("Cache-Control: public, max-age=60");
			header('Content-type: image/jpeg');
			if (getRAMUsage() > 80) passthruErrorImg();
			else passthru("{$ffmpeg_base} -vframes 1 -q:v {$jpeg_quality} -f singlejpeg pipe:");
			break;
		case "mjpeg":
			disableBrowserCaching();
			$boundary = mpjpeg_getBoundary();
			header("Content-type: multipart/x-mixed-replace;boundary={$boundary}");
			passthru("{$ffmpeg_base_lq} -t {$duration_limit} -b:v {$mjpeg_bitrate} -s {$mjpeg_resolution} -r {$mjpeg_fps} -f mpjpeg pipe:");
			break;
		case "mp4":
			disableBrowserCaching();
			header('Content-type: video/mp4');
			passthru("{$ffmpeg_base} -t {$duration_limit} -c copy -an -movflags empty_moov+omit_tfhd_offset+frag_keyframe+default_base_moof -f mp4 pipe:");
			break;
		case "webm":
			disableBrowserCaching();
			header('Content-type: video/webm');
			passthru("{$ffmpeg_base_lq} -t {$duration_limit} -c:v vp8 -b:v {$webm_ogv_bitrate} -an -s {$webm_ogv_resolution} -r {$webm_ogv_fps} -f webm pipe:");
			break;
		case "ogv":
			disableBrowserCaching();
			header('Content-type: video/ogg');
			passthru("{$ffmpeg_base_lq} -t {$duration_limit} -c:v libtheora -b:v {$webm_ogv_bitrate} -an -s {$webm_ogv_resolution} -r {$webm_ogv_fps} -f ogg pipe:");			
			break;
	}
}
else {
	$key_encoded = urlencode($key);
	$rtsp_url = urlencode($_REQUEST["a"]);
	$rtsp_lq_url = isset($_REQUEST["c"]) ? urlencode($_REQUEST["c"]) : $rtsp_url;
	$time = time();
	echo <<<HTMLMARKER
<html>
	<head>
		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=0;" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<title>Видеонаблюдение</title>
		<script src="ifvisible.js"></script>
		<style>
			body { 
				background-color: black;
				padding: 0; margin: 0; 
			}
			
			#blackbox { 
				max-width: 100%; width: 100%; height: 100%; 
				color: white; background-color: black; 
				text-align: center; 
				font-size: 50px; line-height: 200px; 
				position: absolute; 
				z-index: 5; 
				top: 0px; left: 0px; right: 0px; bottom: 0px; 
				margin: auto;
			}
			
			#mjpegbox {
				max-width: 100%; height: 100%; 
				position: absolute; 
				z-index: 10; 
				top: 0px; left: 0px; right: 0px; bottom: 0px; 
				margin: auto;
			}
			
			#videobox {
				max-width: 100%; 
				position: absolute; 
				z-index: 1; 
				height: 100%; 
				top: 0px; left: 0px; right: 0px; bottom: 0px; 
				margin: auto;
			}
		</style>
	</head>
	<body>
		<div id="blackbox">Загрузка...</div>
		<img id="mjpegbox" src="?get=mjpeg&b={$key_encoded}&a={$rtsp_url}&c={$rtsp_lq_url}" />
		<video muted preload="auto" id="videobox" onerror="videobox_onerror()" oncanplay="videobox_oncanplay()">
			<source src="?get=mp4&b={$key_encoded}&a={$rtsp_url}&c={$rtsp_lq_url}&time={$time}" type="video/mp4">
			<source src="?get=webm&b={$key_encoded}&a={$rtsp_url}&c={$rtsp_lq_url}&time={$time}" type="video/webm">
			<source src="?get=ogv&b={$key_encoded}&a={$rtsp_url}&c={$rtsp_lq_url}&time={$time}" type="video/ogg">
			Ваш браузер не поддерживает HTML5 video!
		</video>
		<script type="text/javascript">
			if (!ifvisible.now()){
				window.location.href = "{$redirectToIfBackground}";
			}
			
			var vid = document.getElementById('videobox');
			var mjpegbox = document.getElementById('mjpegbox');
			var blackbox = document.getElementById('blackbox');
			var mjpegboxDummy = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEAAAAALAAAAAABAAEAAAI=;';
			var videoStarted = false;
			var videoCheckCount = 0;
			var checkVideoOK = null;
			
			function videobox_oncanplay() {
				if (videoStarted) return;
				var i = setTimeout(function() {
					vid.play(); 
					videoStarted = true;
					mjpegbox.src = mjpegboxDummy;
					mjpegbox.style.display = 'none';
					blackbox.style.display = 'none';
				}, 5000);
			}
			
			function videobox_onerror() {
				clearInterval(checkVideoOK);
				if (mjpegbox.src == mjpegboxDummy) mjpegbox.src = '?get=mjpeg&b={$key_encoded}&a={$rtsp_url}&c={$rtsp_lq_url}';
				mjpegbox.style.display = 'inline';
				blackbox.style.display = 'inline';
				alert('Не удалось воспроизвести это видео в максимальном качестве. Возможные причины: нестабильная сеть (в т.ч. со стороны камеры), неподдерживаемый браузер');
			}			
			
			checkVideoOK = setInterval(function() {
				if (videoStarted && (vid.paused || vid.ended)) {
					videoCheckCount++;
					if (!vid.ended) vid.play();
				}
				else {
					videoCheckCount = 0;
				}
				
				if (videoCheckCount > 4) {
					videobox_onerror();
					clearInterval(checkVideoOK);
				}
			}, 2000);
			
			setTimeout(function() {
				clearInterval(checkVideoOK);
			}, 35000);
		</script>
	</body>
</html>
HTMLMARKER;
}
?>