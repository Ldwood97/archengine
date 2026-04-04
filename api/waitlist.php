<?php
/**
 * ArchEngine Waitlist — Email Capture
 * Stores emails in a JSON file outside the public directory.
 * Returns JSON response for fetch() calls.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Storage file — one level above public_html for security
$storageFile = __DIR__ . '/../waitlist_data/emails.json';
$storageDir  = dirname($storageFile);

// Ensure storage directory exists
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// GET — return count
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $emails = file_exists($storageFile)
        ? json_decode(file_get_contents($storageFile), true) ?? []
        : [];
    echo json_encode(['count' => count($emails)]);
    exit;
}

// POST — add email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = isset($input['email']) ? trim(strtolower($input['email'])) : '';

    // Validate
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid email required.']);
        exit;
    }

    // Load existing
    $emails = file_exists($storageFile)
        ? json_decode(file_get_contents($storageFile), true) ?? []
        : [];

    // Check duplicate
    $existing = array_column($emails, 'email');
    if (in_array($email, $existing)) {
        echo json_encode(['success' => true, 'message' => 'Already on the list.', 'count' => count($emails)]);
        exit;
    }

    // Add new entry
    $emails[] = [
        'email'     => $email,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
        'source'    => 'archengine.io',
    ];

    // Save
    file_put_contents($storageFile, json_encode($emails, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'count' => count($emails)]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
