<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use App\Models\LoanDtl;
use App\Models\WeekPeriod;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected static ?string $title = 'Add Loan';

    protected function handleRecordCreation(array $data): \App\Models\Loan
    {
        // Call the parent method to handle the main loan record creation
        $request = parent::handleRecordCreation($data);

        // Capture form values
        $loanID = $request->id;  // Assuming LoanID is the primary key from the created loan record
        $periodID = $data['PeriodID'];  // Starting period ID (kinsena)
        $loanAmount = $data['LoanAmount'];
        $KinsenaDeduction = $data['KinsenaDeduction']; // Amount to deduct for each kinsena
        $noOfPayments = isset($data['NumberOfPayments']) ? (int) $data['NumberOfPayments'] * 2 : 0;

        // Ensure proper values are provided
        if (!$loanID || !$periodID || !$loanAmount || !$noOfPayments) {
            return $request;  // Return the created loan record if any critical data is missing
        }

        // Default values for ispaid and isrenewed
        $isPaid = false;
        $isRenewed = false;

        // Loop through the number of payments and insert details for each kinsena
        for ($i = 0; $i < $noOfPayments; $i++) {
            // Get the current period from the WeekPeriod table filtered by Category 'Kinsena'
            $currentPeriod = WeekPeriod::where('id', $periodID)
                ->where('Category', 'Kinsenas')  // Filter by Category
                ->first();

            // If the current period doesn't exist, exit the loop
            if (!$currentPeriod) {
                break;  // Exit if current period is not found
            }

            // Insert data for the kinsena
            $sequence = $i + 1;  // Sequence number for Kinsena
            DB::table('loandtl')->insert([
                'loanid' => $loanID,
                'sequence' => $sequence,
                'tran_date' => $currentPeriod->StartDate,  // Transaction date based on StartDate
                'periodid' => $periodID,
                'amount' => $KinsenaDeduction,  // Amount for Kinsena
                'ispaid' => (int) $isPaid,
                'isrenewed' => (int) $isRenewed,
            ]);

            // Find the next consecutive period in the WeekPeriod table based on StartDate filtered by Category 'Kinsena'
            $nextPeriod = WeekPeriod::where('StartDate', '>', $currentPeriod->StartDate)
                ->where('Category', 'Kinsenas')  // Filter by Category
                ->orderBy('StartDate', 'asc')
                ->first();

            // If there is no next period, stop the loop
            if (!$nextPeriod) {
                break;
            }

            // Update the periodID for the next iteration
            $periodID = $nextPeriod->id;

            // Debugging output
            // Log current values for debugging
            \Log::info("Current Period: ", [$currentPeriod]);
            \Log::info("Next Period: ", [$nextPeriod]);
            \Log::info("Iteration: $i, Total Payments: $noOfPayments");
        }

        // Optionally return the request or created record after processing
        return $request;
    }



}
