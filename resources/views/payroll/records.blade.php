<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Records</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Payroll Records</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Emp. Code No</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Project Site</th>
                    <th>Monthly Basic Salary</th>
                    <th>Hourly Rate</th>
                    <th>Salary Type</th>
                    <th>Regular Status</th>
                    @foreach ($payroll->dates as $date)
                        <th>{{ $date->format('M d, Y') }}</th>
                        <th>OT</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $index => $employee)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $employee->emp_code }}</td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->project_site }}</td>
                        <td>{{ number_format($employee->monthly_basic_salary, 2) }}</td>
                        <td>{{ number_format($employee->hourly_rate, 2) }}</td>
                        <td>OPEN</td>
                        <td>{{ $employee->regular_status ? 'YES' : 'NO' }}</td>
                        @foreach ($payroll->dates as $date)
                            <td>{{ $employee->getHoursWorked($date) }}</td>
                            <td>{{ $employee->getOvertimeHours($date) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
