<?php

declare(strict_types=1);

use OwCampaign\Database;

$databaseBootstrapCandidates = [
    __DIR__ . '/../../src/Database.php',
    __DIR__ . '/../backend/src/Database.php',
    __DIR__ . '/../src/Database.php',
    __DIR__ . '/../../backend/src/Database.php',
];

$databaseBootstrapPath = firstExistingPath($databaseBootstrapCandidates);

if ($databaseBootstrapPath === null) {
    jsonResponse([
        'error' => 'File Database.php non trovato. Verifica la struttura del deploy.',
        'checkedPaths' => $databaseBootstrapCandidates,
    ], 500);
}

require_once $databaseBootstrapPath;

session_start();

$configCandidates = [
    __DIR__ . '/../../config/config.php',
    __DIR__ . '/../backend/config/config.php',
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../../backend/config/config.php',
];

$configPath = firstExistingPath($configCandidates);

if ($configPath === null) {
    jsonResponse([
        'error' => 'Config file mancante. Verifica config.php nel deploy.',
        'checkedPaths' => $configCandidates,
    ], 500);
}

$config = require $configPath;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$route = isset($_GET['route']) ? (string) $_GET['route'] : '';
$path = $route !== '' ? $route : (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = preg_replace('#^/api/index\.php#', '', $path);
$path = preg_replace('#^/api#', '', $path);
$path = $path === '' ? '/' : $path;
$payload = json_decode(file_get_contents('php://input'), true) ?? [];

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

if ($method === 'GET' && $path === '/me') {
    $userId = $_SESSION['user_id'] ?? null;

    if (! is_string($userId) || $userId === '') {
        jsonResponse(['user' => null], 200);
    }

    $stmt = $pdo->prepare('
        SELECT
            id,
            nickname,
            email,
            avatar_url AS avatarUrl,
            preferred_army_id AS preferredArmyId,
            preferred_faction AS preferredFaction
        FROM users
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse(['user' => $user ?: null], 200);
}

if ($method === 'POST' && $path === '/auth/register') {
    $nickname = trim((string) ($payload['nickname'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $passwordConfirmation = (string) ($payload['passwordConfirmation'] ?? '');

    if (mb_strlen($nickname) < 4) {
        jsonResponse(['error' => 'Il nickname deve avere almeno 4 caratteri.'], 422);
    }

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'Inserisci una email valida.'], 422);
    }

    if (strlen($password) < 8) {
        jsonResponse(['error' => 'La password deve avere almeno 8 caratteri.'], 422);
    }

    if ($password !== $passwordConfirmation) {
        jsonResponse(['error' => 'Le due password non coincidono.'], 422);
    }

    try {
        $duplicateStmt = $pdo->prepare('
            SELECT id, nickname, email
            FROM users
            WHERE LOWER(nickname) = LOWER(:nickname)
               OR LOWER(email) = LOWER(:email)
            LIMIT 1
        ');
        $duplicateStmt->execute([
            'nickname' => $nickname,
            'email' => $email,
        ]);

        $duplicate = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

        if ($duplicate) {
            if (strcasecmp((string) $duplicate['nickname'], $nickname) === 0) {
                jsonResponse(['error' => 'Nickname gia in uso.'], 409);
            }

            jsonResponse(['error' => 'Email gia registrata.'], 409);
        }

        $userId = uuidV4();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare('
            INSERT INTO users (
                id,
                nickname,
                email,
                password_hash,
                role,
                is_active,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :nickname,
                :email,
                :password_hash,
                :role,
                :is_active,
                NOW(),
                NOW()
            )
        ');

        $insertStmt->execute([
            'id' => $userId,
            'nickname' => $nickname,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'USER',
            'is_active' => 1,
        ]);

        $_SESSION['user_id'] = $userId;

        $emailStatus = sendRegistrationEmail($config['app'], $email, $nickname) ? 'sent' : 'failed';

        jsonResponse([
            'message' => $emailStatus === 'sent'
                ? 'Registrazione completata. Email di benvenuto inviata.'
                : 'Registrazione completata. Il server non ha confermato l invio della mail.',
            'emailStatus' => $emailStatus,
            'user' => [
                'id' => $userId,
                'nickname' => $nickname,
                'email' => $email,
                'avatarUrl' => null,
                'preferredArmyId' => null,
                'preferredFaction' => null,
            ],
        ], 201);
    } catch (Throwable $exception) {
        jsonResponse([
            'error' => 'Errore durante la registrazione.',
            'details' => $exception->getMessage(),
        ], 500);
    }
}

if ($method === 'POST' && $path === '/auth/login') {
    $login = trim((string) ($payload['email'] ?? $payload['login'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($login === '' || $password === '') {
        jsonResponse(['error' => 'Credenziali non valide.'], 422);
    }

    $stmt = $pdo->prepare('
        SELECT
            id,
            nickname,
            email,
            password_hash,
            avatar_url AS avatarUrl,
            preferred_army_id AS preferredArmyId,
            preferred_faction AS preferredFaction
        FROM users
        WHERE LOWER(email) = LOWER(:login)
           OR LOWER(nickname) = LOWER(:login)
        LIMIT 1
    ');
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (! $user || ! password_verify($password, (string) $user['password_hash'])) {
        jsonResponse(['error' => 'Credenziali non valide.'], 401);
    }

    $_SESSION['user_id'] = $user['id'];
    unset($user['password_hash']);

    jsonResponse([
        'message' => 'Login effettuato con successo.',
        'user' => $user,
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

if ($method === 'GET' && $path === '/users') {
    $stmt = $pdo->query('
        SELECT
            id,
            nickname
        FROM users
        WHERE is_active = 1
        ORDER BY nickname ASC
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
    if (! isset($_SESSION['user_id'])) {
      jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
    }

    $territoryId = trim((string) ($payload['territoryId'] ?? ''));
    $ownArmyId = trim((string) ($payload['ownArmyId'] ?? ''));
    $ownFaction = trim((string) ($payload['ownFaction'] ?? ''));
    $opponentNickname = trim((string) ($payload['opponentNickname'] ?? ''));
    $ownScore = (int) ($payload['ownScore'] ?? -1);
    $opponentScore = (int) ($payload['opponentScore'] ?? -1);
    $playedAt = trim((string) ($payload['playedAt'] ?? ''));

    if ($territoryId === '' || $ownArmyId === '' || $ownFaction === '' || $opponentNickname === '') {
        jsonResponse(['error' => 'Compila tutti i campi obbligatori.'], 422);
    }

    if ($ownScore < 0 || $opponentScore < 0) {
        jsonResponse(['error' => 'I punteggi non possono essere negativi.'], 422);
    }

    $currentUserId = (string) $_SESSION['user_id'];
    $opponentStmt = $pdo->prepare('
        SELECT id, nickname
        FROM users
        WHERE LOWER(nickname) = LOWER(:nickname)
        LIMIT 1
    ');
    $opponentStmt->execute(['nickname' => $opponentNickname]);
    $opponent = $opponentStmt->fetch(PDO::FETCH_ASSOC);

    if (! $opponent) {
        jsonResponse(['error' => 'Avversario non trovato.'], 404);
    }

    if ((string) $opponent['id'] === $currentUserId) {
        jsonResponse(['error' => 'Non puoi registrare una partita contro te stesso.'], 422);
    }

    $matchId = uuidV4();
    $resultId = uuidV4();

    $pdo->beginTransaction();

    try {
        $matchStmt = $pdo->prepare('
            INSERT INTO matches (
                id,
                territory_id,
                player_a_id,
                player_b_id,
                status,
                played_at,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :territory_id,
                :player_a_id,
                :player_b_id,
                :status,
                :played_at,
                NOW(),
                NOW()
            )
        ');

        $matchStmt->execute([
            'id' => $matchId,
            'territory_id' => $territoryId,
            'player_a_id' => $currentUserId,
            'player_b_id' => $opponent['id'],
            'status' => 'PENDING',
            'played_at' => $playedAt !== '' ? $playedAt : null,
        ]);

        $resultStmt = $pdo->prepare('
            INSERT INTO match_results (
                id,
                match_id,
                submitted_by_user_id,
                opponent_user_id,
                own_army_id,
                own_faction,
                own_score,
                opponent_score,
                status,
                note,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :match_id,
                :submitted_by_user_id,
                :opponent_user_id,
                :own_army_id,
                :own_faction,
                :own_score,
                :opponent_score,
                :status,
                :note,
                NOW(),
                NOW()
            )
        ');

        $resultStmt->execute([
            'id' => $resultId,
            'match_id' => $matchId,
            'submitted_by_user_id' => $currentUserId,
            'opponent_user_id' => $opponent['id'],
            'own_army_id' => $ownArmyId,
            'own_faction' => $ownFaction,
            'own_score' => $ownScore,
            'opponent_score' => $opponentScore,
            'status' => 'PENDING',
            'note' => (string) ($payload['note'] ?? ''),
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        jsonResponse([
            'error' => 'Errore durante il salvataggio del risultato.',
            'details' => $exception->getMessage(),
        ], 500);
    }

    jsonResponse([
        'message' => 'Risultato registrato correttamente e in attesa di conferma.',
    ], 201);
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

function uuidV4(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function sendRegistrationEmail(array $appConfig, string $email, string $nickname): bool
{
    $appName = (string) ($appConfig['name'] ?? 'Old World Federation Play Portal');
    $from = (string) ($appConfig['registration_mail_from'] ?? $appConfig['contact_email'] ?? '');

    if ($from === '') {
        return false;
    }

    $subject = sprintf('Benvenuto su %s', $appName);
    $message = implode("\r\n", [
        sprintf('Ciao %s,', $nickname),
        '',
        sprintf('la tua registrazione a %s e andata a buon fine.', $appName),
        '',
        'Ora puoi accedere al portale e partecipare alle attivita di campagna.',
        '',
        'Questo messaggio e stato generato automaticamente.',
    ]);

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        sprintf('From: %s <%s>', $appName, $from),
        sprintf('Reply-To: %s', $from),
    ];

    return mail($email, $subject, $message, implode("\r\n", $headers));
}

function firstExistingPath(array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            return $candidate;
        }
    }

    return null;
}
