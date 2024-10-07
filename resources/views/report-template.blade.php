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

        /* General Report Container */
        .report-container {
            font-family: Arial, sans-serif;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            font-size:13px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            /* Use flexbox for layout */
            justify-content: space-between;
            /* Space between left and right elements */
            align-items: center;
            /* Center items vertically */
            margin-bottom: 10px;
            /* Optional: add space below header */
        }

        .left,
        .right {
            flex: 2;
            text-align: left;
            /* Left aligns content inside */
        }

        .right {
            text-align: right;
            /* Right aligns content inside */
        }


        /* Table Styling */
        .contribution-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            
        }

        .contribution-table th,
        .contribution-table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        .contribution-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .contribution-table td {
            height: 40px;
            vertical-align: middle;
        }

        /* Table Row Stripes (Optional) */
        .contribution-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .logo-container {
            display: flex;
            /* Use flexbox for centering */
            justify-content: center;
            /* Center horizontally */
            margin: 20px 0;
            /* Optional: add vertical spacing */
        }

        .logo {
            max-width: 200px;
            height: auto;
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
            <div class="logo-container">
                <img src="{{ $src }}" alt="Company Logo" class="logo">
            </div>
            <div>

                @php
                    $employeeda = $payrollData->first();
                @endphp
                @if ($employeeda)
                    <h2>{{ $employeeda['ReportType'] ?? 'Report' }} Report</h2>
                    <div class="header">
                        <div>
                            <b>{{ $employeeda['ProjectName'] ?? '[Project Name]' }}</b>
                        </div>
                        <div>
                            <b>Period Covered:</b> {{ $employeeda['Period'] ?? '[Period Covered]' }}
                        </div>
                    </div>
                @endif
            </div>

            <table class="contribution-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Monthly Compensation</th>
                        <th>Employee Share</th>
                        <th>Employer Share</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payrollData as $employee)
                        <tr>
                            <td>{{ $employee['DeductionID'] ?? '' }}</td>
                            <td>{{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}
                            </td>
                            <td>{{ number_format($employee['DeductionMonthly'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Deduction'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['DeductionEmployer'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['DeductionTotal'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

</body>

</html>