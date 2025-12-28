@extends('layouts.app')

@section('title', __('accounting::lang.journal_entry'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.journal_entry') - {{ $journal->ref_no }} - @lang('accounting::lang.history_edit')</h1>
    </section>
    <section class="content">

        @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-sm-12">

                    <table class="table table-bordered table-striped hide-footer" id="journal_table">
                        <thead>
                            <tr>
                                <th class="col-md-1">#</th>
                                <th class="col-md-4">@lang('accounting::lang.account')</th>
                                <th class="col-md-2">@lang('accounting::lang.cost_center')</th>

                                <th class="col-md-1">@lang('accounting::lang.debit')</th>
                                <th class="col-md-1">@lang('accounting::lang.credit')</th>
                                <th class="col-md-5">@lang('accounting::lang.additional_notes')</th>

                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 1; $i <= count($accounts_transactions); $i++)
                                <tr>

                                    @php
                                        $account_id = '';
                                        $debit = '';
                                        $credit = '';
                                        $additional_notes = '';
                                        $default_array = [];
                                        $selected_partner_id = null;
                                        $selected_partner_type = '';
                                        $partner = '-';
                                        $partner_type = '-';
                                        $cost_center_id =null;

                                    @endphp

                                    @if (isset($accounts_transactions[$i - 1]))
                                        @php
 
                                            $account_id = $accounts_transactions[$i - 1]['accounting_account_id'];
                                            $cost_center_id = $accounts_transactions[$i - 1]['cost_center_id'];
                                          
                                            $debit =
                                                $accounts_transactions[$i - 1]['type'] == 'debit'
                                                    ? $accounts_transactions[$i - 1]['amount']
                                                    : '';
                                            $credit =
                                                $accounts_transactions[$i - 1]['type'] == 'credit'
                                                    ? $accounts_transactions[$i - 1]['amount']
                                                    : '';
                                            $default_array = [
                                                $account_id => $accounts_transactions[$i - 1]['account']['name'],
                                            ];
                                            $additional_notes =
                                                $accounts_transactions[$i - 1]['additional_notes'] ?? '';
                                            

                                        @endphp

                                        {!! Form::hidden('accounts_transactions_id[' . $i . ']', $accounts_transactions[$i - 1]['id']) !!}
                                    @endif

                                    <td>{{ $i }}</td>
                                    <td>
                                        {!! Form::select('account_id[' . $i . ']', $default_array, $account_id, [
                                            'class' => 'form-control accounts-dropdown account_id',
                                            'readonly' => 'readonly',
                                            'placeholder' => __('messages.please_select'),
                                            'style' => 'width: 100%;disabled:true;',
                                        ]) !!}
                                    </td>
                                   
                                    <td>
                                        <select readonly class="form-control cost_center" style="width: 100%;disabled:true;" name="cost_center[{{ $i }}]">
                                            <option  value="">يرجى الاختيار</option>
                                            @foreach ($allCenters as $allCenter)
                                                <option @if ($cost_center_id == $allCenter->id)
                                                    selected
                                                @endif value="{{ $allCenter->id }}">{{ $allCenter->ar_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        {!! Form::text('debit[' . $i . ']', $debit, [
                                            'class' => 'form-control input_number debit',
                                            'readonly' => 'readonly',
                                        ]) !!}
                                    </td>

                                    <td>
                                        {!! Form::text('credit[' . $i . ']', $credit, [
                                            'class' => 'form-control input_number credit',
                                            'readonly' => 'readonly',
                                        ]) !!}
                                    </td>

                                    <td>
                                        {!! Form::text('additional_notes[' . $i . ']', $additional_notes, [
                                            'class' => 'form-control additional_notes',
                                            'readonly' => 'readonly',
                                        ]) !!}
                                    </td>
                                </tr>
                            @endfor
                        </tbody>

                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th class="text-center">@lang('accounting::lang.total')</th>
                                <th><input type="hidden" class="total_debit_hidden"><span class="total_debit"></span></th>
                                <th><input type="hidden" class="total_credit_hidden"><span class="total_credit"></span></th>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        @endcomponent

        {!! Form::close() !!}
    </section>

@stop

@section('javascript')
    @include('accounting::accounting.common_js')
    <script type="text/javascript">
        $(document).ready(function() {
            $(document).ready(function() {
                $('.account_id').prop('disabled', true);
                $('.cost_center').prop('disabled', true);
            });
            calculate_total();

            $('.journal_add_btn').click(function(e) {
                //e.preventDefault();
                calculate_total();

                var is_valid = true;

                //check if same or not
                if ($('.total_credit_hidden').val() != $('.total_debit_hidden').val()) {
                    is_valid = false;
                    alert("@lang('accounting::lang.credit_debit_equal')");
                }

                //check if all account selected or not
                $('table > tbody  > tr').each(function(index, tr) {
                    var credit = __read_number($(tr).find('.credit'));
                    var debit = __read_number($(tr).find('.debit'));

                    if (credit != 0 || debit != 0) {
                        if ($(tr).find('.account_id').val() == '') {
                            is_valid = false;
                            alert("@lang('accounting::lang.select_all_accounts')");
                        }
                    }
                });

                if (is_valid) {
                    $('form#journal_add_form').submit();
                }

                return is_valid;
            });

            $('.credit').change(function() {
                if ($(this).val() > 0) {
                    $(this).parents('tr').find('.debit').val('');
                }
                calculate_total();
            });
            $('.debit').change(function() {
                if ($(this).val() > 0) {
                    $(this).parents('tr').find('.credit').val('');
                }
                calculate_total();
            });
        });

        function calculate_total() {
            var total_credit = 0;
            var total_debit = 0;
            $('table > tbody  > tr').each(function(index, tr) {
                var credit = __read_number($(tr).find('.credit'));
                total_credit += credit;

                var debit = __read_number($(tr).find('.debit'));
                total_debit += debit;
            });

            $('.total_credit_hidden').val(total_credit);
            $('.total_debit_hidden').val(total_debit);

            $('.total_credit').text(__currency_trans_from_en(total_credit));
            $('.total_debit').text(__currency_trans_from_en(total_debit));
        }
    </script>
@endsection
