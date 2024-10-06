<?php

namespace App\Http\Controllers;

use App\Models\Employee; // Assuming you have a User model
use Dompdf\Dompdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignment' => 'required|string',
            'PayrollFrequency' => 'required|string',
            'EmployeeStatus' => 'required|string',
            // ... other validation rules
        ]);

        // Create or update the employee
        Employee::create($validated);

        // Redirect or return response
    }

    public function generatePayslips($projectId)
    {
        // Fetch employees based on ProjectID
        $employees = Employee::where('project_id', $projectId)->get();

        // Initialize Dompdf instance
        $dompdf = new Dompdf();

        // Render each employee's payslip
        $payslipHtml = '';

        foreach ($employees as $employee) {
            // Render the payslip view for each employee
            $payslipHtml .= view('payslip-template', compact('employee'))->render();
        }

        // Load the HTML content
        $dompdf->loadHtml($payslipHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output the generated PDF to Browser
        return $dompdf->stream('payslips.pdf', ['Attachment' => false]);
    }
}
