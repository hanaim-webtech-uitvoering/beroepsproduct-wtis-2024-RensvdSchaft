<?php

function destroySession()
{
    if (!isset($_SESSION)) {
        session_start();
    }
    $sessionName = 'PHPSESSID';
    $_SESSION = [];
    if (isset($_COOKIE[$sessionName])) {
        unset($_COOKIE[$sessionName]);
        setcookie($sessionName, '', time() - 3600, '/');
    }
    session_regenerate_id(true);
    session_destroy();
}

require_once('../db_connectie.php');

function validateInput($username, $password)
{
    $errors = [];

    if (strlen($username) < 3 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9._]+$/', $username)) {
        $errors[] = "De gebruikersnaam moet tussen de 3 en 20 tekens zijn en alleen letters, cijfers, punten of underscores bevatten.";
    }

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
        $errors[] = "Het wachtwoord moet minimaal 8 tekens lang zijn en ten minste één hoofdletter, één kleine letter, één cijfer en één speciaal teken bevatten.";
    }

    return $errors;
}

function usernameExists($username)
{
    $conn = maakVerbinding();
    $checkSql = "SELECT COUNT(*) FROM [User] WHERE username = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$username]);
    return $checkStmt->fetchColumn() > 0;
}

function registerUser($hashed_password, $username, $first_name, $last_name, $role, $address)
{
    $conn = maakVerbinding();
    $sql = "INSERT INTO [User] (username, first_name, last_name, role, address, password) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$username, $first_name, $last_name, $role, $address, $hashed_password]);
}

