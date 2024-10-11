<?php

$mysqlConfig = [
    'host' => 'localhost',
    'dbname' => 'payroll-system',
    'username' => 'root',
    'password' => ''
];


$Employee_ID = $_POST['Employee_ID'] ?? null;
$Checkin_One = $_POST['Checkin_One'] ?? null;
$Date = date('Y-m-d'); // Get the current date
$ProjectID = 0;

try {
    // Connect to MySQL
    $mysqlDsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['dbname']}";
    $mysqlPDO = new PDO($mysqlDsn, $mysqlConfig['username'], $mysqlConfig['password']);
    $mysqlPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if data is valid
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

        // Check if an entry for the Employee_ID and Date already exists
        $existingSQL = "SELECT * FROM attendance WHERE Employee_ID = :Employee_ID AND Date = :Date";
        $stmt = $mysqlPDO->prepare($existingSQL);
        $stmt->execute([':Employee_ID' => $Employee_ID, ':Date' => $Date]);
        $existingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($checkinTime >= $morningCheckinStart && $checkinTime <= $morningCheckinEnd) {
            // Morning check-in
            if ($existingAttendance) {
                $updateSQL = "UPDATE attendance SET Checkin_One = :Checkin_One, ProjectID = :ProjectID WHERE Employee_ID = :Employee_ID AND Date = :Date";
            } else {
                $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_One, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID)";
            }
        } elseif ($checkinTime >= $morningCheckoutStart && $checkinTime <= $morningCheckoutEnd) {
            // Morning check-out
            if ($existingAttendance) {
                $updateSQL = "UPDATE attendance SET Checkout_One = :Checkin_One, ProjectID = :ProjectID WHERE Employee_ID = :Employee_ID AND Date = :Date";
            } else {
                $insertSQL = "INSERT INTO attendance (Employee_ID, Checkout_One, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID)";
            }
        } elseif ($checkinTime >= $afternoonCheckinStart && $checkinTime <= $afternoonCheckinEnd) {
            // Afternoon check-in
            if ($existingAttendance) {
                $updateSQL = "UPDATE attendance SET Checkin_Two = :Checkin_One, ProjectID = :ProjectID WHERE Employee_ID = :Employee_ID AND Date = :Date";
            } else {
                $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_Two, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID)";
            }
        } elseif ($checkinTime >= $afternoonCheckoutStart && $checkinTime <= $afternoonCheckoutEnd) {
            // Afternoon check-out
            if ($existingAttendance) {
                $updateSQL = "UPDATE attendance SET Checkout_Two = :Checkin_One, ProjectID = :ProjectID WHERE Employee_ID = :Employee_ID AND Date = :Date";
            } else {
                $insertSQL = "INSERT INTO attendance (Employee_ID, Checkout_Two, Date, ProjectID) VALUES (:Employee_ID, :Checkin_One, :Date, :ProjectID)";
            }
        } else {
            throw new Exception("Invalid check-in time.");
        }

        // Execute the appropriate SQL statement
        if (isset($updateSQL)) {
            $stmt = $mysqlPDO->prepare($updateSQL);
            $stmt->execute([':Employee_ID' => $Employee_ID, ':Checkin_One' => $Checkin_One, ':Date' => $Date, ':ProjectID' => $ProjectID]);
        } elseif (isset($insertSQL)) {
            $stmt = $mysqlPDO->prepare($insertSQL);
            $stmt->execute([':Employee_ID' => $Employee_ID, ':Checkin_One' => $Checkin_One, ':Date' => $Date, ':ProjectID' => $ProjectID]);
        }

        return response()->json(['message' => 'Data transferred successfully.']);
    } else {
        return response()->json(['error' => 'Invalid data.'], 400);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}