<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Host OS is Windows
    define('PANDOC', 'pandoc.exe');
} else {
    // Host OS is not Windows
    define('PANDOC', "/usr/local/bin/pandoc");
}

define('INPUTDIR', "public/hotfolder");
define("ELASTICSEARCH_SERVER_URL", "http://localhost");
define("ELASTICSEARCH_SERVER_PORT", "9200");
define("INDEX", "archive");
define("ARCHIVEDIR", 'public/archives');
define("PUBLICATION", "LandbouWeekblad");
define("TEMPDIR", "public/temp");

require 'vendor/autoload.php';
use Elasticsearch\ClientBuilder;

$start_time = microtime(true);
$inputfiles = scandir(INPUTDIR);

$host = ELASTICSEARCH_SERVER_URL . ":" . ELASTICSEARCH_SERVER_PORT;
$hosts = [$host];
$client = ClientBuilder::create()->setHosts($hosts)->build();

function check_if_indexed($filename, $client){
    $params = [
        'index' => 'archive',
        'body' => [
            'query' => [
                "match_phrase" => [
                    "filename" => $filename
                ]
            ]
        ]
    ];
    $response = $client->search($params);
    $found = $response['hits']['total']['value'];
    return $found;
}

function post_entry($entry, $client){
    $params = [
        'index' => 'archive',
        // 'id' => 'my_id',
        'body' => $entry
    ];
    // var_dump($params);
    $response = $client->index($params);
    return $response;
}
function index_check($client){
    $params['index'] = INDEX;
    $index_exists = $client->indices()->exists($params);
    if (!$index_exists){
        $params = [
            'index' => INDEX,
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 0
                ]
            ]
        ];
        $response = $client->indices()->create($params);
        if ($response['acknowledged'] == 1){
            print_r("New index created.");
        }
    }
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
function get_headlines($htmlfile){
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
function get_credits($htmlfile){
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
function get_metas($htmlfile){
    $metas = [];
    $filename = basename($htmlfile);
    $filedate = get_dates_from_filename($htmlfile);
    $datestring = intval($filedate['year']) . "-" . intval($filedate['month']) . "-" . intval($filedate['day']);
    $date = new DateTime($datestring);
    $date = $date->format('Y-m-d');
    $metas['date'] = $date;
    $html_content = file_get_contents($htmlfile);
    $metas['headlines'] = get_headlines($htmlfile);
    $metas['credits'] = get_credits($htmlfile);
    $metas['content'] = get_articlehtml($htmlfile);
    $metas['pagenos'] = get_pagenumbers($htmlfile);
    $metas['file'] = build_newdir($htmlfile) . basename($htmlfile);
    $metas['filename'] = basename($htmlfile);
    return $metas;
}

function get_dates_from_filename($file){
    $filename = basename($file);
    $date['day'] = substr($filename, 0, 2);
    $date['month'] = substr($filename, 2, 2);
    $date['year'] = "20" . substr($filename, 4, 2);
    return $date;
}

function store_file($file, $newdir){
    if (!file_exists($file)){
        echo "No such file found as " . $file . "\n";
        return false;
    }
    $filedate = get_dates_from_filename($file);
    $newname = $newdir . basename($file);
    if (!file_exists($newdir)){
        if (!mkdir($newdir, 0777, true)){
            die('Failed to create folder');
        }
    }
    copy($file, $newname);
    if (file_exists($newname)){
        unlink($file);
    }
}
function build_newdir($file){
    $filedate = get_dates_from_filename($file);
    $newdir = ARCHIVEDIR . DIRECTORY_SEPARATOR . PUBLICATION . DIRECTORY_SEPARATOR . $filedate['year'] . DIRECTORY_SEPARATOR . $filedate['month'] . DIRECTORY_SEPARATOR . $filedate['day'] . DIRECTORY_SEPARATOR;
    return $newdir;
}

index_check($client);
$files_total = count($inputfiles);
$files_counter = 0;
$files_indexed_count = 0;

foreach ($inputfiles as $filename) {
    if (preg_match('/^\./', $filename)){
        $files_total--;
        continue;
    }
    $files_counter++;
    $file = INPUTDIR . DIRECTORY_SEPARATOR . $filename;
    print_r("\nProcessing file " . $files_counter . " of " . $files_total . ": " . $filename . " ");
    if (strpos($filename, ".docx")){
        print_r("    Converting file to HTML...\n");
        $htmlfile = convert_to_html($file);
    } elseif(strpos($filename, ".html")) {
        $htmlfile = $file;
    }
    $found = check_if_indexed($filename, $client);
    if ($found){
        print_r("[ ALREADY INDEXED ]\n");
        continue;
    } else {
        print_r("[ NEW FILE! ]\n");
        $files_indexed_count++;
        $item = get_metas($htmlfile);
        $result = post_entry($item, $client);
        print_r("    " . $result['result'] . " new item. ID: " . $result["_id"] . "\n");
    }
    $newdir = build_newdir($file);
    print_r("    Storing " . basename($htmlfile) . "\n");
    store_file($htmlfile, $newdir);

}
$end_time = microtime(true);
$execution_time = ($end_time - $start_time)/60;
$execution_time = round($execution_time, 2);
echo("\nIndexing complete. " . $files_indexed_count . " new items indexed. Script took $execution_time minutes to complete.\n");

?>  