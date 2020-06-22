<?php
$content = file_get_contents("php://input");
if (!strlen($content)) {
    die("My friend, give me some information to write down, please.");
}
ob_end_clean();
// once sent, connection closed by client
header("Connection: close\r\n");
header("Content-Encoding: none\r\n");
// key word
ignore_user_abort(true);
// start new buffer
ob_start();
echo "The log is writing, please wait a minute\n";
$size = ob_get_length();
header("Content-Length: $size");
ob_end_flush();
flush();
//fastcgi_finish_request(); /*important when called in php-fpm*/ 
ob_end_clean();
sleep(5);
$logFileName = "Behave.log." . date("Y.m.d");
file_put_contents(LOG_PATH . $logFileName, "[" . date("Y-m-d H:i:s") . "] " . $content . "\r\n", FILE_APPEND | LOCK_EX);
die("finish log writing");
