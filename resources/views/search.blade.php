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
    <div class="search-background" style="background-image: url({{$random_image}})">
        <div class="searchbox">
            <form action="" class="searchform">
                <div class="logobox">
                    <div class="logo">
                        <img src="logos/landboulogo.png" alt="logo">
                    </div>
                </div>
                <input type="text" class="form-control big-search-input">
                <div class="button-group">
                    <div class="buttons">
                        <button type="submit" class="button search-button">Search</button>
                        <button type="submit" class="button options-button">More Options</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection