@extends('layouts.app')

@section('content')
    <x-searchbar/>

    <div class="container">
        <div class="backlink">
            <a href="javascript:history.back()"><< Back to results</a>
        </div>
        <div class="article-container">
            <div class="article-content">
                {!!$result['hits']['hits'][0]['_source']['content']!!}
            </div>
            <div class="article-metabox-column">
                <div class="article-metabox-container">
                    <div class="metabox-header">Article Information</div>
                    <div class="metabox-body">
                        <div class="info-item">Date: {{$result['hits']['hits'][0]['_source']['date']}}</div>
                        @if ($result['hits']['hits'][0]['_source']['credits'])
                            @foreach ($result['hits']['hits'][0]['_source']['credits'] as $credit)
                                <div class="info-item">Credit: {{$credit}}</div>
                            @endforeach    
                        @endif
                        @if ($result['hits']['hits'][0]['_source']['pagenos'])
                            <div class="info-item">Page reference: {{$result['hits']['hits'][0]['_source']['pagenos'][0]}}</div>
                        @endif
                        <div class="info-item"><a href="/article/download/{{$result['hits']['hits'][0]['_id']}}">Download</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>    
@endsection