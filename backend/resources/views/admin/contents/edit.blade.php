@extends('adminlte::page')

@section('title', 'Edit content #' . $content->id)

@section('content_header')
    <h1>Edit content #{{ $content->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Content data</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.contents.update', $content) }}"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $content->name) }}">
                            @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" id="code" name="code"
                                   {{ $content->is_system ? 'readonly' : '' }} class="form-control"
                                   value="{{ $content->code }}">
                        </div>

                        <div class="card">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                    @foreach(config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('title.' . $code) || $errors->has('message.' . $code))
                                        <li class="nav-item">
                                            <a class="nav-link @if($hasError) text-danger @endif {{ $code == 'ru' ? 'active' : null }}"
                                               id="tab_{{ $code }}" data-toggle="pill" href="#tab_message_{{ $code }}"
                                               role="tab">
                                                <span
                                                    class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}  @if($hasError)
                                                    *
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body p-2">
                                <div class="tab-content">
                                    @foreach(config('langs') as $code => $flag)
                                        @include('admin.contents._language_tab_content', ['code' => $code, 'flag' => $flag, 'contentData' => $contentData])
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="submit" name="save" class="btn btn-primary">Save & Continue</button>
                        <a href="{{ route('admin.contents.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .repeatable-blocks > .repeatable-group:first-child .remove-block {
            display: none;
        }

        /* Grid layout: up to 3 fields per row, 1 per row on mobile */
        .repeatable-group {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            grid-gap: 10px;
        }

        @media (max-width: 576px) {
            .repeatable-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            function refreshBlockIndices(lang) {
                const container = $('.repeatable-blocks[data-lang="' + lang + '"]');

                container.find('.repeatable-group').each(function (groupIndex) {
                    $(this).find('input, textarea, label').each(function () {
                        ['id', 'for'].forEach(attr => {
                            const val = $(this).attr(attr);
                            if (val) {
                                const updated = val.replace(/_\w+_(\d+)_\d+$/, function (match, p1) {
                                    return '_' + lang + '_' + groupIndex + '_0';
                                });
                                $(this).attr(attr, updated);
                            }
                        });
                    });
                });
            }

            $('.add-block').on('click', function () {
                const lang = $(this).data('lang');
                const container = $('.repeatable-blocks[data-lang="' + lang + '"]');
                const firstBlock = container.find('.repeatable-group').first();
                const newBlock = firstBlock.clone();

                newBlock.find('input, textarea').each(function () {
                    $(this).val('');
                });

                newBlock.find('input[type="file"]').val('');
                newBlock.find('img.img-bordered').each(function () {
                    const container = $(this).closest('.mt-2');
                    if (container.length) {
                        container.remove();
                    } else {
                        $(this).remove();
                    }
                });

                container.append(newBlock);
                refreshBlockIndices(lang);
            });

            $(document).on('click', '.remove-block', function () {
                const container = $(this).closest('.repeatable-blocks');
                const lang = container.data('lang');

                if (container.find('.repeatable-group').length > 1) {
                    $(this).closest('.repeatable-group').remove();
                    refreshBlockIndices(lang);
                }
            });

            // Remove existing file: clear hidden value to keep index alignment, clear file input, remove preview
            $(document).on('click', '.remove-file', function (e) {
                e.preventDefault();
                const formGroup = $(this).closest('.form-group');
                formGroup.find('input[type="hidden"]').val('');
                formGroup.find('input[type="file"]').val('');
                $(this).closest('.mt-2').remove();
            });
        });
    </script>
@endsection



