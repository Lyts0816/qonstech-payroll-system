<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .container {
            font-size: 13px;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        h2 {
            text-align: center;
        }

        .employee-details,
        .earnings,
        .deductions,
        .totals {
            width: 100%;
            margin-bottom: 20px;
        }

        .employee-details table,
        .earnings table,
        .deductions table,
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-details th,
        .employee-details td,
        .earnings th,
        .earnings td,
        .deductions th,
        .deductions td,
        .totals th,
        .totals td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .totals td,
        .totals th {
            text-align: center;
            /* Center-align heading */
            margin: 0;
            /* Remove default margin */
        }


        .net-pay {
            width: 100%;
            text-align: right;
            margin-top: 30px;
            font-size: 1.5em;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 400px;
            height: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo-container">
            <img src="{{ asset('images/qonstech.png') }}" alt="Company Logo" class="logo">
        </div>
        <h1>PAYSLIP</h1>

        <!-- Employee Details -->
        <div class="employee-details">
            <table>
                @foreach ($payrollRecords as $record)
                    <tr>
                        <th>Employee Name</th>
                        <td>{{ $record->first_name }} {{ $record->middle_name }} {{ $record->last_name }}</td>
                        <th>Regular Status</th>
                        <td>{{ $record->RegularStatus }}</td>
                    </tr>
                    <tr>
                        <th>Position</th>
                        <td>{{ $record->position }}</td>
                        <th>Salary Type</th>
                        <td>{{ $record->SalaryType }}</td>
                    </tr>
                    <tr>
                        <th>Monthly Salary</th>
                        <td>{{ number_format($record->monthlySalary, 2) }}</td>
                        <th>Hourly Rate</th>
                        <td>{{ number_format($record->hourlyRate, 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="earnings">
            <h3>Earnings</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Pay</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Sunday Pay</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Special Holiday Pay</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Regular Holiday Pay</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Overtime</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Other Earnings</td>
                        <td>{{ isset($earnings) ? number_format($earnings, 2) : '--' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Deductions Section -->
        <div class="deductions">
            <h3>Deductions</h3>
            <table>
                <thead>
                    <tr>
                        <th>SSS</th>
                        <td>{{ number_format($sss, 2) }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Philhealth</td>
                        <td>{{ number_format($philHealth, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Pag-ibig</td>
                        <td>{{ number_format($pagIbig, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Other Deductions</td>
                        <td>{{ number_format($deductions, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Totals Section -->
        <div class="totals">
            <table>
                <thead>
                    <tr>
                        <th>Total Deductions</th>
                        <th>Total Earnings</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format($philHealth, 2) }}</td>
                        <td>{{ number_format($philHealth, 2) }}</td>
                    </tr>
                </tbody>
            </table>

        </div>
        <!-- Net Pay Section -->
        <div class="net-pay">
            <p><strong>Net Pay: {{ number_format($philHealth, 2) }}</strong></p>
        </div>
    </div>
</body>

</html>