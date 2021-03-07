<?php
//Copyright (c) 2020-2021 Carpe Diem Software Developing by Alex Versetty.
//http://carpediem.0fees.us/

//Программа получает ссылку на камеру и выводит видео, при необходимости - делает перекодировку через ffmpeg.

// Формат запроса видео: camera.php?b=[ключ]&a=[ссылка rtsp в base64]&c=[ссылка второго потока rtsp в base64]
// Формат запроса картинки: camera.php?b=[ключ]&a=[ссылка rtsp в base64]&c=[ссылка второго потока rtsp в base64]&get=jpeg
// или картинка в полном размере: camera.php?b=[ключ]&a=[ссылка rtsp в base64]&c=[ссылка второго потока rtsp в base64]&get=jpeg-hq
// Параметр "с" везде необязательный.
// Примечание к ffmpeg - probesize 32  может дать нереальный траффик, если не указывать rate!

//////////////////////// КОНСТАНТЫ //////////////////////////////////
$key = "ПРИДУМАЙТЕ_ДЛИННЫЙ_КЛЮЧ_ИЗ_ЛАТИНИЦЫ_И_ЦИФР";
$duration_limit = 600; //ограничение на просмотр, секунд (потом картинка остановится)
$def_rate = 6;	//кадров в секунду, если нужно перекодирование
$jpeg_params = "-s 260x148 -q:v 5";  //параметры кодирования картинки
$mjpeg_params = "-b:v 512k -s 426x240 -r 2";  //параметры кодирования mjpeg
$redirectToIfBackground = 'http://САЙТИК'; //сайт для переадресации на него, если мы в фоновой вкладке (экономия трафика)
/////////////////////////////////////////////////////////////////////

function get_server_memory_usage(){

    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2]/$mem[1]*100;
    return $memory_usage;
}

// запрет доступа без указания ключа
if (!isset($_REQUEST["b"]) || $_REQUEST["b"] !== $key) { 
	echo "forbidden";
	die;
}

