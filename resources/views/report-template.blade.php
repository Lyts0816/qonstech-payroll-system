<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .report-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            font-size: 13px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .header-table, .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .header-table td {
            padding: 5px;
        }

        .header-table td:first-child {
            width: 25%;
            font-weight: bold;
        }

        .details-table th,
        .details-table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        .details-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .details-table td {
            height: 40px;
            vertical-align: middle;
        }

        .details-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 120px;
            height: auto;
        }

        .footer {
            margin-top: 20px;
            text-align: left;
        }
    </style>
</head>

<body>
    <?php
    $imageData = base64_encode(file_get_contents(public_path('images/qonstech.png')));
    $src = 'data:image/png;base64,' . $imageData;
    ?>
    
    <div class="container">
        <div class="report-container">
            <!-- Logo Section -->
            <div class="logo-container">
                <img src="{{ $src }}" alt="Company Logo" class="logo">
            </div>

            @php
                $employeeda = $payrollData->first();
                $formattedPeriod = '';
                $totalEmployeeShare = 0;
                $totalEmployerShare = 0;
                $totalDeduction = 0;
                $totalMonthlyContribution = 0;

                if (isset($employeeda['Period'])) {
                    $dates = explode(' - ', $employeeda['Period']);
                    if (count($dates) == 2) {
                        $startDate = \Carbon\Carbon::parse($dates[0])->format('m-d-Y');
                        $endDate = \Carbon\Carbon::parse($dates[1])->format('m-d-Y');
                        $formattedPeriod = "{$startDate} - {$endDate}";
                    }
                }
            @endphp

            @if ($employeeda)
                <h2>{{ $employeeda['ReportType'] ?? 'Report' }} Report</h2>

               
                <table class="header-table">
                    <tr>
                        <td>EMPLOYER ID NUMBER:</td>
                        <td>xxxxxxxxxxxx</td>
                        <td>PERIOD COVERED:</td>
                        <td>{{ $formattedPeriod }}</td>
                    </tr>
                    <tr>
                        <td>PROJECT NAME:</td>
                        <td>{{ $employeeda['ProjectName'] ?? '[Project Name]' }}</td>
                        <td>EMPLOYER TYPE:</td>
                        <td>Private</td>
                    </tr>
                    <tr>
                        <td>TEL NO.:</td>
                        <td>09 1234 567 8912</td>
                        <td>ADDRESS:</td>
                        <td>Brgy. Zone III, Koronadal City, South Cotabato</td>
                    </tr>
                </table>
            @endif

           
            <table class="details-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Monthly Contribution</th>
                        <th>Employee Share</th>
                        <th>Employer Share</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payrollData as $employee)
                        @php
                            
                            $employeeShare = $employee['Deduction'] ?? 0;
                            $employerShare = $employee['DeductionEmployer'] ?? 0;
                            $totalContribution = $employee['DeductionTotal'] ?? 0;
                            $monthlyContribution = $employee['DeductionMonthly'] ?? 0;

                            
                            $totalEmployeeShare += $employeeShare;
                            $totalEmployerShare += $employerShare;
                            $totalDeduction += $totalContribution;
                            $totalMonthlyContribution += $monthlyContribution;
                            
                        @endphp
                        <tr>
                            <td>{{ $employee['DeductionID'] ?? '' }}</td>
                            <td>{{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}</td>
                            <td>{{ number_format($employee['DeductionMonthly'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employeeShare, 2) }}</td>
                            <td>{{ number_format($employerShare, 2) }}</td>
                            <td>{{ number_format($totalContribution, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <!-- Totals row -->
                <tfoot>
                    <tr>
                        <th colspan="2" style="text-align: right;">Overall Total:</th>
                        <th>{{ number_format($totalMonthlyContribution, 2) }}</th>
                        <th>{{ number_format($totalEmployeeShare, 2) }}</th>
                        <th>{{ number_format($totalEmployerShare, 2) }}</th>
                        <th>{{ number_format($totalDeduction, 2) }}</th>
                    </tr>
                </tfoot>
            </table>

            <!-- Footer Section -->
            <div class="footer">
                <b>Date Generated:</b> {{ now()->format('m-d-Y H:i:s') }}<br>
                <p>Prepared By:</p>
                <b>HR OFFICER</b><br>
                <b>ALMA MAE S. GEPELLANO</b>
            </div>
        </div>
    </div>
</body>

</html>
