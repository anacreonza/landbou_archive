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
                </div>
            </form>
            <div class="search-links">
                <div>
                    <a class="home-link" href="/search_options">More search options</a>
                </div>
                @if (Auth::check())
                    @if (Auth::user()->role == "admin" || Auth::user()->role == "creator")
                        <div>
                            <a class="home-link" href="/article/compose/">Compose new article</a>
                        </div>
                    @endif
                    @if (Auth::user()->role == "admin")
                        <div>
                            <a class="home-link" href="/admin">Admin page</a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <div class="index-info-block">
            <div class="index_info-block-left">
                {{$index_info['total']}} items indexed. Latest document: {{$index_info['newest_date']}}
            </div>
            <div class="index_info-block-right">
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }}
                            </a>
        
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>
        
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                {{-- <a href="/home">{{ Auth::user()->name }} </a> --}}
            </div>
        </div>
    </div>
@endsection