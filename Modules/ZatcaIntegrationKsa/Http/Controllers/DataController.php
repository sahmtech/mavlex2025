<?php

namespace Modules\ZatcaIntegrationKsa\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Menu;
use Modules\ZatcaIntegrationKsa\Entities\ZatcaDocument;

class DataController extends ZatcaInvoiceController
{
    public function superadmin_package()
    {
        return [
            [
                'name' => 'ZatcaIntegrationKsa',
                'label' => __('zatcaintegrationksa::lang.zatca'),
                'default' => false,
            ],
        ];
    }

    public function user_permissions()
    {
        return [
            [
                'value' => 'ZatcaIntegrationKsa.access_zatca_module',
                'label' => __('zatcaintegrationksa::lang.access_zatca_module'),
                'default' => false,
            ],
            [
                'value' => 'ZatcaIntegrationKsa.onboarding_screen',
                'label' => __('zatcaintegrationksa::lang.onboarding_screen'),
                'default' => false,
            ],
            [
                'value' => 'ZatcaIntegrationKsa.sales',
                'label' => __('zatcaintegrationksa::lang.sales'),
                'default' => false,
            ],
            [
                'value' => 'ZatcaIntegrationKsa.sales_return',
                'label' => __('zatcaintegrationksa::lang.sales_return'),
                'default' => false,
            ],
        ];
    }

    public function after_sales($data)
    {
        $transaction = $data['transaction'];
        $business_id = request()->session()->get('user.business_id');
        $buniess = $buniess = Business::find($business_id);
        $settings = json_decode($buniess->zatca_settings, true);

        if ((isset($settings['sync_frequency']) && $settings['sync_frequency'] == 'instant')) {
            return $this->sync_zatca_sale($transaction->id, $business_id);
        }
    }

    public function after_sales_return($data)
    {

        $transaction = $data['transaction'];
        $business_id = request()->session()->get('user.business_id');
        $buniess = $buniess = Business::find($business_id);
        $settings = json_decode($buniess->zatca_settings, true);

        if ((isset($settings['sync_frequency']) && $settings['sync_frequency'] == 'instant')) {
            return $this->sync_zatca_sale_return($transaction->id, $business_id);
        }
    }

    public function InvoiceQrCode($data){
        $transaction = $data['transaction'];
        $business_id = request()->session()->get('user.business_id');
        
        $document = ZatcaDocument::where('transaction_id', $transaction->id)->where('type', 'sale')->where('business_id', $business_id)->where('sent_to_zatca_status', 'success')->first();
       
        if($transaction->type == 'sell_return'){
            $document = ZatcaDocument::where('transaction_id', $transaction->id)->where('business_id', $business_id)->where('type', 'sale-return')->where('sent_to_zatca_status', 'success')->first();
        }

        if (! $document) {
            return '';
        }

        return json_decode($document->response)->qr_value ?? '';
    }

    public function modifyAdminMenu()
    {

        $module_util = new ModuleUtil();
        $business_id = session()->get('user.business_id');

        $is_zatca_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'ZatcaIntegrationKsa');

        if ($is_zatca_enabled  && auth()->user()->can('ZatcaIntegrationKsa.access_zatca_module')) {
            Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(
                    action([\Modules\ZatcaIntegrationKsa\Http\Controllers\DashBoardController::class, 'index']),
                    __('zatcaintegrationksa::lang.zatca'),
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2"> <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2"></path> </svg>', 'style' => config('app.env') == 'demo' ? 'background-color: #A9A9A9 !important;' : '', 'active' => request()->segment(1) == 'zatca']
                )->order(51);
            });
        }
    }
}
