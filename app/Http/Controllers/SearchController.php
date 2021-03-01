<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
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

        if (isset($_GET['sort_order'])){
            $sort_order = $_GET['sort_order'];
        } else {
            $sort_order = Session::get('sort_order');
        }
        switch ($sort_order) {

            case 'oldest':
                $sort_options = [
                    'date' => [
                        'order' => 'asc'
                    ]
                ];
                break;

            case 'relevant':
                $sort_options = [
                    '_score' => [
                        'order' => 'asc'
                    ]
                ];
                break;
            
            default:
                $sort_options = [
                    'date' => [
                        'order' => 'desc'
                    ]
                ];
                break;
        }
        Session::put('sort_order', $sort_order);

        // If a date range is specified
        if (isset($_GET['startdate']) && $_GET['startdate'] != ''){
            $startdate = $_GET['startdate'];
            $filter = [];
            $filter['range']['date']['gte'] = $startdate;
        }
        if (isset($_GET['enddate']) && $_GET['enddate'] != ''){
            $enddate = $_GET['enddate'];
            if (!isset($filter)){
                $filter = [];
            }
            $filter['range']['date']["lte"] = $enddate;
        }
        
        $index = Config::get('elastic.index');
        if (Session::get('itemsperpage')){
            $size = Session::get('itemsperpage');
        } else {
            Session::put('itemsperpage', "25");
            $size = "25";
        }
        if (isset($_GET['page'])){
            $page = $_GET['page'];
        } else {
            $page = 1;
        }
        if ($page == 1){
            $from = "0";
        } else {
            $from = ($size * $page) - $size;
        }

        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'match_phrase' => [
                                'content' => $searchstring
                            ]
                        ]
                    ]],
                'highlight' => [
                    'pre_tags' => "<span class='highlighted-text'>",
                    'post_tags'=> "</span>",
                    'fields' => [
                        'content' => new \stdClass()
                    ],
                    'fragment_size' => 100
                ],
                'sort' => $sort_options,
                'size' => $size,
                'from' => $from
            ]
        ];
        // Add date filter if necessary
        if (isset($filter)){
            $params['body']['query']['bool']['filter'] = $filter; 
        }
        $params_json = \json_encode($params['body'], JSON_PRETTY_PRINT);
        // die(print_r($params_json));
        Session::put('query', $params);
        Log::info($params_json);
        $results = $this->elasticsearch->search($params);
        Session::put('totalhits', $results['hits']['total']['value']);
        return view('results')->with('results', $results)->with('params', $params);
    }
    public function search_id(Request $request, $id){
        $meta = $this->retrieve_article_meta_by_id($id);
        return view('article_viewer')->with('result', $meta);
    }
    public function retrieve_article_meta_by_id($id){
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
        return $result;
    }
}
