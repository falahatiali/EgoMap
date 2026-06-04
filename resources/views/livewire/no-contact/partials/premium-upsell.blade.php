@php
    $variant = $upsell['variant'] ?? 'first';
    $discount = (int) ($upsell['discount_percent'] ?? 40);
    $price = $upsell['price_display'] ?? '';
@endphp

<aside
    class="eg-gm-premium-upsell eg-glass mb-4"
    role="complementary"
    aria-label="{{ __('no_contact.premium_upsell_aria') }}"
    wire:key="premium-upsell-{{ $variant }}"
>
    <div class="eg-gm-premium-upsell__glow" aria-hidden="true"></div>
    <div class="eg-gm-premium-upsell__body">
        <p class="eg-gm-premium-upsell__title mb-2">{{ __('no_contact.premium_upsell_title') }}</p>
        <p class="eg-gm-premium-upsell__text small eg-text-muted mb-3">{{ __('no_contact.premium_upsell_body') }}</p>
        <p class="eg-gm-premium-upsell__offer small mb-0">
            @if ($variant === 'reminder')
                {{ __('no_contact.premium_upsell_offer_reminder', ['percent' => eg_num($discount), 'price' => $price]) }}
            @else
                {{ __('no_contact.premium_upsell_offer_first', ['percent' => eg_num($discount), 'price' => $price]) }}
            @endif
        </p>
    </div>
    <div class="eg-gm-premium-upsell__actions">
        <button
            type="button"
            class="eg-gm-premium-upsell__cta eg-transition"
            wire:click="redirectToCheckout"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="redirectToCheckout">{{ __('no_contact.premium_upsell_cta') }}</span>
            <span wire:loading wire:target="redirectToCheckout">…</span>
        </button>
        <button
            type="button"
            class="eg-gm-premium-upsell__defer eg-transition"
            wire:click="deferPremiumUpsell"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="deferPremiumUpsell">{{ __('no_contact.premium_upsell_defer') }}</span>
            <span wire:loading wire:target="deferPremiumUpsell">…</span>
        </button>
    </div>
</aside>
