<?php
// upload_document.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Load Composer autoloader
require 'vendor/autoload.php';

use MongoDB\Client;
use MongoDB\GridFS\Bucket;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file was uploaded
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'File upload error']);
        exit;
    }
    
    $file = $_FILES['document'];
    
    // Connect to MongoDB and set up GridFS bucket
    $client = new Client("mongodb://localhost:27017");
    $db = $client->jarvis_db;
    $bucket = $db->selectGridFSBucket();
    $conversations = $db->conversations;  // Define conversations collection
    
    // Upload the file to GridFS
    $stream = fopen($file['tmp_name'], "rb");
    $fileId = $bucket->uploadFromStream($file['name'], $stream);
    fclose($stream);
    
    // Attempt to extract text from the PDF using Smalot PDFParser
    $extractedText = "";
    try {
        $parser = new Parser();
        $pdf = $parser->parseFile($file['tmp_name']);
        $extractedText = $pdf->getText();
    } catch (Exception $e) {
        error_log("PDFParser error: " . $e->getMessage());
    }
    
    // If extraction yields no text, assume it's a scanned document and use OCR
    if (trim($extractedText) === "") {
        if (!extension_loaded('imagick')) {
            echo json_encode(['error' => 'Imagick extension not installed. Cannot process scanned PDFs without OCR.']);
            exit;
        }
        try {
            $imagick = new Imagick();
            $imagick->readImage($file['tmp_name']);
            $pages = $imagick->getNumberImages();
            $ocrText = "";
            for ($i = 0; $i < $pages; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');
                $tempImage = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
                $imagick->writeImage($tempImage);
                
                // Run Tesseract OCR on the image
                $ocrResult = (new TesseractOCR($tempImage))->run();
                $ocrText .= $ocrResult . "\n";
                unlink($tempImage); // Clean up temporary image
            }
            $extractedText = $ocrText;
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error during OCR: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Optionally, extract keywords from the text.
    // For simplicity, we use the extracted text itself as keywords.
    $extractedKeywords = $extractedText;
    
    // Insert the document's information into MongoDB (into the conversations collection)
    $conversations->insertOne([
        "query"     => "Uploaded document: " . $file['name'],
        "response"  => $extractedText,
        "timestamp" => new MongoDB\BSON\UTCDateTime(),
        "topic"     => "document",
        "keywords"  => $extractedKeywords,
        "file_id"   => (string)$fileId  // Store the GridFS file ID as a string
    ]);
    
    echo json_encode([
        'message'       => 'File uploaded and processed successfully.',
        'fileId'        => (string)$fileId,
        'extractedText' => $extractedText
    ]);
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
