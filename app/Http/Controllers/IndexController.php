<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;
use Config;

class IndexController extends Controller
{
    protected $elasticsearch;
    
    public function __construct() {
        # Build URL for Elastic server from config
        $server_address = Config::get('elastic.server.ip');
        $server_port = Config::get('elastic.server.port');
        $hosts = [
            $server_address . ":" . $server_port
        ];
        $this->elasticsearch = ClientBuilder::create()->setHosts($hosts)->build();
    }
    public function delete(){
        $params = ['index' => 'archive'];
        $response = $this->elasticsearch->indices()->delete($params);
        var_dump($response);
    }
}
