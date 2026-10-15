@props(['icon' => 'close'])
<button {{ $attributes->merge(['type'=>'button','class'=>'ui-btn-secondary ui-icon-'.$icon]) }}>{{ $slot }}</button>
