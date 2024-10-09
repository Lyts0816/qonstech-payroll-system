<!-- resources/views/dtr/show.blade.php -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTR for {{ $employee->full_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            text-align: center;
            width: 100%;
            max-width: 800px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Additional styling for logo */
        .logo {
            width: 400px;
            /* Adjust logo size as needed */
            height: auto;
            /* Space below the logo */
        }

        .download-button {
            margin-top: 20px;
            /* Space above the button */
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: black;
            /* Bootstrap primary color */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }


        h2 {
            margin-top: -20px;
        }

        .download-button:hover {
            background-color: black;
            /* Darker shade on hover */
        }
    </style>
</head>

<body>
<div>
    <div style="display: flex; justify-content: center; align-items: center;">
        <button class="download-button" id="download-btn">Download DTR</button>
    </div>
    <div class="container" id="dtr-container">
        <!-- Company Logo -->
        <img src="{{ asset('images/qonstech.png') }}" alt="Company Logo" class="logo">

        <!-- Update path accordingly -->

        <h4 class="text-2xl font-bold">DAILY TIME RECORD</h4>
        <h2>{{ $employee->full_name }}</h2>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Morning Check-in</th>
                    <th>Morning Checkout</th>
                    <th>Afternoon Check-in</th>
                    <th>Afternoon Checkout</th>
                    <th>Morning Late</th>
                    <th>Morning Undertime</th>
                    <th>Afternoon Late</th>
                    <th>Afternoon Undertime</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->Date)->format('F j, Y') }}</td>
                        <td>{{ $row->Checkin_One > 0 ? $row->Checkin_One : 0 }}</td>
                        <td>{{ $row->Checkout_One > 0 ? $row->Checkout_One : 0 }}</td>
                        <td>{{ $row->Checkin_Two > 0 ? $row->Checkin_Two : 0 }}</td>
                        <td>{{ $row->Checkout_Two > 0 ? $row->Checkout_Two : 0 }}</td>
                        <td>{{ $row->MorningTardy > 0 ? $row->MorningTardy : 0 }} mins</td>
                        <td>{{ $row->MorningUndertime > 0 ? $row->MorningUndertime : 0 }} mins</td>
                        <td>{{ $row->AfternoonTardy > 0 ? $row->AfternoonTardy : 0 }} mins</td>
                        <td>{{ $row->AfternoonUndertime > 0 ? $row->AfternoonUndertime : 0 }} mins</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">Total:</td>
                    <td style="font-weight: bold;">
                        {{ $data->sum('MorningTardy') }} mins
                    </td>
                    <td style="font-weight: bold;">
                        {{ $data->sum('MorningUndertime') }} mins
                    </td>
                    <td style="font-weight: bold;">
                        {{ $data->sum('AfternoonTardy') }} mins
                    </td>
                    <td style="font-weight: bold;">
                        {{ $data->sum('AfternoonUndertime') }} mins
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>




    <script>
        document.getElementById('download-btn').addEventListener('click', function () {
            const downloadBtn = document.getElementById('download-btn');
            downloadBtn.style.display = 'none'; // Hide the button before capturing the canvas

            html2canvas(document.querySelector("#dtr-container")).then(canvas => {
                const link = document.createElement('a');
                link.download = '{{ $employee->full_name }}_DTR.png'; // Set download filename
                link.href = canvas.toDataURL(); // Convert canvas to data URL
                link.click(); // Trigger the download

                downloadBtn.style.display = 'block'; // Show the button again after download
            });
        });
    </script>

</body>

</html>