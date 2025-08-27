<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth.php?action=login');
    exit;
}
$pageTitle = 'Contact Messages';
$currentPage = 'contacts';
ob_start();
$db = getDbConnection();
// Fetch contact messages
$stmt = $db->prepare("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$result = $stmt->get_result();
$contacts = $result->fetch_all(MYSQLI_ASSOC);
?>
<div class="content-wrapper1">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Contact Messages</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer Inquiries</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?= $contact['id'] ?></td>
                                    <td><?= htmlspecialchars($contact['name']) ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a></td>
                                    <td><?= htmlspecialchars($contact['phone']) ?></td>
                                    <td><?= htmlspecialchars($contact['subject']) ?></td>
                                    <td><pre><?= htmlspecialchars($contact['message']) ?></pre></td>
                                    <td><?= $contact['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
