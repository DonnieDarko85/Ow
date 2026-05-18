<?php

declare(strict_types=1);

use OwCampaign\Database;

require_once __DIR__ . '/../../src/Database.php';

$configPath = __DIR__ . '/../../config/config.php';

if (! file_exists($configPath)) {
    jsonResponse([
        'error' => 'Config file mancante. Copiare backend/config/config.example.php in backend/config/config.php e inserire i parametri reali.',
    ], 500);
}

$config = require $configPath;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/api#', '', $path);

try {
    $pdo = Database::connect($config['db']);
} catch (Throwable $exception) {
    jsonResponse([
        'error' => 'Connessione al database fallita.',
        'details' => $exception->getMessage(),
    ], 500);
}

if ($method === 'GET' && $path === '/config') {
    jsonResponse([
        'appName' => $config['app']['name'],
        'appVersion' => $config['app']['version'],
        'organizationName' => $config['app']['organization_name'],
        'legalNote' => $config['app']['legal_note'],
        'privacyUrl' => '#privacy',
        'legalUrl' => '#legal',
        'cookieUrl' => '#cookie',
        'contactEmail' => $config['app']['contact_email'],
    ]);
}

if ($method === 'GET' && $path === '/armies') {
    $stmt = $pdo->query('
        SELECT
            id,
            name,
            slug,
            default_faction AS defaultFaction
        FROM armies
        WHERE is_active = 1
        ORDER BY sort_order, name
    ');

    jsonResponse($stmt->fetchAll());
}

if ($method === 'GET' && $path === '/territories') {
    $stmt = $pdo->query('
        SELECT
            t.id,
            t.name,
            t.slug,
            t.description,
            t.lore,
            t.map_path_id AS mapPathId
        FROM territories t
        WHERE t.is_active = 1
        ORDER BY t.sort_order, t.name
    ');

    $territories = [];

    foreach ($stmt->fetchAll() as $row) {
        $territories[] = [
            ...$row,
            'stats' => [
                'confirmedBattles' => 0,
                'pendingBattles' => 0,
                'dominantFaction' => 'FORCES_OF_FANTASY',
                'factionControl' => [],
                'armyControl' => [],
            ],
        ];
    }

    jsonResponse($territories);
}

if ($method === 'GET' && $path === '/matches/recent') {
    $stmt = $pdo->query('
        SELECT
            m.id,
            m.played_at AS playedAt,
            m.status,
            t.slug AS territorySlug,
            t.name AS territoryName
        FROM matches m
        INNER JOIN territories t ON t.id = m.territory_id
        ORDER BY m.played_at DESC, m.created_at DESC
        LIMIT 20
    ');

    jsonResponse($stmt->fetchAll());
}

if ($method === 'POST' && $path === '/matches/results') {
    $payload = json_decode(file_get_contents('php://input'), true);

    jsonResponse([
        'message' => 'Endpoint pronto. Collegare qui la logica di validazione, deduplica e conferma automatica.',
        'payload' => $payload,
    ], 202);
}

jsonResponse([
    'error' => 'Endpoint non trovato.',
    'path' => $path,
    'method' => $method,
], 404);

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

