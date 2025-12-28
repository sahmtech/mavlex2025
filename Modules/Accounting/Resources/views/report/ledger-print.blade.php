<div class="modal-dialog modal-lg" id="printledger" role="document">
    <div class="modal-content" style="padding: 0px 30px">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-body">
                            <table class="table table-condensed">
                                <tr>
                                    <th>@lang('user.name'):</th>
                                    <td>
                                        @if (app()->getLocale() == 'ar')
                                            @if (!empty($account->gl_code))
                                                ({{ $account->gl_code }})
                                                -
                                            @endif
                                            @if (Lang::has('accounting::lang.' . $account->name))
                                                @lang('accounting::lang.' . $account->name)
                                            @else
                                                {{ $account->name }}
                                            @endif
                                        @else
                                            @if (Lang::has('accounting::lang.' . $account->name))
                                                @lang('accounting::lang.' . $account->name)
                                            @else
                                                {{ $account->name }}
                                                @endif @if (!empty($account->gl_code))
                                                    - ({{ $account->gl_code }})
                                                @endif
                                            @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('accounting::lang.account_primary_type'):</th>
                                    <td>
                                        @if (!empty($account->account_primary_type))
                                            {{ __('accounting::lang.' . $account->account_primary_type) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('accounting::lang.account_sub_type'):</th>
                                    <td>
                                        @if (!empty($account->account_sub_type))
                                            {{ __('accounting::lang.' . $account->account_sub_type->name) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('accounting::lang.detail_type'):</th>
                                    <td>
                                        @if (!empty($account->detail_type))
                                            {{ __('accounting::lang.' . $account->detail_type->name) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('accounting::lang.account_category'):</th>
                                    <td>
                                        @if (!empty($account->account_category))
                                            {{ __('accounting::lang.' . $account->account_category) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('accounting::lang.account_type'):</th>
                                    <td>
                                        @if (!empty($account->account_type))
                                            {{ __('accounting::lang.' . $account->account_type) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('lang_v1.balance'):</th>
                                    <td>@format_currency($current_bal)</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped nowrap" id="ledger" style="display: block;">
                            <thead>
                                <tr>
                                    <th>@lang('accounting::lang.number')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('accounting::lang.transaction')</th>
                                    <th>@lang('accounting::lang.cost_center')</th>
                                    <th>@lang('brand.note')</th>
                                    <th>@lang('lang_v1.added_by')</th>
                                    <th>@lang('account.debit')</th>
                                    <th>@lang('account.credit')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->ref_no }}</td>
                                        <td>{{ $transaction->operation_date }}</td>
                                        <td>{{ $transaction->transaction }}</td>
                                        <td>{{ $transaction->cost_center_name }}</td>
                                        <td>{{ $transaction->note }}</td>
                                        <td>{{ $transaction->added_by }}</td>
                                        <td>
                                            @if ($transaction->type == 'debit')
                                                @format_currency($transaction->amount)
                                            @else
                                                @format_currency(0)
                                            @endif
                                        </td>
                                        <td>
                                            @if ($transaction->type == 'credit')
                                                @format_currency($transaction->amount)
                                            @else
                                                @format_currency(0)
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td colspan="6"><strong>@lang('accounting::lang.autoMigration.final_total'):</strong></td>
                                    <td class="footer_final_total_debit">@format_currency($total_debit_bal)</td>
                                    <td class="footer_final_total_credit">@format_currency($total_credit_bal)</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print" aria-label="Print"
                onclick="$(this).closest('div.modal').printThis();">
                <i class="fa fa-print"></i> @lang('messages.print')
            </button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang('messages.close')
            </button>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#ledger').DataTable();
    });
</script>
