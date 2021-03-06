@extends('layouts.app')

@section('content')
    <?php
    $background_images = scandir("img/");
    $safe_bg_images = [];
    foreach ($background_images as $image) {
        if (strpos($image, ".jpg")){
            array_push($safe_bg_images, $image);
        }
    }
    $i = rand(0, count($safe_bg_images)-1);
    $random_image = "img/" . $safe_bg_images[$i];
    ?>
    @if (session('message'))
    <div class="alert alert-warning">
        {{ session('message') }}
    </div>
    @endif
    <div class="search-background" style="background-image: url({{$random_image}})">
        <div class="searchbox">
        <form action="/search" class="searchform" method="GET">
                <div class="logobox">
                    <div class="logo">
                        <img src="logos/landboulogo.png" alt="logo">
                    </div>
                </div>
                <input type="text" class="form-control big-search-input" id="searchstring" name="searchstring" value="{{Session::get('searchstring')}}">
                <input type="hidden" name="sort_order" value="newest">
                <div class="button-group">
                    <div class="buttons">
                        <button type="submit" class="button search-button">Search</button>
                    </div>
                    <a href="/search_options">More search options</a>
                    <a href="/admin">Admin page</a>
                </div>
            </form>
        </div>
    </div>
@endsection