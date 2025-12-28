<div class="modal-dialog modal-lg" id="printJournalEntry" role="document">
    <div class="modal-content" style="padding: 0px 30px">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="row">

                <div class="col-xs-8"
                    style="   
                            /* padding: 37px; */
                        align-items: center;
                       
                        justify-content: flex-start;
                         ">

                    <p>{{ $journal->business->name }}</p>
                    <p>@lang('purchase.ref_no'): {{ $journal->ref_no }}</p>
                </div>
                <div class="col-xs-4"
                    style="
                    align-items: center;
                    justify-content: end;
                    
                                font-weight: bold;
                    ">
                    <p>@lang('accounting::lang.journal_date'): {{ \Carbon\Carbon::parse($journal->operation_date)->format('Y-m-d') }}</p>
                    <p>@lang('accounting::lang.edit_date'):
                        {{ $journal->latest_update_at ? \Carbon\Carbon::parse($journal->latest_update_at)->format('Y-m-d') : ' - ' }}
                    </p>

                </div>

                <hr style="width:100%;text-align:left;margin-left:0">

                <table class="table table-bordered table-striped hide-footer" id="journal_table">
                    <thead>
                        <tr>
                            <th class="col-md-4">@lang('accounting::lang.account')</th>
                            <th class="col-md-2">@lang('accounting::lang.cost_center')</th>

                            <th class="col-md-1">@lang('accounting::lang.debit')</th>
                            <th class="col-md-1">@lang('accounting::lang.credit')</th>
                            <th class="col-md-5">@lang('accounting::lang.additional_notes')</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts_transactions as $accounts_transaction)
                            <tr>
                                <td>{{ $accounts_transaction->account->name }}</td>
                                
                                <td>{{ $accounts_transaction?->costCenter?->ar_name ?? '' }}</td>
                                @if ($accounts_transaction->type == 'debit')
                                    <td>{{ $accounts_transaction->amount }}</td>
                                @else
                                    <td>0</td>
                                @endif
                                @if ($accounts_transaction->type == 'credit')
                                    <td>{{ $accounts_transaction->amount }}</td>
                                @else
                                    <td>0</td>
                                @endif
                                <td>{{ $accounts_transaction->additional_notes }}</td>

                            </tr>
                        @endforeach

                    </tbody>
                </table>


            </div>
            <hr style="width:100%;text-align:left;margin-left:0">

            <div class="row">

                <div class="col-xs-6"
                    style="   
                            /* padding: 37px; */
                        align-items: center;
                       
                        justify-content: flex-start;
                         ">

                    <p>@lang('lang_v1.added_by'): {{ $journal->creator->first_name . ' ' . $journal->creator->last_name }}</p>
                </div>


                <div class="col-xs-6"
                    style="   
                            /* padding: 37px; */
                        align-items: center;
                       
                        justify-content: flex-start;
                         ">

                    <p>@lang('accounting::lang.edited_by'): {{ $journal->latest_update }}</p>
                </div>
            </div>
            {{-- </div> --}}

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
