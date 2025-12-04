@extends('layouts.app')
@section('title', __('lang_v1.credit_notes'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('lang_v1.credit_notes')</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print" >

      
        {!! Form::open([
            'url' => action([\App\Http\Controllers\CreditNotesController::class, 'store']),
            'method' => 'post',
            'id' => 'credit_notes_form',
        ]) !!}
      
        <div class="box box-solid" style="    padding: 20px;">
            <!-- <div class="box-header">
                <h3 class="box-title">@lang('lang_v1.parent_sale')</h3>
            </div> -->
            <div class="box-body">
 <div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="customer_id"><strong>@lang('contact.customer'):</strong></label>
            <select name="customer_id" id="customer_id" class="form-control select2">
                <option value="">@lang('messages.please_select')</option>
                @foreach($customers as $id => $name)
                    <option value="{{ $id }}" {{ isset($sell) && $sell->contact_id == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="credit_note_number"><strong>@lang('lang_v1.credit_note_number'):</strong></label>
            <input type="text" name="credit_note_number" id="credit_note_number" class="form-control" value="{{ old('credit_note_number', $sell->credit_note_number ?? '') }}">
        </div>
    </div>

    <div class="col-sm-6">
         <div class="form-group">
            <label for="location_id"><strong>@lang('purchase.business_location'):</strong></label>
            <select name="location_id" id="location_id" class="form-control select2">
                <option value="">@lang('messages.please_select')</option>
                @foreach($business_locations as $id => $name)
                    <option value="{{ $id }}" {{ isset($sell) && $sell->location_id == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="credit_note_date"><strong>@lang('lang_v1.credit_note_date'):</strong></label>
          <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                             
                      {!! Form::text('transaction_date', now()->format('m/d/Y h:i A'), [
    'class' => 'form-control datetimepicker',
    'required',
]) !!}


                            </div>  </div>
    </div>
</div>

            </div>


       
 <div class="col-sm-12" style="    padding: 0;">
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
                <th>@lang('messages.action')</th>
            </tr>
        </thead>
        <tbody>
            <tr data-row="0">
                <td class="row_index">1</td>

               <td>
                    <input type="text" name="products[0][name]" class="form-control" placeholder="@lang('product.item_name')">
                </td>

                <td>
                    <input type="text" name="products[0][description]" class="form-control" placeholder="@lang('lang_v1.description')">
                </td>

                <td>
                    <input type="text" name="products[0][unit_price]" class="form-control input_number unit_price" placeholder="@lang('sale.unit_price')">
                </td>
   <td>
                    <input type="text" name="products[0][quantity]" class="form-control input_number return_qty" placeholder="@lang('lang_v1.sell_quantity')">
                </td>

                <td>
                    <select name="products[0][tax_id]" class="form-control select2">
                        @foreach ($taxes['tax_rates'] as $id => $tax_label)
                            <option value="{{ $id }}"
                                @isset($taxes['attributes'][$id])
                                    data-rate="{{ $taxes['attributes'][$id]['data-rate'] ?? '' }}"
                                    data-min_amount="{{ $taxes['attributes'][$id]['data-min_amount'] ?? '' }}"
                                    @if(isset($taxes['attributes'][$id]['data-tax2_rate']))
                                        data-tax2_rate="{{ $taxes['attributes'][$id]['data-tax2_rate'] }}"
                                        data-tax2_min_amount="{{ $taxes['attributes'][$id]['data-tax2_min_amount'] }}"
                                    @endif
                                @endisset
                            >
                                {{ $tax_label }}
                            </option>
                        @endforeach
                    </select>
                </td>
   <td>
                    <div class="return_subtotal_display"></div>
                    <input type="hidden" name="products[0][unit_price_inc_tax]" class="return_subtotal" value="0">
                    <input type="hidden" name="products[0][item_tax]" class="item_tax" value="0">
                </td>

                <td>
                    <button type="button" class="btn btn-danger btn-sm remove_row">@lang('messages.delete')</button>
                </td>
            </tr>
        </tbody>
    </table>

    <button type="button" class="btn btn-success" id="add_credit_note_row">@lang('messages.add_row')</button>
</div>






                    <div class="row">
        <div class="col-sm-4">
        <div class="form-group">
            <label for="discount_type">{{ __('purchase.discount_type') }}:</label>
           {!! Form::select(
    'discount_type',
    ['' => __('lang_v1.none'), 'fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')],
    '',
    ['class' => 'form-control', 'id' => 'discount_type'],
) !!}

        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="discount_amount">{{ __('purchase.discount_amount') }}:</label>
            <input type="text" name="discount_amount" id="discount_amount" class="form-control input_number" value="">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            <label for="adjustment_title">{{ __('lang_v1.adjustment_title') }}:</label>
            <input 
                type="text" 
                name="adjustment_title" 
                id="adjustment_title" 
                class="form-control" 
                placeholder="{{ __('lang_v1.enter_adjustment_title') }}" 
                value="">
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="adjustment_amount">{{ __('lang_v1.adjustment_amount') }}:</label>
            <input 
                type="text" 
                name="adjustment_amount" 
                id="adjustment_amount" 
                class="form-control input_number" 
                placeholder="{{ __('lang_v1.enter_adjustment_amount') }}" 
                value="">
        </div>
    </div>
</div>




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
          

$('.datetimepicker').datetimepicker({
    format: 'm/d/Y h:i A',
    step: 5
});


$(document).on('click', '#add_credit_note_row', function() {
    var tbody = $('#credit_notes_table tbody');
    var lastRow = tbody.find('tr:last');

     lastRow.find('select.select2').select2('destroy');

    var rowIndex = parseInt(lastRow.attr('data-row')) + 1;
    var newRow = lastRow.clone();

    newRow.attr('data-row', rowIndex);
    newRow.find('.row_index').text(rowIndex + 1);

    newRow.find('input, select').each(function() {
        var name = $(this).attr('name');
        if(name) {
            name = name.replace(/\d+/, rowIndex);
            $(this).attr('name', name);
        }
        if($(this).is('input')) {
            $(this).val('');
        } else if($(this).is('select')) {
            $(this).val('');
        }
    });

    tbody.append(newRow);

   tbody.find('select.select2').select2();
});

    $(document).on('click', '.remove_row', function() {
        if($('#credit_notes_table tbody tr').length > 1) {
            $(this).closest('tr').remove();
            $('#credit_notes_table tbody tr').each(function(index) {
                $(this).attr('data-row', index);
                $(this).find('.row_index').text(index + 1);
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if(name) {
                        name = name.replace(/\d+/, index);
                        $(this).attr('name', name);
                    }
                });
            });
        }
    });

    $(document).on('input change', 'input.return_qty, input.unit_price, select[name*="[tax_id]"]', function() {
        update_credit_notes_total();
    });


    $('form#credit_notes_form').validate();
    update_credit_notes_total();
});

$(document).on('input change', 
    'input.return_qty, input.unit_price, select[name*="[tax_id]"], #discount_amount, #discount_type, #adjustment_title, #adjustment_amount',
    function() {
        update_credit_notes_total();
    }
);

function update_credit_notes_total() {
    var net_return = 0;
    var total_tax = 0;

    $('table#credit_notes_table tbody tr').each(function() {
        var quantity = __read_number($(this).find('input.return_qty'));
        var unit_price = __read_number($(this).find('input.unit_price'));
        var subtotal = quantity * unit_price;

         var tax_select = $(this).find('select[name*="[tax_id]"]');
         var tax_rate = parseFloat(tax_select.find('option:selected').data('rate')) || 0;
         var row_tax = __calculate_amount('percentage', tax_rate, subtotal);
         var subtotal_with_tax = subtotal+row_tax;
     
         $(this).find('.return_subtotal_display').text(__currency_trans_from_en(subtotal_with_tax, true));
         $(this).find('.return_subtotal').val(subtotal_with_tax);
         $(this).find('.item_tax').val(row_tax);

        
        net_return += subtotal;

         total_tax += row_tax;

    });

   var discount = 0;
if ($('#discount_type').val() == 'fixed') {
    discount = __read_number($("#discount_amount"));
} else if ($('#discount_type').val() == 'percentage') {
    var discount_percent = __read_number($("#discount_amount"));
    discount = __calculate_amount('percentage', discount_percent, net_return);
}

    console.log(discount);
    console.log(discount_percent);
    
    var discounted_net_return = net_return - discount;

    var net_return_inc_tax = discounted_net_return + total_tax;

   var adjustment_title = $('#adjustment_title').val();
    var adjustment_amount = __read_number($('#adjustment_amount'));

    $('#adjustment_label').text(adjustment_title ? adjustment_title + ': ' : '@lang("lang_v1.adjustment_default_title")' + ':');
    $('#adjustment_value').text(__currency_trans_from_en(adjustment_amount, true));

    var net_return_with_adjustment = net_return_inc_tax + adjustment_amount;

    $('input#tax_amount').val(total_tax);
    $('span#total_return_discount').text(__currency_trans_from_en(discount, true));
    $('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
    $('span#net_return').text(__currency_trans_from_en(net_return_with_adjustment, true));
}


    </script>
@endsection
