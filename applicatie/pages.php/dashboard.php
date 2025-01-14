<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'klant';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    require_once('functions.php');
    addToCart($_POST['item_name']);
}

require_once('functions.php');
$items = getProducts();
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>

<body>
    <h1>Welkom, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

    <h2>Bestelbare Items</h2>
    <ul>
        <?php echo renderProducts($items); ?>
    </ul>

    <a href="winkelmand.php">Bekijk winkelmand</a>
    <a href="profiel.php">Bekijk uw bestellingen</a>
    <a href="logout.php">Uitloggen</a>
</body>

</html>