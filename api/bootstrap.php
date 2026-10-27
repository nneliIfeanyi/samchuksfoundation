<?php
declare(strict_types=1);

const MAX_EVENT_IMAGES = 4;

function json_response(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw === false ? '' : $raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Request body must be valid JSON.'], 400);
    }

    return $data;
}

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['name']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Events API database connection failed: ' . $exception->getMessage());
        json_response(['error' => 'The events service is temporarily unavailable.'], 503);
    }

    return $pdo;
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $config = require __DIR__ . '/config.php';
    $sameSite = $config['session_samesite'] ?? 'Strict';
    if (!in_array($sameSite, ['Strict', 'Lax', 'None'], true)) {
        json_response(['error' => 'The API session cookie policy is invalid.'], 500);
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($sameSite === 'None' && !$https) {
        json_response(['error' => 'Cross-site admin sessions require HTTPS.'], 503);
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => $sameSite,
        'path' => '/',
    ]);
    session_start();
}

function require_admin(bool $checkCsrf = false): int
{
    start_admin_session();
    $adminId = $_SESSION['admin_id'] ?? null;
    if (!is_int($adminId) || $adminId < 1) {
        json_response(['error' => 'Authentication required.'], 401);
    }

    if ($checkCsrf) {
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION['csrf_token'] ?? '';
        if (!is_string($csrf) || !is_string($expected) || $expected === '' || !hash_equals($expected, $csrf)) {
            json_response(['error' => 'Invalid or missing CSRF token.'], 403);
        }
    }

    return $adminId;
}

function request_method(string ...$allowed): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_response(['error' => 'Method not allowed.'], 405);
    }

    return $method;
}

function clean_event_payload(array $input): array
{
    $title = trim((string)($input['title'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $slug = strtolower(trim((string)($input['slug'] ?? '')));
    if ($slug === '') {
        $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    }

    if ($title === '' || $category === '' || $description === '') {
        json_response(['error' => 'Title, category, and description are required.'], 422);
    }
    if (strlen($title) > 180 || strlen($category) > 120 || strlen($slug) > 180) {
        json_response(['error' => 'A title, category, or slug is too long.'], 422);
    }
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        json_response(['error' => 'Slug must contain lowercase letters, numbers, and single hyphens only.'], 422);
    }

    $date = trim((string)($input['event_date'] ?? ''));
    if ($date !== '') {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            json_response(['error' => 'Event date must use YYYY-MM-DD format.'], 422);
        }
    }

    $status = (string)($input['status'] ?? 'draft');
    if (!in_array($status, ['draft', 'published'], true)) {
        json_response(['error' => 'Status must be draft or published.'], 422);
    }

    $images = $input['gallery'] ?? [];
    if (is_string($images)) {
        $images = preg_split('/\R+/', $images, -1, PREG_SPLIT_NO_EMPTY);
    }
    if (!is_array($images)) {
        json_response(['error' => 'Gallery must be a list of image paths.'], 422);
    }
    $images = array_values(array_unique(array_filter(array_map(
        static fn($path) => trim((string)$path),
        $images
    ))));
    foreach ($images as $path) {
        if (strlen($path) > 500 || str_contains($path, '..') || str_contains($path, '\\') ||
            !preg_match('#^assets/(?:img/[a-zA-Z0-9._/-]+|uploads/events/[a-zA-Z0-9._/-]+)$#', $path)) {
            json_response(['error' => 'Gallery paths must point to local event images.'], 422);
        }
    }

    $cover = trim((string)($input['cover_image'] ?? ''));
    if ($cover !== '' && (strlen($cover) > 500 || str_contains($cover, '..') ||
        str_contains($cover, '\\') ||
        !preg_match('#^assets/(?:img/[a-zA-Z0-9._/-]+|uploads/events/[a-zA-Z0-9._/-]+)$#', $cover))) {
        json_response(['error' => 'Cover image must point to a local image.'], 422);
    }
    $eventImages = array_values(array_unique(array_filter([$cover, ...$images])));
    if (count($eventImages) > MAX_EVENT_IMAGES) {
        json_response([
            'error' => 'Each event can have no more than ' . MAX_EVENT_IMAGES . ' images, including the cover.',
        ], 422);
    }

    return [
        'title' => $title,
        'slug' => $slug,
        'category' => $category,
        'description' => $description,
        'event_date' => $date === '' ? null : $date,
        'programme_year' => trim((string)($input['programme_year'] ?? '')),
        'participants' => trim((string)($input['participants'] ?? '')),
        'duration' => trim((string)($input['duration'] ?? '')),
        'cover_image' => $cover,
        'gallery' => $images,
        'status' => $status,
        'featured' => !empty($input['featured']) ? 1 : 0,
    ];
}

function event_record(array $row): array
{
    $row['gallery'] = json_decode((string)($row['gallery_json'] ?? '[]'), true) ?: [];
    unset($row['gallery_json']);
    $row['featured'] = (bool)$row['featured'];
    return $row;
}

function normalize_origin(string $origin): ?string
{
    $parts = parse_url($origin);
    if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) ||
        !in_array(strtolower($parts['scheme']), ['http', 'https'], true) ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) ||
        isset($parts['fragment']) || (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')) {
        return null;
    }

    $normalized = strtolower($parts['scheme']) . '://' . strtolower($parts['host']);
    if (isset($parts['port'])) {
        $normalized .= ':' . $parts['port'];
    }

    return $normalized;
}

function configure_cors(): void
{
    $config = require __DIR__ . '/config.php';
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));

    if ($origin !== '') {
        $normalizedOrigin = normalize_origin($origin);
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $requestHost = (string)($_SERVER['HTTP_HOST'] ?? '');
        $sameOrigin = normalize_origin($scheme . '://' . $requestHost);
        $isSameOrigin = $normalizedOrigin !== null && $normalizedOrigin === $sameOrigin;

        if (!$isSameOrigin) {
            $allowedOrigins = array_values(array_filter(array_map(
                static fn($allowed) => is_string($allowed) ? normalize_origin(trim($allowed)) : null,
                $config['cors_origins'] ?? []
            )));
            if ($normalizedOrigin === null || !in_array($normalizedOrigin, $allowedOrigins, true)) {
                json_response(['error' => 'This origin is not allowed to access the API.'], 403);
            }

            header('Access-Control-Allow-Origin: ' . $normalizedOrigin);
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        }
    }

    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'OPTIONS') {
        return;
    }

    if ($origin === '') {
        json_response(['error' => 'CORS preflight requires an Origin header.'], 400);
    }

    $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    $requestedMethod = strtoupper((string)($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ?? ''));
    if (!in_array($requestedMethod, $allowedMethods, true)) {
        json_response(['error' => 'This request method is not allowed.'], 403);
    }

    $allowedHeaders = ['accept', 'content-type', 'x-csrf-token'];
    $requestedHeaders = array_filter(array_map(
        static fn($header) => strtolower(trim($header)),
        explode(',', (string)($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? ''))
    ));
    if (array_diff($requestedHeaders, $allowedHeaders)) {
        json_response(['error' => 'This request header is not allowed.'], 403);
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Accept, Content-Type, X-CSRF-Token');
    header('Access-Control-Max-Age: 600');
    header('Vary: Origin, Access-Control-Request-Method, Access-Control-Request-Headers');
    http_response_code(204);
    exit;
}

configure_cors();
