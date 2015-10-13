<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

    // Make sure composer dependencies have been installed
    require __DIR__ . '/vendor/autoload.php';

/**
 * chat.php
 * Send any incoming messages to all connected clients (except sender)
 */
class MyChat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        $obj = json_decode($msg, true);
        var_dump($msg);
        var_dump($obj);
        if ($obj["command"] == "user" ) {
            $this->clients[$from] = $obj["username"];
            var_dump($this->clients[$from]);
        } else if ($obj["command"] == "message"){
            foreach ($this->clients as $client) {
                if ($this->clients[$client] == $obj["to"]) {
                    $client->send(json_encode(array(
                        "from" => $this->clients[$from],
                        "content" => $obj["content"],
                    )));
                }
            }
        } else if ($obj["command"] == "message_all") {
            foreach ($this->clients as $client) {
                if ($from != $client) {
                    $client->send(json_encode(array(
                        "from" => $this->clients[$from],
                        "content" => $obj["content"]
                    )));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "用户 " . $this->clients[$conn] . " 断开连接了";
        $conn->close();
    }
}

// Run the server application through the WebSocket protocol on port 8080
$app = new Ratchet\App('211.87.226.143', 5000, '0.0.0.0');
$app->route('/', new MyChat);
$app->run();
