<?php
// Database connection configuration
$host = 'localhost';
$db   = 'migrations'; 
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Function to get user by email
if (!function_exists('getUserByEmail')) {
    function getUserByEmail($pdo, $email) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}

// Function to verify password
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Function to register a new user
if (!function_exists('registerUser')) {
    function registerUser($pdo, $name, $surname, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, lastname, email, password, is_admin) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$name, $surname, $email, $hashedPassword]);
            return true;
        } catch (PDOException $e) {
            error_log('Error registering user: ' . $e->getMessage());
            return false;
        }
    }
}
function joinEvent($pdo, $event_id, $user_email) {
    //  get the current attendees
    $stmt = $pdo->prepare("SELECT attendees FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        $attendees = $event['attendees'] ? $event['attendees'] . ', ' . $user_email : $user_email;
        
        // update the event with the new attendees list
        $stmt = $pdo->prepare("UPDATE events SET attendees = ? WHERE id = ?");
        return $stmt->execute([$attendees, $event_id]);
    }
    
    return false;
}
// Function to create an admin user
if (!function_exists('createAdminUser')) {
    function createAdminUser($pdo, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, lastname, email, password, is_admin) VALUES (?, ?, ?, ?, 1)");
            return $stmt->execute(['Admin', 'User', $email, $hashedPassword]);
        } catch (PDOException $e) {
            error_log('Error creating admin user: ' . $e->getMessage());
            return false;
        }
    }
}

// Function to get all events
if (!function_exists('getAllEvents')) {
    function getAllEvents($pdo) {
        $stmt = $pdo->query("SELECT * FROM events ORDER BY data_event ASC");
        return $stmt->fetchAll();
    }
}

// Function to add a new event
function addEvent($pdo, $name, $date, $attendees) {
    $stmt = $pdo->prepare("INSERT INTO events (name, data_event, attendees) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $date, $attendees]);
}
// Function to get a single event by ID
function getEventById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to update an existing event
function updateEvent($pdo, $id, $name, $date, $attendees) {
    $stmt = $pdo->prepare("UPDATE events SET name = ?, data_event = ?, attendees = ? WHERE id = ?");
    return $stmt->execute([$name, $date, $attendees, $id]);
}

// Function to delete an event
function deleteEvent($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    return $stmt->execute([$id]);
}

//Function for forgot password
function storeResetToken($pdo, $user_id, $token, $expires) {
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    return $stmt->execute([$token, $expires, $user_id]);
}

function getUserByResetToken($pdo, $token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateUserPassword($pdo, $user_id, $hashed_password) {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashed_password, $user_id]);
}

function clearResetToken($pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = ?");
    return $stmt->execute([$user_id]);
}
?>
