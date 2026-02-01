@extends('layouts.app')
@section('title', __('product.allergens'))

@section('content')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
            @lang('product.allergens')
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
                @lang('product.manage_allergens')
            </small>
        </h1>
    </section>

    <section class="content">

        @can('allergen.create')
            <button
                class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white
            tw-border-none tw-rounded-full pull-right tw-mb-3"
                data-toggle="modal" data-target="#allergen_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')
            </button>
        @endcan

        <div class="clearfix"></div>

        <div class="box box-solid">
            <div class="box-body">
                <table class="table table-bordered table-striped" id="allergens_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.name')</th>
                            <th>@lang('lang_v1.icon')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </section>

    {{-- Create / Edit Modal --}}
    <div class="modal fade" id="allergen_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            {!! Form::open([
                'url' => action([\App\Http\Controllers\AllergenController::class, 'store']),
                'method' => 'post',
                'id' => 'allergen_form',
            ]) !!}
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">@lang('product.add_allergen')</h4>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('name', __('lang_v1.name') . ':*') !!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('icon', __('lang_v1.icon') . ':') !!}
                        {!! Form::text('icon', null, [
                            'class' => 'form-control',
                            'placeholder' => 'fa fa-fish / mdi-peanut',
                        ]) !!}
                        <small class="text-muted">
                            FontAwesome / Material Icons class
                        </small>
                    </div>

                    {!! Form::hidden('id', null, ['id' => 'allergen_id']) !!}
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        @lang('messages.close')
                    </button>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {

            let allergens_table = $('#allergens_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ action([\App\Http\Controllers\AllergenController::class, 'index']) }}',
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return data ? '<i class="' + data + ' fa-2x"></i>' : '-';
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Open Edit Modal
            $(document).on('click', '.edit-allergen', function() {
                let data = $(this).data();

                $('#allergen_modal').modal('show');
                $('#allergen_modal .modal-title').text('@lang('product.edit_allergen')');

                $('#allergen_form').attr('action', data.href);
                $('#allergen_form input[name=_method]').remove();
                $('#allergen_form').append('<input type="hidden" name="_method" value="PUT">');

                $('input[name=name]').val(data.name);
                $('input[name=icon]').val(data.icon);
                $('#allergen_id').val(data.id);
            });

            // Reset modal on close
            $('#allergen_modal').on('hidden.bs.modal', function() {
                $('#allergen_form')[0].reset();
                $('#allergen_form').attr(
                    'action',
                    '{{ action([\App\Http\Controllers\AllergenController::class, 'store']) }}'
                );
                $('#allergen_form input[name=_method]').remove();
                $('#allergen_modal .modal-title').text('@lang('product.add_allergen')');
            });

            $('#allergen_form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let url = form.attr('action');
                let method = form.find('input[name=_method]').val() || form.attr('method');
                let submitBtn = form.find('button[type=submit]');

                // Disable button to prevent double click
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: url,
                    method: method,
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg); // Show success
                            $('#allergen_modal').modal('hide');
                            allergens_table.ajax.reload(null,
                                false); // reload table without resetting paging
                            form[0].reset(); // optional: reset form for next submission
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('@lang('messages.something_went_wrong')');
                    },
                    complete: function() {
                        // Re-enable button after AJAX finishes
                        submitBtn.prop('disabled', false);
                    }
                });
            });


            $(document).on('click', '.delete-allergen', function(e) {
                e.preventDefault();
                let url = $(this).data('href');

                swal({
                    title: '@lang('messages.are_you_sure')',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            dataType: 'json',
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    allergens_table.ajax.reload(null, false);
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                            error: function(xhr) {
                                toastr.error('@lang('messages.something_went_wrong')');
                            }
                        });
                    }
                });
            });

        });
    </script>
@endsection
