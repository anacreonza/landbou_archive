<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

class FileController extends Controller
{
    public function __construct() {
        // Check that user is authenticated
        $this->middleware('auth');
    }
    public function download(Request $request, $id){
        $filename = app('App\Http\Controllers\ArticleController')->get_filename_from_id($id);
        return Storage::download($filename);
    }
    public function convert(Request $request, $id){
        echo "Converting file with id: $id";
    }
}
