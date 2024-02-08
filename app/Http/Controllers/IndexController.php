<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Elasticsearch\ClientBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Auth;
use Config;
use DateTime;
use DOMDocument;

define("TEMP_DIR", "temp");
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Host OS is Windows
    define('PANDOC', 'pandoc.exe');
} else {
    // Host OS is not Windows
    define('PANDOC', "/usr/local/bin/pandoc");
}

class IndexController extends Controller
{
    protected $elasticsearch;
    
    protected $archive = "archives";
    
    public function __construct() {
        // Check that user is authenticated
        $this->middleware('auth');
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
        if (Auth::user()->role != "admin"){
            return redirect("/");
        }
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
    function convert_word_to_html($file){
        $filename = basename($file);
        $htmlfile = TEMP_DIR . DIRECTORY_SEPARATOR . $filename . ".html";
        // Use Pandoc to convert the .docx file to .html
        $containerName = "lbarchive-pandoc";
        $pandocCommand = "pandoc --quiet -s -o \"" . $htmlfile . "\" \"" . $file . "\"";
        $dockerCommand = "docker exec $containerName $pandocCommand";
        // $command_string = PANDOC . " --quiet -s -o \"" . $htmlfile . "\" \"" . $file . "\"";
        // echo("Executing: " . $command_string . "\n");
        exec($dockerCommand, $output, $returnValue);
        die(var_dump($output));
        if ($returnValue === 0){
            foreach ($output as $line)
            echo $line .  PHP_EOL;
        }
        if (!file_exists($htmlfile)){
            die("Failed to create html output file.");
        }
        $handle = fopen($htmlfile, 'r');
        $html = fread($handle, filesize($file));
        fclose($handle);
        // Convert extended characters to HTML entities
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        fclose($handle);
        return $html;
    }
    function convert_xml_to_html($file){
        // $htmlheader = '<!DOCTYPE html>
        // <html lang="af">
        // <head>
        //     <meta charset="UTF-8">
        //     <meta http-equiv="X-UA-Compatible" content="IE=edge">
        //     <meta name="viewport" content="width=device-width, initial-scale=1.0">
        //     <title>Document</title>
        //     <style>
        //         body {font-family: Arial, Helvetica, sans-serif; padding: 10pt}
        //         p {padding-bottom: 5pt}
        //         h3 {font-size: 14pt}
        //         h3.byline {font-size: 14pt; font-style: italic}
        //         h4 {font-size: 11pt; font-weight: bold}
        //         h5 {font-size: 10pt; font-weight: bold}
        //         .Byline {font-style: italic}
        //         .Image {display: none}
        //     </style>
        // </head>
        // <body>';
        $htmlheader = '<head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
        <title>' . htmlentities(basename($file)) . '</title>
        <style>
          html {
            line-height: 1.5;
            font-family: Georgia, serif;
            font-size: 20px;
            color: #1a1a1a;
            background-color: #fdfdfd;
          }
          body {
            margin: 0 auto;
            max-width: 36em;
            padding-left: 50px;
            padding-right: 50px;
            padding-top: 50px;
            padding-bottom: 50px;
            hyphens: auto;
            word-wrap: break-word;
            text-rendering: optimizeLegibility;
            font-kerning: normal;
          }
          @media (max-width: 600px) {
            body {
              font-size: 0.9em;
              padding: 1em;
            }
          }
          @media print {
            body {
              background-color: transparent;
              color: black;
              font-size: 12pt;
            }
            p, h2, h3 {
              orphans: 3;
              widows: 3;
            }
            h2, h3, h4 {
              page-break-after: avoid;
            }
          }
          p {
            margin: 1em 0;
          }
          a {
            color: #1a1a1a;
          }
          a:visited {
            color: #1a1a1a;
          }
          img {
            max-width: 100%;
          }
          h1, h2, h3, h4, h5, h6 {
            margin-top: 1.4em;
          }
          h5, h6 {
            font-size: 1em;
            font-style: italic;
          }
          h6 {
            font-weight: normal;
          }
          ol, ul {
            padding-left: 1.7em;
            margin-top: 1em;
          }
          li > ol, li > ul {
            margin-top: 0;
          }
          blockquote {
            margin: 1em 0 1em 1.7em;
            padding-left: 1em;
            border-left: 2px solid #e6e6e6;
            color: #606060;
          }
          code {
            font-family: Menlo, Monaco, \'Lucida Console\', Consolas, monospace;
            font-size: 85%;
            margin: 0;
          }
          pre {
            margin: 1em 0;
            overflow: auto;
          }
          pre code {
            padding: 0;
            overflow: visible;
          }
          .sourceCode {
           background-color: transparent;
           overflow: visible;
          }
          hr {
            background-color: #1a1a1a;
            border: none;
            height: 1px;
            margin: 1em 0;
          }
          table {
            margin: 1em 0;
            border-collapse: collapse;
            width: 100%;
            overflow-x: auto;
            display: block;
            font-variant-numeric: lining-nums tabular-nums;
          }
          table caption {
            margin-bottom: 0.75em;
          }
          tbody {
            margin-top: 0.5em;
            border-top: 1px solid #1a1a1a;
            border-bottom: 1px solid #1a1a1a;
          }
          th {
            border-top: 1px solid #1a1a1a;
            padding: 0.25em 0.5em 0.25em 0.5em;
          }
          td {
            padding: 0.125em 0.5em 0.25em 0.5em;
          }
          header {
            margin-bottom: 4em;
            text-align: center;
          }
          #TOC li {
            list-style: none;
          }
          #TOC a:not(:hover) {
            text-decoration: none;
          }
          code{white-space: pre-wrap;}
          span.smallcaps{font-variant: small-caps;}
          span.underline{text-decoration: underline;}
          div.column{display: inline-block; vertical-align: top; width: 50%;}
          div.hanging-indent{margin-left: 1.5em; text-indent: -1.5em;}
          ul.task-list{list-style: none;}
          .display.math{display: block; text-align: center; margin: 0.5rem auto;}
        </style>
        </head>';

