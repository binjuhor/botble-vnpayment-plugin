@php $payPalStatus = setting('payment_vnpay_status'); @endphp
<table class="table payment-method-item">
    <tbody>
    <tr class="border-pay-row">
        <td class="border-pay-col"><i class="fa fa-theme-payments"></i></td>
        <td style="width: 20%;">
            <img class="filter-black" src="{{ url('vendor/core/plugins/vnpay/images/vnpay.svg') }}" alt="VNPay">
        </td>
        <td class="border-right">
            <ul>
                <li>
                    <a href="https://vnpay.vn" target="_blank">VNPay</a>
                    <p>{{ trans('plugins/vnpay::vnpay.vnpay_description') }}</p>
                </li>
            </ul>
        </td>
    </tr>
    <tr class="bg-white">
        <td colspan="3">
            <div class="float-start" style="margin-top: 5px;">
                <div class="payment-name-label-group  @if ($payPalStatus== 0) hidden @endif">
                    <span class="payment-note v-a-t">{{ trans('plugins/payment::payment.use') }}:</span> <label class="ws-nm inline-display method-name-label">{{ setting('payment_vnpay_name') }}</label>
                </div>
            </div>
            <div class="float-end">
                <a class="btn btn-secondary toggle-payment-item edit-payment-item-btn-trigger @if ($payPalStatus == 0) hidden @endif">{{ trans('plugins/payment::payment.edit') }}</a>
                <a class="btn btn-secondary toggle-payment-item save-payment-item-btn-trigger @if ($payPalStatus == 1) hidden @endif">{{ trans('plugins/payment::payment.settings') }}</a>
            </div>
        </td>
    </tr>
    <tr class="vnpay-online-payment payment-content-item hidden">
        <td class="border-left" colspan="3">
            {!! Form::open() !!}
            {!! Form::hidden('type', VNPAY_PAYMENT_METHOD_NAME, ['class' => 'payment_type']) !!}
            <div class="row">
                <div class="col-sm-6">
                    <ul>
                        <li>
                            <label>{{ trans('plugins/payment::payment.configuration_instruction', ['name' => 'VNPay']) }}</label>
                        </li>
                        <li class="payment-note">
                            <p>{{ trans('plugins/payment::payment.configuration_requirement', ['name' => 'VNPay']) }}:</p>
                            <ul class="m-md-l" style="list-style-type:decimal">
                                <li style="list-style-type:decimal">
                                    <a href="https://doitac.vnpay.vn/login" target="_blank">
                                        {{ trans('plugins/payment::payment.service_registration', ['name' => 'VNPay']) }}
                                    </a>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ trans('plugins/vnpay::vnpay.after_service_registration_msg', ['name' => 'VNPay']) }}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ trans('plugins/vnpay::vnpay.enter_client_id_and_secret') }}</p>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="col-sm-6">
                    <div class="well bg-white">
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="vnpay_name">{{ trans('plugins/payment::payment.method_name') }}</label>
                            <input type="text" class="next-input input-name" name="payment_vnpay_name" id="vnpay_name" data-counter="400" value="{{ setting('payment_vnpay_name', trans('plugins/payment::payment.pay_online_via', ['name' => 'VNPay'])) }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_vnpay_description">{{ trans('core/base::forms.description') }}</label>
                            <textarea class="next-input" name="payment_vnpay_description" id="payment_vnpay_description">{{ get_payment_setting('description', 'vnpay', __('You will be redirected to VNPay to complete the payment.')) }}</textarea>
                        </div>
                        <p class="payment-note">
                            {{ trans('plugins/payment::payment.please_provide_information') }} <a target="_blank" href="https://vnpay.vn">VNPay</a>:
                        </p>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="vnpay_client_url">{{ trans('plugins/vnpay::vnpay.vnpay_url') }}</label>
                            <input type="text" class="next-input" name="payment_vnpay_client_url" id="vnpay_client_url" value="{{ setting('payment_vnpay_client_url') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_vnpay_tmncode">{{ trans('plugins/vnpay::vnpay.vnpay_tmncode') }}</label>
                            <input type="text" class="next-input" name="payment_vnpay_tmncode" id="payment_vnpay_tmncode" value="{{ setting('payment_vnpay_tmncode') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_vnpay_secret">{{ trans('plugins/vnpay::vnpay.vnpay_secret') }}</label>
                            <div class="input-option">
                                <input type="password" class="next-input" placeholder="••••••••" id="payment_vnpay_secret" name="payment_vnpay_secret" value="{{ setting('payment_vnpay_secret') }}">
                            </div>
                        </div>
                        {!! apply_filters(PAYMENT_METHOD_SETTINGS_CONTENT, null, 'vnpay') !!}
                    </div>
                </div>
            </div>
            <div class="col-12 bg-white text-end">
                <button class="btn btn-warning disable-payment-item @if ($payPalStatus == 0) hidden @endif" type="button">{{ trans('plugins/payment::payment.deactivate') }}</button>
                <button class="btn btn-info save-payment-item btn-text-trigger-save @if ($payPalStatus == 1) hidden @endif" type="button">{{ trans('plugins/payment::payment.activate') }}</button>
                <button class="btn btn-info save-payment-item btn-text-trigger-update @if ($payPalStatus == 0) hidden @endif" type="button">{{ trans('plugins/payment::payment.update') }}</button>
            </div>
            {!! Form::close() !!}
        </td>
    </tr>
    </tbody>
</table>
