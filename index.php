<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_connection.php';

error_log("Session variables: " . print_r($_SESSION, true));
error_log("POST variables: " . print_r($_POST, true));
error_log("GET variables: " . print_r($_GET, true));

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create admin user if it doesn't exist
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
    $stmt->execute(['admin@example.com']);
    if ($stmt->rowCount() == 0) {
        createAdminUser($pdo, 'admin@example.com', 'admin123');
    }
} catch (PDOException $e) {
    error_log('Error checking/creating admin user: ' . $e->getMessage());
}

$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Check if user is logged in for the home and admin pages
if (($page === 'home' || $page === 'admin') && !isset($_SESSION['logged_in'])) {
    $page = 'login';
}

// Redirect non-admin users trying to access the admin page
if ($page === 'admin' && !isset($_SESSION['is_admin'])) {
    $page = 'home';
}

// Handle login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $user = getUserByEmail($pdo, $email);
        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $page = $user['is_admin'] ? 'admin' : 'home';
            error_log("User logged in successfully. Session: " . print_r($_SESSION, true));
        } else {
            $_SESSION['login_error'] = 'Invalid email or password';
            error_log("Login failed for email: $email");
        }
    } catch (PDOException $e) {
        $_SESSION['login_error'] = 'Database error: ' . $e->getMessage();
        error_log("Database error during login: " . $e->getMessage());
    }
}

// Handle forgot password request
if (isset($_POST['forgot_password'])) {
    $email = $_POST['email'];
    try {
        $user = getUserByEmail($pdo, $email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store the token in the database
            storeResetToken($pdo, $user['id'], $token, $expires);
            
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'pedromuttenda@gmail.com'; // Replace with your SMTP username
                $mail->Password   = 'hyddvijlwkjkqvzo'; // Replace with your SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                //Recipients
                $mail->setFrom('noreply@yourdomain.com', 'Your Company Name');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $resetLink = "  http://localhost/Task%20backend/edusogno-task/new_password.php?token=" . urlencode($token);
                $mail->Body    = "Click the following link to reset your password: <a href='$resetLink'>Reset Password</a>";
                $mail->AltBody = "Click the following link to reset your password: $resetLink";
              
                $mail->send();
                $_SESSION['forgot_password_message'] = "An email has been sent with instructions to reset your password.";
            } catch (Exception $e) {
                $_SESSION['forgot_password_error'] = "Failed to send email. Error: {$mail->ErrorInfo}";
                error_log("Email sending failed: {$mail->ErrorInfo}");
            }
        } else {
            $_SESSION['forgot_password_error'] = "No user found with that email address.";
        }
    } catch (PDOException $e) {
        $_SESSION['forgot_password_error'] = "Database error: " . $e->getMessage();
        error_log("Database error during password reset: " . $e->getMessage());
    }
    header("Location: index.php?page=forgot_password");
    exit();
}

// Handle registration
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        if (registerUser($pdo, $name, $surname, $email, $password)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $page = 'home';
        } else {
            $_SESSION['register_error'] = 'Registration failed';
        }
    } catch (PDOException $e) {
        $_SESSION['register_error'] = 'Database error: ' . $e->getMessage();
    }
}

// Handle AJAX join event request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_event') {
    if (isset($_SESSION['logged_in'])) {
        $event_id = (int)$_POST['event_id'];
        $user_email = $_SESSION['user_email']; // Make sure to set this when the user logs in
        
        try {
            if (joinEvent($pdo, $event_id, $user_email)) {
                echo json_encode(['success' => true, 'message' => 'Successfully joined the event']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to join the event']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to join an event']);
        exit;
    }
}


