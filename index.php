<?php
session_start();
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Simulate login/registration success
if (isset($_POST['login']) || isset($_POST['register'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_name'] = isset($_POST['name']) ? $_POST['name'] : 'User';
    
    // Check if the user is an admin
    if ($_POST['email'] === 'admin@example.com' && $_POST['password'] === 'adminpassword') {
        $_SESSION['is_admin'] = true;
        $page = 'admin';
    } else {
        $page = 'home';
    }
}

// Check if user is logged in for the home and admin pages
if (($page === 'home' || $page === 'admin') && !isset($_SESSION['logged_in'])) {
    $page = 'login';
}

// Redirect non-admin users trying to access the admin page
if ($page === 'admin' && !isset($_SESSION['is_admin'])) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/styles/<?php 
        echo $page === 'home' ? 'homes.css' : 
            ($page === 'login' ? 'styles.css' : 
                ($page === 'admin' ? 'admin.css' : 'login.css')); 
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
                    <span>Enter your email</span>
                    <input type="email" name="email" id="email" required>
                    <span>Enter your password</span>
                    <input type="password" name="password" id="password" required>
                    <button type="submit" name="login">Log In</button>
                    <div class="forgot">
                      <a href=""> Forgot password</a>
                    </div>     
                    <div class="account">
                        <p>Don't have an account? <a href="?page=register">Register</a></p>
                    </div>
                </form>
            </div>
        <?php elseif ($page === 'register'): ?>
            <h2>Create Account</h2>
            <div class="container">
                <form action="index.php" method="POST">
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
            <div class="sidebar">
                <a class="active" href="#home">All Events</a>
                <a href="#news">Add Events</a>
                <a href="#contact">Log out</a>
            </div>
            <div class="content">
            </div>
        </div>
        <?php else: ?>
            <h1>Hi <?php echo htmlspecialchars($_SESSION['user_name']); ?>, here are your events</h1>
            <div class="container">
                <div class="first-event">
                    <h2>Name Event</h2>
                    <p>15-10-2022 15:00</p>
                    <button>JOIN</button>
                </div>
                <div class="second-event">
                    <h2>Name Event</h2>
                    <p>15-10-2022 15:00</p>
                    <button>JOIN</button>
                </div>
                <div class="third-event">
                    <h2>Name Event</h2>
                    <p>15-10-2022 15:00</p>
                    <button>JOIN</button>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>   
