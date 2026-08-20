<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

$usersFile = __DIR__ . '/users.json';
$historyFile = __DIR__ . '/bmi_history.json';

function loadData(string $file): array {
	if (!is_file($file)) return [];
	$data = json_decode((string) file_get_contents($file), true);
	return is_array($data) ? $data : [];
}

function saveData(string $file, array $data): void {
	file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}
function response(bool $success, string $message = '', array $data = [], int $status = 200): never {
	http_response_code($status);
	echo json_encode(['success' => $success, 'message' => $message] + $data);
	exit;
}

$body = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($body) ? $body : $_POST;
$action = strtolower((string) ($input['action'] ?? $_GET['action'] ?? ''));
$users = loadData($usersFile);
if ($action === 'register' || $action === 'login') {
	$email = strtolower(trim((string) ($input['email'] ?? '')));
	$password = (string) ($input['password'] ?? '');
	if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
		response(false, 'Enter a valid email and a password of at least 6 characters.', [], 422);
	}
	if ($action === 'register') {
		if (isset($users[$email])) response(false, 'Account already exists.', [], 409);
		$users[$email] = ['password' => password_hash($password, PASSWORD_DEFAULT)];
		saveData($usersFile, $users);
	} elseif (!isset($users[$email]) || !password_verify($password, $users[$email]['password'])) {
		response(false, 'Invalid email or password.', [], 401);
	}
	session_regenerate_id(true);
	$_SESSION['user'] = $email;
	response(true, $action === 'register' ? 'Account created.' : 'Logged in.', ['user' => $email]);
}

if ($action === 'logout') {
	session_destroy();
	response(true, 'Logged out.');
}
if ($action === 'me') {
	response(true, '', ['authenticated' => isset($_SESSION['user']), 'user' => $_SESSION['user'] ?? null]);
}
if ($action === 'calculate') {
	$weight = (float) ($input['weight'] ?? 0);
	$height = (float) ($input['height'] ?? 0);
	if (($input['height_unit'] ?? 'cm') === 'in') $height *= 2.54;
	if ($weight <= 0 || $height <= 0) response(false, 'Weight and height must be positive.', [], 422);
	$bmi = round($weight / (($height / 100) ** 2), 1);
	$category = $bmi < 18.5 ? 'Underweight' : ($bmi < 25 ? 'Normal weight' : ($bmi < 30 ? 'Overweight' : 'Obesity'));
	$result = ['bmi' => $bmi, 'category' => $category];
	if (isset($_SESSION['user'])) {
		$history = loadData($historyFile);
		$history[] = ['user' => $_SESSION['user'], 'weight' => $weight, 'height_cm' => $height, 'bmi' => $bmi, 'category' => $category, 'date' => date('c')];
		saveData($historyFile, $history);
	}
	response(true, 'BMI calculated.', ['result' => $result]);
}
if ($action === 'history') {
	if (!isset($_SESSION['user'])) response(false, 'Login required.', [], 401);
	$history = array_values(array_filter(loadData($historyFile), fn($item) => ($item['user'] ?? '') === $_SESSION['user']));
	response(true, '', ['history' => $history]);
}

response(false, 'Unknown action.', [], 400);
