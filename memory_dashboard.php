<?php
// memory_dashboard.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

// Connect to MongoDB
$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->jarvis_db;
$conversations = $db->conversations;

// Fetch all conversation entries (sorted by timestamp)
$cursor = $conversations->find([], ['sort' => ['timestamp' => 1]]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Jarvis Memory Dashboard</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Jarvis Memory Dashboard</h1>
    <table>
        <tr>
            <th>Timestamp</th>
            <th>Topic</th>
            <th>User Query</th>
            <th>Jarvis Response</th>
        </tr>
        <?php foreach ($cursor as $entry): ?>
        <tr>
            <td><?php echo $entry['timestamp']->toDateTime()->format('Y-m-d H:i:s'); ?></td>
            <td><?php echo isset($entry['topic']) ? htmlspecialchars($entry['topic']) : 'N/A'; ?></td>
            <td><?php echo htmlspecialchars($entry['query']); ?></td>
            <td><?php echo htmlspecialchars($entry['response']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
