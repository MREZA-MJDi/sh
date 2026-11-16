@props(['name', 'id' => null])

@error($name)
    <div id="{{ $id ?? 'field-error-'.$name }}" class="field-error" role="alert">
        {{ $message }}
    </div>
@enderror
