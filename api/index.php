<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function errorResponse(string $detail, int $statusCode): void
{
    jsonResponse(['detail' => $detail], $statusCode);
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        errorResponse('Invalid JSON body', 400);
    }

    return $data;
}

function parseInterests(?string $raw): array
{
    if ($raw === null || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    $values = [];
    foreach ($decoded as $item) {
        $itemStr = trim((string) $item);
        if ($itemStr !== '') {
            $values[] = $itemStr;
        }
    }

    return $values;
}

function studentToProfile(array $row): array
{
    return [
        'id' => $row['id'],
        'email' => $row['email'],
        'name' => $row['name'],
        'grade' => $row['grade'],
        'bio' => $row['bio'] ?? '',
        'interests' => parseInterests($row['interests'] ?? '[]'),
        'friends' => [],
        'created_at' => isset($row['created_at']) ? gmdate('c', strtotime((string) $row['created_at'])) : null,
    ];
}

function getRoutePath(): string
{
    $path = $_GET['path'] ?? null;
    if (is_string($path) && $path !== '') {
        return trim($path, '/');
    }

    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($uriPath)) {
        return '';
    }

    $parts = explode('/api/', $uriPath, 2);
    if (count($parts) === 2) {
        return trim($parts[1], '/');
    }

    return trim($uriPath, '/');
}

function contains(string $haystack, string $needle): bool
{
    return strpos($haystack, $needle) !== false;
}

