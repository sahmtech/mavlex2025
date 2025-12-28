@extends('layouts.app')

@section('title', __('accounting::lang.journal_entry') . '-' . __('accounting::lang.history_edit'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.journal_entry') - @lang('accounting::lang.history_edit')</h1>
    </section>
    <section class="content no-print">

        @component('components.widget', ['class' => 'box-solid'])
            <table class="table table-bordered table-striped" id="journal_table">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('accounting::lang.journal_date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('accounting::lang.edited_by')</th>
                        <th>@lang('accounting::lang.edit_date')</th>
                        <th>@lang('accounting::lang.additional_notes')</th>
                        <th>@lang('accounting::lang.attachment')</th>


                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        @endcomponent
    </section>

@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            var path = window.location.pathname;

            var parts = path.split('/');

            var id = parts[parts.length - 1];

            console.log(id);
            //Journal table
            journal_table = $('#journal_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'journal-entry/history/'.id,
                    data: function(d) {
                        var start = '';
                        var end = '';
                        if ($('#journal_entry_date_range_filter').val()) {
                            start = $('input#journal_entry_date_range_filter')
                                .data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            end = $('input#journal_entry_date_range_filter')
                                .data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                        }
                        d.start_date = start;
                        d.end_date = end;
                    },
                },
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'operation_date',
                        name: 'operation_date'
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                var date = new Date(data);
                                var formattedDate = date.toLocaleDateString() + ' ' + date
                                    .toLocaleTimeString();
                                return formattedDate;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'path_file',
                        name: 'path_file'
                    }
                ]
            });

            $('#journal_entry_date_range_filter').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#journal_entry_date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end
                        .format(moment_date_format));
                    journal_table.ajax.reload();
                }
            );
            $('#journal_entry_date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#journal_entry_date_range_filter').val('');
                journal_table.ajax.reload();
            });

            //Delete Sale
            $(document).on('click', '.delete_journal_button', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    journal_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });

        });
    </script>
@endsection
