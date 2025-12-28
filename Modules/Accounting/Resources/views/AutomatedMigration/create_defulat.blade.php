<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:red"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><i class="fas fa-plus"></i> @lang('accounting::lang.add_auto_migration')</h4>
        </div>
        {!! Form::open([
            'url' => action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@store_deflute_auto_migration'),
            'method' => 'post',
            'id' => 'journal_add_form',
        ]) !!}
        <div class="modal-body">
            <section class="content" style="min-height: 97px;">
                <div class="row">
                    <div class="col-sm-12" style="margin-bottom: 5px;">
                        {!! Form::label('business_location', __('accounting::lang.autoMigration.business_location') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="business_location_id"
                            id="business_location"style="padding: 3px" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($business_locations as $business_location)
                                <option value="{{ $business_location->id }}">{{ $business_location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary  btn-flat journal_add_btn">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div> <!-- /.modal-content -->
</div><!-- /.modal-dialog -->
