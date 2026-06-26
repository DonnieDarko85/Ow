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
            role,
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
                'role' => 'USER',
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
            role,
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
    if (tableExists($pdo, 'factions') && columnExists($pdo, 'armies', 'faction_id')) {
        $stmt = $pdo->query('
            SELECT
                a.id,
                a.name,
                a.slug,
                a.faction_id AS factionId,
                COALESCE(f.code, a.default_faction) AS defaultFaction
            FROM armies a
            LEFT JOIN factions f ON f.id = a.faction_id
            WHERE a.is_active = 1
            ORDER BY a.sort_order, a.name
        ');
    } else {
        $stmt = $pdo->query('
            SELECT
                id,
                name,
                slug,
                NULL AS factionId,
                default_faction AS defaultFaction
            FROM armies
            WHERE is_active = 1
            ORDER BY sort_order, name
        ');
    }

    jsonResponse($stmt->fetchAll());
}

if ($method === 'GET' && $path === '/factions') {
    if (tableExists($pdo, 'factions')) {
        $stmt = $pdo->query('
            SELECT
                id,
                code,
                name,
                color_hex AS color
            FROM factions
            WHERE is_active = 1
            ORDER BY sort_order, name
        ');

        jsonResponse($stmt->fetchAll());
    }

    jsonResponse([
        [
            'id' => 'fallback-faction-1',
            'code' => 'FORCES_OF_FANTASY',
            'name' => 'Forces of Fantasy',
            'color' => '#2f6fdd',
        ],
        [
            'id' => 'fallback-faction-2',
            'code' => 'RAVAGING_HORDES',
            'name' => 'Ravaging Hordes',
            'color' => '#b3181f',
        ],
        [
            'id' => 'fallback-faction-3',
            'code' => 'UNDEAD',
            'name' => 'Undead',
            'color' => '#777777',
        ],
    ]);
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

    $factionCodes = getActiveFactionCodes($pdo);
    $territoryStats = [];

    $matchesStmt = $pdo->query("
        SELECT
            m.id,
            m.territory_id AS territoryId,
            m.status,
            resultA.own_faction AS factionA,
            armyA.name AS armyAName,
            resultA.own_score AS victoryScoreA,
            resultB.own_faction AS factionB,
            armyB.name AS armyBName,
            resultB.own_score AS victoryScoreB
        FROM matches m
        LEFT JOIN match_results resultA
            ON resultA.match_id = m.id
           AND resultA.submitted_by_user_id = m.player_a_id
           AND resultA.status = 'CONFIRMED'
        LEFT JOIN match_results resultB
            ON resultB.match_id = m.id
           AND resultB.submitted_by_user_id = m.player_b_id
           AND resultB.status = 'CONFIRMED'
        LEFT JOIN armies armyA ON armyA.id = resultA.own_army_id
        LEFT JOIN armies armyB ON armyB.id = resultB.own_army_id
        WHERE m.status IN ('PENDING', 'CONFIRMED')
    ");

    foreach ($matchesStmt->fetchAll(PDO::FETCH_ASSOC) as $matchRow) {
        $territoryId = (string) $matchRow['territoryId'];

        if (! isset($territoryStats[$territoryId])) {
            $territoryStats[$territoryId] = [
                'confirmedBattles' => 0,
                'pendingBattles' => 0,
                'factionWins' => [],
                'armyWins' => [],
            ];
        }

        if ($matchRow['status'] === 'PENDING') {
            $territoryStats[$territoryId]['pendingBattles']++;
            continue;
        }

        if (
            ! is_string($matchRow['factionA']) || $matchRow['factionA'] === ''
            || ! is_string($matchRow['factionB']) || $matchRow['factionB'] === ''
        ) {
            continue;
        }

        $territoryStats[$territoryId]['confirmedBattles']++;

        $matchPoints = calculateMatchPoints(
            (int) $matchRow['victoryScoreA'],
            (int) $matchRow['victoryScoreB']
        );

        if ($matchPoints['playerA'] === $matchPoints['playerB']) {
            incrementWeightedCount($territoryStats[$territoryId]['factionWins'], (string) $matchRow['factionA'], 0.5);
            incrementWeightedCount($territoryStats[$territoryId]['factionWins'], (string) $matchRow['factionB'], 0.5);

            if (is_string($matchRow['armyAName']) && $matchRow['armyAName'] !== '') {
                incrementWeightedCount($territoryStats[$territoryId]['armyWins'], (string) $matchRow['armyAName'], 0.5);
            }

            if (is_string($matchRow['armyBName']) && $matchRow['armyBName'] !== '') {
                incrementWeightedCount($territoryStats[$territoryId]['armyWins'], (string) $matchRow['armyBName'], 0.5);
            }

            continue;
        }

        $winningFaction = $matchPoints['playerA'] > $matchPoints['playerB']
            ? (string) $matchRow['factionA']
            : (string) $matchRow['factionB'];
        $winningArmy = $matchPoints['playerA'] > $matchPoints['playerB']
            ? (string) ($matchRow['armyAName'] ?? '')
            : (string) ($matchRow['armyBName'] ?? '');

        incrementWeightedCount($territoryStats[$territoryId]['factionWins'], $winningFaction, 1);

        if ($winningArmy !== '') {
            incrementWeightedCount($territoryStats[$territoryId]['armyWins'], $winningArmy, 1);
        }
    }

    $territories = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $stats = $territoryStats[(string) $row['id']] ?? [
            'confirmedBattles' => 0,
            'pendingBattles' => 0,
            'factionWins' => [],
            'armyWins' => [],
        ];

        $row['stats'] = buildTerritoryStatsPayload(
            $stats['confirmedBattles'],
            $stats['pendingBattles'],
            $stats['factionWins'],
            $stats['armyWins'],
            $factionCodes
        );

        $territories[] = $row;
    }

    jsonResponse($territories);
}

if ($method === 'GET' && $path === '/matches/recent') {
    $stmt = $pdo->query("
        SELECT
            m.id,
            COALESCE(m.played_at, DATE(m.confirmed_at), DATE(m.created_at)) AS playedAt,
            m.status,
            t.slug AS territorySlug,
            t.name AS territoryName,
            playerA.nickname AS playerA,
            armyA.name AS armyA,
            resultA.own_faction AS factionA,
            resultA.own_score AS victoryScoreA,
            playerB.nickname AS playerB,
            armyB.name AS armyB,
            resultB.own_faction AS factionB,
            resultB.own_score AS victoryScoreB
        FROM matches m
        INNER JOIN territories t ON t.id = m.territory_id
        INNER JOIN users playerA ON playerA.id = m.player_a_id
        INNER JOIN users playerB ON playerB.id = m.player_b_id
        INNER JOIN match_results resultA
            ON resultA.match_id = m.id
           AND resultA.submitted_by_user_id = m.player_a_id
           AND resultA.status = 'CONFIRMED'
        INNER JOIN match_results resultB
            ON resultB.match_id = m.id
           AND resultB.submitted_by_user_id = m.player_b_id
           AND resultB.status = 'CONFIRMED'
        INNER JOIN armies armyA ON armyA.id = resultA.own_army_id
        INNER JOIN armies armyB ON armyB.id = resultB.own_army_id
        WHERE m.status = 'CONFIRMED'
        ORDER BY COALESCE(m.played_at, DATE(m.confirmed_at), DATE(m.created_at)) DESC, m.confirmed_at DESC, m.created_at DESC
        LIMIT 20
    ");

    $matches = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $matchPoints = calculateMatchPoints(
            (int) $row['victoryScoreA'],
            (int) $row['victoryScoreB']
        );

        $row['scoreA'] = $matchPoints['playerA'];
        $row['scoreB'] = $matchPoints['playerB'];
        $row['victoryPointsA'] = (int) $row['victoryScoreA'];
        $row['victoryPointsB'] = (int) $row['victoryScoreB'];
        unset($row['victoryScoreA'], $row['victoryScoreB']);
        $matches[] = $row;
    }

    jsonResponse($matches);
}

if ($method === 'GET' && $path === '/matches/pending-for-me') {
    if (! isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
    }

    $currentUserId = (string) $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            m.id AS matchId,
            m.territory_id AS territoryId,
            t.name AS territoryName,
            opponent.id AS opponentUserId,
            opponent.nickname AS opponentNickname,
            mr.own_army_id AS opponentArmyId,
            army.name AS opponentArmyName,
            mr.own_faction AS opponentFaction,
            mr.own_score AS opponentScore,
            mr.opponent_score AS yourScore,
            COALESCE(mr.note, '') AS note,
            mr.created_at AS createdAt
        FROM matches m
        INNER JOIN territories t ON t.id = m.territory_id
        INNER JOIN match_results mr
            ON mr.match_id = m.id
           AND mr.opponent_user_id = :current_user_id
           AND mr.status = 'PENDING'
        INNER JOIN users opponent ON opponent.id = mr.submitted_by_user_id
        INNER JOIN armies army ON army.id = mr.own_army_id
        LEFT JOIN match_results my_result
            ON my_result.match_id = m.id
           AND my_result.submitted_by_user_id = :current_user_id
        WHERE m.status = 'PENDING'
          AND my_result.id IS NULL
        ORDER BY mr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute(['current_user_id' => $currentUserId]);

    jsonResponse($stmt->fetchAll());
}

if ($method === 'GET' && $path === '/matches/pending-by-me') {
    if (! isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
    }

    $currentUserId = (string) $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            m.id AS matchId,
            m.territory_id AS territoryId,
            t.name AS territoryName,
            opponent.id AS opponentUserId,
            opponent.nickname AS opponentNickname,
            mr.own_army_id AS ownArmyId,
            army.name AS ownArmyName,
            mr.own_faction AS ownFaction,
            mr.own_score AS ownScore,
            mr.opponent_score AS opponentScore,
            COALESCE(mr.note, '') AS note,
            mr.created_at AS createdAt,
            mr.status AS status
        FROM matches m
        INNER JOIN territories t ON t.id = m.territory_id
        INNER JOIN match_results mr
            ON mr.match_id = m.id
           AND mr.submitted_by_user_id = :current_user_id
           AND mr.status = 'PENDING'
        INNER JOIN users opponent ON opponent.id = mr.opponent_user_id
        INNER JOIN armies army ON army.id = mr.own_army_id
        LEFT JOIN match_results opponent_result
            ON opponent_result.match_id = m.id
           AND opponent_result.submitted_by_user_id = mr.opponent_user_id
        WHERE m.status = 'PENDING'
          AND opponent_result.id IS NULL
        ORDER BY mr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute(['current_user_id' => $currentUserId]);

    jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($method === 'POST' && $path === '/matches/results') {
    if (! isset($_SESSION['user_id'])) {
      jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
    }

    $territoryId = trim((string) ($payload['territoryId'] ?? ''));
    $ownArmyId = trim((string) ($payload['ownArmyId'] ?? ''));
    $opponentNickname = trim((string) ($payload['opponentNickname'] ?? ''));
    $ownScore = (int) ($payload['ownScore'] ?? -1);
    $opponentScore = (int) ($payload['opponentScore'] ?? -1);
    $playedAt = trim((string) ($payload['playedAt'] ?? ''));

    if ($territoryId === '' || $ownArmyId === '' || $opponentNickname === '') {
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

    if (tableExists($pdo, 'factions') && columnExists($pdo, 'armies', 'faction_id')) {
        $armyStmt = $pdo->prepare('
            SELECT COALESCE(f.code, a.default_faction) AS factionCode
            FROM armies a
            LEFT JOIN factions f ON f.id = a.faction_id
            WHERE a.id = :id
            LIMIT 1
        ');
    } else {
        $armyStmt = $pdo->prepare('
            SELECT default_faction AS factionCode
            FROM armies
            WHERE id = :id
            LIMIT 1
        ');
    }
    $armyStmt->execute(['id' => $ownArmyId]);
    $army = $armyStmt->fetch(PDO::FETCH_ASSOC);

    if (! $army || ! is_string($army['factionCode']) || $army['factionCode'] === '') {
        jsonResponse(['error' => 'Armata non valida o fazione non configurata.'], 422);
    }

    $ownFaction = (string) $army['factionCode'];

    $pdo->beginTransaction();

    try {
        $candidateStmt = $pdo->prepare("
            SELECT
                m.id AS matchId,
                existing_result.id AS existingResultId,
                existing_result.own_score AS existingOwnScore,
                existing_result.opponent_score AS existingOpponentScore,
                existing_result.status AS existingResultStatus
            FROM matches m
            INNER JOIN match_results existing_result
                ON existing_result.match_id = m.id
               AND existing_result.submitted_by_user_id = :opponent_user_id
               AND existing_result.opponent_user_id = :current_user_id
            LEFT JOIN match_results current_result
                ON current_result.match_id = m.id
               AND current_result.submitted_by_user_id = :current_user_id
            WHERE m.status = 'PENDING'
              AND existing_result.status = 'PENDING'
              AND current_result.id IS NULL
              AND m.territory_id = :territory_id
              AND (
                    (m.player_a_id = :current_user_id AND m.player_b_id = :opponent_user_id)
                 OR (m.player_a_id = :opponent_user_id AND m.player_b_id = :current_user_id)
              )
            ORDER BY m.created_at DESC
            FOR UPDATE
        ");
        $candidateStmt->execute([
            'current_user_id' => $currentUserId,
            'opponent_user_id' => $opponent['id'],
            'territory_id' => $territoryId,
        ]);
        $pendingCandidates = $candidateStmt->fetchAll(PDO::FETCH_ASSOC);

        $mirroredCandidate = null;

        foreach ($pendingCandidates as $candidate) {
            if (
                (int) $candidate['existingOwnScore'] === $opponentScore
                && (int) $candidate['existingOpponentScore'] === $ownScore
            ) {
                $mirroredCandidate = $candidate;
                break;
            }
        }

        $insertResultStmt = $pdo->prepare('
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

        if ($mirroredCandidate !== null) {
            $insertResultStmt->execute([
                'id' => uuidV4(),
                'match_id' => $mirroredCandidate['matchId'],
                'submitted_by_user_id' => $currentUserId,
                'opponent_user_id' => $opponent['id'],
                'own_army_id' => $ownArmyId,
                'own_faction' => $ownFaction,
                'own_score' => $ownScore,
                'opponent_score' => $opponentScore,
                'status' => 'CONFIRMED',
                'note' => (string) ($payload['note'] ?? ''),
            ]);

            $updateExistingResultStmt = $pdo->prepare('
                UPDATE match_results
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $updateExistingResultStmt->execute([
                'status' => 'CONFIRMED',
                'id' => $mirroredCandidate['existingResultId'],
            ]);

            $matchPoints = calculateMatchPoints($ownScore, $opponentScore);
            $winnerUserId = null;

            if ($matchPoints['playerA'] > $matchPoints['playerB']) {
                $winnerUserId = $currentUserId;
            } elseif ($matchPoints['playerB'] > $matchPoints['playerA']) {
                $winnerUserId = (string) $opponent['id'];
            }

            $updateMatchStmt = $pdo->prepare('
                UPDATE matches
                SET status = :status,
                    confirmed_at = NOW(),
                    winner_user_id = :winner_user_id,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $updateMatchStmt->execute([
                'status' => 'CONFIRMED',
                'winner_user_id' => $winnerUserId,
                'id' => $mirroredCandidate['matchId'],
            ]);

            $pdo->commit();

            jsonResponse([
                'message' => 'Match confermato correttamente: i risultati dei due giocatori coincidono.',
            ], 201);
        }

        if ($pendingCandidates !== []) {
            $conflictCandidate = $pendingCandidates[0];

            $insertResultStmt->execute([
                'id' => uuidV4(),
                'match_id' => $conflictCandidate['matchId'],
                'submitted_by_user_id' => $currentUserId,
                'opponent_user_id' => $opponent['id'],
                'own_army_id' => $ownArmyId,
                'own_faction' => $ownFaction,
                'own_score' => $ownScore,
                'opponent_score' => $opponentScore,
                'status' => 'CONFLICT',
                'note' => (string) ($payload['note'] ?? ''),
            ]);

            $updateConflictResultsStmt = $pdo->prepare('
                UPDATE match_results
                SET status = :status,
                    updated_at = NOW()
                WHERE match_id = :match_id
            ');
            $updateConflictResultsStmt->execute([
                'status' => 'CONFLICT',
                'match_id' => $conflictCandidate['matchId'],
            ]);

            $updateConflictMatchStmt = $pdo->prepare('
                UPDATE matches
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $updateConflictMatchStmt->execute([
                'status' => 'CONFLICT',
                'id' => $conflictCandidate['matchId'],
            ]);

            $pdo->commit();

            jsonResponse([
                'message' => 'Risultato registrato, ma i due inserimenti non coincidono: il match e ora in conflitto.',
            ], 201);
        }

        $duplicatePendingStmt = $pdo->prepare("
            SELECT m.id
            FROM matches m
            INNER JOIN match_results mr
                ON mr.match_id = m.id
               AND mr.submitted_by_user_id = :current_user_id
               AND mr.opponent_user_id = :opponent_user_id
            WHERE m.status = 'PENDING'
              AND mr.status = 'PENDING'
              AND m.territory_id = :territory_id
              AND mr.own_army_id = :own_army_id
              AND mr.own_score = :own_score
              AND mr.opponent_score = :opponent_score
              AND (
                    (m.player_a_id = :current_user_id AND m.player_b_id = :opponent_user_id)
                 OR (m.player_a_id = :opponent_user_id AND m.player_b_id = :current_user_id)
              )
            LIMIT 1
            FOR UPDATE
        ");
        $duplicatePendingStmt->execute([
            'current_user_id' => $currentUserId,
            'opponent_user_id' => $opponent['id'],
            'territory_id' => $territoryId,
            'own_army_id' => $ownArmyId,
            'own_score' => $ownScore,
            'opponent_score' => $opponentScore,
        ]);

        if ($duplicatePendingStmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            jsonResponse([
                'error' => 'Hai gia inserito questo risultato ed e ancora in attesa della conferma dell avversario.',
            ], 409);
        }

        $matchId = uuidV4();

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

        $insertResultStmt->execute([
            'id' => uuidV4(),
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
    $appName = (string) ($appConfig['name'] ?? 'Sun-Tzu Secrets Play Portal');
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

function tableExists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    $key = $tableName;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
    $stmt->execute(['table_name' => $tableName]);
    $cache[$key] = (bool) $stmt->fetchColumn();

    return $cache[$key];
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    static $cache = [];
    $key = sprintf('%s.%s', $tableName, $columnName);

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE :column_name");
    $stmt->execute(['column_name' => $columnName]);
    $cache[$key] = (bool) $stmt->fetchColumn();

    return $cache[$key];
}

function calculateMatchPoints(int $playerAVictoryPoints, int $playerBVictoryPoints): array
{
    $difference = abs($playerAVictoryPoints - $playerBVictoryPoints);

    if ($difference <= 450) {
        return ['playerA' => 3, 'playerB' => 3];
    }

    if ($difference <= 950) {
        return $playerAVictoryPoints > $playerBVictoryPoints
            ? ['playerA' => 4, 'playerB' => 2]
            : ['playerA' => 2, 'playerB' => 4];
    }

    if ($difference <= 1400) {
        return $playerAVictoryPoints > $playerBVictoryPoints
            ? ['playerA' => 5, 'playerB' => 1]
            : ['playerA' => 1, 'playerB' => 5];
    }

    return $playerAVictoryPoints > $playerBVictoryPoints
        ? ['playerA' => 6, 'playerB' => 0]
        : ['playerA' => 0, 'playerB' => 6];
}

function getActiveFactionCodes(PDO $pdo): array
{
    if (tableExists($pdo, 'factions')) {
        $stmt = $pdo->query("
            SELECT code
            FROM factions
            WHERE is_active = 1
            ORDER BY sort_order, name
        ");

        $codes = array_map(
            static fn (array $row): string => (string) $row['code'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        if ($codes !== []) {
            return $codes;
        }
    }

    return ['FORCES_OF_FANTASY', 'RAVAGING_HORDES', 'UNDEAD'];
}

function incrementWeightedCount(array &$bucket, string $key, float $amount): void
{
    if (! isset($bucket[$key])) {
        $bucket[$key] = 0.0;
    }

    $bucket[$key] += $amount;
}

function buildTerritoryStatsPayload(
    int $confirmedBattles,
    int $pendingBattles,
    array $factionWins,
    array $armyWins,
    array $factionCodes
): array {
    $factionControl = [];
    $dominantFaction = (string) ($factionCodes[0] ?? 'FORCES_OF_FANTASY');

    foreach ($factionCodes as $factionCode) {
        $value = (float) ($factionWins[$factionCode] ?? 0);
        $percentage = $confirmedBattles > 0 ? (int) round(($value / $confirmedBattles) * 100) : 0;
        $factionControl[] = [
            'faction' => $factionCode,
            'percentage' => $percentage,
        ];
    }

    usort(
        $factionControl,
        static function (array $left, array $right): int {
            return $right['percentage'] <=> $left['percentage'];
        }
    );

    if ($factionControl !== [] && $factionControl[0]['percentage'] > 0) {
        $dominantFaction = (string) $factionControl[0]['faction'];
    }

    $armyControl = [];

    foreach ($armyWins as $armyName => $value) {
        $armyControl[] = [
            'armyName' => (string) $armyName,
            'percentage' => $confirmedBattles > 0 ? (int) round((((float) $value) / $confirmedBattles) * 100) : 0,
        ];
    }

    usort(
        $armyControl,
        static function (array $left, array $right): int {
            return $right['percentage'] <=> $left['percentage'];
        }
    );

    return [
        'confirmedBattles' => $confirmedBattles,
        'pendingBattles' => $pendingBattles,
        'dominantFaction' => $dominantFaction,
        'factionControl' => $factionControl,
        'armyControl' => $armyControl,
    ];
}
