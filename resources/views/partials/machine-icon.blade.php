{{--
    Shown where a machine has no photograph yet.

    A real photograph, not a grey box: an empty-looking card makes the whole
    catalogue look broken. It is a stand-in and never a claim about the machine
    on the card — the alt text says so, so a screen reader and an image crawler
    both read it as "no photo yet" rather than as this model's picture, and
    JsonLd deliberately leaves `image` null rather than publishing it.

    The stand-in shows a branded machine, so anywhere it renders large enough to
    be mistaken for the real thing — a detail hero beside a firm price — pass
    $caption and a sighted buyer is told in words too.

    Replace it by uploading real photographs — see docs/19-IMAGE-SOURCING.md.

    Optional: $alt (what the card is about), $sizes (layout width hint),
    $caption (show the visible stand-in note), $hero (it is the page's largest
    paintable element, so fetch it eagerly rather than behind the grid).
--}}
<picture style="display:block; width:100%; height:100%;">
    <source type="image/webp"
            srcset="{{ asset('assets/brand/machines/tractor-640.webp') }} 640w, {{ asset('assets/brand/machines/tractor-1280.webp') }} 1280w"
            sizes="{{ $sizes ?? '(max-width: 767px) 100vw, 420px' }}">
    <img src="{{ asset('assets/brand/machines/tractor.jpg') }}"
         alt="{{ isset($alt) ? __('No photograph uploaded yet for :machine', ['machine' => $alt]) : __('No photograph uploaded yet') }}"
         width="800" height="600"
         loading="{{ empty($hero) ? 'lazy' : 'eager' }}" fetchpriority="{{ empty($hero) ? 'auto' : 'high' }}"
         decoding="async"
         style="width:100%; height:100%; object-fit:cover; display:block;">
</picture>
@if (! empty($caption))
    <span class="kj-photo-note">{{ __('Sample photograph — no picture of this machine has been uploaded yet.') }}</span>
@endif
