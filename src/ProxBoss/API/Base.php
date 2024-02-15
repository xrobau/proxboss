<?php

namespace ProxBoss\API;

use GuzzleHttp\Client;

class Base
{
    public static string $connection = "null";

    public function getClient($path = ""): Client
    {
        $tok = json_decode(file_get_contents(__DIR__ . "/../../../token.json"), true)[self::$connection];
        $auth = "PVEAPIToken=" . $tok['id'] . "=" . $tok['secret'];
        $headers = ["Authorization" => $auth];
        return new Client(["base_uri" => $tok['base_uri']."/$path", "verify" => $tok['verify'], "headers" => $headers]);
    }
}
