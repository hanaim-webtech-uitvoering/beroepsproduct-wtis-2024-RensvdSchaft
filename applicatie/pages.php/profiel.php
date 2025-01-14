<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

require_once('../db_connectie.php');
require_once('functions.php');

$clientUsername = $_SESSION['username'];
$orders = getUserOrders($clientUsername);
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Bestellingen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <h1>Mijn Bestellingen</h1>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Datum en Tijd</th>
                <th>Status</th>
                <th>Personeel</th>
                <th>Items</th>
                <th>Totaalbedrag</th>
            </tr>
        </thead>
        <tbody>
            <?php echo renderOrderTableRows($orders); ?>
        </tbody>
    </table>

    <a href="dashboard.php">Terug naar Dashboard</a>
    <a href="logout.php">Uitloggen</a>
    <a href="privacy.php">Privacyverklaring</a>
</body>

</html>