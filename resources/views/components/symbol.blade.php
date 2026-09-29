@props([
    'nama',
    'class' => 'text-[20px]',
])

{{--
    Ikon Material Symbols Outlined.

    Font-nya dimuat di <head> admin/layout.blade.php, jadi nama ikon
    ditulis sebagai teks ligature, misalnya "crisis_alert".
--}}
<span {{ $attributes->merge(['class' => 'material-symbols-outlined '.$class]) }} aria-hidden="true">{{ $nama }}</span>
