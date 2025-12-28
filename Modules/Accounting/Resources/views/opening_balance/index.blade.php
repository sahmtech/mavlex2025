@extends('layouts.app')

@section('title', __('accounting::lang.opening_balances'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.opening_balances')</h1>
    </section>
    <section class="content no-print">
        @component('components.widget', ['class' => 'box-solid'])
            @can('accounting.add_opening_balance')
                @slot('tool')
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary btn-modal create_cost_center" data-toggle="modal"
                            data-target="#create_opening_balance_modal">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                        @if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ||
                                auth()->user()->can('accouning.import_opeining_balances'))
                            <a class="btn btn-block btn-primary  " style="font-size: small;"
                                href="{{ route('viewImporte_openingBalance') }}">
                                <i class="fa fa-download"></i> @lang('accounting::lang.importe_openingBalance')
                            </a>
                        @endif
                    </div>
                @endslot
            @endcan

            <table class="table table-bordered table-striped" id="opening_balance_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.account_name')</th>
                        <th>@lang('accounting::lang.account_number')</th>
                        <th>@lang('accounting::lang.debtor')</th>
                        <th>@lang('accounting::lang.creditor')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>

                        </th>
                    </tr>
                </tbody>
            </table>
        @endcomponent
    </section>
    @include('accounting::opening_balance.edit')
    @include('accounting::opening_balance.create')
@stop


@section('javascript')
    <script>
        function calc() {
            setTimeout(function() {
                $.ajax({
                    url: '{{ route('opening_balance.calc') }}',
                    success: function(res) {
                        let debts = res.debt
                        let credits = res.credit
                        if (debts > credits) {
                            $('#opening_balance_table tbody').append(
                                '<tr role="row"><td class="text-red"><b>المعادلة غير متوازنة</b></td><td class="text-red sorting_1"></td><td  class="text-red"><b>' +
                                debts + '</b></td><td  class="text-red"><b>' + credits +
                                '</b></td><td  class="text-red"><b>زيادة بالمدين</b></td></tr>')
                        }
                        if (debts < credits) {
                            $('#opening_balance_table tbody').append(
                                '<tr role="row"><td class="text-red"><b>المعادلة غير متوازنة</b></td><td class="text-red sorting_1"></td><td  class="text-red"><b>' +
                                debts + '</b></td><td  class="text-red"><b>' + credits +
                                '</b></td><td  class="text-red"><b>زيادة بالدائن</b></td></tr>')
                        }
                        if (debts === credits) {
                            $('#opening_balance_table tbody').append(
                                '<tr role="row"><td class="text-green"><b>المعادلة متوازنة</b></td><td class="text-green sorting_1"></td><td  class="text-green"><b>' +
                                debts + '</b></td><td  class="text-green"><b>' + credits +
                                '</b></td><td  class="text-green"></td></tr>')
                        }
                    }
                })
            }, 2000);
        }

        $(document).ready(function() {
            $('#opening_balance_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/accounting/opening_balances'
                },
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'account_name',
                        name: 'account_name'
                    },
                    {
                        data: 'account_number',
                        name: 'account_number'
                    },
                    {
                        data: 'debit',
                        name: 'debit'
                    },
                    {
                        data: 'credit',
                        name: 'credit'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            })
            calc()
        });


        $(document).on('click', '.edit_opening_balance', function(e) {
            $('#edit_opening_balance_form').trigger('reset')

            let type = $(this).data('type')
            let year = $(this).data('year')
            let account_id = $(this).data('accountid')
            let id = $(this).data('id')
            let value = $(this).data('value')

            if (type === 'debit') {
                $('#type_creditor').attr('checked', false)
                $('#type_debtor').attr('checked', true)
            } else {
                $('#type_debtor').attr('checked', false)
                $('#type_creditor').attr('checked', true)
            }
            $('#edit_year').val(year).trigger('change')
            $('#edit_account_name').val(account_id).trigger('change')
            $('#edit_value').val(value)
            $('#opening_balance_id').val(id)
        })
        $(document).on('click', '.create_opening_balance', function() {
            $('#create_opening_balance_form').trigger('reset')
        })

        $(document).on('shown.bs.modal', '#create_opening_balance_modal', function() {
            $(this).find('#year').select2({
                tags: true,
                dropdownParent: $("#create_opening_balance_modal")
            });
            $(this).find('#account_name').select2({
                tags: true,
                dropdownParent: $("#create_opening_balance_modal")
            });
        });

        $(document).on('shown.bs.modal', '#edit_opening_balance_modal', function() {
            $(this).find('#edit_year').select2({
                tags: true,
                dropdownParent: $("#edit_opening_balance_modal")
            });
            $(this).find('#edit_account_name').select2({
                tags: true,
                dropdownParent: $("#edit_opening_balance_modal")
            });
        });




        $(document).on('click', 'button.delete_opening_balance_button', function() {
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_opening_balance,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    let href = $(this).data('href');
                    let data = $(this).serialize();

                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                           
                                toastr.success(result.msg);
                                $('#opening_balance_table').DataTable().ajax.reload();
                                calc()
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    </script>
@endsection

@push('javascript')
    <script>
        $(document).on('submit', '#edit_opening_balance_form', function(e) {
            e.preventDefault()

            let id = $('#opening_balance_id').val()

            let url = "{{ route('opening_balances.update', 'id') }}"
            url = url.replace('id', id)
            let form = $(this);
            let data = form.serialize();

            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        $('div#edit_opening_balance_modal').modal('hide');
                        toastr.success(result.msg);
                        $('#opening_balance_table').DataTable().ajax.reload();
                        calc()
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('submit', '#create_opening_balance_form', function(e) {
            e.preventDefault()

            let url = "{{ route('opening_balances.store') }}"
            let form = $(this);
            let data = form.serialize();

            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                data: data,
                success: function(result) {
                    if (result.success === true) {
                        $('div#create_opening_balance_modal').modal('hide');
                        toastr.success(result.msg);
                        $('#opening_balance_table').DataTable().ajax.reload();
                        calc()
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    </script>
@endpush
