<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Config;
use Carbon\Carbon;
use Datetime;
use Session;
use Auth;

class ArticleController extends Controller
{
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
    public function get_filename_from_id($id){
        $params = [
          'index' => $this->index,
          'id' => $id
        ];
        $response = $this->elasticsearch->get($params);
        $file = $response['_source']['file'];
        return $file;
    }
    public function build_article_url($id){
        $article_meta = app('App\Http\Controllers\SearchController')->retrieve_article_meta_by_id($id);
        $filename = \urlencode($article_meta['hits']['hits'][0]['_source']['filename']);
        return "/archives/Landbouweekblad/2020/11/12/" . $filename;
    }
    public function get_dates_from_filename($file){
        $filename = basename($file);
        $date['day'] = substr($filename, 0, 2);
        $date['month'] = substr($filename, 2, 2);
        $date['year'] = "20" . substr($filename, 4, 2);
        return $date;
    }
    public function build_newdir($file){
        $filedate = $this->get_dates_from_filename($file);
        $newdir = 'Archives' . DIRECTORY_SEPARATOR . env('ARCHIVE_PUBNAME') . DIRECTORY_SEPARATOR . $filedate['year'] . DIRECTORY_SEPARATOR . $filedate['month'] . DIRECTORY_SEPARATOR . $filedate['day'] . DIRECTORY_SEPARATOR;
        return $newdir;
    }
    public function store_article_file($sourcefile){
        if (!file_exists($sourcefile)){
            log::info("No such file found as " . $sourcefile);
            return false;
        }
        $newdir = $this->build_newdir($sourcefile);
        $destfile = $newdir . basename($sourcefile);
        $content = \file_get_contents($sourcefile);
        Storage::disk("local")->put($destfile, $content);
        if (Storage::disk('local')->exists($destfile)) {
            unlink($sourcefile);
            $message = "File successfully stored at: " . $destfile;
            Log::info($message);
            return $message;
        }
    }
    public function compose(){
        return view('article_compose');
    }
    public function create(Request $request){
        $validated = $request->validate([
            'credit' => 'required',
            'date' => 'required|date',
            'storytitle' => 'required',
            'content' => 'required',
        ]);
        $article = [];
        $article['credit'] = htmlentities($request['credit']);
        $date = new Datetime(htmlentities($request['date']));
        $article['date'] = $date->format('dmy');
        $article['storytitle'] = htmlentities($request['storytitle']);
        $article['content'] = htmlentities($request['content']);
        if (isset($request['pageref'])){
            $article['pageref'] = htmlentities($request['pageref']);
        }
        $style = "\n<style>
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
        h1 {
          font-size: 18pt;
        }
        h4, h5, h6 {
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
          font-family: Menlo, Monaco, 'Lucida Console', Consolas, monospace;
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
        .display.math{display: block; text-align: center; margin: 0.5rem auto;}</style>\n";
        $html_header = "<head>\n<meta charset=\"utf-8\"/>\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, user-scalable=yes\"/>\n<title>" . $article['storytitle'] . "</title>" . $style . "</head>\n";
        $html_content = str_replace('&lt;','<', $article['content']);
        $html_content = "\n" . str_replace('&gt;','>', $html_content);

        // Append the metadata into html.

        $story_title = "\n<h1 id=\"storytitle\">" . $article['storytitle'] . "</h1>";
        $credit_line = "\n<h4 id=\"credit\">" . $article['credit'] . "</h4>";
        $article['filename'] = str_replace('-','',$article['date']) . " " . $article['storytitle'] . ".html";
        $html_content = $story_title . $credit_line . $html_content;
        $html_content .= "\n<div style=\"display: none;\" id=\"pubdate\">" . htmlentities($request['date']) . "</div>";
        $html_content .= "\n<div style=\"display: none;\" id=\"filename\">" . $article['filename'] . "</div>";
        if (isset($article['pageref'])){
          $html_content .= "\n<div style=\"display: none;\" id=\"pageref\">" . $article['pageref'] . "</div>";
        }
        $content = "<html>\n" . $html_header . "<body>\n" . $html_content . "\n</body>\n</html>";
        Storage::disk('local')->put($article['filename'], $content);
        $article_content = Storage::get($article['filename']);
        $index_result = app('App\Http\Controllers\IndexController')->index_content($article_content);
        $message = $this->store_article_file($article['filename']);
        return redirect('/')->with('message', $message);
    }
    public function create_file($request){

    }
    public function delete($id){
        if (Auth::check()){
            if (Auth::user()->role == "admin"){
                $params = [
                    'index' => $this->index,
                    'id' => $id
                ];
                try {
                    $response = $this->elasticsearch->get($params);
                } catch (\Throwable $th) {
                    //throw $th;
                    return redirect('/')->with('message', "Unable to locate document with id: " . $id);
                }
                
                $response = $this->elasticsearch->delete($params);
                if ($response['result'] == 'deleted'){
                    $message = "Item: " . $id . " deleted by " . Auth::user()->name;
                } else {
                    $message = "Item failed to delete: " . $id;
                }
                Log::info($message);
                \sleep(1); // Delete takes time so delay returning so the deleted item does not still appear.
                $searchstring = Session::get('searchstring');
                $sort_order = Session::get('sort_order');
                $start_date = Session::get('start_date');
                $end_date = Session::get('end_date');
                $newurl = "/search?searchstring=$searchstring";
                if (isset($sort_order)){
                  $newurl .= "&sort_order=$sort_order";
                }
                if (isset($start_date)){
                  $newurl .= "&startdate=$start_date";
                }
                if (isset($end_date)){
                  $newurl .= "&enddate=$end_date";
                }
                return redirect($newurl)->with('message', $message);
            } else {
                return redirect('/')->with('message', "No permission to delete document with id: " . $id);
            }
        } else {
            return redirect('/')->with('message', "No permission to delete document with id: " . $id);
        }
    }
}
