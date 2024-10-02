<?php
// Database configuration
$mysqlConfig = [
    'host' => 'localhost',
    'dbname' => 'payroll-master',
    'username' => 'root',
    'password' => ''
];

try {
    // // Connect to MySQL
    // $mysqlDsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['dbname']}";
    // $mysqlPDO = new PDO($mysqlDsn, $mysqlConfig['username'], $mysqlConfig['password']);
    // $mysqlPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // // Get data from POST request
    // // $id = $_POST['id'] ?? null;
    // $Employee_ID = $_POST['Employee_ID'] ?? null;
    // $Checkin_One = $_POST['Checkin_One'] ?? null;

    // // Check if data is valid
    // if ($Employee_ID !== null) {
    //     // Prepare and execute insert statement in MySQL
    //     $insertSQL = "INSERT INTO attendance (Employee_ID, Checkin_One) VALUES (:Employee_ID, :Checkin_One) ON DUPLICATE KEY UPDATE Employee_ID = :Employee_ID";
    //     $stmt = $mysqlPDO->prepare($insertSQL);
    //     $stmt->execute([':Employee_ID' => $Employee_ID, ':Checkin_One' => $Checkin_One]);
        
    //     echo "Data transferred successfully.";
    // } else {
    //     echo "Invalid data.";
    // }

    $mysqlDsn = "mysql:host={$mysqlConfig['host']};dbname={$mysqlConfig['dbname']}";
    $mysqlPDO = new PDO($mysqlDsn, $mysqlConfig['username'], $mysqlConfig['password']);
    $mysqlPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get data from POST request
    $Employee_ID = $_POST['Employee_ID'] ?? null;
    $Checkin_One = $_POST['Checkin_One'] ?? null;
    
    // Check if data is valid
    if ($Employee_ID !== null && $Checkin_One !== null) {
        // Determine if the Checkin_One time is morning or afternoon
        $checkinTime = new DateTime($Checkin_One);
        $isMorning = $checkinTime->format('H') < 12;
        $isAfternoon = !$isMorning;
    
        // Retrieve the existing attendance record if it exists
        $stmt = $mysqlPDO->prepare("SELECT * FROM attendance WHERE Employee_ID = :Employee_ID AND Date = CURDATE()");
        $stmt->execute([':Employee_ID' => $Employee_ID]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Initialize $totalMinutes
        $totalMinutes = 0;
    
        // Get current time
        $currentTime = date('H:i:s');
    
        if ($attendance) {
            // Update existing record
            if ($isMorning) {
                if (!$attendance['Checkin_One']) {
                    // If no morning check-in, set check-in
                    $attendance['Checkin_One'] = $currentTime;
                } elseif (!$attendance['Checkout_One']) {
                    // If morning check-in exists but no checkout, set checkout
                    $attendance['Checkout_One'] = $currentTime;
                }
            } else {
                if (!$attendance['Checkin_Two']) {
                    // If no afternoon check-in, set check-in
                    $attendance['Checkin_Two'] = $currentTime;
                } elseif (!$attendance['Checkout_Two']) {
                    // If afternoon check-in exists but no checkout, set checkout
                    $attendance['Checkout_Two'] = $currentTime;
                }
            }
    
            // Calculate Total Hours
            if ($attendance['Checkin_One'] && $attendance['Checkout_One']) {
                $checkinOne = new DateTime($attendance['Checkin_One']);
                $checkoutOne = new DateTime($attendance['Checkout_One']);
    
                // Ensure checkout is after checkin
                if ($checkoutOne > $checkinOne) {
                    $minutesOne = $checkoutOne->diff($checkinOne)->i; // Minutes difference
                    $totalMinutes += $minutesOne;
                } else {
                    error_log('Checkout time is earlier than checkin time for morning shift.');
                }
            }
    
            if ($attendance['Checkin_Two'] && $attendance['Checkout_Two']) {
                $checkinTwo = new DateTime($attendance['Checkin_Two']);
                $checkoutTwo = new DateTime($attendance['Checkout_Two']);
    
                // Ensure checkout is after checkin
                if ($checkoutTwo > $checkinTwo) {
                    $minutesTwo = $checkoutTwo->diff($checkinTwo)->i; // Minutes difference
                    $totalMinutes += $minutesTwo;
                } else {
                    error_log('Checkout time is earlier than checkin time for afternoon shift.');
                }
            }
    
            // Convert minutes to hours and update Total Hours
            $attendance['Total_Hours'] = $totalMinutes / 60;
    
            // Update the record in the database
            $updateSQL = "UPDATE attendance SET Checkin_One = :Checkin_One, Checkout_One = :Checkout_One, Checkin_Two = :Checkin_Two, Checkout_Two = :Checkout_Two, Total_Hours = :Total_Hours WHERE Employee_ID = :Employee_ID AND Date = CURDATE()";
            $stmt = $mysqlPDO->prepare($updateSQL);
            $stmt->execute([
                ':Checkin_One' => $attendance['Checkin_One'],
                ':Checkout_One' => $attendance['Checkout_One'],
                ':Checkin_Two' => $attendance['Checkin_Two'],
                ':Checkout_Two' => $attendance['Checkout_Two'],
                ':Total_Hours' => $attendance['Total_Hours'],
                ':Employee_ID' => $Employee_ID
            ]);
        } else {
            // Create a new attendance record
            $insertSQL = "INSERT INTO attendance (Employee_ID, Date, Checkin_One, Checkout_One, Checkin_Two, Checkout_Two, Total_Hours) VALUES (:Employee_ID, CURDATE(), :Checkin_One, NULL, :Checkin_Two, NULL, 0)";
            $stmt = $mysqlPDO->prepare($insertSQL);
            $stmt->execute([
                ':Employee_ID' => $Employee_ID,
                ':Checkin_One' => $isMorning ? $currentTime : null,
                ':Checkin_Two' => $isAfternoon ? $currentTime : null
            ]);
        }
    
        echo "Data transferred successfully.";
    } else {
        echo "Invalid data.";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
