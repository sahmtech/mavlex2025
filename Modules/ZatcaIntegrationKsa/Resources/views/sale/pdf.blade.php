<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>{!! $receipt_details->invoice_no !!}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            direction: rtl;
            text-align: right;
            color: #333;
        }

        .table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }

        .gray-bg {
            background-color: #f2f2f2;
        }

        .center {
            text-align: center;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .rtl {
            direction: rtl;
            text-align: right;
        }

        .invoice-logo {
            width: 160px; 
            height: auto;
            max-height: 160px;
            display: block;
            margin: 0 auto;
            background-color: #fff;
        }

        .website-link {
            color: #000 !important;
            text-decoration: none;
            font-weight: bold;
        }

        .text-blue {
            /* color: #22489B; */
               color: #000 !important;
         
        }

        .font-14 { font-size: 14px; }
        .font-18 { font-size: 18px; }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9pt;
        }
    </style>
</head>

<body>
    <table class="table">
        <tr>
            <td width="35%" class="rtl">
                <div class="text-blue">
                    <strong class="font-18">{!! $receipt_details->display_name !!}</strong>
                    <div class="font-14" style="margin-top: 5px;">
                        {!! $receipt_details->address !!}
                        
                        @if (!empty($location_details->mobile))
                            <br>@lang('lang_v1.contact_no'): <span class="ltr">{{ $location_details->mobile }}</span>
                        @endif

                        @if (!empty($location_details->website))
                            <br>@lang('lang_v1.website'): 
                            <a href="{!! $location_details->website !!}" class="website-link" target="_blank">
                                {!! $location_details->website !!}
                            </a>
                        @endif

                        @if(!empty($receipt_details->custom__fields))
                            @foreach($receipt_details->custom__fields as $label => $value)
                                <br><strong>{{ $label }}:</strong> {{ $value }}
                            @endforeach
                        @endif
                    </div>
                </div>
            </td>

            <td width="30%" class="center">
                @if (!empty($receipt_details->logo))
                    <img src="{{ $receipt_details->logo }}" class="invoice-logo" alt="Logo">
                @endif
            </td>

            <td width="35%" class="center">
                @if (!empty($zatca_qr_code))
                    <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($zatca_qr_code, 'QRCODE', 2.0, 2.0, [0,0,0]) }}">
                @endif
            </td>
        </tr>
    </table>

    <h3 class="center">Tax Invoice / فاتورة ضريبية</h3>

    <table class="table table-bordered">
        <tr class="gray-bg">
            <td width="25%"><strong>Invoice Number<br>رقم الفاتورة</strong></td>
            <td width="25%">{!! $receipt_details->invoice_no !!}</td>
            <td width="25%"><strong>Invoice Date<br>تاريخ الفاتورة</strong></td>
            <td width="25%">{!! $receipt_details->invoice_date !!}</td>
        </tr>
        <tr>
            <td><strong>Due Date<br>تاريخ الاستحقاق</strong></td>
            <td>{{ @format_date($transaction->due_date) }}</td>
            <td><strong>Payment Terms<br>شروط الدفع</strong></td>
            <td>
                @if (!empty($transaction->pay_term_number))
                    {{ $transaction->pay_term_number }} {{ $transaction->pay_term_type }}
                @else N/A @endif
            </td>
        </tr>
    </table>

    <table class="table table-bordered">
        <tr class="gray-bg">
            <td width="50%"><strong>Seller / البائع</strong></td>
            <td width="50%"><strong>Buyer / المشتري</strong></td>
        </tr>
        <tr>
            <td>
                @php $zatca = json_decode($location_details->zatca_details); @endphp
                <strong>{{ $zatca->organization_name ?? '' }}</strong><br>
                {{ $zatca->street_name ?? '' }}, {{ $zatca->building_number ?? '' }}<br>
                {{ $zatca->city_name ?? '' }}, {{ $zatca->country_name ?? '' }}<br>
                الرقم الضريبي: {{ $receipt_details->business_tax_number }}
            </td>
            <td>
                {!! ltrim($receipt_details->customer_info_address, '<br>') !!}
                @if(!empty($receipt_details->customer_tax_number))
                    <br>الرقم الضريبي للعميل: {{$receipt_details->customer_tax_number}}
                @endif
            </td>
        </tr>
    </table>

   <table class="table table-bordered">
        <tr class="gray-bg">
            <th>Seq</th>
            <th>Description / البيان</th>
            <th>Qty / الكمية</th>
            <th>Unit Price / السعر</th>
            <th>Disc / خصم</th>
            <th>Tax %</th>
            <th>Tax / الضريبة</th>
            <th>Total / المجموع</th>
        </tr>
        @php
            $subtotal = 0;
            $total_discount = 0;
        @endphp
        @foreach ($receipt_details->lines as $line)
            @php
                $line_subtotal = ($line['unit_price_before_discount_uf'] ?? 0) * ($line['quantity_uf'] ?? 0);
                $subtotal += $line_subtotal;
                $discount = $transactionUtil->get_sell_line_discount_amount($line['line_discount_type_uf'], $line['line_discount_amount_uf'], $line['unit_price_before_discount_uf']) * ($line['quantity_uf'] ?? 0);
                $total_discount += round($discount, 2);
            @endphp
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                {{-- <td>
                    {!! $line['name'] !!} 
                    @if(!empty($line['sub_sku'])) <br><small>({{ $line['sub_sku'] }})</small> @endif
                </td>
                <td class="center">{{ $line['quantity'] }}</td> --}}
                <td>
                    {!! $line['name'] !!}
                    <br>
                    {!! $line['sub_sku'] ?? '' !!}
                   
                     @if(!empty($line['product_description']))
                            <br><small>{!!$line['product_description']!!}</small> <br>
                        @endif
                        @if(!empty($line['sell_line_note']))
                            <br><small>{!!$line['sell_line_note']!!}</small> <br>
                        @endif
                </td>
                <td>
                    {!! $line['quantity'] ?? '' !!} ({{ $line['units'] ?? '' }})
                </td>
                <td class="ltr">@format_currency($line['unit_price_before_discount_uf'])</td>
                <td class="ltr">@format_currency($discount)</td>
                <td class="center">{{ $line['tax_percent'] }}%</td>
                <td class="ltr">{{ (str_replace(',', '', $line['tax'])) }}</td>
                <td class="ltr">@format_currency($line['line_total_uf'])</td>
            </tr>
        @endforeach
    </table>

    <div style="width: 45%; float: left;">
        <table class="table table-bordered">
            <tr>
                <td class="gray-bg">Subtotal / المجموع الفرعي</td>
                <td class="ltr">@format_currency($subtotal)</td>
            </tr>
            <tr>
                <td class="gray-bg">Total Discount / إجمالي الخصم</td>
                <td class="ltr">@format_currency($total_discount)</td>
            </tr>
            <tr>
                {{-- تم التعديل هنا ليطابق حساباتك الصافية --}}
                <td class="gray-bg">Net Amount / المبلغ الصافي</td>
                <td class="ltr">@format_currency($subtotal)</td> 
            </tr>
            <tr>
                {{-- الضريبة = الإجمالي - الصافي --}}
                <td class="gray-bg">Total Tax / إجمالي الضريبة</td>
                <td class="ltr">@format_currency($receipt_details->total_unformatted - $subtotal)</td>
            </tr>
            <tr style="font-weight: bold; font-size: 11pt;">
                <td class="gray-bg">Total Amount / المجموع الكلي</td>
                <td class="ltr">@format_currency($receipt_details->total_unformatted)</td>
            </tr>
            <tr>
                <td class="gray-bg">Due Amount / المبلغ المستحق</td>
                <td class="ltr">{!! $receipt_details->total_due !!}</td>
            </tr>
        </table>
    </div>

   <div style="width: 50%; float: right; font-size: 9pt;">
        <p><strong>Invoiced Amount:</strong> {{ $transactionUtil->numberToCurrencyWords($receipt_details->total_unformatted, 'riyal', 'halala', 'en') }}</p>
        <p><strong>مبلغ الفاتورة:</strong> {{ $transactionUtil->numberToCurrencyWords($receipt_details->total_unformatted, 'ريالًا و', ' هللة فقط', 'ar') }}</p>
        
        <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
            {!! $receipt_details->footer_text !!}
        </div>
    </div>

    <footer name="page-footer">
        <hr>
        Page {PAGENO} of {nbpg}
    </footer>
</body>
</html>