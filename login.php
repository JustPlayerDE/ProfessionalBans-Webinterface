<?php
session_start();
require_once("./mysql.php");
require("./datamanager.php");

$Error = false;
if (isset($_POST["submit"])) {

  $stmt = $mysql->prepare("SELECT * FROM accounts WHERE USERNAME = :username");
  $stmt->bindParam(":username", $_POST['username'], PDO::PARAM_STR);
  $stmt->execute();
  $row = $stmt->fetch();

  if (!empty($row)) {
    if (password_verify($_POST['password'], $row["PASSWORD"])) {
      //Login war erfolgreich
      if (!hasGoogleAuth($row["USERNAME"])) {
        //Starte Session
        $_SESSION['username'] = $row["USERNAME"];
        if ($row["AUTHCODE"] == "initialpassword") {
          header("Location: resetpassword.php?name=" . $row["USERNAME"]);
          exit;
        }
        header('Location: index.php');
      } else {
        //Google Auth aktiviert
        $_SESSION['username_unauth'] = $row["USERNAME"];
        header('Location: verify.php');
      }
    } else {
      $Error = true;
    }
  } else {
    $Error = true;
  }
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" type="image/x-icon" href="css/favicon.ico">
</head>

<body>
  <form class="login" action="login.php" method="post">
    <?php
    if ($Error) {
      ?>
      <div class="error">
        <h4>Der Login ist fehlgeschlagen.</h4>
      </div>
    <?php
    }
    ?>
    <h1><i class="fas fa-user"></i> Login</h1>
    <input type="text" name="username" placeholder="Username" autocomplete="username" required><br>
    <input type="password" name="password" placeholder="Passwort" autocomplete="current-password" required><br>
    <button type="submit" name="submit">Login</button><br><br><br>
    <a href="recovery.php"><i class="fas fa-key"></i> Passwort vergessen?</a>
  </form>
</body>

</html>