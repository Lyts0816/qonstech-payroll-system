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
        .deductions {
            width: 100%;
            margin-bottom: 20px;
        }

        .employee-details table,
        .earnings table,
        .deductions table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-details th,
        .employee-details td,
        .earnings th,
        .earnings td,
        .deductions th,
        .deductions td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .totals {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .totals div {
            width: 48%;
            padding: 10px;
            border: 1px solid #000;
        }

        .totals div h3 {
            text-align: center;
            margin: 0;
        }

        .totals div td {
            text-align: center;
            margin: 0;

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
                <tr>
                    <th>Employee Name</th>
                    <td>{{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }}</td>
                    <th>Regular Status</th>
                    <td>{{ $employee->position->PositionName }}</td>
                </tr>
                <tr>
                    <th>Position</th>
                    <td>{{ $employee->position->PositionName }}</td>
                    <th>Salary Type</th>
                    <td>OPEN</td>
                </tr>
                <tr>
                    <th>Monthly Salary</th>
                    <td>{{ number_format($employee->position->MonthlySalary, 2) }}</td>
                    <th>Hourly Rate</th>
                    <td>{{ number_format($employee->position->HourlyRate, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
