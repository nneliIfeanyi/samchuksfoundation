<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$method = request_method('GET', 'POST', 'PUT', 'DELETE');
$adminId = require_admin($method !== 'GET');
$pdo = database();

if ($method === 'GET') {
    $statement = $pdo->query(
        'SELECT id, title, slug, category, description, event_date, programme_year, participants, duration, cover_image, gallery_json, status, featured, created_at, updated_at FROM events ORDER BY updated_at DESC, id DESC'
    );
    json_response(['events' => array_map('event_record', $statement->fetchAll())]);
}

if ($method === 'DELETE') {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        json_response(['error' => 'A valid event ID is required.'], 422);
    }
    $statement = $pdo->prepare('DELETE FROM events WHERE id = :id');
    $statement->execute(['id' => $id]);
    if ($statement->rowCount() === 0) {
        json_response(['error' => 'Event not found.'], 404);
    }
    json_response(['deleted' => true]);
}

$event = clean_event_payload(request_json());
try {
    if ($method === 'POST') {
        $statement = $pdo->prepare(
            'INSERT INTO events (title, slug, category, description, event_date, programme_year, participants, duration, cover_image, gallery_json, status, featured, created_by) '
            . 'VALUES (:title, :slug, :category, :description, :event_date, :programme_year, :participants, :duration, :cover_image, :gallery_json, :status, :featured, :created_by)'
        );
        $values = $event;
        $values['gallery_json'] = json_encode($event['gallery'], JSON_UNESCAPED_SLASHES);
        unset($values['gallery']);
        $values['created_by'] = $adminId;
        $statement->execute($values);
        json_response(['event' => ['id' => (int)$pdo->lastInsertId()] + $event], 201);
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        json_response(['error' => 'A valid event ID is required.'], 422);
    }
    $statement = $pdo->prepare(
        'UPDATE events SET title = :title, slug = :slug, category = :category, description = :description, event_date = :event_date, programme_year = :programme_year, participants = :participants, duration = :duration, cover_image = :cover_image, gallery_json = :gallery_json, status = :status, featured = :featured WHERE id = :id'
    );
    $values = $event;
    $values['gallery_json'] = json_encode($event['gallery'], JSON_UNESCAPED_SLASHES);
    unset($values['gallery']);
    $values['id'] = $id;
    $statement->execute($values);
    if ($statement->rowCount() === 0) {
        $exists = $pdo->prepare('SELECT id FROM events WHERE id = :id');
        $exists->execute(['id' => $id]);
        if (!$exists->fetch()) {
            json_response(['error' => 'Event not found.'], 404);
        }
    }
    json_response(['event' => ['id' => $id] + $event]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        json_response(['error' => 'That event slug is already in use.'], 409);
    }
    throw $exception;
}
