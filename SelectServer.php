<?php
// 定义需要绑定的地址和端口号
$localAddress = "127.0.0.1";
$bindPort = 8001;

// 创建socket作为server socket
$sckd = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));

if (false === $sckd) {
    die("Could not create server socket.\r\n");
}

// 设置阻塞
socket_set_nonblock($sckd);

// 绑定IP+端口
if (!socket_bind($sckd, $localAddress, $bindPort)) {
    die("Could not bind to {$localAddress}:{$bindPort}.\r\n");
}

// 监听端口
socket_listen($sckd);

echo "listen on {$localAddress}:{$bindPort}\r\n";

$acceptor = [$sckd];

$read = [];

$write = [];

$gc = [];

// 注册 shutdown 函数
register_shutdown_function(function () use ($sckd) {
    socket_close($sckd);
});

// 多路复用的方式
while (true) {
    $readable = array_merge($acceptor, $read);
    $writable = $write;
    $count = socket_select($readable, $writable, $except, null);
    if ($count <= 0) {
        continue;
    }
    accept($sckd, $readable, $read, $write);
    readAvaliable($readable, $read, $gc);
    writeAvaliable($writable, $write, $gc);
    gc($gc);
    var_dump($gc);
}

function accept($socket, $acceptable, &$read, &$write)
{
    if (in_array($socket, $acceptable)) {
        $rSock = socket_accept($socket);
        if (false === $rSock) {
            echo socket_strerror(socket_last_error($socket)) . "\r\n";
            socket_clear_error($socket);
        } else {
            $read[] = $rSock;
            $write[] = $rSock;
        }
    }
}

function readAvaliable($readable, &$read, &$gc)
{
    foreach ($read as $key => $socket) {
        if (!in_array($socket, $readable)) {
            continue;
        }
        while ($rb = socket_read($socket, 1024)) {
            echo $rb;
        }
        echo "\r\n";
        socket_shutdown($socket, 0);
        unset($read[$key]);
        release($socket, $gc);
    }
}

function writeAvaliable($writable, &$write, &$gc)
{
    foreach ($write as $key => $socket) {
        if (!in_array($socket, $writable)) {
            continue;
        }
        $wb = "server received.\r\n";
        socket_send($socket, $wb, strlen($wb), MSG_DONTROUTE);
        socket_shutdown($socket, 1);
        unset($write[$key]);
        release($socket, $gc);
    }
}

function release($socket, &$gc)
{
    if (!array_key_exists("{$socket}", $gc)) {
        $gc["{$socket}"] = [$socket, 1];
    } else {
        $gc["{$socket}"] = [$socket, 2];
    }
}

function gc(&$gc)
{
    foreach ($gc as $key => $package) {
        list($socket, $count) = $package;
        if ($count > 1) {
            if ($socket) {
                socket_close($socket);
            }
            unset($gc[$key]);
        }
    }
}
