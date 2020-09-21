<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;
use Config;
use Session;
use DOMDocument;

class SearchController extends Controller
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
    public function search(){
        $searchstring = $_GET['searchstring'];
        Session::put('searchstring', $searchstring);
        $index = Config::get('elastic.index');
        if (Session::get('itemsperpage')){
            $size = Session::get('itemsperpage');
        } else {
            Session::put('itemsperpage', "25");
            $size = "25";
        }
        $from = "0";
        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    'match_phrase' => [
                        'content' => $searchstring
                    ]
                ],
                'highlight' => [
                    'pre_tags' => "<span class='highlighted-text'>",
                    'post_tags'=> "</span>",
                    'fields' => [
                        'content' => new \stdClass()
                    ],
                    'fragment_size' => 100
                ],
                'sort' => [
                    'date' => [
                        'order' => 'desc'
                    ]
                ],
                'size' => $size,
                'from' => $from
            ]
        ];
        $results = $this->elasticsearch->search($params);
        Session::put('totalhits', $results['hits']['total']['value']);
        return view('results')->with('results', $results);
    }
    public function search_id(){
        $id = $_GET['id'];
        $index = Config::get('elastic.index');
        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    'match' => [
                        '_id' => $id
                    ]
                ]
            ]
        ];
        $result = $this->elasticsearch->search($params);
        return view('article_viewer')->with('result', $result);
    }
}
