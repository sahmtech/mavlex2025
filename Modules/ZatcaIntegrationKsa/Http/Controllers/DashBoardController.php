<?php

namespace Modules\ZatcaIntegrationKsa\Http\Controllers;

use App\System;
use App\Transaction;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ZatcaIntegrationKsa\Entities\ZatcaDocument;

class DashBoardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        $business_id = request()->session()->get('user.business_id');

        $total_un_synced = Transaction::whereIn('type', ['sell', 'sell_return'])
            ->whereNull('zatca_status') // Only consider transactions without a ZATCA status
            ->where('business_id', $business_id) // Filter by the current business
            ->count();

        $total_synced = Transaction::whereIn('type', ['sell', 'sell_return'])
            ->whereNotNull('zatca_status') // Only consider transactions with a ZATCA status
            ->where('business_id', $business_id) // Filter by the current business
            ->count();
            
        $total_success_synced = Transaction::whereIn('type', ['sell', 'sell_return'])
            ->where('zatca_status', 'success') // Only consider transactions with a successful ZATCA status
            ->where('business_id', $business_id) // Filter by the current business
            ->count();

        $total_failed_synced = Transaction::whereIn('type', ['sell', 'sell_return'])
            ->where('zatca_status', 'failed') // Only consider transactions with a failed ZATCA status
            ->where('business_id', $business_id) // Filter by the current business
            ->count();

        $module_version = System::getProperty('ZatcaIntegrationKsa_version');

        return view('zatcaintegrationksa::dashboard.index', compact('module_version', 'total_un_synced', 'total_synced', 'total_success_synced', 'total_failed_synced'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('zatcaintegrationksa::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('zatcaintegrationksa::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('zatcaintegrationksa::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
