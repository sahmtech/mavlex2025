@extends('layouts.app')

@section('title', __('accounting::lang.cost_centers'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.cost_centers')</h1>
    </section>
    <section class="content no-print">
        @component('components.widget', ['class' => 'box-solid'])
            @if ((auth()->user()->can('Admin#'.request()->session()->get('user.business_id')) ||
        auth()->user()->can('superadmin') ||
        auth()->user()->can('accounting.add_cost_center')
    ))
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary btn-modal create_cost_center" data-toggle="modal"
                        data-target="#create_cost_center_modal">
                        <i class="fas fa-plus"></i> @lang('messages.add')
                    </a>
                </div>
            @endslot
            @endif

            <table class="table table-bordered table-striped" id="cost_center_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.name_ar')</th>
                        <th>@lang('accounting::lang.name_en')</th>
                        <th>@lang('accounting::lang.main_acc_name')</th>
                        <th>@lang('accounting::lang.account_center_number')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        @endcomponent
    </section>
    @include('accounting::cost_center.create')
    @include('accounting::cost_center.edit')

@stop

{{-- @push('javascript') --}}


@section('javascript')
    <script>
        $(document).ready(function() {

            $('#cost_center_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/accounting/cost_centers'
                },
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'ar_name',
                        name: 'ar_name'
                    },
                    {
                        data: 'en_name',
                        name: 'en_name'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'account_center_number',
                        name: 'account_center_number'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            })

        });

        $(document).on('click', '.create_cost_center', function() {
            $('#create_cost_center_form').trigger('reset')
            $('#create_parent_id').hide()
            $('#create_parent_id_label').hide()
        })

        $(document).on('shown.bs.modal', '#create_cost_center_modal', function() {
            $(this).find('#business_location_id').select2({
                tags: true,
                dropdownParent: $("#create_cost_center_modal")
            });
            $('#business_location_id').next(".select2-container").hide();
            $('.business_location_id_label').hide();

        });

        $(document).on('click', '.edit_cost_center', function(e) {
            $(this).find('#edit_business_location_id').select2({
                tags: true,
                dropdownParent: $("#edit_cost_center_modal")
            });
            $('#edit_cost_center_form').trigger('reset')
            if (!$(this).data('parent')) {
                $('#edit_parent_id').hide()
                $('#edit_parent_id_label').hide()
                $('#main').attr('checked', true)
            } else {
                $('#edit_parent_id').show()
                $('#edit_parent_id_label').show()
                $("#edit_parent_id option").removeAttr('selected')
                    .filter('[value=' + $(this).data('parent') + ']')
                    .attr('selected', true);
                $('#sub_main').attr('checked', true)
                $('#sub_main').attr('data-parent', $(this).data('parent'))
            }
            let name_ar = $(this).data('namear')
            let name_en = $(this).data('nameen')
            let account_center_number = $(this).data('accountcenternumber')
            let business_location_id = $(this).data('businesslocationid')
            let id = $(this).data('id')
            $('#edit_name_ar').val(name_ar)
            $('#edit_name_en').val(name_en)
            $('#edit_account_center_number').val(account_center_number)
            $('#edit_business_location_id').val(business_location_id).trigger('change')
            $('#cost_center_id').val(id)
            if (!business_location_id) {
                $('#edit_business_location_id').next(".select2-container").hide();
                $('.edit_business_location_id_label').hide();
                $('#edit_is_location_yes').prop('checked', false)
                $('#edit_is_location_no').prop('checked', true)
            } else {
                $('#edit_business_location_id').next(".select2-container").show();
                $('.edit_business_location_id_label').show();
                $('#edit_is_location_yes').prop('checked', true)
                $('#edit_is_location_no').prop('checked', false)
            }
        })

        $(document).ready(function() {
            $('input[name=is_location]').change(function() {
                // console.log($(this).val());
                if ($(this).val() === 'yes') {

                    $('#business_location_id').next(".select2-container").show();
                    $('.business_location_id_label').show()
                } else {
                    $('#business_location_id').next(".select2-container").hide();
                    $('.business_location_id_label').hide();
                }
                $("#business_location_id").val('').change()
            })
            $('input[name=edit_is_location]').change(function() {
                if ($(this).val() === 'yes') {
                    $('#edit_business_location_id').next(".select2-container").show();
                    $('.edit_business_location_id_label').show()
                } else {
                    $('#edit_business_location_id').next(".select2-container").hide();
                    $('.edit_business_location_id_label').hide();
                }
                $("#edit_business_location_id").val('').change()
            })
            $('input[name=is_main_create]').change(function() {
                if ($(this).val() === 'main') {
                    $('#create_parent_id').hide()
                    $('#create_parent_id_label').hide()
                    $('#create_parent_id').next(".select2-container").hide();

                } else {
                    $('#create_parent_id').show()

                    $('#create_parent_id_label').show()
                }
                $("#create_parent_id").val('').change()
            });

            $('input[name=is_main]').change(function() {
                if ($(this).val() === 'main') {
                    $('#edit_parent_id').hide()
                    $('#edit_parent_id_label').hide()
                    $("#edit_parent_id").val('').change()
                } else {
                    if ($(this).data('parent')) {
                        $("#edit_parent_id").val($(this).data('parent')).change()
                    }
                    $('#edit_parent_id').show()
                    $('#edit_parent_id_label').show()
                }
            });
        });



        $(document).on('click', 'button.delete_cost_center_button', function() {
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_cost_center,
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
                                $('#cost_center_table').DataTable().ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });

        function get_id() {
            return $('#cost_center_id').val();
        }
    </script>
    {{-- @endpush --}}
@endsection

@push('javascript')
    <script>
        $(document).on('submit', '#create_cost_center_form', function(e) {
            e.preventDefault()

            let url = "{{ route('cost_center_store') }}"
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
                        $('div#create_cost_center_modal').modal('hide');
                        toastr.success(result.msg);
                        $('#cost_center_table').DataTable().ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        })
        $(document).on('submit', '#edit_cost_center_form', function(e) {

            e.preventDefault()
            let id = $('#cost_center_id').val()


            let url = `{{ route('cost_center_update', 'id') }}`
            url = url.replace('id', id)
            let form = $(this);
            let data = form.serialize();

            $.ajax({
                method: 'PUT',
                url: url,
                dataType: 'json',
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        $('#edit_cost_center_modal').modal('hide');
                        toastr.success(result.msg);
                        $('#cost_center_table').DataTable().ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    </script>
@endpush
