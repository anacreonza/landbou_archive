<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ArticleController extends Controller
{
    public function download(Request $request, $id){
        $url = $this->build_article_url($id);
        var_dump($url);
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
            echo "No such file found as " . $sourcefile . "\n";
            return false;
        }
        $newdir = $this->build_newdir($sourcefile);
        $destfile = $newdir . basename($sourcefile);
        $content = \file_get_contents($sourcefile);
        Storage::disk("local")->put($destfile, $content);
        if (Storage::disk('local')->exists($destfile)) {
            unlink($sourcefile);
            $message = Carbon::now() . " File successfully stored at: " . $destfile;
            Storage::append('index.log', $message);
        }
    }
}
