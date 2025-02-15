<?php
// upload.php

header('Content-Type: application/json');
require 'vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->jarvis_db;
$bucket = $db->selectGridFSBucket();

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'File upload error: ' . $file['error']]);
    exit;
}

// Open the file and save it to GridFS
$stream = fopen($file['tmp_name'], "rb");
$uploadOptions = [
    'metadata' => [
        'originalName' => $file['name'],
        'uploadDate'   => new MongoDB\BSON\UTCDateTime()
    ]
];
$fileId = $bucket->uploadFromStream($file['name'], $stream, $uploadOptions);
fclose($stream);

echo json_encode([
    'message' => 'File uploaded successfully',
    'file_id' => (string)$fileId
]);
