<?php
//Copyright (c) 2021-2023 Carpe Diem Software Developing by Alex Versetty.
//http://carpediem.0fees.us/

//version 1.2

//Описание:
//Ищет на страницах строки вида {camera*<название>*<rtsp-ссылка>*<rtsp-ссылка-вторичный-поток>} 
//и преобразует в превью+ссылка на камеру

//Description:
//Searches the pages for strings like {camera*<name>*<rtsp-url>*<rtsp-url-secondary-stream>}
//and converts to preview+url to camera

/////////////////////////////// Конфигурация / Configuration ////////////////////////////////////////////////////////////////////////////////////////////////
$key = "";											//указать тот же ключ, что прописан в camera.php
													//specify the same key that is specified in camera.php
$server = "https://example.com/rtsp2html5";			// "https://example.com/rtsp2html5" заменить на URL-ссылку на веб-каталог, в который вы установили rtsp2html5
													// "https://example.com/rtsp2html5" replace with a URL to the web directory where you installed rtsp2html5
$img_width = 360;									//ширина снимка/видео
$img_height = 202;									//высота снимка/видео
$title_font_size = '70%';							//размер шрифта для названий
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$key = url_encode($key);
$camera_server_url = "{$server}/camera.php?b={$key}";
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

	    $html = "<div style='padding:5px;display:inline-block;'><center><a href='{$click_url}'>";
        $html .= "<img width='{$img_width}' height='{$img_height}' border='1' src='{$jpeg_url}' " . 
		    "style='background: url(\"rtsp2html5/modx_plugin/cam-loading.png\") no-repeat; background-size:100% 100%;' onerror=\"this.src='rtsp2html5/modx_plugin/cam-error.png'\" />";
        $html .= "</a><br /><span style='vertical-align:top;font-size:{$title_font_size};'>{$name}</span></center></div>";

	    $output = str_replace($matches[0][$i], $html, $output);
    }
}
