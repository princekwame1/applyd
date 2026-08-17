@use('App\Models\Course')
@use('App\Support\PaystackFees')

{{--
    The payment-charge breakdown, shown wherever a price is quoted.

    Nothing renders at all when the charge isn't being passed on, so the same
    include is safe on every page whichever way the setting is left.

    $net — the price before the charge.
--}}
@if (PaystackFees::passedOn() && ($net ?? 0) > 0)
    <div class="fee-breakdown">
        <div class="fee-row">
            <span>{{ $label ?? 'Subtotal' }}</span>
            <span>{{ Course::money($net) }}</span>
        </div>
        <div class="fee-row">
            <span>Payment charge ({{ PaystackFees::label() }})</span>
            <span>{{ Course::money(PaystackFees::fee($net)) }}</span>
        </div>
        <div class="fee-row fee-row-total">
            <span>Total to pay</span>
            <strong>{{ Course::money(PaystackFees::gross($net)) }}</strong>
        </div>
    </div>
@endif
