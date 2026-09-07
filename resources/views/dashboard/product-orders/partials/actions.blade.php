<div class="row-actions">
    <a href="{{ route('dashboard.product-orders.show', $order->id) }}" title="View" aria-label="View"><i class="fa-solid fa-eye"></i></a>
    @if ($order->status === 'paid')
        <form method="POST" action="{{ route('dashboard.product-orders.resend', $order->id) }}" data-confirm="Send this buyer their download link again?">
            @csrf
            <button type="submit" title="Resend download link" aria-label="Resend download link"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
    @else
        <form method="POST" action="{{ route('dashboard.product-orders.mark-paid', $order->id) }}" data-confirm="Mark this order as paid? The buyer gets their download link straight away.">
            @csrf
            <button type="submit" title="Mark as paid" aria-label="Mark as paid"><i class="fa-solid fa-circle-check"></i></button>
        </form>
    @endif
    @include('dashboard.partials.row-delete', [
        'id' => $order->id,
        'title' => 'Delete this order?',
        'text' => 'Abandoned and failed checkouts only — a paid order is kept.',
    ])
</div>
