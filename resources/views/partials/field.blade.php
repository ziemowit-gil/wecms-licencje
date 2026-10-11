{{-- Pole formularza z etykietą, podpowiedzią i błędem powiązanymi przez aria-describedby. --}}
@php
    $id = $id ?? 'f-'.$name;
    $type = $type ?? 'text';
    $describedBy = trim(($hint ?? null ? $id.'-hint ' : '').($errors->has($name) ? $id.'-error' : ''));
@endphp
<div class="field">
    <label for="{{ $id }}">{{ $label }}@if ($required ?? false) <span aria-hidden="true">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows ?? 4 }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($errors->has($name)) aria-invalid="true" @endif>{{ old($name, $value ?? '') }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @if ($required ?? false) required @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($errors->has($name)) aria-invalid="true" @endif>
            @isset($placeholder)<option value="">{{ $placeholder }}</option>@endisset
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) old($name, $value ?? '') === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value ?? '') }}"
            @if ($required ?? false) required @endif
            @isset($autocomplete) autocomplete="{{ $autocomplete }}" @endisset
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($errors->has($name)) aria-invalid="true" @endif>
    @endif
    @if ($hint ?? null)<span id="{{ $id }}-hint" class="hint">{{ $hint }}</span>@endif
    @error($name)<span id="{{ $id }}-error" class="error-text">{{ $message }}</span>@enderror
</div>
