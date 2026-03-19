<?php
// ============================================================
//  SL JetSpot — Contact Form API  /api/contact.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// MUST start session before CSRF check — token lives in $_SESSION
Auth::start();

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Method not allowed.']));
}

// CSRF check
if (!csrf_verify()) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Security token mismatch. Please refresh the page and try again.']));
}

// Rate limiting — max 3 messages per IP per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

try {
    $recent = (int) DB::query(
        'SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
        [$ip]
    )->fetchColumn();
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['ok' => false, 'error' => 'Database error. Please try again later.']));
}

if ($recent >= 3) {
    http_response_code(429);
    exit(json_encode(['ok' => false, 'error' => 'Too many messages. Please wait an hour before trying again.']));
}

// Sanitise & validate input
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (strlen($name)    < 2  || strlen($name)    > 120)  $errors[] = 'Name must be 2–120 characters.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'Please enter a valid email address.';
if (strlen($subject) < 3  || strlen($subject) > 200)  $errors[] = 'Subject must be 3–200 characters.';
if (strlen($message) < 10 || strlen($message) > 3000) $errors[] = 'Message must be 10–3000 characters.';

if ($errors) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'error' => implode(' ', $errors)]));
}

// Save to DB
try {
    DB::query(
        'INSERT INTO messages (name, email, subject, message, ip) VALUES (?, ?, ?, ?, ?)',
        [$name, $email, $subject, $message, $ip]
    );
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['ok' => false, 'error' => 'Could not save your message. Please try again.']));
}

// Send email notification (best-effort — site still succeeds if mail fails)
$host     = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
$headers  = "From: noreply@{$host}\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$body = "New contact message on " . SITE_NAME . "\n\n"
      . "Name    : {$name}\n"
      . "Email   : {$email}\n"
      . "Subject : {$subject}\n\n"
      . "Message :\n{$message}\n\n"
      . "---\nIP: {$ip}\nTime: " . date('Y-m-d H:i:s');

@mail(ADMIN_EMAIL, "[JetSpot] {$subject}", $body, $headers);

// Regenerate CSRF token after successful submission so the form stays usable
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

exit(json_encode([
    'ok'      => true,
    'message' => "Thanks {$name}! Your message has been sent. I'll get back to you soon.",
    'token'   => $_SESSION['csrf_token'], // send fresh token back to JS
]));