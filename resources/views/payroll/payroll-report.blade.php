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

        .container {
            width: 100%;
            max-width: 13in;
            margin: 10px auto;
            border: 1px solid #000;
            padding: 10px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 300px;
            height: auto;
            margin: 0;
        }

        .header div {
            text-align: left;
            flex-grow: 1;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
        }

        .header p {
            font-size: 14px;
        }

        .project-name {
            font-size: 18px;
            color: red;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
            /* Smaller font size for better fit */
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px;
            /* Reduced padding to fit more content */
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .total-row th,
        .total-row td {
            font-weight: bold;
        }

        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signatures div {
            text-align: center;
            width: 30%;
        }

        #exportPDF {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('js/jspdf.umd.min.js') }}"></script> -->
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                @php
                    $employeeda = $payrollData->first();

                @endphp
                @if ($employeeda)
                    <h1>HRD Raw Payroll Report</h1>
                    <p>Period covered: {{ $employeeda['Period'] ?? '' }}</p>
                    <p class="project-name">{{ $employeeda['ProjectName'] ?? '' }}</p>
                @endif
            </div>
            <img src="{{ asset('images/qonstech.png') }}" alt="Company Logo" class="logo">
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2">ID</th>
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Position</th>
                    <th rowspan="2">Project Site</th>
                    <th rowspan="2">Monthly Salary</th>
                    <th rowspan="2">Hourly Rate</th>
                    <th rowspan="2">Salary Type</th>
                    <th rowspan="2">Regular Status</th>
                    <th rowspan="2">Regular Total Hours</th>
                    <th rowspan="2">Regular O.T Hours</th>
                    <th rowspan="2">Sunday Hours</th>
                    <th colspan="2">Holidays</th>
                    <th rowspan="2">Paid Amount For Regular Hours (Basic Pay)</th>
                    <th rowspan="2">Paid Amount For O.T Hours 25%</th>
                    <th rowspan="2">Paid Amount For Sunday Hours 30%</th>
                    <th rowspan="2">Paid Amount For Legal Holiday</th>
                    <th rowspan="2">Paid Amount For Special Holiday 30%</th>
                    <th rowspan="2">Other Allowance</th>
                    <th rowspan="2">Gross Amount</th>
                    <th colspan="7">Deductions</th>
                    <th rowspan="2">Total Deductions & Adjustment</th>
                    <th rowspan="2">NET PAY</th>
                </tr>
                <tr>
                    <th>Legal Holiday Hours</th>
                    <th>Special Holiday Hours</th>
                    <th>TAXES</th>
                    <th>SSS</th>
                    <th>PHIC</th>
                    <th>HDMF</th>
                    <th>Total Government Deduction</th>
                    <th>CASH ADVANCES</th>
                    <th>Total Office Deduction & Adjustment</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrollData as $employee)
                    <tr>
                        <td>{{ $employee['EmployeeID'] ?? '' }}</td>
                        <td>{{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}
                        </td>
                        <td>{{ $employee['position'] ?? '' }}</td>
                        <td>{{ $employee['ProjectName'] ?? '' }}</td>
                        <td>₱{{ number_format($employee['monthlySalary'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['hourlyRate'] ?? 0, 2) }}</td>
                        <td>{{ $employee['SalaryType'] ?? '' }}</td>
                        <td>{{ $employee['RegularStatus'] ?? '' }}</td>
                        <td>{{ $employee['TotalHours'] ?? 0 }}</td>
                        <td>{{ $employee['TotalOvertimeHours'] ?? 0 }}</td>
                        <td>{{ $employee['TotalHoursSunday'] ?? 0 }}</td>
                        <td>{{ $employee['TotalHrsRegularHol'] ?? 0 }}</td>
                        <td>{{ $employee['TotalHrsSpecialHol'] ?? 0 }}</td>
                        <td>₱{{ number_format($employee['BasicPay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['TotalOvertimePay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['SundayPay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['RegularHolidayPay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['SpecialHolidayPay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['EarningPay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['GrossPay'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format(0, 2) }}</td>
                        <td>₱{{ number_format($employee['SSSDeduction'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['PhilHealthDeduction'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['PagIbigDeduction'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['TotalGovDeductions'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['DeductionFee'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['TotalOfficeDeductions'] ?? 0) }}</td>
                        <td>₱{{ number_format($employee['TotalDeductions'] ?? 0, 2) }}</td>
                        <td>₱{{ number_format($employee['NetPay'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div>
                <p>Prepared by:</p>
                <p>ALMA MAE S. GEPELLANO</p>
                <p>HR OFFICER</p>
            </div>
            <div>
                <p>Certified Correct by:</p>
                <p>Rosimo Jonas</p>
                <p>VP ADMIN</p>
            </div>
            <div>
                <p>Approved by:</p>
                <p>Mary Jane Villanueva</p>
                <p>VP FINANCE</p>
            </div>
        </div>
    </div>

    <!-- Export to PDF button -->
    <button id="exportPDF">Export to PDF</button>

    <script>
document.getElementById('exportPDF').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;

    // Initialize jsPDF with landscape orientation and custom size (8x13 inches)
    const doc = new jsPDF('landscape', 'pt', [576, 936]);

    const element = document.querySelector('.container');

    if (element) {
        doc.html(element, {
            callback: function (doc) {
                doc.save('payroll-report.pdf');
            },
            x: 10,
            y: 10,
            autoPaging: 'text',
            width: 800, // Adjust width to fit the 8x13 size
            windowWidth: 1200
        });
    } else {
        console.error('Element .container not found!');
    }
});

    </script>
</body>

</html>