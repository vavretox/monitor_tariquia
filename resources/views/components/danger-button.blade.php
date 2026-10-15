@props(['icon' => 'delete'])
<button {{ $attributes->merge(['type'=>'submit','class'=>'ui-btn-danger ui-icon-'.$icon]) }}>{{ $slot }}</button>
