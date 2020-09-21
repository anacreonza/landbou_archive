<?php
$number_of_pages = Session::get('totalhits') / 25;
?>
<div class="pagination-block">
    <nav aria-label="Page navigation example">
        <ul class="pagination">
        <li class="page-item"><a class="page-link" href="">Previous</a></li>
        @for ($i = 1; $i < $number_of_pages; $i++)
            <li class="page-item"><a class="page-link" href="">{{$i}}</a></li>
        @endfor
        <li class="page-item"><a class="page-link" href="">Next</a></li>
        </ul>
      </nav>
</div>