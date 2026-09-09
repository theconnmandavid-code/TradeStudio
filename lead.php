<?php
/**
 * Lead intake for the Trade Studio landing page.
 *
 * Order of operations matters: every accepted lead is appended to leads.jsonl
 * BEFORE any delivery is attempted. Mail can fail — a host with mail() disabled,
 * a throttled SMS gateway, a typo'd address — and a lead that only ever existed
 * in an email that didn't send is lost money. Disk first, then notify.
 *
 * Runs with no config.php: records the lead, skips delivery, reports success.
 * That's the local-dev mode, so the form is testable before hosting is picked.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

const MAX_FIELD = 2000;

function reply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

/** Strip CR/LF so user input can never inject extra mail headers. */
function header_safe(string $s): string
{
    return trim(str_replace(["\r", "\n", "\0"], ' ', $s));
}

function field(string $key): string
{
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw)) {
        return '';
    }
    return trim(mb_substr($raw, 0, MAX_FIELD));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    reply(405, ['ok' => false, 'error' => 'Use POST.']);
}

$config = is_file(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : [];

// --- Honeypot -------------------------------------------------------------
// Real people never see this field. Bots fill everything. Answer 200 so the
// bot believes it succeeded and doesn't retry with a different shape.
if (field('website') !== '') {
    reply(200, ['ok' => true]);
}

// --- Validate -------------------------------------------------------------
$name    = field('name');
$company = field('company');
$phone   = field('phone');
$email   = field('email');
$city    = field('city');
$trade   = field('trade');
$need    = field('message');

$errors = [];
if ($name === '') {
    $errors['name'] = 'Tell us your name.';
}
if ($company === '') {
    $errors['company'] = 'Company name, even if it is just you.';
}

// Phone: require 10+ digits, but accept whatever punctuation people type.
$digits = preg_replace('/\D+/', '', $phone) ?? '';
if (strlen($digits) < 10) {
    $errors['phone'] = 'A phone number we can actually call.';
}

// Email is optional — but if given, it has to be real, since we may reply to it.
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'That email address looks off.';
}

if ($errors) {
    reply(422, ['ok' => false, 'errors' => $errors]);
}

// --- Rate limit -----------------------------------------------------------
// Per IP, per hour, file-backed. Not bulletproof; enough to stop casual floods.
$limit = (int) ($config['rate_limit'] ?? 8);
$ip    = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$dir   = __DIR__ . '/.rate';
if (!is_dir($dir)) {
    @mkdir($dir, 0700, true);
}
$bucket = $dir . '/' . hash('sha256', $ip . date('YmdH')) . '.txt';
$hits   = is_file($bucket) ? (int) file_get_contents($bucket) : 0;
if ($hits >= $limit) {
    reply(429, ['ok' => false, 'error' => 'Too many submissions. Call us instead.']);
}
@file_put_contents($bucket, (string) ($hits + 1), LOCK_EX);

// --- Record first ---------------------------------------------------------
$lead = [
    'at'      => gmdate('c'),
    'name'    => $name,
    'company' => $company,
    'phone'   => $phone,
    'email'   => $email,
    'city'    => $city,
    'trade'   => $trade,
    'message' => $need,
    'ip'      => $ip,
];

$stored = @file_put_contents(
    __DIR__ . '/leads.jsonl',
    json_encode($lead, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
    FILE_APPEND | LOCK_EX
);

if ($stored === false) {
    // Can't persist and haven't sent anything — don't tell them we got it.
    error_log('TradeStudio: could not write leads.jsonl');
    reply(500, ['ok' => false, 'error' => 'Something broke on our end. Please call instead.']);
}

// --- Notify ---------------------------------------------------------------
$to     = (string) ($config['to_email'] ?? '');
$sms    = (string) ($config['to_sms'] ?? '');
$from   = (string) ($config['from_email'] ?? '');
$fname  = (string) ($config['from_name'] ?? 'Trade Studio site');
$sent   = false;

if ($to !== '' && function_exists('mail')) {
    $subject = header_safe(sprintf('Lead: %s — %s', $company, $trade !== '' ? $trade : 'trade'));
    $body = implode("\n", [
        'Name:    ' . $name,
        'Company: ' . $company,
        'Phone:   ' . $phone,
        'Email:   ' . ($email !== '' ? $email : '(not given)'),
        'City:    ' . ($city !== '' ? $city : '(not given)'),
        'Trade:   ' . ($trade !== '' ? $trade : '(not given)'),
        '',
        'What they need:',
        $need !== '' ? $need : '(blank)',
        '',
        '--',
        'Sent ' . gmdate('c') . ' UTC from the Trade Studio landing page.',
    ]);

    $headers = ['MIME-Version: 1.0', 'Content-Type: text/plain; charset=utf-8'];
    if ($from !== '') {
        $headers[] = sprintf('From: %s <%s>', header_safe($fname), header_safe($from));
    }
    // Reply-To only if they gave a valid address — makes replying one click.
    if ($email !== '') {
        $headers[] = 'Reply-To: ' . header_safe($email);
    }

    $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
    if (!$sent) {
        error_log('TradeStudio: mail() failed for lead from ' . $company);
    }

    // Text: short enough to survive a carrier gateway intact.
    if ($sms !== '') {
        $short = sprintf('%s (%s) %s — %s', $name, $company, $phone, $trade !== '' ? $trade : 'trade');
        @mail($sms, '', mb_substr($short, 0, 300), implode("\r\n", $headers));
    }
}

// The lead is on disk either way, so this is honest: we have it.
reply(200, ['ok' => true, 'delivered' => $sent]);
