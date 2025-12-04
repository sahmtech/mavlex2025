<section class="no-print">
    <nav
        class="navbar-default tw-transition-all tw-duration-5000 tw-shrink-0 tw-rounded-2xl tw-m-[16px] tw-border-2 !tw-bg-white">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1" aria-expanded="false"
                    style="margin-top: 3px; margin-right: 3px;">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand"
                    href="{{ action([\Modules\ZatcaIntegrationKsa\Http\Controllers\DashBoardController::class, 'index']) }}"><i
                        class="fas fa-wallet"></i> @lang('zatcaintegrationksa::lang.zatca')</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    @can('ZatcaIntegrationKsa.sales')
                        <li @if (request()->segment(1) == 'zatca' && request()->segment(2) == 'sales') class="active" @endif><a
                                href="{{ action([Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'salesList']) }}">@lang('zatcaintegrationksa::lang.sales')</a>
                        </li>
                    @endcan
                    @can('ZatcaIntegrationKsa.sales_return')
                        <li @if (request()->segment(1) == 'zatca' && request()->segment(2) == 'sales-return') class="active" @endif><a
                                href="{{ action([Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'returnSalesList']) }}">@lang('zatcaintegrationksa::lang.sale_return')</a>
                        </li>
                    @endcan
                    @can('ZatcaIntegrationKsa.onboarding_screen')
                        <li @if (request()->segment(1) == 'zatca' && request()->segment(2) == 'onboarding') class="active" @endif><a
                                href="{{ action([Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'index']) }}">@lang('zatcaintegrationksa::lang.on_boarding')</a>
                        </li>
                    @endcan
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section>
