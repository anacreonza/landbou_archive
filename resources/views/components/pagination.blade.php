<?php
$query = Session::get('query');
$items_per_page = $query['body']['size'];
$number_of_pages = Session::get('totalhits') / $items_per_page;
$number_of_pages = ceil($number_of_pages);

$search_string = $_GET['searchstring'];
if (isset($_GET['page'])){
    $page = $_GET['page'];
} else {
    $page = 1;
}
?>
<div class="pagination-block">
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            @if ($page == 1)
                <li class="page-item disabled"><a class="page-link" href=""><<</a></li>
            @else
                <li class="page-item"><a class="page-link" href="/search?searchstring={{$search_string}}&page={{$page-1}}"><<</a></li>
            @endif
            @for ($i = 1; $i <= $number_of_pages; $i++)
                @if ($i == $page)
                <li class="page-item active"><a class="page-link" href="/search?searchstring={{$search_string}}&page={{$i}}">{{$i}}</a></li>
                @else
                <li class="page-item"><a class="page-link" href="/search?searchstring={{$search_string}}&page={{$i}}">{{$i}}</a></li>
                @endif
                @if ($i > 25)
                <li class="page-item"><a class="page-link" href="#">...</a></li>
                @break
                @endif
            @endfor
            @if ($page == $number_of_pages)
                <li class="page-item disabled"><a class="page-link" href="">>></a></li>
            @else
                <li class="page-item"><a class="page-link" href="/search?searchstring={{$search_string}}&page={{$page+1}}">>></a></li>
            @endif
        </ul>
      </nav>
</div>