        $xmlDoc = new DOMDocument();
        $xmlDoc->load($file);
        // Process the XML to pull out certain tags and attributes.
        $ImageTags = $xmlDoc->getElementsByTagName('Image');
        $captions = [];
        foreach($ImageTags as $tag){
            if ($tag->hasAttribute("Caption")){
                $captions[] = $tag->getAttribute("Caption");
            }
        }
        dd($captions);

        $raw_html = $xmlDoc->SaveHTML();
        // $raw_html = str_replace("<head></head>", $htmlheader, $raw_html);
        $tags = [
            ["Root", "div"],
            ["Article", "div"],
            ["ArticleHeader", "div"],
            ["Heading", "h1"],
            ["Blurb", "h2"],
            ["Byline", "h3"],
            ["IssueDate", "p"],
            ["ArticleBody", "div"],
            ["Story", "div"],
            ["HeadingLarge", "h2"],
            ["HeadingMedium", "h3"],
            ["InfoBlock", "p"],
            ["QuoteBlock", "p"],
            ["HeadingSmall", "h4"]
        ];
        foreach ($tags as $tag){
            $raw_html = str_replace("<" . $tag[0] . ">", "<" . $tag[1] . " class=\"" . $tag[0] . "\">", $raw_html);
            $raw_html = str_replace("</" . $tag[0] . ">", "</" . $tag[1] . ">", $raw_html);
        }
        // Deal with Image tags
        $raw_html = str_replace("<Image href=\"", "<img class=\"Image\" src=\"", $raw_html);
        $raw_html = str_replace("</Image>", "", $raw_html);
        $raw_html = str_replace("Caption=\"", "alt=\"", $raw_html);

        // Deal with InfoBlock tags with an href reference with a number in it
        $pattern = "/<InfoBlock href=\"file:\/+[0-9]+\">/";
        $raw_html = preg_replace($pattern, "<p class=\"InfoBlock\">", $raw_html);
        // Deal with InfoBlock tags with image links in them
        $raw_html = str_replace("<InfoBlock href=\"", "<p class=\"InfoBlock\" title=\"", $raw_html);

