<?php

namespace Modules\ZatcaIntegrationKsa\Console;

use App\Business;
use App\Transaction;
use App\Utils\ModuleUtil;
use Illuminate\Console\Command;
use Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController;

class CreateZatcaDailyInvoiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:createZatcaDailyInvoiceCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $moduleUtil;

    public function __construct(
        ModuleUtil $moduleUtil
    ) {
        parent::__construct();
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Retrieve all businesses with ZATCA settings
        $business = Business::whereNotNull('zatca_settings')->get();
        foreach ($business as $busines) {
            // Decode the ZATCA settings for each business
            $settings = json_decode($busines->zatca_settings);

            // Check if auto-sync is enabled for ZATCA
            if (isset($settings->sync_frequency) && $settings->sync_frequency == 'daily') {

                // Retrieve transactions for the business that are eligible for ZATCA sync
                $transactions = Transaction::whereIn('type', ['sell', 'sell_return'])
                    ->whereNull('zatca_status') // Only consider transactions without a ZATCA status
                    ->where('business_id', $busines->id) // Filter by the current business
                    ->whereHas('location', function ($query) {
                        // Ensure the location has both response and details for ZATCA
                        $query->whereNotNull('zatca_response')
                            ->whereNotNull('zatca_details');
                    })
                    ->get();
                // Process each eligible transaction for ZATCA sync
                // Iterate through each transaction to process for ZATCA sync
                foreach ($transactions as $transaction) {
                    // Initialize a new instance of ZatcaInvoiceController for ZATCA operations
                    $zatca = new ZatcaInvoiceController;
                    // Determine the type of transaction to sync with ZATCA
                    if ($transaction->type == 'sell') {
                        // Sync a sale transaction with ZATCA
                        $zatca->sync_zatca_sale($transaction->id, $busines->id);
                    } elseif ($transaction->type == 'sell_return') {
                        // For a sell return transaction, first find the associated parent sale
                        $parent_sell = Transaction::where('business_id', $busines->id)
                            ->where('id', $transaction->return_parent_id)->firstOrFail();
                        // Ensure the parent sale has been successfully synced with ZATCA before processing the return
                        if ($parent_sell->zatca_status == 'success') {
                            // Sync the sell return transaction with ZATCA
                            $zatca->sync_zatca_sale_return($transaction->id, $busines->id);
                        }
                    }
                }
            }
        }

    }

}
