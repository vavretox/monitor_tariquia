@props(['active'])
@php($classes=($active??false)
    ? 'main-nav-link is-active inline-flex items-center px-3 text-sm font-bold transition'
    : 'main-nav-link inline-flex items-center px-3 text-sm font-semibold transition')
<a {{ $attributes->merge(['class'=>$classes]) }}>{{ $slot }}</a>