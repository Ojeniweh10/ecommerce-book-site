<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if (!defined('FILTER_FLAG_HOST_REQUIRED')) {
        define('FILTER_FLAG_HOST_REQUIRED', 1);
    }

    include 'includes/session.php';

    if(isset($_POST['signup'])){
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $repassword = $_POST['repassword'];

        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['email'] = $email;

        if($password != $repassword){
            $_SESSION['error'] = 'Passwords did not match';
            header('location: signup.php');
        }
        else{
            $conn = $pdo->open();

            $stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM users WHERE email=:email");
            $stmt->execute(['email'=>$email]);
            $row = $stmt->fetch();
            if($row['numrows'] > 0){
                $_SESSION['error'] = 'Email already taken';
                header('location: signup.php');
            }
            else{
                $now = date('Y-m-d');
                $password = password_hash($password, PASSWORD_DEFAULT);

                //generate code
                $set='123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $code=substr(str_shuffle($set), 0, 12);

                try{
                    $stmt = $conn->prepare("INSERT INTO users (email, password, firstname, lastname, activate_code, created_on) VALUES (:email, :password, :firstname, :lastname, :code, :now)");
                    $stmt->execute(['email'=>$email, 'password'=>$password, 'firstname'=>$firstname, 'lastname'=>$lastname, 'code'=>$code, 'now'=>$now]);

                    // Automatically activate the account
                    $stmt = $conn->prepare("UPDATE users SET status=1 WHERE email=:email");
                    $stmt->execute(['email'=>$email]);

                    unset($_SESSION['firstname']);
                    unset($_SESSION['lastname']);
                    unset($_SESSION['email']);

                    $_SESSION['success'] = 'Account created and activated. You can now log in.';
                    header('location: login.php');

                }
                catch(PDOException $e){
                    $_SESSION['error'] = $e->getMessage();
                    header('location: register.php');
                }

                $pdo->close();

            }

        }

    }
    else{
        $_SESSION['error'] = 'Fill up signup form first';
        header('location: signup.php');
    }

?>
