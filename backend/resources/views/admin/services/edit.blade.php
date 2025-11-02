@extends('adminlte::page')

@section('title', 'Edit service #' . $service->id)

@section('content_header')
    <h1>Edit service #{{ $service->id }}</h1>
@stop

@section('content')
    <div class="row">
        @if(session('success'))
            <div class="col-12">
                <div class="alert alert-success">{{ session('success') }}</div>
            </div>
        @endif

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service data</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.services.update', $service) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" readonly id="code" class="form-control"
                                   value="{{ $service->code }}">
                        </div>

                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select name="is_active" id="is_active" class="form-control @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $service->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $service->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" step="0.1" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $service->amount) }}">
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="trial_amount">Trial amount</label>
                            <input type="number" step="0.1" name="trial_amount" id="trial_amount" class="form-control @error('trial_amount') is-invalid @enderror" value="{{ old('trial_amount', $service->trial_amount) }}">
                            @error('trial_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="number" step="1" name="position" id="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $service->position) }}">
                            @error('position')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="logo">Logo</label>
                            <input type="hidden" name="logo_text" value="{{ $service->logo }}">
                            <input type="file" accept="image/*" class="form-control-file @error('logo') is-invalid @enderror" id="logo" name="logo">
                            @if($service->logo != \App\Models\Service::DEFAULT_LOGO)
                                <div id="logoImage">
                                    <img src="{{ url($service->logo) }}" class="img-fluid img-bordered mt-2" style="width: 150px;">
                                    <a href="#" onclick="removeLogo(event)" class="d-block mt-1">Delete</a>
                                </div>
                            @endif
                            @error('logo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>
                        <hr>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Browser settings</h3>
                            </div>
                            <div class="card-body p-3">
                                <div class="form-group">
                                    <label for="params_link">Service link (URL)</label>
                                    <input type="url" name="params[link]" id="params_link" class="form-control @error('params.link') is-invalid @enderror" value="{{ old('params.link', $service->params['link'] ?? '') }}" placeholder="https://example.com">
                                    @error('params.link')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="params_title">Window title</label>
                                    <input type="text" name="params[title]" id="params_title" class="form-control @error('params.title') is-invalid @enderror" value="{{ old('params.title', $service->params['title'] ?? '') }}" placeholder="Title">
                                    @error('params.title')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                

                                <div class="form-group">
                                    <label for="params_icon">Window icon (favicon)</label>
                                    @if(!empty($service->params['icon'] ?? ''))
                                        <input type="hidden" name="params_icon_text" value="{{ $service->params['icon'] }}">
                                    @endif
                                    <input type="file" accept="image/*" class="form-control-file @error('params_icon') is-invalid @enderror" id="params_icon" name="params_icon">
                                    @if(!empty($service->params['icon'] ?? ''))
                                        <div id="iconImage">
                                            <img src="{{ url($service->params['icon']) }}" class="img-fluid img-bordered mt-2" style="width: 32px; height: 32px;">
                                            <a href="#" onclick="removeIcon(event)" class="d-block mt-1">Delete</a>
                                        </div>
                                    @endif
                                    @error('params_icon')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header no-border border-0 p-0">
                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                    @foreach(config('langs') as $code => $flag)
                                        @php($hasError = $errors->has('name.' . $code) || $errors->has('description.' . $code))
                                        <li class="nav-item">
                                            <a class="nav-link @if($hasError) text-danger @endif {{ $code == 'ru' ? 'active' : null }}"
                                               id="tab_{{ $code }}" data-toggle="pill" href="#content_{{ $code }}" role="tab">
                                                <span class="flag-icon flag-icon-{{ $flag }} mr-1"></span> {{ strtoupper($code) }}  @if($hasError)*@endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    @foreach(config('langs') as $code => $flag)
                                        <div class="tab-pane fade show {{ $code == 'ru' ? 'active' : null }}" id="content_{{ $code }}" role="tabpanel">
                                            <div class="form-group">
                                                <label for="name_{{ $code }}">Name</label>
                                                <input type="text" name="name[{{ $code }}]" id="name_{{ $code }}"
                                                       class="form-control @error('name.' . $code) is-invalid @enderror"
                                                       value="{{ old('name.' . $code, $serviceData[$code]['name'] ?? null) }}">
                                                @error('name.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="subtitle_{{ $code }}">Subtitle</label>
                                                <input type="text" name="subtitle[{{ $code }}]" id="subtitle_{{ $code }}"
                                                       class="form-control @error('subtitle.' . $code) is-invalid @enderror"
                                                       value="{{ old('subtitle.' . $code, $serviceData[$code]['subtitle'] ?? null) }}">
                                                @error('subtitle.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="short_description_card_{{ $code }}">Short description (card)</label>
                                                <textarea style="height: 210px"
                                                          name="short_description_card[{{ $code }}]"
                                                          class="ckeditor form-control @error('short_description_card.' . $code) is-invalid @enderror"
                                                          id="short_description_card_{{ $code }}">{!! old('short_description_card.' . $code, $serviceData[$code]['short_description_card'] ?? null) !!}</textarea>
                                                @error('short_description_card.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="short_description_checkout_{{ $code }}">Short description (checkout)</label>
                                                <textarea style="height: 210px"
                                                          name="short_description_checkout[{{ $code }}]"
                                                          class="ckeditor form-control @error('short_description_checkout.' . $code) is-invalid @enderror"
                                                          id="short_description_checkout_{{ $code }}">{!! old('short_description_checkout.' . $code, $serviceData[$code]['short_description_checkout'] ?? null) !!}</textarea>
                                                @error('short_description_checkout.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label for="full_description_{{ $code }}">Full Description</label>
                                                <textarea style="height: 210px"
                                                          name="full_description[{{ $code }}]"
                                                          class="ckeditor form-control @error('full_description.' . $code) is-invalid @enderror"
                                                          id="full_description_{{ $code }}">{!! old('full_description.' . $code, $serviceData[$code]['full_description'] ?? null) !!}</textarea>
                                                @error('full_description.' . $code)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="submit" name="save" class="btn btn-primary">Save & Continue</button>
                        <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.querySelectorAll('.ckeditor').forEach(function (textarea) {
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

        function removeLogo(event) {
            event.preventDefault();

            const logoImage = document.getElementById('logoImage');
            if (logoImage) {
                logoImage.remove();
            }

            const logoText = document.querySelector('input[name="logo_text"]');
            if (logoText) {
                logoText.remove();
            }

            const logoInput = document.getElementById('logo');
            if (logoInput) {
                logoInput.value = '';
            }
        }

        function removeIcon(event) {
            event.preventDefault();

            const iconImage = document.getElementById('iconImage');
            if (iconImage) {
                iconImage.remove();
            }

            const iconText = document.querySelector('input[name="params_icon_text"]');
            if (iconText) {
                iconText.remove();
            }

            const iconInput = document.getElementById('params_icon');
            if (iconInput) {
                iconInput.value = '';
            }
        }

    </script>
@endsection

