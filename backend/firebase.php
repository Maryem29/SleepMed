<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;

$pythonPath = trim(shell_exec("which python3"));

if (!$pythonPath) {
    die("Error: Python is not installed or not found in PATH. Please install Python 3 and ensure it's added to the PATH.");
}

$scriptDir = __DIR__;
$pythonScriptPath = $scriptDir . "/read_h5.py";

if (!file_exists($pythonScriptPath)) {
    die("Error: The Python script is not found at: $pythonScriptPath");
}

$userId = "user_id";

$command = escapeshellcmd("$pythonPath $pythonScriptPath $userId");

$output = shell_exec($command . " 2>&1"); // Capture output and errors

if ($output) {
    echo "<pre>$output</pre>";
} else {
    echo "No output returned or an error occurred.";
}

function get_sleep_data_by_week($userId, $week) {
    $database = initialize_firebase();
    $heartbeatRef = $database->getReference("users/$userId/heartbeat_data");

    try {
        $date = new DateTime();
        [$year, $weekNumber] = explode('-W', $week);
        $date->setISODate((int)$year, (int)$weekNumber);

        $weekStart = $date->format('Y-m-d');
        $date->modify('+6 days');
        $weekEnd = $date->format('Y-m-d');

        $data = $heartbeatRef->orderByKey()
            ->startAt($weekStart)
            ->endAt($weekEnd)
            ->getValue();

        return $data ?? [];
    } catch (FirebaseException $e) {
        die("Firebase error: " . $e->getMessage());
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
function register_user($userId, $userData) {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        $database->getReference('users/' . $userId)->set($userData);
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error registering user: " . $e->getMessage() . "\n";
        exit;
    }
}

function update_user_profile($userId, $profileData) {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        $database->getReference('users/' . $userId)->update($profileData);
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error updating user profile: " . $e->getMessage() . "\n";
        exit;
    }
}

function upload_sleep_data($userId, $sleepData) {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        $database->getReference('users/' . $userId . '/sleepData')->push($sleepData);
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error uploading sleep data: " . $e->getMessage() . "\n";
        exit;
    }
}

function get_all_users_data() {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        $snapshot = $database->getReference('users')->getSnapshot();
        $usersData = $snapshot->getValue();
        return $usersData;
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error retrieving all users data: " . $e->getMessage() . "\n";
        exit;
    }
}

function get_user_data($userId) {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        $userSnapshot = $database->getReference('users/' . $userId)->getSnapshot();
        $userData = $userSnapshot->getValue();
        return $userData;
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error retrieving user data: " . $e->getMessage() . "\n";
        exit;
    }
}

function get_sleep_data($userId) {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        $snapshot = $database->getReference('users/' . $userId . '/sleepData')->getSnapshot();
        $sleepData = $snapshot->getValue();
        return $sleepData;
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error retrieving sleep data: " . $e->getMessage() . "\n";
        exit;
    }
}

// Add function to get sleep data by date
function get_sleep_data_by_date($userId, $date) {
    $firebase = initialize_firebase();
    $database = $firebase->createDatabase(); // Fixed method call

    try {
        // Access the sleep data for the user and filter by the specific date
        $snapshot = $database->getReference('users/' . $userId . '/sleepData')
            ->orderByChild('date')  // Assuming you have a 'date' field in your data
            ->equalTo($date)  // Filter by the provided date
            ->getSnapshot();

        $sleepData = $snapshot->getValue();
        return $sleepData;
    } catch (FirebaseException $e) {
        echo "Firebase SDK error: " . $e->getMessage() . "\n";
        exit;
    } catch (Exception $e) {
        echo "Error retrieving sleep data: " . $e->getMessage() . "\n";
        exit;
    }
    function getDataForDate($userId, $selectedDate) {
        // Reference to the user's heartbeat data
        $userRef = $database->getReference('users/'.$userId.'/heartbeat_data');
    
        // Query for data corresponding to the selected date
        $data = $userRef->orderByChild('date')->equalTo($selectedDate)->getValue();
    
        return $data;
    }
}
?>