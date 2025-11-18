<?php
/**
 * GitHub Webhook Deployment Script
 * Automatically deploys when code is pushed to main branch
 */

// Configuration
$secret = '413d66fed586f3447e62dd9f2f574400868b1ebf738cdd4278cf31b0a0be3b6b';
$repoDir = '/home/stats/repo';
$deployScript = '/home/stats/deploy.sh';
$logFile = '/home/stats/logs/webhook-deploy.log';

// Get the payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Handle GitHub ping event (webhook test)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($payload)) {
    // GitHub sends a ping event when webhook is first created
    $headers = getallheaders();
    $eventType = $headers['X-GitHub-Event'] ?? '';
    
    if ($eventType === 'ping') {
        http_response_code(200);
        echo json_encode(['message' => 'Webhook is active and ready']);
        exit;
    }
}

// Log all requests for debugging
$logMessage = sprintf(
    "[%s] Webhook request: Method=%s, Event=%s, Signature=%s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown',
    !empty($signature) ? 'present' : 'missing'
);
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Verify GitHub signature
if (empty($signature)) {
    http_response_code(403);
    $errorMsg = 'Missing signature header';
    file_put_contents($logFile, "[ERROR] $errorMsg\n", FILE_APPEND);
    die(json_encode(['error' => $errorMsg]));
}

$expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    $errorMsg = 'Invalid signature';
    file_put_contents($logFile, "[ERROR] $errorMsg\n", FILE_APPEND);
    die(json_encode(['error' => $errorMsg]));
}

// Parse payload
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $errorMsg = 'Invalid JSON payload: ' . json_last_error_msg();
    file_put_contents($logFile, "[ERROR] $errorMsg\n", FILE_APPEND);
    die(json_encode(['error' => $errorMsg]));
}

// Only deploy on push to main branch
if (!isset($data['ref']) || $data['ref'] !== 'refs/heads/main') {
    $branch = $data['ref'] ?? 'unknown';
    $logMessage = sprintf(
        "[%s] Skipping deployment - not main branch (ref: %s)\n",
        date('Y-m-d H:i:s'),
        $branch
    );
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    http_response_code(200);
    die(json_encode(['message' => 'Not main branch, skipping deployment', 'ref' => $branch]));
}

// Log the deployment request
$logMessage = sprintf(
    "[%s] Deployment triggered by %s (%s)\n",
    date('Y-m-d H:i:s'),
    $data['pusher']['name'] ?? 'unknown',
    $data['head_commit']['message'] ?? 'no message'
);
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Verify repo directory exists
if (!is_dir($repoDir)) {
    $errorMsg = "Repository directory not found: $repoDir";
    file_put_contents($logFile, "[ERROR] $errorMsg\n", FILE_APPEND);
    http_response_code(500);
    die(json_encode(['error' => $errorMsg]));
}

// Trigger deployment by creating a flag file
// Note: We use a trigger file + cron job approach because:
// - Webhook runs as web server user (nobody) which can't execute deploy.sh
// - Cron job runs as root and can properly execute deployment
// - This is a proven pattern for webhook deployments requiring elevated privileges
$triggerFile = '/home/stats/logs/webhook-trigger';
$triggerData = json_encode([
    'timestamp' => time(),
    'commit' => substr($data['head_commit']['id'] ?? '', 0, 7),
    'message' => $data['head_commit']['message'] ?? 'no message',
    'pusher' => $data['pusher']['name'] ?? 'unknown'
]);

if (file_put_contents($triggerFile, $triggerData) === false) {
    $errorMsg = "Failed to create deployment trigger file";
    file_put_contents($logFile, "[ERROR] $errorMsg\n", FILE_APPEND);
    http_response_code(500);
    die(json_encode(['error' => $errorMsg]));
}

$logMessage = sprintf(
    "[%s] Deployment trigger created. Cron job will process it shortly.\n",
    date('Y-m-d H:i:s')
);
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Return success
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment started',
    'commit' => substr($data['head_commit']['id'] ?? '', 0, 7)
]);