try {
    $pdo = getDbConnection();
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $path = getRoutePath();

    if ($method === 'GET' && $path === 'health') {
        jsonResponse(['status' => 'ok']);
    }

    if ($method === 'POST' && $path === 'auth/register') {
        $body = getJsonBody();

        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');
        $name = trim((string) ($body['name'] ?? ''));
        $grade = trim((string) ($body['grade'] ?? ''));

        if ($email === '' || $password === '' || $name === '' || $grade === '') {
            errorResponse('Missing required fields', 400);
        }

        $checkStmt = $pdo->prepare('SELECT id FROM students WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            errorResponse('Email already registered', 400);
        }

        $studentId = 'stu_' . (string) round(microtime(true) * 1000);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $createdAt = gmdate('Y-m-d H:i:s');

        $insertStmt = $pdo->prepare(
            'INSERT INTO students (id, email, password, name, grade, bio, interests, friends, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertStmt->execute([
            $studentId,
            $email,
            $hash,
            $name,
            $grade,
            '',
            json_encode([]),
            json_encode([]),
            $createdAt,
        ]);

        jsonResponse([
            'id' => $studentId,
            'email' => $email,
            'name' => $name,
            'grade' => $grade,
            'bio' => '',
            'interests' => [],
            'friends' => [],
            'created_at' => gmdate('c', strtotime($createdAt)),
        ]);
    }

    if ($method === 'POST' && $path === 'auth/login') {
        $body = getJsonBody();

        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            errorResponse('Email and password are required', 400);
        }

        $stmt = $pdo->prepare('SELECT * FROM students WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        $student = $stmt->fetch();

        if (!$student || !password_verify($password, (string) $student['password'])) {
            errorResponse('Invalid credentials', 401);
        }

        jsonResponse(studentToProfile($student));
    }

    if ($method === 'PUT' && preg_match('#^students/([^/]+)$#', $path, $matches) === 1) {
        $studentId = $matches[1];
        $bio = isset($_GET['bio']) ? trim((string) $_GET['bio']) : '';

        $interestsRaw = $_GET['interests'] ?? [];
        if (!is_array($interestsRaw)) {
            $interestsRaw = [$interestsRaw];
        }

        $interests = [];
        foreach ($interestsRaw as $interest) {
            $interestStr = trim((string) $interest);
            if ($interestStr !== '') {
                $interests[] = $interestStr;
            }
        }

        $existsStmt = $pdo->prepare('SELECT id FROM students WHERE id = ? LIMIT 1');
        $existsStmt->execute([$studentId]);
        if (!$existsStmt->fetch()) {
            errorResponse('Student not found', 404);
        }

        $updateStmt = $pdo->prepare('UPDATE students SET bio = ?, interests = ? WHERE id = ?');
        $updateStmt->execute([$bio, json_encode(array_values($interests)), $studentId]);

        jsonResponse(['message' => 'Profile updated']);
    }

    if ($method === 'POST' && $path === 'sick-leave') {
        $body = getJsonBody();

        $studentId = trim((string) ($body['student_id'] ?? ''));
        $reason = trim((string) ($body['reason'] ?? ''));
        $startDate = trim((string) ($body['start_date'] ?? ''));
        $endDate = trim((string) ($body['end_date'] ?? ''));
        $doctorNote = trim((string) ($body['doctor_note'] ?? ''));

        if ($studentId === '' || $reason === '' || $startDate === '' || $endDate === '') {
            errorResponse('Missing required fields', 400);
        }

        $leaveId = 'leave_' . (string) round(microtime(true) * 1000);
        $createdAt = gmdate('Y-m-d H:i:s');

        $insertStmt = $pdo->prepare(
            'INSERT INTO sick_leaves (id, student_id, reason, start_date, end_date, doctor_note, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertStmt->execute([
            $leaveId,
            $studentId,
            $reason,
            $startDate,
            $endDate,
            $doctorNote !== '' ? $doctorNote : null,
            'pending',
            $createdAt,
        ]);

        jsonResponse(['message' => 'Sick leave submitted successfully', 'id' => $leaveId]);
    }

    if ($method === 'GET' && preg_match('#^sick-leave/([^/]+)$#', $path, $matches) === 1) {
        $studentId = $matches[1];
        $stmt = $pdo->prepare('SELECT * FROM sick_leaves WHERE student_id = ? ORDER BY created_at DESC');
        $stmt->execute([$studentId]);
        $rows = $stmt->fetchAll();

        $result = array_map(static function (array $row): array {
            return [
                'id' => $row['id'],
                'student_id' => $row['student_id'],
                'reason' => $row['reason'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'doctor_note' => $row['doctor_note'],
                'status' => $row['status'],
                'created_at' => gmdate('c', strtotime((string) $row['created_at'])),
            ];
        }, $rows);

        jsonResponse($result);
    }

    if ($method === 'GET' && $path === 'emergency-contacts') {
        jsonResponse([
            ['id' => '1', 'type' => 'Medic/Ambulance', 'number' => '10177', 'description' => 'Emergency medical services'],
            ['id' => '2', 'type' => 'Fire Emergency', 'number' => '10111', 'description' => 'Fire department emergency line'],
            ['id' => '3', 'type' => 'School Security', 'number' => '+27 11 555 0100', 'description' => '24/7 school security'],
            ['id' => '4', 'type' => 'Poison Control', 'number' => '0800 333 444', 'description' => 'Poison information center'],
        ]);
    }

    if ($method === 'POST' && $path === 'chat') {
        $body = getJsonBody();
        $text = strtolower((string) ($body['message'] ?? ''));
        $response = 'I can help with admissions, homework, events, sports, fees, and contact details.';

        if (contains($text, 'admission') || contains($text, 'apply') || contains($text, 'enroll')) {
            $response = 'Admissions are handled by the school office. Visit the Admissions page for requirements and document submission details.';
        } elseif (contains($text, 'homework') || contains($text, 'assignment')) {
            $response = 'Homework is shown in the portal Homework section. Open a task card to view details and due dates.';
        } elseif (contains($text, 'sport') || contains($text, 'soccer') || contains($text, 'rugby') || contains($text, 'netball')) {
            $response = 'Sports updates are on the Sports and News pages. For trial dates, ask your coach or class teacher.';
        } elseif (contains($text, 'event') || contains($text, 'calendar')) {
            $response = 'Upcoming activities are listed in the Events section and on the News page.';
        }

        jsonResponse(['response' => $response]);
    }

    errorResponse('Not found', 404);
} catch (Throwable $exception) {
    errorResponse('Server error', 500);
}