// Handle admin actions
if ($page === 'admin' && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $name = $_POST['name_event'];
                    $date = $_POST['data_event'];
                    $attendees = $_POST['attendees'];
                    $user_id = $_SESSION['user_id']; // Add this line
                    addEvent($pdo, $name, $date, $attendees, $user_id); // Updated this line
                    break;
                case 'edit':
                    $id = (int)$_POST['id'];
                    $name = $_POST['name_event'];
                    $date = $_POST['data_event'];
                    $attendees = $_POST['attendees']; // Add this line if editing attendees
                    updateEvent($pdo, $id, $name, $date, $attendees); // Updated this line
                    break;
                case 'delete':
                    $id = (int)$_POST['id'];
                    deleteEvent($pdo, $id);
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['admin_error'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        $name = $_POST['name_event'];
        $date = $_POST['data_event'];
        $attendees = $_POST['attendees']; 
        $user_id = $_SESSION['user_id']; // Add this line
        
        try {
            if (addEvent($pdo, $name, $date, $attendees, $user_id)) { // Updated this line
                echo json_encode(['success' => true, 'message' => 'Event added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add event']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}
// Handle AJAX delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        $id = (int)$_POST['id'];
        
        try {
            if (deleteEvent($pdo, $id)) {
                echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete event']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}
// Handle AJAX edit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_event') {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        $id = (int)$_POST['id'];
        $name = $_POST['name_event'];
        $date = $_POST['data_event'];
        $attendees = $_POST['attendees'];
        
        try {
            if (updateEvent($pdo, $id, $name, $date, $attendees)) {
                echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update event']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}
// Get error message if it exists and clear it from the session
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);

$register_error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : '';
unset($_SESSION['register_error']);

$admin_error = isset($_SESSION['admin_error']) ? $_SESSION['admin_error'] : '';
unset($_SESSION['admin_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/styles/<?php 
        echo $page === 'home' ? 'home.css' : 
            ($page === 'login' ? 'styles.css' : 
                ($page === 'admin' ? 'admins.css' : 'login.css')); 
    ?>">
    <title>Edusogno - <?php echo ucfirst($page); ?></title>
</head>
<body>
    <header>
        <div class="top">
            <img src="https://yt3.googleusercontent.com/nL-9BX85Bs_XTcQHX_Dhpmd8bta65sIpXeGL_21Ga26EapZzdSjlvOCA3kYs1z6t4ibW9Hrrgg=s900-c-k-c0x00ffffff-no-rj" alt="logo">
        </div>
    </header>
    <main>
        <?php if ($page === 'login'): ?>
            <h2>Login to Your Account</h2>
            <div class="container">
                <form action="index.php" method="POST">
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                    <span>Enter your email</span>
                    <input type="email" name="email" id="email" required>
                    <span>Enter your password</span>
                    <input type="password" name="password" id="password" required>
                    <button type="submit" name="login">Log In</button>
                    <div class="forgot">
                      <a href="?page=forgot_password">Forgot password</a>
                    </div>     
                    <div class="account">
                        <p>Don't have an account? <a href="?page=register">Register</a></p>
                    </div>
                </form>
            </div>
        <?php elseif ($page === 'forgot_password'): ?>
            <h2>Forgot Password</h2>
            <div class="container">
                <form action="index.php" method="POST">
                <?php if (isset($_SESSION['forgot_password_error'])): ?>
                    <div class="error"><?php echo htmlspecialchars($_SESSION['forgot_password_error']); ?></div>
                    <?php unset($_SESSION['forgot_password_error']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['forgot_password_message'])): ?>
                    <div class="message"><?php echo htmlspecialchars($_SESSION['forgot_password_message']); ?></div>
                    <?php unset($_SESSION['forgot_password_message']); ?>
                <?php endif; ?>
                    <span>Enter your email</span>
                    <input type="email" name="email" id="email" required>
                    <button type="submit" name="forgot_password">Reset Password</button>
                    <div class="account">
                        <p>Remember your password? <a href="?page=login">Log In</a></p>
                    </div>
                </form>
            </div>
        <?php elseif ($page === 'register'): ?>
            <h2>Create Account</h2>
            <div class="container">
                <form action="index.php" method="POST">
                <?php if ($register_error): ?>
                    <div class="error"><?php echo htmlspecialchars($register_error); ?></div>
                <?php endif; ?>
                    <span>Enter your name</span>
                    <input type="text" name="name" id="name" required>
                    <span>Enter your surname</span>
                    <input type="text" name="surname" id="surname" required>
                    <span>Enter your email</span>
                    <input type="email" name="email" id="email" required>
                    <span>Enter your password</span>
                    <input type="password" name="password" id="password" required>
                    <button type="submit" name="register">Register</button>
                    <div class="account">
                        <p>Already have an account? <a href="?page=login">Log In</a></p>
                    </div>
                </form>
            </div>
      
            <?php elseif ($page === 'admin'): ?>
        <div class="admin">
            <a href="index.php">Logout</a>
            <div class="content">
                <h1>Admin</h1>
                <h2>All Events</h2>
                <?php if ($admin_error): ?>
                    <div class="error"><?php echo htmlspecialchars($admin_error); ?></div>
                <?php endif; ?>
                <table id="eventsTable">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Attendees</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    $events = getAllEvents($pdo);
                    foreach ($events as $event):
                    ?>
                    <tr id="event-<?php echo $event['id']; ?>">
                        <td><?php echo htmlspecialchars($event['id']); ?></td>
                        <td><?php echo htmlspecialchars($event['name']); ?></td>
                        <td><?php echo htmlspecialchars($event['data_event']); ?></td>
                        <td><?php echo htmlspecialchars($event['attendees']); ?></td>
                        <td>
                            <button class="edit" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">Edit</button>
                            <button class="delete" onclick="deleteEvent(<?php echo $event['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <h2>Add New Event</h2>
                <form id="eventForm">
                    <input type="hidden" id="event_id" name="id">
                    <input type="hidden" id="form_action" name="action" value="add_event">
                    <label for="name_event">Event Name:</label>
                    <input type="text" id="name_event" name="name_event" required>
                    <label for="attendees">Attendees:</label>
                    <input type="text" id="attendees" name="attendees" required>
                    <label for="data_event">Event Date:</label>
                    <input type="datetime-local" id="data_event" name="data_event" required>
                    <button type="submit" id="submitBtn">Add Event</button>
                </form>
                <div id="eventMessage"></div>
            </div>
        </div>
        <?php else: ?>
        <h1>Hi <?php echo htmlspecialchars($_SESSION['user_name']); ?>, here are your events</h1>
        <div class="container">
            <?php
            $events = getAllEvents($pdo);
            foreach ($events as $event):
            ?>
            <div class="event" id="event-<?php echo $event['id']; ?>">
                <h2><?php echo htmlspecialchars($event['name']); ?></h2>
                <p>Date: <?php echo htmlspecialchars($event['data_event']); ?></p>
                <p>Attendees: <span class="attendees"><br><?php echo htmlspecialchars($event['attendees']); ?><br></span></p>
                <button onclick="joinEvent(<?php echo $event['id']; ?>)">JOIN</button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    </main>
    <script>
    function editEvent(event) {
        document.getElementById('event_id').value = event.id;
        document.getElementById('name_event').value = event.name;
        document.getElementById('attendees').value = event.attendees;
        document.getElementById('data_event').value = event.data_event.replace(' ', 'T');
        document.getElementById('form_action').value = 'edit_event';
        document.getElementById('submitBtn').textContent = 'Update Event';
        document.getElementById('eventForm').scrollIntoView({behavior: 'smooth'});
    }

    function joinEvent(eventId) {
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=join_event&event_id=' + eventId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Update the attendees list in the UI
                let eventDiv = document.getElementById('event-' + eventId);
                let attendeesSpan = eventDiv.querySelector('.attendees');
                let currentAttendees = attendeesSpan.textContent.trim();
                let userEmail = '<?php echo $_SESSION['user_email']; ?>';
                if (currentAttendees) {
                    attendeesSpan.textContent = currentAttendees + ', ' + userEmail;
                } else {
                    attendeesSpan.textContent = userEmail;
                }
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while joining the event.');
        });
    }

    function deleteEvent(id) {
        if (confirm('Are you sure you want to delete this event?')) {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_event&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('event-' + id).remove();
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the event.');
            });
        }
    }

    document.getElementById('eventForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('eventMessage').innerHTML = data.message;
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('eventMessage').innerHTML = 'An error occurred while processing the event.';
        });
    });
    </script>
</body>
</html>
