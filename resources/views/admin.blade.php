@extends('layouts.app')
@section('content')
    <x-searchbar/>
    <div class="container">
        @if (session('message'))
        <div class="alert alert-warning">
            {{ session('message') }}
        </div>
        @endif
        <ul>
            <li><a href="/admin/rebuild_index">Rebuild Index</a></li>
            <li><a href="/admin/delete_index">Delete Index</a></li>
            <li><a href="/admin/ingest_files">Ingest New Files</a></li>
            <li><a href="/logs" target="_blank">View logs</a></li>
            <li><a href="http://localhost:5601/app/dev_tools#/console">Kibana Console</a></li>
        </ul>
        <div>
            <p>{{$indexinfo}} items in index.</p>
        </div>
    </div>
@endsection