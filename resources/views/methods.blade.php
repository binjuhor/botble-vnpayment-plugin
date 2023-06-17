@if (setting('payment_vnpay_status') == 1)
    <li class="list-group-item">
        <input class="magic-radio js_payment_method" type="radio" name="payment_method" id="payment_vnpay"
               @if ($selecting == VNPAY_PAYMENT_METHOD_NAME) checked @endif
               value="vnpay" data-bs-toggle="collapse" data-bs-target=".payment_vnpay_wrap" data-toggle="collapse" data-target=".payment_vnpay_wrap" data-parent=".list_payment_method">
        <label for="payment_vnpay" class="text-start">{{ setting('payment_vnpay_name', trans('plugins/vnpay::vnpay.payment_via_vnpay')) }}</label>
        <div class="payment_vnpay_wrap payment_collapse_wrap collapse @if ($selecting == VNPAY_PAYMENT_METHOD_NAME) show @endif" style="padding: 15px 0;">
            <p>{!! BaseHelper::clean(setting('payment_vnpay_description')) !!}</p>
        </div>
    </li>
@endif
