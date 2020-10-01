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
                <label for="order">Order:</label>
                <select name="order" id="" class="form-control">
                    <option value="">Show newest result first</option>
                    <option value="">Show oldest result first</option>
                    <option value="">Show most relevant first</option>
                </select>
                <div class="daterange-block">
                    <div class="date-item">
                        <label for="startdate">From:</label>
                        <input type="date" name="startdate" id="startdate" class="form-control date-from">
                    </div>
                    <div class="date-item">
                        <label for="enddate">To:</label>
                        <input type="date" name="enddate" id="enddate" class="form-control date-to">
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