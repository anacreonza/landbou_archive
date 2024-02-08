@extends('layouts.app')

@section('content')
    <x-searchbar/>
    @php
        $id = $result['hits']['hits'][0]['_id'];
        $filename = $result['hits']['hits'][0]['_source']['filename'];
    @endphp
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
                        @php
                        $filename = basename($result['hits']['hits'][0]['_source']['filename']);
                        $date['day'] = substr($filename, 0, 2);
                        $date['month'] = substr($filename, 2, 2);
                        $date['year'] = "20" . substr($filename, 4, 2);
                        $docfilename = str_replace(".html", '', $filename);
                        $docpath = env('DOCX_BACKUP_FOLDER') . $date['year'] . "/" . $date['month'] . "/" . $date['day'] . "/" . $docfilename
                        @endphp
                        <!-- <div class="info-item"><a href="/article/download/{{$result['hits']['hits'][0]['_id']}}">Download</a></div> -->
                        @if (file_exists($docpath))
                            <div class="info-item"><a href="/backup/{{$date['year']}}/{{$date['month']}}/{{$date['day']}}/{{$docfilename}}">Download Word Doc</a></div>
                        @endif
                        @if (Auth::check())
                            @if (Auth::user()->role == "admin")
                                <div class="info-item">
                                    <form action="/article/delete/{{$id}}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you wish to delete this article?')" >Delete Article</button>
                                    </form>
                                </div>
                            @endif
                        @endif
                        <div class="info-item"><a href="/archives/LandbouWeekblad/{{$date['year']}}/{{$date['month']}}/{{$date['day']}}/{{$filename}}" target="_blank">Download HTML</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>    
@endsection