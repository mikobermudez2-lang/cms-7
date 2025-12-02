<?php
$base = getenv('HR_API_BASE') ?: 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
$url = $base . '/get_users.php';

$data = [
    'user' => [
        'email' => $_GET['email'] ?? '',
        'password' => $_GET['password'] ?? '',
        'sub_role' => $_GET['sub_role'] ?? '',
    ],
];

$payload = json_encode($data);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
if ($err) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $err]);
    exit;
}
http_response_code($code);
echo $res;  