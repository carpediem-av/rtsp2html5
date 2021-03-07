<?php
//Copyright (c) 2020-2021 Carpe Diem Software Developing by Alex Versetty.
//http://carpediem.0fees.us/

/////////////////////////////// Константы ////////////////////////////////////////////
$key = "КЛЮЧ_ИЗ_ФАЙЛА_CAMERA.PHP";
$camera_server_url = "https://СЕРВЕР_КУДА_РАСПАКОВАЛИ_СКРИПТ/camera.php?b={$key}";
//////////////////////////////////////////////////////////////////////////////////////

$output = &$modx->resource->_output;

$regex = '/\{camera\*([^*\{]+)\*([^*\}]+)\*([^*\}]+)\}/';
$matches = array();
preg_match_all($regex, $output, $matches);

if (count($matches) > 0) {
    for($i = 0; $i < count($matches[0]); $i++) {
	    $name = $matches[1][$i];
        $rtsp_encoded = urlencode(base64_encode($matches[2][$i]));
        $rtsp_lq_encoded = urlencode(base64_encode($matches[3][$i]));
	    $jpeg_url = "{$camera_server_url}&a={$rtsp_encoded}&c={$rtsp_lq_encoded}&get=jpeg";
	    $click_url = "{$camera_server_url}&a={$rtsp_encoded}&c={$rtsp_lq_encoded}";

	    $html = "<div style='padding:10px;display:inline-block;'><center><a href='{$click_url}'>";
        $html .= "<img width='260' height='148' border='1' src='{$jpeg_url}' " . 
		    "style='background: url(\"cam.png\") no-repeat; background-size:100% 100%;' />";
        $html .= "</a><br /><span style='font-size:70%;'>{$name}</span></center></div>";

	    $output = str_replace($matches[0][$i], $html, $output);
    }
}