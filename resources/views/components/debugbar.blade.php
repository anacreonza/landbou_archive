<div>
    <pre>
    @php
        $query = Session::get('query');
        $query_body = $query['body'];
        $query_body_json = json_encode($query_body, JSON_PRETTY_PRINT);
    @endphp
    {{print_r($query_body_json)}}
    </pre>
</div>