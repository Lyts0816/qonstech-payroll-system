<!-- resources/views/dtr/show.blade.php -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: white;
        }

        .container {
            width: 100%;
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
            margin: 5px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;

        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: right;
        }

        th {
            background-color: #f2f2f2;
        }

        .logo {
            width: 200px;
            height: auto;
        }
    </style>
</head>
<?php
$imageData = base64_encode(file_get_contents(public_path('images/qonstech.png')));
$src = 'data:image/png;base64,' . $imageData;
?>

<body>
    <div>
        <div class="container" id="dtr-container">
            <img src="{{ $src }}" alt="Company Logo" class="logo">

            <h2>ATTENDANCE SUMMARY REPORT</h2>

            <table>
                @php
                   $employee = $payrollRecords->first(); // Get the first employee record
                    $formattedPeriod = 'N/A'; // Default value

                    if ($employee && isset($employee['Period'])) { // Check if the employee exists
                        $dates = explode(' - ', $employee['Period']);
                        if (count($dates) == 2) {
                            $startDate = \Carbon\Carbon::parse(trim($dates[0]))->format('m-d-Y');
                            $endDate = \Carbon\Carbon::parse(trim($dates[1]))->format('m-d-Y');
                            $formattedPeriod = "{$startDate} - {$endDate}";
                        }
                    }
                @endphp

                @if ($employee) <!-- Check if the employee exists -->
                    <tr>
                        <td colspan="3" style="text-align: left"><b>Project Name:</b>
                            {{ $employee['ProjectName'] ?? 'N/A' }}</td>
                        <td colspan="3" style="text-align: left"><b>Work Schedule:</b> 8:00 AM – 5:00 PM</td>
                        <td colspan="3" style="text-align: left"><b>Payroll Period:</b> {{ $formattedPeriod }}</td>
                    </tr>
                @endif


                <thead>
                    <tr>
                        <th style="text-align:left">Employee Name</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                        <th>Sunday</th>
                        <th>Total Hours Worked</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payrollRecords as $employee)
                        <tr>
                            <td style="text-align:left">
                                {{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}
                            <td>{{ number_format($employee['Monday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Tuesday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Wednesday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Thursday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Friday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Saturday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['Sunday'] ?? 0, 2) }}</td>
                            <td>{{ number_format($employee['TotalHours'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>