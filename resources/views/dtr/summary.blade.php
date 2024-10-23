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

        .footer {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            /* margin-top: 30px; */
            /* margin-bottom: 30px; */
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
                        <td colspan="4" style="text-align: left"><b>Payroll Period:</b> {{ $formattedPeriod }}</td>
                    </tr>
                @endif


                <thead>
                <tr>
                    <th>ID</th>
                    <th style="text-align: left;">Name</th>
                    <th>Date</th>
                    <th>Morning Check In</th>
                    <th>Morning Check Out</th>
                    <th>Afternoon Check In</th>
                    <th>Afternoon Check Out</th>
                    <th>Tardiness</th>
                    <th>Undertime</th>
                    <th>Total Hours Worked</th>
                </tr>
								</thead>
								<tbody>
										@foreach ($payrollRecords as $record)
												<tr>
														<td>{{ isset($record['EmployeeID']) ? $record['EmployeeID'] : '' }}</td>
														<td style="text-align: left;">{{ $record['first_name'] . ' ' . ($record['middle_name'] ?? '') . ' ' . ($record['last_name'] ?? '') }}</td>
														<td>{{ isset($record['DateNow']) ? $record['DateNow'] : '' }}</td>
														<td>{{ isset($record['MorningCheckIn']) ? $record['MorningCheckIn'] : '' }}</td>
														<td>{{ isset($record['MorningCheckOut']) ? $record['MorningCheckOut'] : '' }}</td>
														<td>{{ isset($record['AfternoonCheckIn']) ? $record['AfternoonCheckIn'] : '' }}</td>
														<td>{{ isset($record['AfternoonCheckOut']) ? $record['AfternoonCheckOut'] : '' }}</td>
														<td>{{ isset($record['TotalTardiness']) ? round($record['TotalTardiness'], 2) . ' minute/s': '0 minute' }}</td>
														<td>{{ isset($record['TotalUndertime']) ? round($record['TotalUndertime'], 2) . ' minute/s': '0 minute' }}</td>
														<td>{{ isset($record['TotalHours']) ? round($record['TotalHours'], 2) . ' hour/s': '0 hour' }}</td>
												</tr>
										@endforeach
								</tbody>
            </table>
            <div class="footer">
                <div class="footer-section">
                    <p>Prepared By:</p>

                    <b>ALMA MAE S. GEPELLANO</b><br>
                    <small><em>Human Resource Officer</em></small>

                </div>
                <div class="footer-section">
                    <p>Date Generated:</p><br>
                    <b>{{ now()->format('F d, Y H:i:s') }}</b>

                </div>
            </div>
        </div>
    </div>

</body>

</html>