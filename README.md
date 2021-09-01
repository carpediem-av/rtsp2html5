# rtsp2html5
A small and simple PHP-script to convert RTSP-stream from IP-cameras to HTML5-video (with switch to MJPEG on failure)
This project uses library "ifvisible.js", developed by Serkan Yerşen, MIT license.

Installation and usage:

1. Make new instance of Linux server (for example Debian) or use existing.
2. Install Apache, PHP 7.0+ and FFMpeg.
3. Get an SSL certificate for your server and install it (optional but highly recommended).
4. Сopy files of my script to root www directory of Apache.
5. Open "camera.php" in text editor and specify your security key (minimum 12 random chars; A-Z, a-z and 0-9 are allowed) in the "$key" variable. Specify in the "$redirectToIfBackground" variable where to redirect from the background tabs (i.e. url).
6. Place links to camera.php on the pages of your broadcasting site in the following format:

camera.php?a=<rtsp-link_in_base64>&b=<your_security_key>&c=<rtsp-link_to_the_second_stream_in_base64> 

In this case, the "c" parameter is optional, but very desirable.

Converting an RTSP link to base64 can be done using the service https://www.utilities-online.info/base64 and in the resulting string, replace each "=" character with string "%3D". RTSP links can be found in the camera documentation. You can also do this through the third-party utility named "Onvif Device Manager".


Установка и использование скрипта:

1. Берете сервер, например, с Debian, ставите Apache+PHP7 и FFMpeg;
2. Получаете SSL-сертификат для своего сервера (необязательно, но строго рекомендуется);
3. Копируете файлы моего скрипта в любую доступную по www папку;
4. Открываете camera.php и указываете свой ключ (придумываете; допустима латиница и цифры) в переменной $key, а в $redirectToIfBackground указываете, куда переадресовывать из фоновых вкладок;
5. Размещаете на страницах своего сайта трансляций ссылки на camera.php в таком формате: camera.php?a=<rtsp-ссылка в base64>&b=<ключ>&c=<rtsp-ссылка на второй поток в base64>. При этом параметр «c» необязательный, но очень желательный.

Риску предположить, что на шаге 5 у вас могут возникнуть затруднения. Но здесь нет ничего сложного, можно взять любой онлайн base64 конвертер, например http://base64.ru/, и сконвертировать вашу ссылку на RTSP-поток, обязательно заменив в полученной строке все знаки "=" на последовательности "%3D".

Если вы планируете сайт с камерами сделать на MODX Revolution, то используйте приложенный плагин, упрощающий работу по размещению ссылок. Инструкция по установке плагинов есть в документации к этой CMS. После установки плагина откройте его на редактирование и в начале файла подставьте свои значения в $key и $camera_server_url (иными словами — замените текст, выделенный заглавными буквами, своим ключом и адресом сервера).

После его установки, в тексте ваших страниц ссылки на камеры теперь можно указывать в таком виде:

{camera\*НАЗВАНИЕ\*RTSP-ССЫЛКА\*RTSP-ССЫЛКА НА ВТОРОЙ ПОТОК}

Название и RTSP-ссылки подставляете свои. Если нет ссылки на второй поток, то дублируете ссылку основного потока. Если есть затруднения с поиском RTSP-ссылок на вашу камеру, то можно использовать программу Onvif Device Manager. Она покажет ссылку снизу слева, по клику на «Живое видео».

По поводу безопасности. В принципе, если сервис будет непубличным, для чего и задумывался скрипт, то всё нормально. В противном случае, любой кто «подсмотрит» ссылку на camera.php, может вытащить исходную RTSP-ссылку, пароль на камеру (он прописывается в RTSP-ссылке), и сам секретный ключ $key. Пароль на камеру дает доступ к её админке, если вы пренебрегли созданием отдельной учетной записи на этой камере специально для RTSP. Секретный же ключ даст возможность через ваш сервер «крутить» сторонние камеры. Поэтому, данный скрипт только для частного доступа. Я мог бы реализовать шифрование параметров, но… при размещении в публичный доступ ввиду отсутствия кэширования видеоряда интернет-канал быстро «забьется», как и ресурсы на сервере.

Подробности в статье https://habr.com/ru/post/545888/

А вот здесь есть еще классные программы моего авторства: http://carpediem.0fees.us/
