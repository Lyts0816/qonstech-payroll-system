<?php

$mysqlConfig = [
    'host' => 'localhost',
    'dbname' => 'payroll-master',
    'username' => 'root',
    'password' => ''
];


$Employee_ID = $_POST['Employee_ID'] ?? null;
$Checkin_One = $_POST['Checkin_One'] ?? null;
$Date = date('Y-m-d'); // Get the current date

try {
    // Connect to MySQL
    $mysqlDsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['dbname']}";
    $mysqlPDO = new PDO($mysqlDsn, $mysqlConfig['username'], $mysqlConfig['password']);
    $mysqlPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if data is valid
    if ($Employee_ID !== null && $Checkin_One !== null) {
        // Determine if it's morning or afternoon for Checkin_One
        $checkinTime = new DateTime($Checkin_One);
        $morningCheckinStart = new DateTime('05:00:00');
        $morningCheckinEnd = new DateTime('08:00:00');
        $morningCheckoutStart = new DateTime('11:00:00');
        $morningCheckoutEnd = new DateTime('11:59:59');
        $afternoonCheckinStart = new DateTime('12:00:00');
        $afternoonCheckinEnd = new DateTime('13:00:00');
        $afternoonCheckoutStart = new DateTime('16:00:00');
        $afternoonCheckoutEnd = new DateTime('17:00:00');

        if ($checkinTime >= $morningCheckinStart && $checkinTime <= $morningCheckinEnd) {
            // Morning check-in
            $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_One, Date) VALUES (:Employee_ID, :Checkin_One, :Date) ON DUPLICATE KEY UPDATE Checkin_One = :Checkin_One, Date = :Date";
        } elseif ($checkinTime >= $morningCheckoutStart && $checkinTime <= $morningCheckoutEnd) {
            // Morning check-out
            $insertSQL = "INSERT INTO attendance (Employee_ID, Checkout_One, Date) VALUES (:Employee_ID, :Checkin_One, :Date) ON DUPLICATE KEY UPDATE Checkout_One = :Checkin_One, Date = :Date";
        } elseif ($checkinTime >= $afternoonCheckinStart && $checkinTime <= $afternoonCheckinEnd) {
            // Afternoon check-in
            $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_Two, Date) VALUES (:Employee_ID, :Checkin_One, :Date) ON DUPLICATE KEY UPDATE Checkin_Two = :Checkin_One, Date = :Date";
        } elseif ($checkinTime >= $afternoonCheckoutStart && $checkinTime <= $afternoonCheckoutEnd) {
            // Afternoon check-out
            $insertSQL = "INSERT INTO attendance (Employee_ID, Checkout_Two, Date) VALUES (:Employee_ID, :Checkin_One, :Date) ON DUPLICATE KEY UPDATE Checkout_Two = :Checkin_One, Date = :Date";
        } else {
            throw new Exception("Invalid check-in time.");
        }


        echo "SQL: $insertSQL\n";
        echo "Parameters: Employee_ID = $Employee_ID, Checkin_One = $Checkin_One, Date = $Date\n";

        $stmt = $mysqlPDO->prepare($insertSQL);
        $stmt->execute([':Employee_ID' => $Employee_ID, ':Checkin_One' => $Checkin_One, ':Date' => $Date]);

        echo "Data transferred successfully.";
    } else {
        echo "Invalid data.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}