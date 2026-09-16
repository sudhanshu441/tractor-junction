{{--
    Shown where a machine has no photograph yet.

    Original artwork, not a grey box: an empty-looking card makes the whole
    catalogue look broken, and a drawn tractor reads as "no photo yet" rather
    than "this page failed". Replace it by uploading real photographs — see
    docs/19-IMAGE-SOURCING.md.
--}}
<img src="{{ asset('assets/brand/machines/tractor.svg') }}"
     alt="{{ $alt ?? __('No photograph uploaded yet') }}"
     loading="lazy" decoding="async"
     style="width:100%; height:100%; object-fit:cover; display:block;">
