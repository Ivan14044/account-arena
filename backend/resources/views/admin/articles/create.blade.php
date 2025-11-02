@extends('adminlte::page')

@section('title', 'Add Article')

@section('content_header')
    <h1>Articles create</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Article data</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="categories">Categories</label>
                            <select name="categories[]" id="categories" class="select2 form-control @error('categories') is-invalid @enderror" multiple>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ collect(old('categories', []))->contains($category->id) ? 'selected' : '' }}>
                                        {{ $category->admin_name ?? 'Category #' . $category->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('categories')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Published</option>
                                <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Draft</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="img">Article Image</label>
                            <input type="file" accept="image/*" class="form-control-file @error('img') is-invalid @enderror" id="img" name="img">
                            @error('img')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="card mt-4">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                    @foreach (config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('title.' . $code) || $errors->has('content.' . $code) || $errors->has('short.' . $code))
                                        <li class="nav-item">
                                            <a class="nav-link @if ($hasError) text-danger @endif {{ $code == 'ru' ? 'active' : null }}"
                                               id="tab_{{ $code }}" data-toggle="pill" href="#content_{{ $code }}" role="tab">
                                                <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }} @if($hasError)*@endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    @foreach (config('langs') as $code => $flag)
                                        <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="content_{{ $code }}" role="tabpanel">
                                            <div class="form-group">
                                                <label for="meta_title_{{ $code }}">Meta title</label>
                                                <input type="text" name="meta_title[{{ $code }}]" id="meta_title_{{ $code }}" class="form-control @error('meta_title.' . $code) is-invalid @enderror" value="{{ old('meta_title.' . $code) }}">
                                                @error('meta_title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="meta_description_{{ $code }}">Meta description</label>
                                                <input type="text" name="meta_description[{{ $code }}]" id="meta_description_{{ $code }}" class="form-control @error('meta_description.' . $code) is-invalid @enderror" value="{{ old('meta_description.' . $code) }}">
                                                @error('meta_description.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="short_{{ $code }}">Short text</label>
                                                <textarea name="short[{{ $code }}]" id="short_{{ $code }}" class="form-control @error('short.' . $code) is-invalid @enderror" rows="3">{{ old('short.' . $code) }}</textarea>
                                                @error('short.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="title_{{ $code }}">Title</label>
                                                <input type="text" name="title[{{ $code }}]" id="title_{{ $code }}" class="form-control @error('title.' . $code) is-invalid @enderror" value="{{ old('title.' . $code) }}">
                                                @error('title.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="content_{{ $code }}">Content</label>
                                                <textarea style="height: 210px" name="content[{{ $code }}]" class="ckeditor form-control @error('content.' . $code) is-invalid @enderror" id="content_{{ $code }}">{!! old('content.' . $code) !!}</textarea>
                                                @error('content.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Create</button>
                        <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('js')
    <style>
        .select2-selection { height: 38px!important; width: 100%; }
    </style>
    <script>
        document.querySelectorAll('.ckeditor').forEach(function(textarea) {
            ClassicEditor
                .create(textarea)
                .then(editor => {
                    editor.editing.view.change(writer => {
                        writer.setStyle('height', '170px', editor.editing.view.document.getRoot());
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        });

        $(document).ready(function () {
            $('#categories').select2({
                placeholder: 'Select categories',
                allowClear: true
            });
        });
    </script>
@endsection
