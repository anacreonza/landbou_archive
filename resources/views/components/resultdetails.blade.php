<?php
$total_hits = Session::get('totalhits');
$items_per_page = Session::get('itemsperpage');
$search_string = Session::get('searchstring');
$first_item_on_page = 1;
if ($total_hits > $items_per_page){
    $last_item_on_page = $items_per_page;
} else {
    $last_item_on_page = $total_hits;
}
?>
<div>
    <p>{{$total_hits}} items found. Showing results {{$first_item_on_page}} - {{$last_item_on_page}} for "{{$search_string}}".</p>
</div>