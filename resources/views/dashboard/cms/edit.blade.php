@extends('layouts.admin')

@section('title', 'Edit '.$config['label'].' — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Edit: {{ $config['label'] }}</h1>
    <div style="display:flex; gap:10px;">
        @if (!empty($config['route']))
            <a class="btn btn-sm btn-outline" href="{{ route($config['route']) }}" target="_blank">View live ↗</a>
        @endif
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.cms') }}">All pages</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="error-box">Please fix the highlighted fields.</div>
@endif

<form method="POST" action="{{ route('dashboard.cms.update', $page) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @foreach ($config['sections'] as $sectionName => $section)
        <div class="card cms-section">
            <div class="card-head">
                <div><h3>{{ $sectionName }}</h3></div>
            </div>

            <div class="cms-fields">
                @foreach ($section['fields'] as $key => $field)
                    @php
                        $type = $field['type'] ?? 'text';
                        $value = cms($page, $key);
                        $inputName = 'fields['.$key.']';
                    @endphp
                    <div class="cms-field {{ $type === 'image' ? '' : 'span-2' }}">
                        <label class="field-label" for="f_{{ $key }}">{{ $field['label'] ?? $key }}</label>

                        @if ($type === 'textarea' || $type === 'richtext')
                            <textarea id="f_{{ $key }}" name="{{ $inputName }}" rows="4" data-rich>{{ old('fields.'.$key, $value) }}</textarea>
                        @elseif ($type === 'image')
                            <div class="file-field">
                                <span class="file-preview" data-preview-for="f_{{ $key }}">
                                    @php $img = cms_image($page, $key); @endphp
                                    @if ($img)<img src="{{ $img }}" alt="">@else<i class="fa-regular fa-image"></i>@endif
                                </span>
                                <label class="file-btn" for="f_{{ $key }}"><i class="fa-solid fa-upload"></i> Choose image</label>
                                <input type="file" id="f_{{ $key }}" name="{{ $inputName }}" accept="image/jpeg,image/png,image/webp" data-preview="f_{{ $key }}" data-max-kb="2048" hidden>
                                <span class="file-name" data-filename-for="f_{{ $key }}"></span>
                            </div>
                            <div class="upload-hint">JPG, PNG or WebP · max 2&nbsp;MB · leave empty to keep current</div>
                        @else
                            <input type="text" id="f_{{ $key }}" name="{{ $inputName }}" value="{{ old('fields.'.$key, $value) }}">
                        @endif
                        @error('fields.'.$key) <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div style="position:sticky; bottom:0; padding:16px 0; background:linear-gradient(to top, var(--bg-soft) 60%, transparent);">
        <button type="submit" class="btn btn-brand">Save changes</button>
    </div>
</form>
@include('partials.quill')
@endsection
