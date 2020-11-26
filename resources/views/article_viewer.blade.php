@extends('layouts.app')

@section('content')
    <x-searchbar/>

    <div class="container">
        <div class="backlink">
            <a href="javascript:history.back()"><< Back to results</a>
        </div>
        <div class="article">
            {!!$result['hits']['hits'][0]['_source']['content']!!}
        </div>
    </div>    
@endsection