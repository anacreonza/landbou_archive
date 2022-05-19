@extends('layouts.app')

@section('content')
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif  
    <x-searchbar/>
    <div class="container">
        <form action="/article/create/" method="POST">
            @csrf
            <div class="right-button-container">
                <div><input type="submit" class="button create-button" value="Create Article"></div>
            </div>
            <div>
                <div class="new-article-metabox">
                    <label for="title">Story Title:</label>
                    <input type="text" name="storytitle" class="form-control" value="{{ old('storytitle') }}">
                    <label for="date">Issue Date:</label>
                    <input type="date" name="date" class="form-control" value="{{ old('date')}}">
                </div>
                <div class="new-article-metabox">
                    <label for="credit">Credit:</label>
                    <input type="text" name="credit" class="form-control" value="{{ old('credit') }}">
                    <label for="pageref">Page Reference:</label>
                    <input type="text" name="pageref" class="form-control" value="{{ old('pageref') }}">
                </div>
            </div>
            <textarea id="editor" name='content'>{{ old('content')}}</textarea>
        </form>
            <script>
            ClassicEditor
                .create( document.querySelector( '#editor' ), {
                    toolbar: [ 'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', '|', 'indent', 'outdent', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo' ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }
                        ]
                    }
                } )
                .catch( error => {
                    console.log( error );
                } );
        </script>
    </div>
@endsection