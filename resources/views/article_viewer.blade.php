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
                        <div class="info-item"><b>Date: </b>{{$result['hits']['hits'][0]['_source']['date']}}</div>
                        @if ($result['hits']['hits'][0]['_source']['credits'])
                            @foreach ($result['hits']['hits'][0]['_source']['credits'] as $credit)
                                <div class="info-item">Credit: {{$credit}}</div>
                            @endforeach    
                        @endif
                        @if ($result['hits']['hits'][0]['_source']['pagenos'])
                            <div class="info-item"><b>Page reference: </b>{{$result['hits']['hits'][0]['_source']['pagenos'][0]}}</div>
                        @endif
                        @if ($result['hits']['hits'][0]['_source']['filename'])
                            <div class="info-item"><b>File Name: </b>{{$result['hits']['hits'][0]['_source']['filename']}}</div>
                        @endif
                        <div class="info-item"><a href="/article/download/{{$result['hits']['hits'][0]['_id']}}">Download</a></div>
                        <div class="info-item"><a href="/article/edit/{{$result['hits']['hits'][0]['_id']}}">Edit</a></div>
                        <form method="POST" action="/article/delete/{{$result['hits']['hits'][0]['_id']}}">
                            @csrf
                            <input name="_method" type="hidden" value="DELETE">
                            <button type="submit" class="btn btn-xs btn-danger btn-flat show_confirm" onclick="return confirm('Are you sure you wish to delete this article?')" title='Delete'>Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>    
@endsection