<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Elasticsearch\ClientBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
            Log::info("Index deleted!");
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
            Log::info("New index created!");
            return redirect('/')->with('message', 'New index created!');
        } else {
            Log::info("Unable to create index: index already exists!");
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
    function convert_to_html($file){
        $filename = basename($file);
        $htmlfile = TEMPDIR . DIRECTORY_SEPARATOR . $filename . ".html";
        // Use Pandoc to convert the .docx file to .html
        $command_string = PANDOC . " --quiet -s -o \"" . $htmlfile . "\" \"" . $file . "\"";
        // echo("Executing: " . $command_string . "\n");
        exec($command_string);
        // Convert extended characters to HTML entities
        $handle = fopen($htmlfile, 'r');
        $html = fread($handle, filesize($file));
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        fclose($handle);
        $handle = fopen($htmlfile, 'w');
        fwrite($handle, $html);
        fclose($handle);
        if (file_exists($htmlfile)){
            return $htmlfile;
        } else {
            return false;
        }
    }
    public function index_file($file){
        $filename = basename($file);
        $found = $this->check_if_indexed($filename);
        if ($found){
            Log::info("Item " . $filename . " already indexed.");
            return False;
        }
        $path_parts = pathinfo($filename);
        if ($path_parts['extension'] == "docx"){
            Log::info("Converting MS Word file to HTML: $filename");
            $htmlfile = $this->convert_to_html($file);
        } elseif ($path_parts['extension'] == "html") {
            $htmlfile = $file;
        } else {
            Log::info("Unknown file type: $filename.");
            return False;
        }
        $filehandle = fopen($htmlfile, "r") or die("Unable to read $htmlfile");
        $html = fread($filehandle,filesize($htmlfile));
        fclose($filehandle);
        $domdoc = New DOMDocument;
        $domdoc->loadHTML($html);
        $entry = $this->get_metas($domdoc, $filename);
        $result = $this->post_entry($entry);
        Log::info($result['result'] . " new item. ID: " . $result["_id"] . " Filename: " . $filename);
        return $result;
    }
    
    function post_entry($entry){
        $params = [
            'index' => 'archive',
            // 'id' => 'my_id',
            'body' => $entry
        ];
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
    public function get_headlines($domdoc){
        $headlines = [];
        $classed_headings = $domdoc->getElementById('storytitle');
        if ($classed_headings){
            foreach ($classed_headings as $heading) {
                $headlines[] = $heading->nodeValue;
            }
        } else {
            $headings = $domdoc->getElementsByTagName('strong');
            for ($i=0; $i < $headings->length; $i++) { 
                $head = $headings->item($i)->nodeValue;
                array_push($headlines, $head);
            }
        }
        $h1s = $domdoc->getElementsByTagName('h1');
        for ($i=0; $i < $h1s->length; $i++) { 
            $head = $h1s->item($i)->nodeValue;
            $head = str_replace("\r", '', $head);
            array_push($headlines, $head);
        }
        return $headlines;
    }
    public function get_credits($domdoc){
        $credits = [];
        $classed_credits = $domdoc->getElementById('credit');
        if ($classed_credits){
            foreach ($classed_credits as $credit){
                $credits[] = $credit->nodeValue;
            }
        } else {
            $strong_credits = $domdoc->getElementsByTagName('strong');
            foreach ($strong_credits as $credit) {
                $pattern = "/[A-Z][a-z]+\w/";
                $credit_string = $credit->nodeValue;
                // We know it's not a name if it's longer than 25 characters.
                if (strlen($credit_string) > 25){
                    continue;
                }
                // We know it's not a name if it has ' - '.
                if (preg_match('/ - /', $credit_string)){
                    continue;
                }
                // We know it's not a name if it has a & in it.
                if (preg_match('/ & /', $credit_string)){
                    continue;
                }
                if (preg_match_all($pattern, $credit_string, $matches) >= 2){
                    $credits[] = $credit_string;
                }
            }
        }
        return $credits;
    }
    function get_pagenumbers($domdoc){
        $page_nos = [];
        $pagerefs = $domdoc->getElementById('pageref');
        if ($pagerefs){
            foreach ($pagerefs as $pageref){
                $page_nos[] = $pageref->nodeValue;
            }
        } else {
            $body = $domdoc->getElementsByTagName('body')->item(0);
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
        }
        return $page_nos;
    }
    function get_articlebody($domdoc){
        $body_content = $domdoc->getElementsByTagName('body')->item(0);
        $body = $domdoc->saveHTML($body_content);
        return $body;
    }
    function get_filename($domdoc){
        $filename = $domdoc->getElementById('filename');
        return $filename->nodeValue;
    }
    public function get_metas($domdoc, $filename){
        $metas = [];
        $metas['headlines'] = $this->get_headlines($domdoc);
        $metas['credits'] = $this->get_credits($domdoc);
        $metas['content'] = $this->get_articlebody($domdoc);
        $metas['pagenos'] = $this->get_pagenumbers($domdoc);
        $metas['filename'] = $filename;
        $filedate = [];
        $filedate['day'] = substr($metas['filename'], 0, 2);
        $filedate['month'] = substr($metas['filename'], 2, 2);
        $filedate['year'] = "20" . substr($metas['filename'], 4, 2);
        $metas['file'] = 'Archives' . DIRECTORY_SEPARATOR . env('ARCHIVE_PUBNAME') . DIRECTORY_SEPARATOR . $filedate['year'] . DIRECTORY_SEPARATOR . $filedate['month'] . DIRECTORY_SEPARATOR . $filedate['day'] . DIRECTORY_SEPARATOR . $metas['filename'];
        $datestring = intval($filedate['year']) . "-" . intval($filedate['month']) . "-" . intval($filedate['day']);
        $date = new DateTime($datestring);
        $metas['date'] = $date->format('Y-m-d');
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
            $message = "Processing " . $files_total . " files in input directory.";
            Log::info($message);
        }
        foreach ($files as $filename){
            if (preg_match('/^\./', $filename)){
                continue;
            }
            $file = $inputdir . DIRECTORY_SEPARATOR . $filename;
            $result = $this->index_file($file);
            $message = "Indexed file: " . $filename;
            Log::info($message);
            $storeresult = app('App\Http\Controllers\ArticleController')->store_article_file($file);
        }
        if ($files_total > 0){
            $message = $files_total . " new files indexed.";
        } else {
            $message = "No new files found to be indexed.";
        }
        Log::info($message);
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

        $message = "Started index rebuild.\n";
        Log::info($message);

        if (!is_dir($this->archive)){
            $message = "Unable to read archive directory " . $this->archive . "\n";
            Log::info($message);
        }
        $message = "Getting files to index...";
        Log::info($message);
        $files = get_files($this->archive);

        foreach ($files as $file) {
            $message = "Indexing file " . $file . "\n";
            Log::info($message);
        }

        return view('admin');
    }
}
