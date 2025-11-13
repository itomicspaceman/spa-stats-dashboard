<?php
/**
 * GitHub Webhook Deployment Script
 * Automatically deploys when code is pushed to main branch
 */

// Configuration
$secret = '413d66fed586f3447e62dd9f2f574400868b1ebf738cdd4278cf31b0a0be3b6b';
$deployScript = '/home/stats/deploy.sh';
$logFile = '/home/stats/logs/webhook-deploy.log';

// Get the payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Verify GitHub signature
$expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid signature']));
}

// Parse payload
$data = json_decode($payload, true);

// Only deploy on push to main branch
if (!isset($data['ref']) || $data['ref'] !== 'refs/heads/main') {
    http_response_code(200);
    die(json_encode(['message' => 'Not main branch, skipping deployment']));
}

// Log the deployment request
$logMessage = sprintf(
    "[%s] Deployment triggered by %s (%s)\n",
    date('Y-m-d H:i:s'),
    $data['pusher']['name'] ?? 'unknown',
    $data['head_commit']['message'] ?? 'no message'
);
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Execute deployment script in background
$command = sprintf('bash %s > /home/stats/logs/deploy-output.log 2>&1 &', escapeshellarg($deployScript));
exec($command);

// Return success
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment started',
    'commit' => substr($data['head_commit']['id'] ?? '', 0, 7)
]);

