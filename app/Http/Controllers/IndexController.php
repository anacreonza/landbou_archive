<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Elasticsearch\ClientBuilder;
use Carbon\Carbon;
use Config;
use DateTime;
use DOMDocument;

class IndexController extends Controller
{
    protected $elasticsearch;
    
    protected $archive = "archives";

    
    public function __construct() {
        # Build URL for Elastic server from config
        $server_address = Config::get('elastic.server.ip');
        $server_port = Config::get('elastic.server.port');
        $this->index = Config::get('elastic.index');
        $hosts = [
            $server_address . ":" . $server_port
        ];
        $this->elasticsearch = ClientBuilder::create()->setHosts($hosts)->build();
    }
    public function delete(){
        $params = ['index' => $this->index];
        $index_exists = $this->elasticsearch->indices()->exists($params);
        if ($index_exists){
            $response = $this->elasticsearch->indices()->delete($params);
            return redirect('/')->with('message', 'Index deleted!');
        } else {
            return redirect('/')->with('message', 'No index to delete!');
        }
    }
    public function init_index(){
        $params = ['index' => $this->index];
        $index_exists = $this->elasticsearch->indices()->exists($params);
        if (!$index_exists){
            $response = $this->elasticsearch->indices()->create($params);
            return redirect('/')->with('message', 'New index created!');
        } else {
            return redirect('/')->with('message', 'Index already exists!');
        }
    }
    public function check_if_indexed($filename){
        $index = Config::get('elastic.index');
        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    "match_phrase" => [
                        "filename" => $filename
                    ]
                ]
            ]
        ];
        $response = $this->elasticsearch->search($params);
        $found = $response['hits']['total']['value'];
        return $found;
    }

    public function index_item($file){
        $filename = basename($file);
        $found = $this->check_if_indexed($filename);
        if ($found){
            $message = Carbon::now() . " Item " . $filename . " already indexed.";
            Storage::append('index.log', $message);
        } else {
            $message = Carbon::now() . " Indexing new item: " . $filename;
            Storage::append('index.log', $message);
            $item = $this->get_metas($file);
            $result = $this->post_entry($item, $this->elasticsearch);
            $message = "    " . $result['result'] . " new item. ID: " . $result["_id"];
            Storage::append('index.log', $message);
        }
        return;
    }
    function post_entry($entry){
        $params = [
            'index' => 'archive',
            // 'id' => 'my_id',
            'body' => $entry
        ];
        // var_dump($params);
        $response = $this->elasticsearch->index($params);
        return $response;
    }
    function get_total_indexed_items(){
        $params = [
            'index' => 'archive',
            'body' => [
                'query' => [
                    'match_all' => new \stdClass()
                ]
            ]
        ];
        $results = $this->elasticsearch->search($params);
        $total = $results['hits']['total']['value'];
        return $total;
    }
    public function get_dates_from_filename($file){
        $filename = basename($file);
        $date['day'] = substr($filename, 0, 2);
        $date['month'] = substr($filename, 2, 2);
        $date['year'] = "20" . substr($filename, 4, 2);
        return $date;
    }
    public function get_headlines($htmlfile){
        $html = file_get_contents($htmlfile);
        $domdoc = new DOMDocument;
        $domdoc->loadHTML($html);
        $headlines = [];
        $headings = $domdoc->getElementsByTagName('strong');
        for ($i=0; $i < $headings->length; $i++) { 
            $head = $headings->item($i)->nodeValue;
            array_push($headlines, $head);
        }
        $h1s = $domdoc->getElementsByTagName('h1');
        for ($i=0; $i < $h1s->length; $i++) { 
            $head = $h1s->item($i)->nodeValue;
            $head = str_replace("\r", '', $head);
            array_push($headlines, $head);
        }
        return $headlines;
    }
    public function get_credits($htmlfile){
        $html = file_get_contents($htmlfile);
        $domdoc = new DOMDocument;
        $domdoc->loadHTML($html);
        $headings = $domdoc->getElementsByTagName('strong');
        $credits = [];
        foreach ($headings as $heading) {
            $pattern = "/[A-Z][a-z]+\w/";
            $heading_string = $heading->nodeValue;
            // We know it's not a name if it's longer than 20 characters.
            if (strlen($heading_string) > 20){
                continue;
            }
            // We know it's not a name if it has ' - '.
            if (preg_match('/ - /', $heading_string)){
                continue;
            }
            // We know it's not a name if it has a & in it.
            if (preg_match('/ & /', $heading_string)){
                continue;
            }
            if (preg_match_all($pattern, $heading_string, $matches) >= 2){
                array_push($credits, $heading_string);
            }
        }
        return $credits;
    }
    function get_pagenumbers($htmlfile){
        $html = file_get_contents($htmlfile);
        $page_nos = [];
        $html_object = new DOMDocument;
        $html_object->loadHTML($html);
        $body = $html_object->getElementsByTagName('body')->item(0);
        $lines = explode("\n", $body->nodeValue);
        foreach ($lines as $line){
            if (strpos($line, "ladsy")){
                $line = str_replace("Bladsy", '', $line);
                $line = str_replace(": ", '', $line);
                $page_nums = explode(";", $line);
                foreach ($page_nums as $num){
                    $num = trim(str_replace("\r", '', $num));
                    array_push($page_nos, $num);
                }
            }
        }
        return $page_nos;
    }
    function get_articlehtml($htmlfile){
        $html = file_get_contents($htmlfile);
        $html_object = new DOMDocument;
        $html_object->loadHTML($html);
        $body_content = $html_object->getElementsByTagName('body')->item(0);
        $content = $html_object->saveHTML($body_content);
        return $content;
    }
    public function get_metas($htmlfile){
        $metas = [];
        $filename = basename($htmlfile);
        $filedate = $this->get_dates_from_filename($htmlfile);
        $datestring = intval($filedate['year']) . "-" . intval($filedate['month']) . "-" . intval($filedate['day']);
        $date = new DateTime($datestring);
        $date = $date->format('Y-m-d');
        $metas['date'] = $date;
        $html_content = file_get_contents($htmlfile);
        $metas['headlines'] = $this->get_headlines($htmlfile);
        $metas['credits'] = $this->get_credits($htmlfile);
        $metas['content'] = $this->get_articlehtml($htmlfile);
        $metas['pagenos'] = $this->get_pagenumbers($htmlfile);
        $metas['file'] = app('App\Http\Controllers\ArticleController')->build_newdir($htmlfile) . basename($htmlfile);
        $metas['filename'] = basename($htmlfile);
        return $metas;
    }
    public function ingest_files(){
        $inputdir = "lbarchive_hotfolder";
        $files = scandir($inputdir);
        $files_total = count($files);
        $files_indexed_count = 0;
        foreach ($files as $filename){
            if (preg_match('/^\./', $filename)){
                $files_total--;
                continue;
            }
        }
        if ($files_total > 0){
            $message = Carbon::now() . " Processing " . $files_total . " files in input directory.";
            Storage::append('index.log', $message);
        }
        foreach ($files as $filename){
            if (preg_match('/^\./', $filename)){
                continue;
            }
            $file = $inputdir . DIRECTORY_SEPARATOR . $filename;
            $result = $this->index_item($file);
            $storeresult = app('App\Http\Controllers\ArticleController')->store_article_file($file);
        }
        $message = $files_total . " new files indexed.";
        return redirect('/admin')->with('message', $message);
    }

    public function rebuild_index(){
        // This will have to be queued.
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
        $message = Carbon::now() . " Getting files to index...";
        Storage::append('index.log', $message);
        $files = get_files($this->archive);

        foreach ($files as $file) {
            $message = Carbon::now() . " Indexing file " . $file . "\n";
            Storage::append('index.log', $message);
        }

        return view('admin');
    }
}
