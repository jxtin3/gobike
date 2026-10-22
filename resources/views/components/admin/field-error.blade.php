{{-- Validation message under a form field. Give the input aria-describedby="{name}-error". --}}
@props(['name', 'bag' => 'default'])
@error($name, $bag)
    <span class="field-error" id="{{ str_replace(['.', '[', ']'], '-', $name) }}-error">{{ $message }}</span>
@enderror
