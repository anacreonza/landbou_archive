<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Elasticsearch\ClientBuilder;
use Carbon\Carbon;
use Config;

class IndexController extends Controller
{
    protected $elasticsearch;
    
    protected $archive = "archives";

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
    public function index_item($item){

    }
    public function rebuild_index(){
        
        function get_files($input_dir){
            $file_extension = '/^.+\.' . "html" . '$/i';
            $Directory = new \RecursiveDirectoryIterator($input_dir);
            $Iterator = new \RecursiveIteratorIterator($Directory);
            $Regex = new \RegexIterator($Iterator, $file_extension, \RecursiveRegexIterator::GET_MATCH);
            $files = array();
            foreach($Regex as $filepath){
                array_push($files, $filepath[0]);
            }
            return $files;
        }

        $message = Carbon::now() . " Started index rebuild.\n";
        Storage::append('index.log', $message);

        if (!is_dir($this->archive)){
            $message = Carbon::now() . " Unable to read archive directory " . $this->archive . "\n";
            Storage::append('index.log', $message);
        }
        $files = get_files($this->archive);

        foreach ($files as $file) {
            $message = Carbon::now() . " Indexing file " . $file . "\n";
            Storage::append('index.log', $message);
        }

        return view('admin');
    }
}
