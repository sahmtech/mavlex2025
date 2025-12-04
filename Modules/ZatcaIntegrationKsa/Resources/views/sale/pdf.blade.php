<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title> {!! $receipt_details->invoice_no !!}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
        }

        .invoice-box {
            width: 100%;
            margin: auto;
            border-collapse: collapse;
        }

        .rtl {
            direction: rtl;
            text-align: right;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        table {
            width: 100%;
            margin-bottom: 10px;
        }

        td,
        th {
            padding: 6px;
            vertical-align: top;
        }

        .header td {
            font-weight: bold;
        }

        .gray-bg {
            background-color: #f2f2f2;
        }

        .border {
            border: 1px solid #ccc;
        }

        .border th {
            border: 1px solid #ccc;
        }

        /* Custom CSS for MPDF */
        .table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .table-bordered {
            border: 1px solid #ccc;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #ccc;
        }

    
    </style>

</head>

<body>
    <htmlpagefooter name="page-footer">
        <div style="text-align: center; font-size: 9pt;">
            Page {PAGENO} of {nbpg}
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="page-footer" value="on" />
    <table class="table">
        <tr>
            <td class="ltr" width="33.3%">
                <div class="width-50 f-left" align="center" style="color: #22489B;padding-top: 5px;">
                    <strong style="font-size: 20px;">
                        {!! $receipt_details->display_name !!}
                    </strong>
                    <div style="font-size: 14px;" align="center">
                        {!! $receipt_details->address !!}
                        @if (!empty($location_details->mobile) || !empty($location_details->alternate_number))
                            <br>
                            @lang('lang_v1.contact_no') :
                            {{ !empty($location_details->mobile) ? $location_details->mobile . ', ' : '' }}
                            {{ $location_details->alternate_number }}
                        @endif
                        @if (!empty($location_details->website))
                            <br>
                            @lang('lang_v1.website'):
                            <a href="{!! $location_details->website !!}" target="_blank" style="text-decoration: none;">
                                {!! $location_details->website !!}
                            </a>
                        @endif
                        @if (!empty($location_details->email))
                            @lang('business.email'): {!! $location_details->email !!}
                        @endif

                            @if(!empty($receipt_details->custom__fields))
                               @foreach($receipt_details->custom__fields as $label => $value)
                               <br>
                                <div>
                                    <strong>{{ $label }}:</strong> {{ $value }}
                                </div>
                                @endforeach
                            @endif
                        {{-- location custom info --}}

                        @if (!empty($receipt_details->location_custom_field_2_label) && !empty($receipt_details->location_custom_field_2_value))
                            <br>
                            {{ $receipt_details->location_custom_field_2_label }} : {!! $receipt_details->location_custom_field_2_value !!}
                        @endif

                        @if (!empty($receipt_details->location_custom_field_3_label) && !empty($receipt_details->location_custom_field_3_value))
                            <br>
                            {{ $receipt_details->location_custom_field_3_label }} : {!! $receipt_details->location_custom_field_3_value !!}
                        @endif

                        @if (!empty($receipt_details->location_custom_field_4_label) && !empty($receipt_details->location_custom_field_4_value))
                            <br>
                            {{ $receipt_details->location_custom_field_4_label }} : {!! $receipt_details->location_custom_field_4_value !!}
                        @endif
                    </div>
                </div>
            </td>
            <td class="center" width="33.3%" style="background-color: white;">
    @if (!empty($receipt_details->logo))
        <div style="display: flex; justify-content: center; background-color: white;">
            <!-- <img src="{{ $receipt_details->logo }}" alt="Logo" style="width: 100px; height: 100px;background-color:#fff;"> -->
       <img src="{{ $receipt_details->logo }}" 
     alt="Logo" 
     style="width:100px; height:100px; background-color:#fff; display:block;">

        </div>
    @endif
</td>

            <td class="center" width="33.3%">
                @if (!empty($zatca_qr_code))
                    <img
                        src="data:image/png;base64,{{ DNS2D::getBarcodePNG($zatca_qr_code, 'QRCODE',2.0,2.0, [0,0,0]) }}">
                @endif
            </td>
        </tr>
    </table>

    <h3 class="center">Tax Invoice / فاتورة ضريبية</h3>

    <table class="table table-bordered">
        <tr class="gray-bg">
            <td><strong>Invoice Number<br>رقم الفاتورة</strong></td>
            <td>
                @if (!empty($receipt_details->invoice_no))
                    {!! $receipt_details->invoice_no !!}
                @endif
            </td>
            <td><strong>Invoice Date<br>تاريخ الفاتورة</strong></td>
            <td>{!! $receipt_details->invoice_date !!}</td>
            <td><strong>Invoice Due Date<br>تاريخ استحقاق الفاتورة</strong></td>
        <td> {{@format_date($transaction->due_date) }}</td>
        <td><strong>Payment Terms<br>شروط الدفع</strong></td>
        <td>
            @if (!empty($transaction->pay_term_number) && !empty($transaction->pay_term_type))
                {{ ucfirst($transaction->pay_term_number) }} {{ ucfirst($transaction->pay_term_type) }}
            @else
                N/A
            @endif
        </td> 
        </tr>
    </table>

    <table class="table table-bordered">
        <tr class="gray-bg">
            <td width="50%"><strong>Seller / البائع</strong></td>
            <td width="50%"><strong>Buyer / المشتري</strong></td>
        </tr>
        <tr>

            @php
                $zatca = json_decode($location_details->zatca_details);
            @endphp

            <td class="border">
                <address>
                    {{ $zatca->organization_name ?? '' }}<br>
                    {{ $zatca->street_name ?? '' }}, {{ $zatca->building_number ?? '' }},
                    {{ $zatca->plot_identification ?? '' }}<br>
                    {{ $zatca->sub_division_name ?? '' }}, {{ $zatca->city_name ?? '' }},
                    {{ $zatca->postal_number ?? '' }}<br>
                    {{ $zatca->country_name ?? '' }} <br>
                    <!-- {{ json_decode($location_details->zatca_details)->vat_number ?? '' }}<br> -->
                      @if(!empty($receipt_details->business_tax_number))
                    {{ $receipt_details->business_tax_number }}<br/>
                @endif
                </address>
            </td>
            <td class="border">
                
                {!! ltrim($receipt_details->customer_info_address, '<br>') !!}
                @if (!empty($receipt_details->customer_custom_fields))
                    {!! $receipt_details->customer_custom_fields !!}<br>
                @endif
                 @if(!empty($receipt_details->customer_tax_number))
                        <br>    {{$receipt_details->customer_tax_number}}<br>
                 @endif
            </td>
        </tr>
    </table>

    <table class="table table-bordered">
        <tr class="gray-bg">
            <th>Seq<br>ترتيب</th>
            <th>Description<br>البيان</th>
            <th>Quantity<br>الكمية</th>
            <th>Unit Price<br>سعر الوحدة</th>
            <th>Disc<br>نسبة الخصم</th>
            <th>Taxes<br>الضريبة</th>
            <th>Tax Amount<br>مقدار الضريبة</th>
            {{-- <th>Amount<br>المبلغ</th> --}}
            <th>Total Price<br>السعر الكلي</th>
        </tr>
        @php
            $subtotal = 0;
            $is_empty_row_looped = true;
            $total_discount = 0;
            $total_tax = 0;
        @endphp
        @foreach ($receipt_details->lines as $line)
            <tr>
                <td>
                    {{ $loop->iteration }}
                </td>
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

                <td>
                    @php
                        $subtotal += ($line['unit_price_before_discount_uf'] ?? 0) * ($line['quantity_uf'] ?? 0);
                    @endphp
                    @format_currency($line['unit_price_before_discount_uf'] ?? 0)
                </td>
                 <td>
                    @format_currency($transactionUtil->get_sell_line_discount_amount($line['line_discount_type_uf'], $line['line_discount_amount_uf'], $line['unit_price_before_discount_uf']) * $line['quantity_uf'] ?? 0)
                </td>
                <td>
                    {{ $line['tax_percent'] ?? '' }}%
                </td>
                <td>
                    @php
                        $discountAmount = $transactionUtil->get_sell_line_discount_amount($line['line_discount_type_uf'], $line['line_discount_amount_uf'], $line['unit_price_before_discount_uf']);
                        $total_discount += round($discountAmount * ($line['quantity_uf'] ?? 0), 2);
                        $total_tax += (str_replace(',', '', $line['tax']) ?? 0) * (float) ($line['quantity_uf'] ?? 0);
                    @endphp
                   
                        @if(!empty($line['tax']))
      {{ (str_replace(',', '', $line['tax']) ) }}
   @endif
                </td>
                <td>
                    @format_currency($line['line_total_uf'] ?? 0)
                </td>
            </tr>
        @endforeach
        </tr>
    </table>
    <div style="width: 50%; float: right;">
        <table class="table table-bordered">
            <tr>
                <td>Subtotal / المجموع الفرعي</td>
                <td>@format_currency($subtotal)</td>
            </tr>
            <tr>
                <td>Total Discount / إجمالي الخصم</td>
                <td>@format_currency($total_discount)</td>
            </tr>
            <tr>
                <td>Net Amount / المبلغ الصافي</td>
                <td>@format_currency($receipt_details->total_unformatted - $total_tax)</td>
            </tr>
            <tr>
                <td>Total Tax / إجمالي الضريبة</td>
                <td>@format_currency($total_tax)</td>
            </tr>
            <tr>
                <td>Total Amount / المبلغ الإجمالي</td>
                <td>@format_currency($receipt_details->total_unformatted)</td>
            </tr>
        <tr>
            <td>Due Amount / المبلغ المستحق</td>
            <td>{!! $receipt_details->total_due !!}</td>
        </tr>
        </table>
        <p><strong>Invoiced Amount:</strong>{{ $transactionUtil->numberToCurrencyWords($receipt_details->total_unformatted, 'riyal', 'halala', 'en'); }}</p>
        <p><strong>مبلغ الفاتورة:</strong>{{ $transactionUtil->numberToCurrencyWords($receipt_details->total_unformatted, 'ريالًا و', ' هللة فقط', 'ar'); }}</p>
    </div>


     <div style="width: 50%; float: left;">
         <p style="margin:3px;">{!!$receipt_details->footer_text!!}</p> 
        </div>



</body>

</html>
