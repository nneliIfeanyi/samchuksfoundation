<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
request_method('GET');
$pdo = database();
$slug = trim((string)($_GET['slug'] ?? ''));
$includeDrafts = ($_GET['include_drafts'] ?? '') === '1';

if ($includeDrafts) {
    require_admin();
}

$where = $includeDrafts ? '' : "WHERE status = 'published'";
$params = [];
if ($slug !== '') {
    $where .= ($where === '' ? 'WHERE' : ' AND') . ' slug = :slug';
    $params['slug'] = $slug;
}
if (($_GET['featured'] ?? '') === '1') {
    $where .= ($where === '' ? 'WHERE' : ' AND') . ' featured = 1';
}

$limit = filter_var($_GET['limit'] ?? null, FILTER_VALIDATE_INT);
$limit = $limit === false || $limit === null ? 0 : max(1, min(50, $limit));
$sql = 'SELECT id, title, slug, category, description, event_date, programme_year, participants, duration, cover_image, gallery_json, status, featured, created_at, updated_at FROM events '
    . $where . ' ORDER BY updated_at DESC, id DESC';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}

$statement = $pdo->prepare($sql);
$statement->execute($params);
$events = array_map('event_record', $statement->fetchAll());
if ($slug !== '') {
    if (!$events) {
        json_response(['error' => 'Event not found.'], 404);
    }
    json_response(['event' => $events[0]]);
}

json_response(['events' => $events]);
