<?php
// get_document.php

require 'vendor/autoload.php';
use MongoDB\Client;

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "No document ID provided.";
    exit;
}

$documentId = $_GET['id'];

try {
    $client = new Client("mongodb://localhost:27017");
    $db = $client->jarvis_db;
    $bucket = $db->selectGridFSBucket();
    $stream = $bucket->openDownloadStream(new MongoDB\BSON\ObjectId($documentId));
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="document.pdf"');
    fpassthru($stream);
} catch (Exception $e) {
    http_response_code(404);
    echo "Document not found: " . $e->getMessage();
}
?>