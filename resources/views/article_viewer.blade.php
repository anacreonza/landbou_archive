@extends('layouts.app')

@section('content')
    <x-searchbar/>

    <div class="container">
        <a href="javascript:history.back()"><< Back to results</a>
        {!!$result['hits']['hits'][0]['_source']['content']!!}
    </div>    
@endsection