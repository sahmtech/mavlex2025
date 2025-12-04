@extends('layouts.app')
@section('title', __('lang_v1.credit_notes'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('lang_v1.credit_notes')</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">

        {!! Form::open([
            'url' => action([\App\Http\Controllers\CreditNotesController::class, 'store']),
            'method' => 'post',
            'id' => 'credit_notes_form',
        ]) !!}
        {!! Form::hidden('transaction_id', $sell->id) !!}
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">@lang('lang_v1.parent_sale')</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
                        <strong>@lang('messages.date'):</strong> {{ @format_date($sell->transaction_date) }}
                    </div>
                    <div class="col-sm-4">
                        <strong>@lang('contact.customer'):</strong> {{ $sell->contact->supplier_business_name?? $sell->contact->name }} <br>
                        <input name="customer_id" type="hidden" value="{{ $sell->contact->id }}">

                        <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
                        <input name="location_id" type="hidden" value="{{ $sell->location->id }}">
             
                    </div>
                    <div class="col-sm-4">
                        <strong>@lang('sale.total_amount'):</strong>
                        <span class="final-total" data-orig-value="{{ $sell->final_total }}">@format_currency($sell->final_total)</span>
                        <br>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-solid">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                           <label for="credit_note_number"><strong>@lang('lang_v1.credit_note_number'):</strong></label>
              
            <input type="text" name="credit_note_number" id="credit_note_number" class="form-control" value="{{ old('credit_note_number', $sell->credit_note_number ?? '') }}">

                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                        <label for="credit_note_date"><strong>@lang('lang_v1.credit_note_date'):</strong></label>
                 <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                @php
                                    $transaction_date = !empty($sell->return_parent->transaction_date)
                                        ? $sell->return_parent->transaction_date
                                        : 'now';
                                @endphp
                                {!! Form::text('transaction_date', @format_datetime($transaction_date), [
                                    'class' => 'form-control',
                                    'readonly',
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <table class="table bg-gray" id="credit_notes_table">
                            <thead>
                                 <tr style="background-color: #2db4ce !important;">
                <th>#</th>
                <th>@lang('product.item_name')</th>
                <th>@lang('lang_v1.description')</th>
                <th>@lang('sale.unit_price')</th>
                <th>@lang('lang_v1.sell_quantity')</th>
                <th>@lang('sale.tax')</th>
                <th>@lang('sale.subtotal_inc_tax')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sell->sell_lines as $sell_line)
                                    @php
                                        $check_decimal = 'false';
                                        if ($sell_line->product->unit->allow_decimal == 0) {
                                            $check_decimal = 'true';
                                        }

                                        $unit_name = $sell_line->product->unit->short_name;

                                        if (!empty($sell_line->sub_unit)) {
                                            $unit_name = $sell_line->sub_unit->short_name;

                                            if ($sell_line->sub_unit->allow_decimal == 0) {
                                                $check_decimal = 'true';
                                            } else {
                                                $check_decimal = 'false';
                                            }
                                        }

                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $sell_line->product->name }}
                                            @if ($sell_line->product->type == 'variable')
                                                - {{ $sell_line->variations->product_variation->name }}
                                                - {{ $sell_line->variations->name }}
                                            @endif
                                            <br>
                                            {{ $sell_line->variations->sub_sku }}
                                              <input type="hidden" name="products[{{ $loop->index }}][name]" value="{{ $sell_line->product->name }}" class="form-control" placeholder="@lang('product.item_name')">
           
                                        </td>
                                         <td>{!! $sell_line->product->product_description !!}

                    <input type="hidden" name="products[{{ $loop->index }}][description]" value="{{$sell_line->product->product_description}}" class="form-control" placeholder="@lang('lang_v1.description')">

                                         </td>
                                        <td><span class="display_currency" 
                                               
                                                data-currency_symbol="true"> {{$sell_line->unit_price }}</span>
               
                                        </td>
                                        <td>{{ $sell_line->formatted_qty }} {{ $unit_name }}

                                        <input type="hidden" name="products[{{ $loop->index }}][quantity]"
                                                value="{{  $sell_line->formatted_qty  }}"
                                                class="form-control input-sm input_number return_qty input_quantity"
                                                data-rule-abs_digit="{{ $check_decimal }}"
                                                data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
                                                data-rule-max-value="{{ $sell_line->quantity }}"
                                                data-msg-max-value="@lang('validation.custom-messages.quantity_not_available', ['qty' => $sell_line->formatted_qty, 'unit' => $unit_name])">
                                       
                                        </td>

                                        <td>
                                            {{ $sell_line->item_tax }}
                                                 <!-- <input name="products[{{ $loop->index }}][unit_price_inc_tax]" type="hidden"
                                                class="unit_price"
                                                value="{{ @num_format($sell_line->unit_price_inc_tax) }}">
                                            <input name="products[{{ $loop->index }}][sell_line_id]" type="hidden"
                                                value="{{ $sell_line->id }}">
                                            <input name="products[{{ $loop->index }}][unit_price]" type="hidden"
                                                value="{{ $sell_line->unit_price }}"> -->

												  <input name="products[{{ $loop->index }}][unit_price_inc_tax]" type="hidden"
                                                class="unit_price_inc_tax"
                                                value="{{ @num_format($sell_line->unit_price_inc_tax) }}">
                                            <input name="products[{{ $loop->index }}][sell_line_id]" type="hidden"
                                                value="{{ $sell_line->id }}">
                                            <input name="products[{{ $loop->index }}][unit_price]" type="hidden"
                                                value="{{ $sell_line->unit_price }}" class="unit_price">

												 <input name="products[{{ $loop->index }}][unit_price]" type="hidden"
                                                value="{{ $sell_line->unit_price }}" class="unit_price">

												 <input name="products[{{ $loop->index }}][item_tax]" class="item_tax" type="hidden"
                                                value="{{ $sell_line->item_tax }}">
												 <input name="products[{{ $loop->index }}][tax_id]" type="hidden"
                                                value="{{ $sell_line->tax_id }}" >
                                        </td>
                                        <td>
                                            <div class="return_subtotal"></div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    @php
                        $discount_type = !empty($sell->return_parent->discount_type)
                            ? $sell->return_parent->discount_type
                            : $sell->discount_type;
                        $discount_amount = !empty($sell->return_parent->discount_amount)
                            ? $sell->return_parent->discount_amount
                            : $sell->discount_amount;
                    @endphp
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('discount_type', __('purchase.discount_type') . ':') !!}
                            {!! Form::select(
                                'discount_type',
                                ['' => __('lang_v1.none'), 'fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')],
                                $discount_type,
                                ['class' => 'form-control'],
                            ) !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('discount_amount', __('purchase.discount_amount') . ':') !!}
                            {!! Form::text('discount_amount', @num_format($discount_amount), ['class' => 'form-control input_number']) !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('adjustment_title', __('lang_v1.adjustment_title') . ':') !!}
                            {!! Form::text('adjustment_title', $sell->return_parent->adjustment_title ?? null, [
                                'class' => 'form-control',
                                'placeholder' => __('lang_v1.enter_adjustment_title'),
                                'id' => 'adjustment_title',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('adjustment_amount', __('lang_v1.adjustment_amount') . ':') !!}
                            {!! Form::text('adjustment_amount', @num_format($sell->return_parent->adjustment_amount ?? 0), [
                                'class' => 'form-control input_number',
                                'id' => 'adjustment_amount',
                                'placeholder' => __('lang_v1.enter_adjustment_amount'),
                            ]) !!}
                        </div>
                    </div>
                </div>


                @php
                    $tax_percent = 0;
                    if (!empty($sell->tax)) {
                        $tax_percent = $sell->tax->amount;
                    }
                @endphp
                {!! Form::hidden('tax_id', $sell->tax_id) !!}
                {!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']) !!}
                {!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']) !!}
            <div class="row">
       @php
                    $tax_percent = 0;
                  
                @endphp
            
                {!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']) !!}
                {!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']) !!}
                    <div class="col-sm-12 text-right">
                        <strong>@lang('lang_v1.total_return_discount'):</strong>
                        &nbsp;(-) <span id="total_return_discount"></span>
                    </div>
                    <div class="col-sm-12 text-right">
                        <strong>@lang('lang_v1.total_return_tax') - @if (!empty($sell->tax))
                                ({{ $sell->tax->name }} - {{ $sell->tax->amount }}%)
                            @endif : </strong>
                        &nbsp;(+) <span id="total_return_tax"></span>
                    </div>
                    <div class="col-sm-12 text-right">
                        <strong id="adjustment_label">@lang('lang_v1.adjustment_default_title'): </strong>&nbsp;
                        <span id="adjustment_value">0</span>
                    </div>
                    <div class="col-sm-12 text-right">
                        <strong>@lang('lang_v1.return_total'): </strong>&nbsp;
                        <span id="net_return">0</span>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
                    </div>
                </div>
        </div>
       

        {!! Form::close() !!}

    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/credit_notes.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('form#credit_notes_form').validate();
            update_credit_notes_total();
            //Date picker
            // $('#transaction_date').datepicker({
            //     autoclose: true,
            //     format: datepicker_date_format
            // });
        });
        $(document).on('change',
            'input.return_qty, #discount_amount, #discount_type, #adjustment_title, #adjustment_amount',
            function() {
                update_credit_notes_total()
            });

        function update_credit_notes_total() {
            var net_return = 0;

            $('table#credit_notes_table tbody tr').each(function() {
                var quantity = __read_number($(this).find('input.return_qty'));
                var unit_price = __read_number($(this).find('input.unit_price'));
                var item_tax = __read_number($(this).find('input.item_tax'));

                
                var subtotal = quantity * unit_price;
				console.log(subtotal);
				
                $(this).find('.return_subtotal').text(__currency_trans_from_en(subtotal+item_tax, true));
                net_return += subtotal;
            });

            var discount = 0;
            if ($('#discount_type').val() == 'fixed') {
                discount = __read_number($("#discount_amount"));
            } else if ($('#discount_type').val() == 'percentage') {
                var discount_percent = __read_number($("#discount_amount"));
                discount = __calculate_amount('percentage', discount_percent, net_return);
            }
            var discounted_net_return = net_return - discount;

            var tax_percent = $('input#tax_percent').val();
            var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
            var net_return_inc_tax = total_tax + discounted_net_return;

            var adjustment_title = $('#adjustment_title').val();
            var adjustment_amount = __read_number($('#adjustment_amount'));

            $('#adjustment_label').text(adjustment_title ? adjustment_title + ': ' : '@lang('lang_v1.adjustment_default_title')' + ': ');
            $('#adjustment_value').text(__currency_trans_from_en(adjustment_amount, true));

            var net_return_with_adjustment = net_return_inc_tax - adjustment_amount;

            $('input#tax_amount').val(total_tax);
            $('span#total_return_discount').text(__currency_trans_from_en(discount, true));
            $('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
            $('span#net_return').text(__currency_trans_from_en(net_return_with_adjustment, true));
        }
    </script>
@endsection