function getOrderDetailsAndItems($orderId)
{
    $conn = maakVerbinding();

    $stmt = $conn->prepare("
        SELECT o.order_id, o.datetime, o.status, o.client_username, u.first_name, o.address, SUM(op.quantity * pr.price) AS total_amount
        FROM Pizza_Order o 
        JOIN [User] u ON o.client_username = u.username 
        LEFT JOIN Pizza_Order_Product op ON o.order_id = op.order_id
        LEFT JOIN Product pr ON op.product_name = pr.name
        WHERE o.order_id = ?
        GROUP BY o.order_id, o.datetime, o.status, u.first_name, o.client_username, o.address
    ");
    $stmt->execute([$orderId]);
    $orderDetails = $stmt->fetch(PDO::FETCH_ASSOC);


    $itemStmt = $conn->prepare("
        SELECT product_name, quantity 
        FROM Pizza_Order_Product 
        WHERE order_id = ?");
    $itemStmt->execute([$orderId]);
    $orderDetails['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    return $orderDetails;
}

function getOrders()
{
    $conn = maakVerbinding();
    $stmt = $conn->prepare("
        SELECT o.order_id, o.datetime, o.status, o.client_username, u.first_name, p.username AS personnel_username, SUM(op.quantity * pr.price) AS total_amount
        FROM Pizza_Order o 
        JOIN [User] u ON o.client_username = u.username 
        JOIN Pizza_Order_Product op ON o.order_id = op.order_id
        JOIN Product pr ON op.product_name = pr.name
        JOIN [User] p ON o.personnel_username = p.username
        GROUP BY o.order_id, o.datetime, o.status, u.first_name, o.client_username, p.username
        ORDER BY o.datetime DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateOrderStatus($orderId, $newStatus)
{
    $conn = maakVerbinding();
    $stmt = $conn->prepare("UPDATE Pizza_Order SET status = ? WHERE order_id = ?");
    return $stmt->execute([$newStatus, $orderId]);
}

function attemptLogin($username, $password)
{
    $conn = maakVerbinding();
    $sql = "SELECT password, role FROM [User] WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $hashedPassword = $userData['password'];
        if (password_verify($password, $hashedPassword)) {
            return $userData['role'];
        }
    }
    return false;
}

function getUserOrders($username)
{
    try {
        $conn = maakVerbinding();

        $sql = "
            SELECT po.order_id, po.datetime, po.status, po.personnel_username
            FROM dbo.Pizza_Order po
            WHERE po.client_username = :username
            ORDER BY po.datetime DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['username' => $username]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $itemSql = "
                SELECT pop.product_name, pop.quantity, p.price
                FROM dbo.Pizza_Order_Product pop
                JOIN dbo.Product p ON pop.product_name = p.name
                WHERE pop.order_id = :order_id";

            $itemStmt = $conn->prepare($itemSql);
            $itemStmt->execute(['order_id' => $order['order_id']]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as &$item) {
                if (!isset($item['price'])) {
                    $item['price'] = 0.00;
                }
            }

            $order['items'] = $items;
        }

        return $orders;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}
function getCartItems($cartItems)
{
    $conn = maakVerbinding();
    $items = [];
    $totalAmount = 0;

    if (!empty($cartItems)) {
        $placeholders = implode(',', array_fill(0, count($cartItems), '?'));
        $sql = "SELECT * FROM Product WHERE name IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_keys($cartItems));
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $totalAmount += $item['price'] * $cartItems[$item['name']];
        }
    }
    return [$items, $totalAmount];
}

function removeItemFromCart($itemName)
{
    unset($_SESSION['cart'][$itemName]);
}

function confirmOrder($clientUsername, $address)
{
    $conn = maakVerbinding();
    $datetime = date('Y-m-d H:i:s');
    $status = 1;

    try {

        $stmt = $conn->prepare("SELECT first_name FROM [User] WHERE username = ?");
        $stmt->execute([$clientUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !isset($user['first_name'])) {
            throw new Exception("Gebruikersnaam niet gevonden in de database.");
        }
        $fullName = $user['first_name'];


        $stmt = $conn->prepare("SELECT username FROM [User] WHERE role = 'Personnel' ORDER BY NEWID() OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY");
        $stmt->execute();
        $personnel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$personnel) {
            throw new Exception("Geen personnel gebruikers gevonden.");
        }
        $personnelUsername = $personnel['username'];

        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO Pizza_Order (client_username, personnel_username, client_name, datetime, status, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$clientUsername, $personnelUsername, $fullName, $datetime, $status, $address]);

        $orderId = $conn->lastInsertId();

        foreach ($_SESSION['cart'] as $productName => $quantity) {
            $stmt = $conn->prepare("INSERT INTO Pizza_Order_Product (order_id, product_name, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$orderId, $productName, $quantity]);
        }

        $conn->commit();

        $_SESSION['confirmation_message'] = "Bestelling bevestigd! Bedankt voor uw aankoop.";
        return $_SESSION['confirmation_message'];
    } catch (Exception $e) {
        $conn->rollBack();
        return "Fout bij het plaatsen van de bestelling: " . $e->getMessage();
    }
}

function renderCartItems($items, $cartItems, $totalAmount)
{
    if (empty($items)) {
        return '<li>Uw winkelmand is leeg.</li>';
    }

    $output = '';
    foreach ($items as $item) {
        $output .= '<li>' . htmlspecialchars($item['name']) . " - €" . number_format($item['price'], 2) . " (Aantal: " . $cartItems[$item['name']] . ")";
        $output .= '<form action="" method="POST" style="display:inline;">
                        <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name']) . '">
                        <button type="submit" name="remove_item">Verwijder</button>
                    </form></li>';
    }
    $output .= '<li><strong>Totaalbedrag: €' . number_format($totalAmount, 2) . '</strong></li>';

    return $output;
}

function displayMessages($errorMessage, $confirmationMessage)
{
    $output = '';
    if (!empty($errorMessage)) {
        $output .= '<p style="color: red;">' . htmlspecialchars($errorMessage) . '</p>';
    }
    if (!empty($confirmationMessage)) {
        $output .= '<p style="color: green;">' . htmlspecialchars($confirmationMessage) . '</p>';
    }
    return $output;
}

function renderOverviewTable($orders)
{
    if (empty($orders)) {
        return "<tr><td colspan='7'>Geen bestellingen gevonden.</td></tr>";
    }

    $rows = '';
    foreach ($orders as $order) {
        $rows .= renderOrderRow($order);
    }
    return $rows;
}

function renderOrderRow($order)
{
    $statusText = getOrderStatusText($order['status']);
    $orderId = htmlspecialchars($order['order_id']);
    $firstName = htmlspecialchars($order['first_name']);
    $datetime = htmlspecialchars($order['datetime']);
    $totalAmount = number_format($order['total_amount'], 2);
    $personnelUsername = htmlspecialchars($order['personnel_username']);

    return "
        <tr>
            <td>{$orderId}</td>
            <td>{$firstName}</td>
            <td>{$datetime}</td>
            <td>{$statusText}</td>
            <td>€{$totalAmount}</td>
            <td>{$personnelUsername}</td>
            <td>
                <form action='' method='POST' style='display:inline;'>
                    <input type='hidden' name='order_id' value='{$orderId}'>
                    <select name='status'>
                        <option value='1' " . ($order['status'] == 1 ? 'selected' : '') . ">Besteld</option>
                        <option value='2' " . ($order['status'] == 2 ? 'selected' : '') . ">In Behandeling</option>
                        <option value='3' " . ($order['status'] == 3 ? 'selected' : '') . ">Afgeleverd</option>
                        <option value='4' " . ($order['status'] == 4 ? 'selected' : '') . ">Geannuleerd</option>
                    </select>
                    <button type='submit' name='update_status'>Bijwerken</button>
                </form>
                <a href='bestelling_details.php?order_id={$orderId}'>Bekijk Details</a>
            </td>
        </tr>
    ";
}

function getOrderStatusText($status)
{
    switch ($status) {
        case 1:
            return 'Besteld';
        case 2:
            return 'In Behandeling';
        case 3:
            return 'Afgeleverd';
        case 4:
            return 'Geannuleerd';
        default:
            return 'Onbekend';
    }
}

function getProducts()
{
    try {
        $conn = maakVerbinding();
        $sql = "SELECT * FROM Product";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}

function addToCart($itemName)
{
    if (isset($_SESSION['cart'][$itemName])) {
        $_SESSION['cart'][$itemName]++;
    } else {
        $_SESSION['cart'][$itemName] = 1;
    }
}

function renderProducts($items)
{
    $output = '';
    foreach ($items as $item) {
        $output .= '<li>' . htmlspecialchars($item['name']) . " - €" . number_format($item['price'], 2) . '
                    <form action="" method="POST" style="display:inline;">
                        <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name']) . '">
                        <button type="submit" name="add_to_cart">Voeg toe aan winkelmand</button>
                    </form>
                </li>';
    }
    return $output;
}

function handleLogin()
{
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $errors = validateInput($username, $password);
        if (empty($errors)) {
            $role = attemptLogin($username, $password);
            if ($role) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                return $role;
            } else {
                return "Fout: Ongeldig wachtwoord of gebruikersnaam.";
            }
        } else {
            return $errors;
        }
    }
}
function renderOrderTableRows($orders)
{
    $statusMapping = [
        1 => 'Besteld',
        2 => 'In behandeling',
        3 => 'Afgeleverd',
        4 => 'Geannuleerd',
    ];

    $html = '';

    foreach ($orders as $order) {
        $totalAmount = 0;
        $itemsDisplay = [];

        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['price'];
                $totalAmount += $itemTotal;
                $itemsDisplay[] = "{$item['product_name']} - Aantal: {$item['quantity']}, Prijs: €" . number_format($itemTotal, 2);
            }
        } else {
            $itemsDisplay[] = '(Geen items)';
        }

        $html .= "<tr>";
        $html .= "<td>{$order['order_id']}</td>";
        $html .= "<td>{$order['datetime']}</td>";
        $html .= "<td>" . $statusMapping[$order['status']] . "</td>";
        $html .= "<td>{$order['personnel_username']}</td>";
        $html .= "<td>" . implode('<br>', $itemsDisplay) . "</td>";
        $html .= "<td>€" . number_format($totalAmount, 2) . "</td>";
        $html .= "</tr>";
    }

    return $html;
}
?>