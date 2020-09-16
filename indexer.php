<?php
$start_time = microtime(true);
define("ELASTICSEARCH_SERVER_URL", "http://localhost");
define("ELASTICSEARCH_SERVER_PORT", "9200");

require 'vendor/autoload.php';

use Elasticsearch\ClientBuilder;

$host = ELASTICSEARCH_SERVER_URL . ":" . ELASTICSEARCH_SERVER_PORT;

$hosts = [$host];

$client = ClientBuilder::create()->setHosts($hosts)->build();

// Read in the html file and extract the different metadata keys from the html.
if (!isset($argv[1])){
    die("\nPlease specify an input path!\n\n");
}

$input_path = $argv[1];

if (!is_dir($input_path)){
    die("Invalid input path!");
}

function get_html($file){
    $raw_html = file_get_contents($file);
    $domdoc = new DOMDocument;
    $encoded_html = mb_convert_encoding($raw_html, 'HTML-ENTITIES', 'UTF-8');
    $domdoc->loadHTML($encoded_html);
    return $domdoc;
}

function get_headlines($html_object){
    $headlines = [];
    $h1s = $html_object->getElementsByTagName('h1');
    for ($i=0; $i < $h1s->length; $i++) { 
        $head = $h1s->item($i)->nodeValue;
        $head = mb_convert_encoding($head, 'HTML-ENTITIES', 'UTF-8');
        $head = str_replace("\r", '', $head);
        array_push($headlines, $head);
    }
    return $headlines;
}
function get_credits($html_object){
    $credits = [];
    $credit_tags = ['h4', 'h5'];
    foreach ($credit_tags as $credit_tag) {
        $credtags = $html_object->getElementsByTagName($credit_tag);
        for ($i=0; $i < $credtags->length; $i++) { 
            array_push($credits, $credtags->item($i)->nodeValue);
        }
    }
    return $credits;
}
function get_categories($html_object){
    $categories = [];
    $body = $html_object->getElementsByTagName('body')->item(0);
    $lines = explode("\n", $body->nodeValue);
    foreach ($lines as $line){
        if (strpos($line, "ategorie")){
            $line = str_replace("Kategorie: ", '', $line);
            $line = str_replace("&nbsp;", '', $line);
            $line = str_replace("\r", '', $line);
            $line = stripcslashes($line);
            array_push($categories, $line);
        }
    }
    return $categories;
}
function get_articlehtml($html_object){
    $body_content = $html_object->getElementsByTagName('body')->item(0);
    $content = $html_object->saveHTML($body_content);
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    $content = str_replace('<body>', '', $content);
    $content = str_replace('</body>', '', $content);
    $content = str_replace("\r", '', $content);
    $content = str_replace("\n", ' ', $content);
    $content = stripslashes($content);
    return $content;
}
function get_pagenumbers($html_object){
    $page_nos = [];
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
function get_date($file){
    $dirname = dirname($file);
    $path_elements = explode("/", $dirname);
    $day = end($path_elements);
    $month = prev($path_elements);
    $year = prev($path_elements);
    $datestring = intval($year) . "-" . intval($month) . "-" . intval($day);
    $date = new DateTime($datestring);
    return $date;
}
function get_path($file){
    $path = $file;
    return $path;
}
function get_metas($file){
    $metas = [];
    $html_object = get_html($file);
    $metas['headlines'] = get_headlines($html_object);
    $metas['credits'] = get_credits($html_object);
    $metas['categories'] = get_categories($html_object);
    $metas['content'] = get_articlehtml($html_object);
    $metas['pagenos'] = get_pagenumbers($html_object);
    $metas['date'] = get_date($file);
    $metas['path'] = get_path($file);
    return $metas;
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
// $json_file = fopen("index.json", "w") or die("Unable to create json file!");

$dir = new RecursiveDirectoryIterator($input_path);
$Iterator = new RecursiveIteratorIterator($dir);
$Regex = new RegexIterator($Iterator, '/^.+\.html$/i', RecursiveRegexIterator::GET_MATCH);
$files = [];
foreach ($Regex as $filepath){
    $files[] = $filepath[0];
}
$idno = 1;
foreach ($files as $file) {
    echo "\nIndexing " . $file . "\n";
    $item = get_metas($file);
    $result = post_entry($item, $client);
    print_r($result);
    $idno++;
}
// fclose($json_file);

$end_time = microtime(true);
$execution_time = ($end_time - $start_time)/60;
$execution_time = round($execution_time, 2);
echo("\nIndexing complete. $idno items indexed. Script took $execution_time minutes to complete.\n");
?>