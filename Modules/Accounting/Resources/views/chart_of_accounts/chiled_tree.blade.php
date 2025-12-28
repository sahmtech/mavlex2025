<ul>
    @foreach ($account->child_accounts as $child_account)
        <li @if (count($child_account->child_accounts) == 0) data-jstree='{ "icon" : "fas fa-arrow-alt-circle-right" }' @endif>
            {{ $child_account->name }}
            @if (!empty($child_account->gl_code))
                - ({{ $child_account->gl_code }})
            @endif
            - @format_currency($child_account->balance)

            @if ($child_account->status == 'active')
                <span><i class="fas fa-check text-success" title="@lang('accounting::lang.active')"></i></span>
            @elseif($child_account->status == 'inactive')
                <span><i class="fas fa-times text-danger" title="@lang('lang_v1.inactive')" style="font-size: 14px;"></i></span>
            @endif
            <span class="tree-actions">
                @if (
                    (auth()->user()->can('Admin#'.request()->session()->get('user.business_id')) ||auth()->user()->can('superadmin') ||
                        auth()->user()->can('accounting.view_ledger')
                    ))
                    <a class="btn-modal btn-xs btn-default text-success ledger-link" title="@lang('accounting::lang.ledger')"
                        style="margin: 2px;"
                        href="{{ action('\Modules\Accounting\Http\Controllers\CoaController@ledger', $child_account->id) }}">
                        <i class="fas fa-file-alt"></i></a>
                @endif
                @if (
                    (auth()->user()->can('Admin#'.request()->session()->get('user.business_id')) ||auth()->user()->can('superadmin') ||
                        auth()->user()->can('accounting.edit_accounts')
                    ))
                    <a class="btn-modal btn-xs btn-default text-primary" title="@lang('messages.edit')" style="margin: 2px;"
                        href="{{ action('\Modules\Accounting\Http\Controllers\CoaController@edit', $child_account->id) }}"
                        data-href="{{ action('\Modules\Accounting\Http\Controllers\CoaController@edit', $child_account->id) }}"
                        data-container="#create_account_modal">
                        <i class="fas fa-edit"></i></a>
                @endif

                @if (
                    (auth()->user()->can('Admin#'.request()->session()->get('user.business_id')) ||auth()->user()->can('superadmin') ||
                        auth()->user()->can('accounting.add_extra_accounts')
                    ))
                    <a class="btn-modal btn-xs btn-default text-primary" title="@lang('accounting::lang.add_account')" style="margin: 2px;"
                        href="{{ action('\Modules\Accounting\Http\Controllers\CoaController@open_create_dialog', $child_account->id) }}"
                        data-href="{{ action('\Modules\Accounting\Http\Controllers\CoaController@open_create_dialog', $child_account->id) }}"
                        data-container="#create_account_modal">
                        <i class="fas fa-plus"></i>
                    </a>
                @endif

                @if (
                    (auth()->user()->can('Admin#'.request()->session()->get('user.business_id')) ||auth()->user()->can('superadmin') ||
                        auth()->user()->can('accounting.active_accounts')
                    ))
                    <a class="activate-deactivate-btn text-warning  btn-xs btn-default" style="margin: 2px;"
                        title="@if ($child_account->status == 'active') @lang('messages.deactivate') @else 
                                                                                  @lang('messages.activate') @endif"
                        href="{{ action('\Modules\Accounting\Http\Controllers\CoaController@activateDeactivate', $child_account->id) }}">
                        <i class="fas fa-power-off"></i>
                    </a>
                @endif
            </span>
            @if (count($child_account->child_accounts) > 0)
                @include('accounting::chart_of_accounts.chiled_tree', ['account' => $child_account])
            @endif
        </li>
    @endforeach
</ul>
