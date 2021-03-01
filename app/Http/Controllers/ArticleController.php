<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
