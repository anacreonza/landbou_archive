<?php $searchstring = $_GET['searchstring'];?>
<div class="searchbar">
    <a href="/" class="title-link">Landbou Text Archive</a>
    <form action="" class="toolbar-searchform">
        <input type="text" value="{{$searchstring}}" name="searchstring" id="searchstring" class="toolbar-search-input">
        <button type="submit" class="mini-search-button"><i class="fa fa-search" aria-hidden="true"></i></button>
    </form>
</div>