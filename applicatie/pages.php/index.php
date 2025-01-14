<?php
session_start();
require_once('../db_connectie.php');
require_once('functions.php');

$message = '';
$role = handleLogin();

if ($role === 'Personnel') {
  header("Location: bestellingsoverzicht.php");
  exit();
} elseif ($role === 'client') {
  header("Location: dashboard.php");
  exit();
} elseif (is_array($role)) {
  $message = implode("<br>", $role);
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inloggen</title>
</head>

<body>
  <h1>Inloggen</h1>

  <?php if ($message): ?>
    <p><?php echo $message; ?></p>
  <?php endif; ?>

  <form action="" method="POST">
    <label for="username">Gebruikersnaam:</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Wachtwoord:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Inloggen</button>
  </form>

  <p>Nog geen account? <a href="register.php">Maak hier een account aan</a></p>
</body>

</html>