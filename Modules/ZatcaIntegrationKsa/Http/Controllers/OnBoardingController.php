<?php

namespace Modules\ZatcaIntegrationKsa\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Transaction;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ZatcaIntegrationKsa\Http\Controllers\src\OnBoarding;
use Modules\ZatcaIntegrationKsa\Entities\ZatcaDocument;

class OnBoardingController extends Controller
{
   
    /**
     * Process a sale transaction and generate an invoice for ZATCA integration.
     *
     * @param int $id The ID of the transaction to process.
     * @return void
     */
    public function index()
    {

        if (!auth()->user()->can('ZatcaIntegrationKsa.onboarding_screen')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $mode = [
            'developer-portal' => 'Developer Portal',
            'simulation' => 'Simulation Mode (Testing)',
            'core' => 'Core Mode (Live)',
        ];

        $invoice_types = [
            '1100' => 'Together (B2B & B2C Invoice)',
            '0100' => 'Simplified Invoice (B2C)',
            '1000' => 'Standard Invoice (B2B)',
        ];

        $business = Business::find($business_id);

        $business_locations = BusinessLocation::where('business_id', $business_id)->Active()->get();

        $mode_count = ZatcaDocument::selectRaw("COALESCE(SUM(CASE WHEN portal_mode = 'developer-portal' THEN 1 ELSE 0 END), 0) as developer_portal_count, 
                                   COALESCE(SUM(CASE WHEN portal_mode = 'simulation' THEN 1 ELSE 0 END), 0) as simulation_count")
                        ->join('business_locations', 'zatca_documents.location_id', '=', 'business_locations.id')
                        ->where('business_locations.business_id', $business_id)
                        ->first();

        return view('zatcaintegrationksa::onboarding.index', compact('business_locations', 'mode', 'business', 'invoice_types', 'mode_count'));
    }

    public function update(Request $request, $id)
    {
         //Disable in demo
         if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                'msg' => 'Feature disabled in demo!!',
            ];

            return back()->with('status', $output);
        }
        $business_id = session()->get('user.business_id');

        try {
            // Find the specific BusinessLocation by ID
            $businessLocation = BusinessLocation::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();
            // Decode existing zatca_details data
            // $detailsZatca = json_decode($businessLocation->zatca_details, true) ?? [];

            // Update fields from request data
            $detailsZatca['portal_mode'] = $request->portal_mode;
            $detailsZatca['otp'] = $request->otp;
            $detailsZatca['email'] = $request->email;
            $detailsZatca['common_name'] = $request->common_name;
            $detailsZatca['country_code'] = $request->country_code;
            $detailsZatca['organization_unit_name'] = $request->organization_unit_name;
            $detailsZatca['organization_name'] = $request->organization_name;
            $detailsZatca['egs_serial_number'] = $request->egs_serial_number;
            $detailsZatca['vat_number'] = $request->vat_number;
            $detailsZatca['vat_name'] = $request->vat_name;
            $detailsZatca['invoice_type'] = $request->invoice_type;
            $detailsZatca['registered_address'] = $request->registered_address;
            $detailsZatca['business_category'] = $request->business_category;
            $detailsZatca['crn'] = $request->crn;
            $detailsZatca['street_name'] = $request->street_name;
            $detailsZatca['building_number'] = $request->building_number;
            $detailsZatca['plot_identification'] = $request->plot_identification;
            $detailsZatca['sub_division_name'] = $request->sub_division_name;
            $detailsZatca['city_name'] = $request->city_name;
            $detailsZatca['postal_number'] = $request->postal_number;
            $detailsZatca['country_name'] = $request->country_name;

            // Save updated zatca_details field
            $businessLocation->zatca_details = json_encode($detailsZatca);
            // Call OnBoarding class and get response
            $response = $response = (new OnBoarding())
                ->setZatcaEnv($request->portal_mode)
                ->setZatcaLang('en')
                ->setEmailAddress($request->email)
                ->setCommonName($request->common_name)
                ->setCountryCode($request->country_code)
                ->setOrganizationUnitName($request->organization_unit_name)
                ->setOrganizationName($request->organization_name)
                ->setEgsSerialNumber($request->egs_serial_number)
                ->setVatNumber($request->vat_number)
                ->setInvoiceType($request->invoice_type)
                ->setRegisteredAddress($request->registered_address) // national short address
                ->setAuthOtp($request->otp)
                ->setBusinessCategory($request->business_category)
                ->getAuthorization();

            // return $response->message;

            //var_dump($response);exit;
            if (!$response['success']) {
                $output = [
                    'success' => 0,
                    'msg' => $response['message'],
                ];

                return redirect()
                    ->action([\Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'index'])
                    ->with('status', $output);
            }

            // Save API response in zatca_response field
            $businessLocation->zatca_response = json_encode($response);
            $businessLocation->save();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ];

            return redirect()
                ->action([\Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'index'])
                ->with('status', $output);

        } catch (\Exception $e) {
            \Log::error('Error updating zatca_details: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];

            return back()->with('status', $output)->withInput();
        }
    }

    public function zataSetting(Request $request)
    {
        $business_id = session()->get('user.business_id');

        try {
            $zatca_settings = $request->only(['sync_frequency']);

            Business::where('id', $business_id)
                ->update(['zatca_settings' => json_encode($zatca_settings)]);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);

    }

    public function posBussinesUpdate(Request $request){
        
    $business_id = session()->get('user.business_id');

    try {
        $business = Business::find($business_id);
        $pos_settings = json_decode($business->pos_settings, true) ?? [];

        $pos_settings['disable_discount'] = 1;
        $pos_settings['disable_order_tax'] = 1;

        Business::where('id', $business_id)
            ->update(['default_sales_discount' => 0, 'pos_settings' => json_encode($pos_settings)]);
            
        request()->session()->regenerate();
        $output = ['success' => true,
            'msg' => __('lang_v1.updated_success'),
        ];

    } catch (\Exception $e) {
        \Log::error('Error updating pos settings: ' . $e->getMessage());

        $output = ['success' => false,
            'msg' => __('messages.something_went_wrong'),
        ];
    }

    return redirect()->back()->with(['status' => $output]);
    }
}
