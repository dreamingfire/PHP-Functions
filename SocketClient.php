<?php
// 创建socket
$sckd = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));

$remoteAddr = "127.0.0.1";

$remotePort = 8001;

// 连接remote address
socket_connect($sckd, $remoteAddr, $remotePort);

sleep(10);

// read & write
$wb = "a client named Lidafei connected\r\n";
socket_send($sckd, $wb, strlen($wb), MSG_DONTROUTE);
socket_shutdown($sckd, 1);

while ($rb = socket_read($sckd, 1024)) {
    echo $rb;
}
socket_shutdown($sckd, 0);
// 关闭socket
socket_close($sckd);
