@extends('layouts.app')
@section('title', __('accounting::lang.importe_accounts'))

@section('content')
    <!-- Content Header (Page header) -->


    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.importe_accounts')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">

        @if (session('notification') || !empty($notification))
            <div class="row">
                <div class="col-sm-12">
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        @if (!empty($notification['msg']))
                            {{ $notification['msg'] }}
                        @elseif(session('notification.msg'))
                            {{ session('notification.msg') }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-sm-12">
                @component('components.widget', ['class' => 'box-primary'])
                    <div class="add-new-data">
                        {!! Form::open([
                            'url' => action([Modules\Accounting\Http\Controllers\CoaController::class, 'importe_accounts']),
                            'method' => 'post',
                            'enctype' => 'multipart/form-data',
                        ]) !!}
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="col-sm-8">
                                    <div class="form-group">
                                        {!! Form::label('name', __('product.file_to_import') . '   ') !!}<span style="color: red; font-size:10px"> *</span>
                                        {!! Form::file('accounts_csv', ['accept' => '.xls', 'required']) !!}
                                    </div>
                                </div>
                                @if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ||
                                        auth()->user()->can('accouning.import_accounts'))
                                    <div class="col-sm-4">
                                        <br>
                                        <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                                    </div>
                                @endif

                                <div class="col-sm-6">
                                    <a href="{{ asset('files/importe_accounts.xls') }}" class="btn btn-success" download><i
                                            class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.instructions')])
                    <strong>@lang('lang_v1.instruction_line1')</strong><br>
                    @lang('lang_v1.instruction_line2')
                    <br><br>
                    <table class="table table-striped">
                        <tr>
                            <th>@lang('lang_v1.col_no')</th>
                            <th>@lang('lang_v1.col_name')</th>
                            <th>@lang('lang_v1.instruction')</th>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>@lang('accounting::lang.account_name_en') <small class="text-muted">(@lang('lang_v1.required'))</small>
                            </td>
                            <td>Accounts Receivable (A/R)</td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td>@lang('accounting::lang.account_name_ar') <small class="text-muted">(@lang('lang_v1.required'))</small>
                            </td>
                            <td>المدينونيات التجارية (A/R)</td>
                        </tr>




                        <tr>
                            <td>3</td>
                            <td>@lang('accounting::lang.account_primary_type') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                            <td>@lang('accounting::lang.liability'):liability, @lang('accounting::lang.income'):income, @lang('accounting::lang.expenses'):expenses,
                                @lang('accounting::lang.asset'):asset</td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>@lang('accounting::lang.gl_code') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>@lang('accounting::lang.gl_code_mainaccount') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                            <td>@lang('accounting::lang.gl_code_mainaccount_nots')</td>
                        </tr>

                        <tr>
                            <td>6</td>
                            <td>@lang('accounting::lang.account_category') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                            <td>@lang('accounting::lang.balance_sheet'):balance_sheet, @lang('accounting::lang.income_list'):income_list</td>
                        </tr>



                    </table>
                @endcomponent
            </div>
        </div>
    </section>
    <!-- /.content -->

@endsection
