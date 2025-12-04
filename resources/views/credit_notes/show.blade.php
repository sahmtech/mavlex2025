<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.credit_notes') (<b>@lang('sale.invoice_no'):</b> {{ $sell->invoice_no }})
    </h4>
</div>
<div class="modal-body">
   <div class="row">
      <div class="col-sm-6 col-xs-6">
        <!-- <h4>@lang('lang_v1.credit_notes_details'):</h4> -->
        <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
        <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
      </div>
      <div class="col-sm-6 col-xs-6">
        <!-- <h4>@lang('lang_v1.sell_details'):</h4> -->
        <strong>@lang('lang_v1.credit_note_number'):</strong> {{ $sell->invoice_no }} <br>
        <strong>@lang('lang_v1.credit_note_date'):</strong> {{@format_date($sell->transaction_date)}}
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12">
        <br>
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
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
            @php
              $total_before_tax = 0;
            @endphp
            @foreach($sell->sell_lines as $sell_line)


            @php
              $unit_name = $sell_line->product->unit->short_name;

              if(!empty($sell_line->sub_unit)) {
                $unit_name = $sell_line?->sub_unit->short_name;
              }
            @endphp

            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                  {{ $sell_line->product->name }}
                  @if( $sell_line->product->type == 'variable')
                    - {{ $sell_line->variations->product_variation->name}}
                    - {{ $sell_line->variations->name}}
                  @endif
                </td>

                
                <td>{!! $sell_line->product->product_description !!}</td>
                
                <td><span class="display_currency" data-currency_symbol="true">{{ $sell_line->unit_price_inc_tax }}</span></td>
                <td>{{@format_quantity($sell_line->quantity)}} {{$unit_name}}</td>
                <td>{{$sell_line->product->item_tax}} </td>
                <td>
                  @php
                    $line_total = $sell_line->unit_price_inc_tax * $sell_line->quantity;
                    $total_before_tax += $line_total ;
                  @endphp
                  <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                </td>
            </tr>
            @endforeach
          </tbody>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
      <table class="table">
        <tr>
          <th>@lang('lang_v1.return_total'): </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
        </tr>

        <tr>
          <th>@lang('lang_v1.total_return_discount'): </th>
          <td><b>(-)</b></td>
          <td class="text-right">@if($sell->discount_type == 'percentage')
              @<strong><small>{{$sell->discount_amount}}%</small></strong> -
              @endif
          <span class="display_currency pull-right" data-currency_symbol="true">{{ $total_discount }}</span></td>
        </tr>
        
        <tr>
          <th>@lang('lang_v1.total_return_tax'):</th>
          <td><b>(+)</b></td>
          <td class="text-right">
              <!-- @if(!empty($sell_taxes))
                @foreach($sell_taxes as $k => $v)
                  <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                @endforeach
              @else -->
              {{$sell->tax_amount}}
              <!-- @endif -->
            </td>
        </tr>
        <tr>
          <th>@lang('lang_v1.return_total'):</th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->final_total }}</span></td>
        </tr>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-md-12">
          <strong>{{ __('repair::lang.activities') }}:</strong><br>
          @includeIf('activity_log.activities', ['activity_type' => 'sell'])
      </div>
  </div>
</div>
<div class="modal-footer">
    <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{action([\App\Http\Controllers\CreditNotesController::class, 'printInvoice'], [$sell->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>