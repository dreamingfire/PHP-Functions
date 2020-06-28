<?php
class SocketService
{
    private $address;
    private $port;
    private $_sockets;
    private $_products;

    public function __construct($address = '', $port='')
    {
            if(!empty($address)){
                $this->address = $address;
            }
            if(!empty($port)) {
                $this->port = $port;
            }
            $this->_products = ["product 1", "product 2", "product 3", "product 4", "product 5"];
    }

    public function service(){
        //获取tcp协议号码
        $tcp = getprotobyname("tcp");
        $sock = socket_create(AF_INET, SOCK_STREAM, $tcp);
        socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
        if($sock < 0)
        {
            throw new Exception("failed to create socket: ".socket_strerror($sock)."\n");
        }
        socket_bind($sock, $this->address, $this->port);
        socket_listen($sock, $this->port);
        echo "listen on $this->address $this->port ... \n";
        $this->_sockets = $sock;
    }

    public function run(){
        $this->service();
        $clientsRead = [];
        $clientsWrite = [];
        $clientProducts = [];
        while (true){
            $changesRead = array_merge([
                "{$this->address}:{$this->port}" => $this->_sockets,
            ], $clientsRead);
            $changesWrite = $clientsWrite;
            $except = $changesRead;
            socket_select($changesRead, $changesWrite, $except, NULL);
            // 接收 和 读
            foreach ($changesRead as $key => $_sock){
                if($this->_sockets == $_sock){ //判断是不是新接入的socket
                    if(false === ($newClient = socket_accept($_sock))) {
                        die('failed to accept socket: ' . socket_strerror(socket_last_error($_sock)) . "\n");
                    }
                    $line = trim(socket_read($newClient, 1024));
                    $this->handshaking($newClient, $line);
                    //获取client ip
                    socket_getpeername($newClient, $ip, $port);
                    $ipAddr = "{$ip}:{$port}";

                    $clientsRead[$ipAddr] = $newClient;
                    $clientProducts[$ipAddr] = $this->_products;
                    echo  "Client ip:{$ip}:{$port}\n";
                    echo "Client msg:{$line}\n";
                }
                if (in_array($_sock, $clientsRead)) {
                    if(!socket_last_error($_sock)) {
                        socket_recv($_sock, $buffer,  2048, 0);
                        $msg = $this->message($buffer);
                        $clientsWrite[$key] = $_sock;
                        echo "{$key} clinet msg:" . $msg . "\n";
                    } else {
                        socket_close($_sock);
                        unset($clientsRead[$key]);
                        unset($clientsWrite[$key]);
                        unset($changesWrite[$key]);
                    }
                }
            }
            // 写
            foreach ($changesWrite as $key => $_sock) {
                if(!socket_last_error($_sock) && count($clientProducts[$key]) > 0) {
                    // 使用输入的方式会导致请求被阻塞
                    //fwrite(STDOUT, "Send to Client ip:{$key}. Please input a argument:");
                    //$response = trim(fgets(STDIN));
                    shuffle($clientProducts[$key]);
                    $response = array_pop($clientProducts[$key]);
                    $this->send($_sock, $response);
                    echo "message to Client:" . $response . "\n";
                } else {
                    socket_close($_sock);
                    unset($clientsRead[$key]);
                    unset($clientsWrite[$key]);
                }
            }
        }
    }

    /**
     * 握手处理
     * @param $newClient socket
     * @return int  接收到的信息
     */
    public function handshaking($newClient, $line){

        $headers = array();
        $lines = preg_split("/\r\n/", $line);
        foreach($lines as $line)
        {
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
            {
                $headers[$matches[1]] = $matches[2];
            }
        }
        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $this->address\r\n" .
            "WebSocket-Location: ws://$this->address:$this->port/websocket/websocket\r\n".
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        return socket_write($newClient, $upgrade, strlen($upgrade));
    }

    /**
     * 解析接收数据
     * @param $buffer
     * @return null|string
     */
    public function message($buffer){
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126)  {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127)  {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else  {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 发送数据
     * @param $newClinet 新接入的socket
     * @param $msg   要发送的数据
     * @return int|string
     */
    public function send($newClinet, $msg){
        $msg = $this->frame($msg);
        socket_write($newClinet, $msg, strlen($msg));
    }

    public function frame($s) {
        $a = str_split($s, 125);
        if (count($a) == 1) {
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }
        $ns = "";
        foreach ($a as $o) {
            $ns .= "\x81" . chr(strlen($o)) . $o;
        }
        return $ns;
    }

    /**
     * 关闭socket
     */
    public function close(){
        return socket_close($this->_sockets);
    }
}

$sock = new SocketService('127.0.0.1','9000');
$sock->run();


// JS客户端建立监听websocket消息的方式
// var ws = new WebSocket("ws://localhost:9000");

// ws.onopen = function(evt) { 
//   console.log("Connection open ..."); 
//   ws.send("Hello WebSockets!");
// };

// ws.onmessage = function(evt) {
//   console.log( "Received Message: " + evt.data);
//   ws.close();
// };

// ws.onclose = function(evt) {
//   console.log("Connection closed.");
// };
