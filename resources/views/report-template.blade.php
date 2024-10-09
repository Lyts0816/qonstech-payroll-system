<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribution Summary Report</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #000;
    }

    .header {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
    }

    .logo {
        width: 60px;
        height: auto;
        margin-right: 20px;
    }

    .header-content {
        text-align: center;
        flex: 1;
        margin-top: -50px;
    }


    h1 {
        font-size: 16px;
        margin: 0;
        font-weight: normal;
    }

    h2 {
        font-size: 20px;
        margin: 0;

    }

    .info-table {
        width: 100%;
        margin-bottom: 20px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
    }

    .info-row div {
        width: 32%;
        padding: 8px;
        border: 1px solid #000;
        font-size: 12px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .header-table td {
        text-align: left;
        border: .1px solid #000;
        padding: 8px;
        font-size: 12px;
    }


    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th,
    .data-table td {

        border: 1px solid #000;
        padding: 15px;
        text-align: center;
        font-size: 12px;
    }

    .data-table thead th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .data-table tfoot th {
        font-weight: bold;
        background-color: #f2f2f2;
    }

    .footer {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        margin-top: 30px;
        margin-bottom: 70px;
        font-size: 12px;
    }

    .footer-section {
        flex: 1;
        text-align: center;
    }

    .footer-section:first-child {
        text-align: left;
    }

    .footer-section:last-child {
        text-align: right;
        margin-top: -90px;
        /* Align the last section to the right */
    }
</style>
<?php
$employeeda = $payrollData->first();

$imageDataP = base64_encode(file_get_contents(public_path('images/pagibig.png')));
$imageDataPh = base64_encode(file_get_contents(public_path('images/philhealth.png')));
$imageDataS = base64_encode(file_get_contents(public_path('images/sss.png')));

$pagibig = 'data:image/png;base64,' . $imageDataP;
$philhealth = 'data:image/png;base64,' . $imageDataPh;
$sss = 'data:image/png;base64,' . $imageDataS;

$reportType = $employeeda['ReportType'] ?? '';

// Set the appropriate title and logo based on ReportType
switch ($reportType) {
    case 'Pagibig Contribution':
        $titleName = 'Pag-IBIG';
        $src = $pagibig;
        $employerNumber = '12-3456789-0';
        $IDName = 'SSS NUMBER';
        break;
    case 'Philhealth Contribution':
        $titleName = 'Philippine Health Insurance Corporation';
        $src = $philhealth;
        $employerNumber = '82-3494289-042';
        $IDName = 'PHILHEALTH NO';
        break;
    case 'SSS Contribution':
    default:
        $titleName = 'Social Security System';
        $src = $sss;
        $employerNumber = '3214-7658-9832';
        $IDName = 'PAG-IBIG NO.';
        break;
}
?>


<body>
    <div class="container">
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
            <div class="header">
                <img src="{{ $src }}" alt="Company Logo" class="logo">
                <div class="header-content">
                    <h1>{{ $titleName }}</h1>
                    <h2>{{ $employeeda['ReportType'] ?? 'Report' }} Report</h2>
                </div>
            </div>


            <!-- Header Details Section -->
            <table class="header-table">
                <tr>
                    <td><b>EMPLOYER ID NUMBER</b> <br>{{$employerNumber}}
                    </td>
                    <td><b>REGISTERED EMPLOYER NAME</b> <br>Qonstech Construction Corporation </td>
                    <td><b>PERIOD COVERED</b> <br>{{ $formattedPeriod }}</td>
                </tr>
                <tr>
                    <td><b>TEL NO.</b> <br>09 1234 567 8912</td>
                    <td><b>ADDRESS</b> <br>Brgy. Zone III, Koronadal City, South Cotabato </td>
                    <td><b>EMPLOYER TYPE </b><br>Private</td>
                </tr>

            </table>
        @endif

        <table class="data-table">
            <thead>

                <tr>
                    <th>{{  $IDName }}</th>
                    <th>Name</th>
                    <!-- <th>Monthly Contribution</th> -->
                    <th>Employee Share</th>
                    <th>Employer Share</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Initialize a variable to track the total number of rows
                    $totalRows = 20;
                    $employeeCount = count($payrollData);
                @endphp

                @foreach ($payrollData as $employee)
                                @php
                                    // Sum up shares and total for each employee
                                    $employeeShare = $employee['Deduction'] ?? 0;
                                    $employerShare = $employee['DeductionEmployer'] ?? 0;
                                    $totalContribution = $employee['DeductionTotal'] ?? 0;

                                    // Add to total sums
                                    $totalEmployeeShare += $employeeShare;
                                    $totalEmployerShare += $employerShare;
                                    $totalDeduction += $totalContribution;
                                @endphp
                                <tr>
                                    <td style="text-align:left">{{ $employee['DeductionID'] ?? '' }}</td>
                                    <td style="text-align:left">
                                        {{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}
                                    </td>
                                    <td style="text-align:right">{{ number_format($employeeShare, 2) }}</td>
                                    <td style="text-align:right">{{ number_format($employerShare, 2) }}</td>
                                    <td style="text-align:right">{{ number_format($totalContribution, 2) }}</td>
                                </tr>
                @endforeach

                @for ($i = $employeeCount; $i < $totalRows; $i++)
                    <tr>
                        <td style="text-align:left"></td>
                        <td style="text-align:left"></td>
                        <td style="text-align:right"></td>
                        <td style="text-align:right"></td>
                        <td style="text-align:right"></td>
                    </tr>
                @endfor
            </tbody>

            <tfoot>
                <tr>
                    <td style="text-align:left" colspan="2"><strong>Subtotal</strong></td>
                    <td style="text-align:right">Php {{ number_format($totalEmployeeShare, 2) }}</td>
                    <td style="text-align:right">Php {{ number_format($totalEmployerShare, 2) }}</td>
                    <td style="text-align:right">Php {{ number_format($totalDeduction, 2) }}</td>
                </tr>
                <tr>
                    <th style="text-align:left" colspan="4"><strong>Total</strong></th>
                    <th style="text-align:right"><strong>Php {{ number_format($totalDeduction, 2) }}</strong></th>
                </tr>
            </tfoot>
        </table>
        <!-- Footer Section -->
        <<div class="footer">
            <div class="footer-section">
                <p>Prepared By:</p>
                
                <b>ALMA MAE S. GEPELLANO</b><br>
                <small><em>HR OFFICER</em></small>

            </div>
            <div class="footer-section">
                <p>Date Generated:</p><br>
                <b>{{ now()->format('F d, Y H:i:s') }}</b>

            </div>
    </div>


    </div>
</body>

</html>