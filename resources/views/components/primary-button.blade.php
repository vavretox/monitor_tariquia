@props(['icon' => 'save'])
<button {{ $attributes->merge(['type'=>'submit','class'=>'ui-btn-primary ui-icon-'.$icon]) }}>{{ $slot }}</button>
