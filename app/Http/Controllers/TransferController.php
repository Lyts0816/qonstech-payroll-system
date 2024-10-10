<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDO;
use DateTime;
use Exception;

class TransferController extends Controller
{
    public function runTransfer(Request $request)
    {
        $mysqlConfig = [
            'host' => 'localhost',
            'dbname' => 'payroll-system',
            'username' => 'root',
            'password' => ''
        ];

        $Employee_ID = $request->input('Employee_ID');
        $Checkin_One = $request->input('Checkin_One');
        $Date = date('Y-m-d');
        $ProjectID = 0;

        try {
            // Connect to MySQL
            $mysqlDsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['dbname']}";
            $mysqlPDO = new PDO($mysqlDsn, $mysqlConfig['username'], $mysqlConfig['password']);
            $mysqlPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($Employee_ID !== null && $Checkin_One !== null) {
                // Define time intervals
                $checkinTime = new DateTime($Checkin_One);
                $morningCheckinStart = new DateTime('05:00:00');
                $morningCheckinEnd = new DateTime('08:00:00');
                $morningCheckoutStart = new DateTime('11:00:00');
                $morningCheckoutEnd = new DateTime('11:59:59');
                $afternoonCheckinStart = new DateTime('12:00:00');
                $afternoonCheckinEnd = new DateTime('13:00:00');
                $afternoonCheckoutStart = new DateTime('16:00:00');
                $afternoonCheckoutEnd = new DateTime('17:00:00');

                // Fetch employee information based on Employee_ID
                $employeeSQL = "SELECT * FROM employees WHERE id = :Employee_ID";
                $stmt = $mysqlPDO->prepare($employeeSQL);
                $stmt->execute([':Employee_ID' => $Employee_ID]);
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);

                // Set ProjectID if available
                if ($employee && isset($employee['project_id'])) {
                    $ProjectID = $employee['project_id'];
                }

                // Determine insert SQL based on check-in/out time
                if ($checkinTime >= $morningCheckinStart && $checkinTime <= $morningCheckinEnd) {
                    $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_One, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID) ON DUPLICATE KEY UPDATE Checkin_One = :Checkin_One, Date = :Date, ProjectID = :ProjectID";
                } elseif ($checkinTime >= $morningCheckoutStart && $checkinTime <= $morningCheckoutEnd) {
                    $insertSQL = "INSERT INTO attendance (Employee_ID, Checkout_One, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID) ON DUPLICATE KEY UPDATE Checkout_One = :Checkin_One, Date = :Date, ProjectID = :ProjectID";
                } elseif ($checkinTime >= $afternoonCheckinStart && $checkinTime <= $afternoonCheckinEnd) {
                    $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_Two, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID) ON DUPLICATE KEY UPDATE Checkin_Two = :Checkin_One, Date = :Date, ProjectID = :ProjectID";
                } elseif ($checkinTime >= $afternoonCheckoutStart && $checkinTime <= $afternoonCheckoutEnd) {
                    $insertSQL = "INSERT INTO attendance (Employee_ID, Checkout_Two, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID) ON DUPLICATE KEY UPDATE Checkout_Two = :Checkin_One, Date = :Date, ProjectID = :ProjectID";
                } else {
                    throw new Exception("Invalid check-in time.");
                }

                // Execute the insert SQL
                $stmt = $mysqlPDO->prepare($insertSQL);
                $stmt->execute([':Employee_ID' => $Employee_ID, ':Checkin_One' => $Checkin_One, ':Date' => $Date, ':ProjectID' => $ProjectID]);

                return response()->json(['message' => 'Data transferred successfully.']);
            } else {
                return response()->json(['error' => 'Invalid data.'], 400);
            }

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
