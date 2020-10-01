<?php
$total_hits = Session::get('totalhits');
$items_per_page = Session::get('itemsperpage');
$search_string = Session::get('searchstring');
$query = Session::get('query');
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

?>
<div>
    <p>{{$total_hits}} items found. Showing results {{$first_item}} - {{$last_item}} for "{{$search_string}}".</p>
</div>