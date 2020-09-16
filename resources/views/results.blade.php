<?php $searchstring = $_GET['searchstring'];?>
@extends('layouts.app')
@section('content')
    <x-searchbar/>
    <div class="container">
        <ul>
            <li>Showing results for {{$searchstring}}. 1002 results found</li>
            <li>Pagination control</li>
            <li>results begin</li>
        </ul>
    </div>    
@endsection