if (isset($_REQUEST["get"])) {
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header('Accept-Ranges:bytes');
	header('Connection:keep-alive');

	$rtsp = str_replace("'", '', str_replace("\"", '', base64_decode($_REQUEST["a"])));
	if (substr( $rtsp, 0, 7 ) !== "rtsp://") die();
	$ffmpeg_base = "ffmpeg -rtsp_transport tcp -probesize 32 -stimeout 5000000 -i \"{$rtsp}\" -loglevel quiet";
	if (isset($_REQUEST["c"])) {
		$rtsp_lq = str_replace("'", '', str_replace("\"", '', base64_decode($_REQUEST["c"])));
		if (substr( $rtsp_lq, 0, 7 ) !== "rtsp://") die();
		$ffmpeg_base_lq = "ffmpeg -rtsp_transport tcp -probesize 32 -stimeout 5000000 -i \"{$rtsp_lq}\" -loglevel quiet";
	}
	else {
		$ffmpeg_base_lq = $ffmpeg_base;
	}
		
	if ($_REQUEST["get"] == "jpeg") {
		header('Content-type: image/jpeg');
		if (get_server_memory_usage() > 80) {
			$errorjpeg = file_get_contents('error_thumb.png');
			echo $errorjpeg;
		}
		else {
			passthru("{$ffmpeg_base_lq} -vframes 1 {$jpeg_params} -f singlejpeg pipe:");
		}
	}
	
	if ($_REQUEST["get"] == "jpeg-hq") {
		header('Content-type: image/jpeg');
		if (get_server_memory_usage() > 80) {
			$errorjpeg = file_get_contents('error_thumb.png');
			echo $errorjpeg;
		}
		else {
			passthru("{$ffmpeg_base} -vframes 1 -f singlejpeg pipe:");
		}
	}
			
	if ($_REQUEST["get"] == "mjpeg") {
		header('Content-type: multipart/x-mixed-replace;boundary=ffserver');
		passthru("{$ffmpeg_base_lq} -t {$duration_limit} {$mjpeg_params} -f mpjpeg pipe:");
	}
				
	if ($_REQUEST["get"] == "mp4") {
		header('Content-type: video/mp4');
		$movflags = "-movflags +frag_keyframe+separate_moof+omit_tfhd_offset+empty_moov";
		passthru("{$ffmpeg_base} -t {$duration_limit} -c copy -an {$movflags} -f mp4 pipe:");
	}

	if ($_REQUEST["get"] == "webm") {
		header('Content-type: video/webm');
		passthru("{$ffmpeg_base_lq} -t {$duration_limit} -c:v vp8 -b:v 256k -an -s 426x240 -r {$def_rate} -f webm pipe:");
	}

	if ($_REQUEST["get"] == "ogv") {
		header('Content-type: video/ogg');
		passthru("{$ffmpeg_base_lq} -t {$duration_limit} -c:v libtheora -b:v 256k -an -s 426x240 -r {$def_rate} -f ogg pipe:");
	}
}
else {
	$rtsp_url = urlencode($_REQUEST["a"]);
	$rtsp_lq_url = urlencode($_REQUEST["c"]);
	$time = time();
	echo <<<HTMLMARKER
<html>
	<head>
		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=0;" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<title>Видеонаблюдение</title>
		<script src="ifvisible.js"></script>
	</head>
	<body style="background-color:black;padding:0;margin:0;">
		<div id="blackbox" style="max-width: 100%; color: white; text-align: center; line-height: 200px; font-size: 50px; background-color: black; position: absolute; z-index: 5; width: 100%; height: 100%; top: 0px; left: 0px; right: 0px; bottom: 0px; margin: auto;">Загрузка...</div>
		<div id="errorbox" style="max-width: 100%; display: none; text-shadow: 1px 1px 2px black, 0 0 3px black; color: red; text-align: center; font-size: 20px; background-color: none; position: absolute; z-index: 100; width: 100%; height: 100%; top: 0px; left: 0px; right: 0px; bottom: 0px; margin: auto;"><br />Ваш браузер не может воспроизвести это видео в максимальном качестве. Рекомендуются браузеры Google Chrome или Mozilla Firefox!</div>
		<img id="mjpegbox" src="?get=mjpeg&b={$key}&a={$rtsp_url}&c={$rtsp_lq_url}" style="max-width: 100%; position: absolute; z-index: 10; height: 100%; top: 0px; left: 0px; right: 0px; bottom: 0px; margin: auto;" />
		<video muted preload="auto" id="videobox" onerror="videobox_onerror()" oncanplay="videobox_oncanplay()" style="max-width: 100%; position: absolute; z-index: 1; height: 100%; top: 0px; left: 0px; right: 0px; bottom: 0px; margin: auto;">
			<source src="?get=mp4&b={$key}&a={$rtsp_url}&c={$rtsp_lq_url}&time={$time}" type="video/mp4">
			<source src="?get=webm&b={$key}&a={$rtsp_url}&c={$rtsp_lq_url}&time={$time}" type="video/webm">-->
			<source src="?get=ogv&b={$key}&a={$rtsp_url}&c={$rtsp_lq_url}&time={$time}" type="video/ogg">-->
			Browser is not support HTML5 video!
		</video>
		<script type="text/javascript">
			if(!ifvisible.now()){
				window.location.href = "{$redirectToIfBackground}";
			}
			
			var mjpegboxDummy = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEAAAAALAAAAAABAAEAAAI=;';
			var videoStarted = false;
			
			function videobox_oncanplay() {
				var i = setInterval(function() {
					var vid = document.getElementById('videobox');
					vid.play(); 
					videoStarted = true;
					if (!vid.paused) {
						var mjpegbox = document.getElementById('mjpegbox');
						var blackbox = document.getElementById('blackbox');
						mjpegbox.src = mjpegboxDummy;
						mjpegbox.style.display = 'none';
						blackbox.style.display = 'none';
					}
					clearInterval(i);
				}, 5000);
			}
			
			function videobox_onerror() {
				var mjpegbox = document.getElementById('mjpegbox');
				var blackbox = document.getElementById('blackbox');
				if (mjpegbox.src == mjpegboxDummy) {
					mjpegbox.src = '?get=mjpeg&b={$key}&a={$rtsp_url}&c={$rtsp_lq_url}';
				}
				mjpegbox.style.display = 'inline';
				blackbox.style.display = 'inline';
				errorbox.style.display = 'inline';
			}			
			
			var checkVideoOK_stage1 = setInterval(function() {
				var vid = document.getElementById('videobox');
				if (videoStarted && (vid.paused || vid.ended)) {
					videobox_onerror();
					clearInterval(checkVideoOK_stage1);
				}
			}, 2000);
				
			var checkVideoOK_stage2 = setInterval(function() {
					clearInterval(checkVideoOK_stage1);
					clearInterval(checkVideoOK_stage2);
					var vid = document.getElementById('videobox');
					if (vid.paused || vid.ended) {
						videobox_onerror();
					}
			}, 30000);
		</script>
	</body>
</html>
HTMLMARKER;
}
?>