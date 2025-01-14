<?php
require_once('../db_connectie.php');
require_once('functions.php');

$errors = [];
$success_message = '';
$username = $first_name = $last_name = $address = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address = !empty($_POST['address']) ? $_POST['address'] : NULL;
    $password = $_POST['password'];

    $errors = validateInput($username, $password);

    if (empty($errors) && usernameExists($username)) {
        $errors[] = "De gebruikersnaam is al in gebruik.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'client';
        if (registerUser($hashed_password, $username, $first_name, $last_name, $role, $address)) {
            $success_message = "Registratie succesvol!";
            $username = $first_name = $last_name = $address = '';
            $password = '';
        } else {
            $errors[] = "Er is een fout opgetreden tijdens de registratie.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Aanmaken</title>
</head>

<body>
    <h1>Account Aanmaken</h1>

    <?php if (!empty($success_message)): ?>
        <div style="color: green;">
            <strong><?php echo htmlspecialchars($success_message); ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <label for="username">Gebruikersnaam:</label>
        <input type="text" id="username" name="username" maxlength="255" required
            value="<?php echo htmlspecialchars($username); ?>">

        <label for="first_name">Voornaam:</label>
        <input type="text" id="first_name" name="first_name" maxlength="255" required
            value="<?php echo htmlspecialchars($first_name); ?>">

        <label for="last_name">Achternaam:</label>
        <input type="text" id="last_name" name="last_name" maxlength="255" required
            value="<?php echo htmlspecialchars($last_name); ?>">

        <input type="hidden" name="role" value="client">

        <label for="address">Adres (optioneel):</label>
        <input type="text" id="address" name="address" maxlength="255"
            value="<?php echo htmlspecialchars($address ?? ''); ?>">

        <label for="password">Wachtwoord:</label>
        <input type="password" id="password" name="password" maxlength="255" required>

        <button type="submit">Registreren</button>
    </form>

    <p>Al een account? <a href="index.php">Log hier in</a></p>
    <a href="privacy.php">Privacyverklaring</a>
</body>

</html>