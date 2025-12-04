<div class="modal-dialog" role="document">
    <div class="modal-content">

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('zatcaintegrationksa::lang.error_modal_title')</h4>
        </div>

        <div class="modal-body">
            @if(empty($errors))
            <p class="text-success">@lang('zatcaintegrationksa::lang.no_errors_found')</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('zatcaintegrationksa::lang.error_code')</th>
                        <th>@lang('zatcaintegrationksa::lang.error_category')</th>
                        <th>@lang('zatcaintegrationksa::lang.error_message')</th>
                        <th>@lang('zatcaintegrationksa::lang.error_status')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($errors as $error)
                        <tr>
                            <td>{{ $error->code }}</td>
                            <td>{{ $error->category }}</td>
                            <td>{{ $error->message }}</td>
                            <td class="text-danger">{{ $error->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        
        </div>

        <div class="modal-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white"
                data-dismiss="modal">@lang('zatcaintegrationksa::lang.close')</button>
        </div>


    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->


