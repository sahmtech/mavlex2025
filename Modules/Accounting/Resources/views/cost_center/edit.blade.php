<div class="modal fade" id="edit_cost_center_modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
           
            {!! Form::open(['method' => 'put', 'id' => 'edit_cost_center_form','route' => 'cost_center_update']) !!}
           
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:red"><span
                        aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> @lang('accounting::lang.edit_cost_center')
                </h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="hidden" name="id" id="cost_center_id">
                            {!! Form::label('edit_name_ar', __('accounting::lang.name_ar') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                            {!! Form::text('ar_name', '', [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('accounting::lang.name_ar'),
                                'id' => 'edit_name_ar',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('edit_name_en', __('accounting::lang.name_en') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                            {!! Form::text('en_name', '', [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('accounting::lang.name_en'),
                                'id' => 'edit_name_en',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('edit_is_location', __('accounting::lang.is_location')) !!}
                            <div class="">
                                <label class="radio-inline">
                                    <input value="yes" type="radio" name="edit_is_location"
                                        id="edit_is_location_yes">@lang('accounting::lang.yes')
                                </label>
                                <label class="radio-inline">
                                    <input value="no" type="radio" name="edit_is_location"
                                        id="edit_is_location_no" checked>@lang('accounting::lang.no')
                                </label>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('edit_business_location_id', __('accounting::lang.business_location'), [
                                'class' => 'edit_business_location_id_label',
                            ]) !!}
                            <select class="form-control select2" style="width: 100%;" name="business_location_id"
                                id="edit_business_location_id">
                                <option value="">@lang('messages.please_select')</option>
                                @foreach ($businessLocations as $businessLocation)
                                    <option value="{{ $businessLocation->id }}">
                                        {{ $businessLocation->location_id . ' - ' . $businessLocation->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('edit_account_center_number', __('accounting::lang.account_center_number') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                            {!! Form::number('account_center_number', '', [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('accounting::lang.account_center_number'),
                                'id' => 'edit_account_center_number',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('is_main', __('accounting::lang.is_main')) !!}
                            <div class="">
                                <label class="radio-inline">
                                    <input value="main" type="radio" name="is_main" id="main">@lang('accounting::lang.main')
                                </label>
                                <label class="radio-inline">
                                    <input value="sub_main" type="radio" name="is_main"
                                        id="sub_main">@lang('accounting::lang.sub_main')
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group" id='edit_parent_id'>
                            <label for="edit_parent_id"
                                id="edit_parent_id_label">{{ __('accounting::lang.main_cost_center') . '  ' }}</label><span
                                style="color: red; font-size:10px" id='edit_parent_id'> *</span>
                            <select class="form-control " style="width: 100%;" name="parent_id" id="edit_parent_id">
                                <option value="">@lang('messages.please_select')</option>
                                @foreach ($mainCenters as $center)
                                    <option value="{{ $center->id }}">
                                        {{ $center->transname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: flex-end">
                <button type="submit" class="btn btn-primary"
                    style="border-radius: 5px;
                min-width: 25%;">@lang('messages.update')</button>
                {{-- <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button> --}}
            </div>

            {!! Form::close() !!}
        </div>



    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