        $raw_html = "<html>\n" . $htmlheader . "<body>\n" . $raw_html . "</body>\n</html>";
        $htmlDoc = new DOMDocument();
        return $raw_html;
    }
    function create_html($file){
        $filename = basename($file);
        $path_parts = pathinfo($filename);
        switch ($path_parts['extension']) {
            case 'docx':
                $html = $this->convert_word_to_html($file);
                break;
            
            case 'html':
                $handle = fopen($file, "r");
                $html = fread($handle,filesize($file));
                fclose($handle);
                break;

            case 'xml';
                $html = $this->convert_xml_to_html($file);
                break;

            default:
                Log::info("Unknown file type: $filename.");
                return False;
                break;
            }
        return $html;
    }
    public function index_html_file($file){
        $filename = basename($file);

        $html = Storage::disk('local')->get($file);
        $domdoc = New DOMDocument;
        try{
            $domdoc->loadHTML($html);
        } catch(Exception $err) {
            Log::error("Unable to load html from: " . $filename);
            return False;
        }
        $entry = $this->get_metas($domdoc, $filename);
        $result = $this->post_entry($entry);
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
    function get_headlines($domdoc){
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
    function get_credits($domdoc){
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
    function get_metas($domdoc, $filename){
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
    function generate_storage_path($filename, $parent_folder){
        $filename = basename($filename);
        $filedate['day'] = substr($filename, 0, 2);
        $filedate['month'] = substr($filename, 2, 2);
        $filedate['year'] = "20" . substr($filename, 4, 2);
        $newdir = $parent_folder . DIRECTORY_SEPARATOR . env('ARCHIVE_PUBNAME') . DIRECTORY_SEPARATOR . $filedate['year'] . DIRECTORY_SEPARATOR . $filedate['month'] . DIRECTORY_SEPARATOR . $filedate['day'] . DIRECTORY_SEPARATOR;
        return $newdir;
    }
    function store_html($html, $filename){
        $parent_folder = "Archives";
        $newdir = $this->generate_storage_path($filename, $parent_folder);
        $destfile = $newdir . basename($filename) . ".html";
        Storage::disk("local")->put($destfile, $html);
        if (Storage::disk('local')->exists($destfile)) {
            return $destfile;
        } else {
            return False;
        }
    }
    function store_backup($filename){
        $parent_folder = "Backup";
        $newdir = $this->generate_storage_path($filename, $parent_folder);
        $destfile = $newdir . basename($filename);
        $handle = fopen($filename, "r");
        $content = fread($handle,filesize($filename));
        fclose($handle);
        Storage::disk("local")->put($destfile, $content);
        if (Storage::disk('local')->exists($destfile)){
            return $destfile;
        } else {
            return False;
        }
    }
    function ingest_files(){
        $input_dir = "lbarchive_hotfolder";
        $working_dir = "temp";
        $backup_dir = "backup";
        $error_dir = "import_errors";
        $files = scandir($input_dir);
        $files_total = count($files);
        $files_indexed_count = 0;
        $files_reindexed_count = 0;
        $files_not_indexed_count = 0;
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
            // Test for the . and .. files
            if (preg_match('/^\./', $filename)){
                continue;
            }
            // Define working folders.
            $source_file = $input_dir . DIRECTORY_SEPARATOR . $filename;
            $working_file = $working_dir . DIRECTORY_SEPARATOR . $filename;
            $backup_file = $this->generate_storage_path($source_file, $backup_dir) . $filename;
            $error_file = "import_errors" . DIRECTORY_SEPARATOR . $filename;
            // Test for badly named files
            if (!preg_match('/[0-9]{6} /', basename($filename))){
                Log::info("File " . basename($filename) . " does not conform to the standard naming scheme. Moving file to errors.");
                rename($source_file, $error_file);
                continue;
            }
            // First move the source file into the working folder.
            rename($source_file, $working_file);
            // Now convert the contents of that file into an html string
            $html = $this->create_html($working_file);
            // Now store that file in the correct storage place
            if ($html){
                $html_file = $this->store_html($html, $working_file);
            } else {
                Log::info("Unable to store html file");
                continue;
            }
            // Now actually index it
            if ($html_file){
                $already_indexed = $this->check_if_indexed(basename($html_file));
                if ($already_indexed){
                    Log::info("Item " . $html_file . " already indexed. Reindexing...");
                    $files_reindexed_count++;
                }
                $indexed = $this->index_html_file($html_file);
            } else {
                Log::info("Unable to read html input file: " . $working_file);
                continue;
            }
            if ($indexed){
                $files_indexed_count++;
                $stored_file = $this->store_backup($working_file);
                Log::info($indexed['result'] . " new item. ID: " . $indexed["_id"] . " Stored as: " . $stored_file);
                if ($stored_file){
                    unlink($working_file);
                }
            } else {
                Log::error("Unable to index file: " . $html_file);
                $files_not_indexed_count++;
            }
        }
        if ($files_total > 0){
            $message = '';
            if ($files_not_indexed_count > 0){
                $message .= $files_not_indexed_count . "new files not indexed!";
            }
            if ($files_reindexed_count > 0){
                $message .= $files_reindexed_count . " files re-indexed.";
            }
            $message .= "Total files indexed: " . $files_indexed_count;
        } else {
            $message = "No new files found to be indexed.";
        }
        if ($files_indexed_count > 0){
            Log::info($message);
        }
        return redirect('/admin')->with('message', $message);
    }
    
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
    function rebuild_index(){
        // This will have to be queued.
        $message = "Started index rebuild.\n";
        Log::info($message);

        if (!is_dir($this->archive)){
            $message = "Unable to read archive directory " . $this->archive . "\n";
            Log::info($message);
        }
        $message = "Getting files to index...";
        Log::info($message);
        $files = $this->get_files($this->archive);

        foreach ($files as $file) {
            $message = "Indexing file " . $file . "\n";
            Log::info($message);
        }
        return view('admin');
    }
}