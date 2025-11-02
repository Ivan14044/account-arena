<div class="tab-pane fade show {{ $code == 'ru' ? 'active' : '' }}"
     id="tab_message_{{ $code }}" role="tabpanel">
    <div class="repeatable-blocks" data-lang="{{ $code }}">
        @php($entries = $contentData[$code] ?? [ [] ])

        @foreach($entries as $index => $entry)
            <div class="repeatable-group border p-2 mb-3 position-relative">
                <button type="button"
                        style="top: 0; right: 0; line-height: 1;"
                        class="btn btn-danger btn-sm p-1 px-2 m-2 position-absolute remove-block">
                    &times;
                </button>

                @foreach(config('contents.' . $content->code . '.fields') as $fieldKey => $field)
                    @php($fieldName = "fields[{$fieldKey}][{$code}][]")
                    @php($fieldId = "{$fieldKey}_{$code}_{$index}_{$loop->index}")
                    @php($value = old("{$fieldKey}.{$code}.{$index}", $entry[$fieldKey] ?? null))
                    @php($hasError = $errors->has("{$fieldKey}.{$code}.{$index}"))

                    <div class="form-group">
                        <label for="{{ $fieldId }}">{{ $field['label'] }}</label>

                        @if($field['type'] === 'string' && !($field['multiline'] ?? false))
                            <input type="text"
                                   name="{{ $fieldName }}"
                                   id="{{ $fieldId }}"
                                   class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                                   value="{{ $value }}">
                        @elseif($field['type'] === 'string' && ($field['multiline'] ?? false))
                            <textarea rows="3"
                                      name="{{ $fieldName }}"
                                      id="{{ $fieldId }}"
                                      class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                                      style="resize: none;">{{ $value }}</textarea>
                        @elseif($field['type'] === 'number')
                            <input type="number"
                                   name="{{ $fieldName }}"
                                   id="{{ $fieldId }}"
                                   class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                                   value="{{ $value }}"
                                   min="{{ $field['min'] ?? '' }}"
                                   max="{{ $field['max'] ?? '' }}">
                        @elseif($field['type'] === 'service')
                            <select
                                name="{{ $fieldName }}"
                                id="{{ $fieldId }}"
                                class="form-control {{ $hasError ? 'is-invalid' : '' }}">
                                <option value="">-- select service --</option>
                                @foreach(($services ?? []) as $service)
                                    @php($serviceName = $service->getTranslation('name', $code) ?? $service->name ?? ('Service #' . $service->id))
                                    <option value="{{ $service->id }}" @if((string)$value === (string)$service->id) selected @endif>
                                        {{ $serviceName }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif($field['type'] === 'file')
                            <input type="hidden" name="fields[{{ $fieldKey }}][{{ $code }}][]" value="{{ $value }}">
                            <input type="file"
                                   name="fields_file[{{ $fieldKey }}][{{ $code }}][]"
                                   id="{{ $fieldId }}"
                                   accept="{{ $field['accept'] ?? 'image/*' }}"
                                   class="form-control-file {{ $hasError ? 'is-invalid' : '' }}">
                            @if(!empty($value))
                                <div class="mt-2">
                                    <img src="{{ url($value) }}" alt="preview" class="img-fluid img-bordered" style="max-width: 150px;">
                                    <a href="#" class="d-block mt-1 text-danger remove-file">Delete</a>
                                </div>
                            @endif
                        @endif

                        @error("{$fieldKey}.{$code}.{$index}")
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-success add-block" data-lang="{{ $code }}">+ Add</button>
</div>
