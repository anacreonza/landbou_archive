<?php
$searchstring = $_GET['searchstring'];
function remove_tags_from_highlight($highlight){
        $highlight = str_replace('<h1>', '', $highlight);
        $highlight = str_replace('</h1>', '', $highlight);
        $highlight = str_replace('<h2>', '', $highlight);
        $highlight = str_replace('</h2>', '', $highlight);
        $highlight = str_replace('<h3>', '', $highlight);
        $highlight = str_replace('</h3>', '', $highlight);
        $highlight = str_replace('<h4>', '', $highlight);
        $highlight = str_replace('</h4>', '', $highlight);
        $highlight = str_replace('<h5>', '', $highlight);
        $highlight = str_replace('</h5>', '', $highlight);
        $highlight = str_replace('<br>', '', $highlight);
        return $highlight;
    }
?>
@extends('layouts.app')
@section('content')
    <x-searchbar/>
    @if (session('message'))
    <div class="alert alert-warning">
        {{ session('message') }}
    </div>
    @endif
    <div class="container">
        <x-resultdetails/>
        <x-pagination/>
        {{-- <x-debugbar/> --}}
        @foreach ($results['hits']['hits'] as $hit)
            <div class="articlepreview">
                <div class="preview-headline">
                @if (isset($hit['_source']['headlines'][0]))
                    <a href="/article/read/{{$hit['_id']}}">{!!$hit['_source']['headlines'][0]!!}</a>
                @else
                    <a href="/article/read/{{$hit['_id']}}">No headline detected in story</a>
                @endif
                </div>
                <div class="preview-highlightbox"><em>Highlights:</em> 
                    @foreach ($hit['highlight']['content'] as $highlight)
                        {!!remove_tags_from_highlight($highlight)!!}
                    @endforeach
                </div>
                <div class="preview-metaitem">{{$hit['_source']['date']}}</div>
                @if (isset($hit['_source']['categories']))
                    @foreach ($hit['_source']['categories'] as $category)
                        <div class="preview-metaitem">{{$category}}</div>
                    @endforeach
                @endif
                @if (isset($hit['_source']['credits']))
                    @foreach ($hit['_source']['credits'] as $credit)
                        <div class="preview-metaitem">{{$credit}}</div>
                    @endforeach
                @endif
            </div>
            <hr>
        @endforeach
        {{-- {!!$results['hits']['hits'][2]['_source']['content']!!} --}}
        <x-pagination/>
    </div>
      
@endsection