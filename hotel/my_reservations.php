<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user's reservations
$sql = "SELECT r.*, rm.room_number, rm.room_type, rm.price_per_night 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC";

$reservations = array();
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Handle reservation cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_reservation'])) {
    $reservation_id = mysqli_real_escape_string($conn, $_POST['reservation_id']);
    
    // Update reservation status
    $sql = "UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update room status
            $update_sql = "UPDATE rooms r 
                          JOIN reservations res ON r.id = res.room_id 
                          SET r.status = 'available' 
                          WHERE res.id = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "i", $reservation_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
            
            redirectWith('my_reservations.php', 'Reservation cancelled successfully!', 'success');
        } else {
            redirectWith('my_reservations.php', 'Something went wrong. Please try again.', 'danger');
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Hotel Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Hotel Reservation</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">Admin Dashboard</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_reservations.php">My Reservations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">My Reservations</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
            <div class="alert alert-info">
                You don't have any reservations yet. <a href="index.php">Book a room now!</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td>
                                    Room <?php echo htmlspecialchars($reservation['room_number']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($reservation['room_type']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></td>
                                <td>$<?php echo number_format($reservation['total_price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $reservation['status'] == 'confirmed' ? 'success' : 
                                            ($reservation['status'] == 'pending' ? 'warning' : 
                                            ($reservation['status'] == 'cancelled' ? 'danger' : 'secondary')); 
                                        ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reservation['status'] == 'confirmed' && strtotime($reservation['check_in_date']) > time()): ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" 
                                              onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" name="cancel_reservation" class="btn btn-danger btn-sm">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 