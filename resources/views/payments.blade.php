<ul>
    @foreach($payments->payments as $payment)
        <li>
            @include('plugins/vnpay::detail', compact('payment'))
        </li>
    @endforeach
</ul>
