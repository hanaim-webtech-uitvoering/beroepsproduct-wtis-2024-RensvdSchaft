<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

require_once('../db_connectie.php');
require_once('functions.php');

$clientUsername = $_SESSION['username'];
$confirmationMessage = '';
$errorMessage = '';

if (isset($_SESSION['confirmation_message'])) {
    $confirmationMessage = $_SESSION['confirmation_message'];
    unset($_SESSION['confirmation_message']);
}

list($items, $totalAmount) = getCartItems($_SESSION['cart']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_item'])) {
    $itemNameToRemove = $_POST['item_name'];
    removeItemFromCart($itemNameToRemove);
    header("Location: winkelmand.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order'])) {
    $address = $_POST['address'];

    if (empty($address)) {
        $errorMessage = "Vul alstublieft uw adres in.";
    } elseif (empty($_SESSION['cart'])) {
        $errorMessage = "Uw winkelmand is leeg. Voeg alstublieft een item toe voordat u bestelt.";
    } else {
        $confirmationMessage = confirmOrder($clientUsername, $address);
        $_SESSION['cart'] = [];
        header("Location: winkelmand.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winkelmand</title>
</head>

<body>
    <h1>Winkelmand van <?php echo htmlspecialchars($clientUsername); ?></h1>

    <?php echo displayMessages($errorMessage, $confirmationMessage); ?>

    <h2>Items in uw winkelmand</h2>
    <ul>
        <?php echo renderCartItems($items, $_SESSION['cart'], $totalAmount); ?>
    </ul>

    <form action="" method="POST">
        <label for="address">Vul uw adres in:</label>
        <input type="text" id="address" name="address" required>
        <button type="submit" name="confirm_order">Bevestig Bestelling</button>
    </form>

    <a href="dashboard.php">Terug naar Dashboard</a>
    <a href="logout.php">Uitloggen</a>
</body>

</html>