@extends('layouts.app')
@php
$background_images = scandir("img/");
$safe_bg_images = [];
foreach ($background_images as $image) {
    if (strpos($image, ".jpg")){
        array_push($safe_bg_images, $image);
    }
}
$i = rand(0, count($safe_bg_images)-1);
$random_image = "img/" . $safe_bg_images[$i];
if (!isset($search_options)){
    $search_options['sort_order'] = "newest";
}
$start_date = Session::get('startdate');
@endphp
@section('content')
    <div class="search-background" style="background-image: url({{$random_image}})">
        <div class="searchbox">
            <form action="/search" class="searchform" method="GET">
                <div class="logobox">
                    <div class="logo">
                        <img src="logos/landboulogo.png" alt="logo">
                    </div>
                </div>
                <label for="searchstring">Text to search for:</label>
                <input type="text" class="form-control big-search-input" id="searchstring" name="searchstring" value="{{Session::get('searchstring')}}">
                <label for="searchtype">Search Type</label>
                <select type="text" class="form-control" id="searchtype" name="searchtype">
                    <option value="match">Match All Terms</option>
                    <option value="match_phrase">Match Exact Phrase</option>
                </select>
                <label for="order">Order:</label>
                <select name="sort_order" id="sort_order" class="form-control">
                    <option value="newest"
                    @if ($search_options['sort_order'] == "newest")
                        selected
                    @endif>Show newest result first</option>
                    <option value="oldest"
                    @if ($search_options['sort_order'] == "oldest")
                    selected
                    @endif>Show oldest result first</option>
                    <option value="relevant" 
                    @if ($search_options['sort_order'] == "relevant")
                    selected
                    @endif>Show most relevant first</option>
                </select>
                <div class="daterange-block">
                    <div class="date-item">
                        <label for="startdate">From:</label>
                        <input type="date" name="startdate" id="startdate" class="form-control date-from" value={{Session::get('start_date')}}>
                    </div>
                    <div class="date-item">
                        <label for="enddate">To:</label>
                        <input type="date" name="enddate" id="enddate" class="form-control date-to" value={{Session::get('end_date')}}>
                    </div>
                </div>
                <div class="button-group">
                    <div class="buttons">
                        <button type="submit" class="button search-button">Search</button>
                    </div>
                    <a href="/">Basic search</a>
                </div>
            </form>
        </div>
    </div>
@endsection