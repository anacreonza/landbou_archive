<?php

$total_hits = Session::get('totalhits');
$query = Session::get('query');
$sort_order = Session::get('sort_order');
$search_type = Session::get('searchtype');
if (isset($search_type)){
    $search_type_string = "Search Type: " . $search_type;
}

switch ($sort_order) {
    case 'oldest':
        $sort_order_string = "Oldest items first.";
        break;

    case 'relevant':
        $sort_order_string = "Highest scoring items first.";
        break;
    
    default:
        $sort_order_string = "Newest items first.";
        break;
}
$items_per_page = Session::get('itemsperpage');
$search_string = Session::get('searchstring');
$items_per_page = $query['body']['size'];
$first_item = $query['body']['from'] + 1;
if ($total_hits < $items_per_page){
    $last_item = $total_hits;
} else {
    $last_item = $first_item + $items_per_page - 1;
}
if ($last_item > $total_hits){
    $last_item = $total_hits;
}
if (isset($query['body']['query']['bool']['filter'])){
    if (isset($query['body']['query']['bool']['filter']['range']['date']['gte'])){
        $startdate = $query['body']['query']['bool']['filter']['range']['date']['gte'];
    } else {
        $startdate = "earliest";
    }
    if (isset($query['body']['query']['bool']['filter']['range']['date']['lte'])){
        $enddate = $query['body']['query']['bool']['filter']['range']['date']['lte'];
    } else {
        $enddate = "latest";
    }
    $date_range_string = ", from date range " . $startdate . " to " . $enddate . ". ";
} else {
    $date_range_string = '. ';
}
?>
<div>
    <p>{{$total_hits}} items found. Showing results {{$first_item}} - {{$last_item}} for "{{$search_string}}"{{$date_range_string}}{{$sort_order_string}} {{$search_type_string}}</p>
</div>