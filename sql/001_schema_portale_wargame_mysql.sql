USE my_mondodelleleggende;

CREATE TABLE IF NOT EXISTS armies (
  id CHAR(36) NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  default_faction ENUM('RAVAGING_HORDES', 'FORCES_OF_FANTASY', 'UNDEAD') DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_armies_name (name),
  UNIQUE KEY uq_armies_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id CHAR(36) NOT NULL,
  nickname VARCHAR(80) NOT NULL,
  email VARCHAR(255) NOT NULL,
  email_encrypted TEXT DEFAULT NULL,
  email_hash CHAR(64) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  avatar_url VARCHAR(500) DEFAULT NULL,
  preferred_army_id CHAR(36) DEFAULT NULL,
  preferred_faction ENUM('RAVAGING_HORDES', 'FORCES_OF_FANTASY', 'UNDEAD') DEFAULT NULL,
  role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  email_verified_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_nickname (nickname),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_email_hash (email_hash),
  KEY idx_users_preferred_army (preferred_army_id),
  CONSTRAINT fk_users_preferred_army
    FOREIGN KEY (preferred_army_id) REFERENCES armies(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS territories (
  id CHAR(36) NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description TEXT DEFAULT NULL,
  lore TEXT DEFAULT NULL,
  map_path_id VARCHAR(120) DEFAULT NULL,
  x DECIMAL(10,4) DEFAULT NULL,
  y DECIMAL(10,4) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_territories_name (name),
  UNIQUE KEY uq_territories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
  id CHAR(36) NOT NULL,
  territory_id CHAR(36) NOT NULL,
  player_a_id CHAR(36) NOT NULL,
  player_b_id CHAR(36) NOT NULL,
  status ENUM('PENDING', 'CONFIRMED', 'CONFLICT', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  played_at DATE DEFAULT NULL,
  confirmed_at TIMESTAMP NULL DEFAULT NULL,
  winner_user_id CHAR(36) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_matches_territory_status (territory_id, status),
  KEY idx_matches_players (player_a_id, player_b_id),
  KEY idx_matches_winner_user (winner_user_id),
  CONSTRAINT fk_matches_territory
    FOREIGN KEY (territory_id) REFERENCES territories(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_matches_player_a
    FOREIGN KEY (player_a_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_matches_player_b
    FOREIGN KEY (player_b_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_matches_winner_user
    FOREIGN KEY (winner_user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_results (
  id CHAR(36) NOT NULL,
  match_id CHAR(36) NOT NULL,
  submitted_by_user_id CHAR(36) NOT NULL,
  opponent_user_id CHAR(36) NOT NULL,
  own_army_id CHAR(36) NOT NULL,
  own_faction ENUM('RAVAGING_HORDES', 'FORCES_OF_FANTASY', 'UNDEAD') NOT NULL,
  own_score INT NOT NULL,
  opponent_score INT NOT NULL,
  status ENUM('PENDING', 'CONFIRMED', 'CONFLICT', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  note TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_match_result_user (match_id, submitted_by_user_id),
  KEY idx_match_results_submitted_by (submitted_by_user_id),
  KEY idx_match_results_opponent (opponent_user_id),
  KEY idx_match_results_army (own_army_id),
  CONSTRAINT fk_match_results_match
    FOREIGN KEY (match_id) REFERENCES matches(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_match_results_submitted_by
    FOREIGN KEY (submitted_by_user_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_match_results_opponent_user
    FOREIGN KEY (opponent_user_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_match_results_own_army
    FOREIGN KEY (own_army_id) REFERENCES armies(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_password_reset_user (user_id),
  KEY idx_password_reset_expires (expires_at),
  CONSTRAINT fk_password_reset_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS territory_statistics (
  id CHAR(36) NOT NULL,
  territory_id CHAR(36) NOT NULL,
  computed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_confirmed_matches INT NOT NULL DEFAULT 0,
  total_pending_matches INT NOT NULL DEFAULT 0,
  dominant_faction ENUM('RAVAGING_HORDES', 'FORCES_OF_FANTASY', 'UNDEAD') DEFAULT NULL,
  dominant_army_id CHAR(36) DEFAULT NULL,
  faction_breakdown_json JSON NOT NULL,
  army_breakdown_json JSON NOT NULL,
  PRIMARY KEY (id),
  KEY idx_territory_statistics_territory (territory_id),
  KEY idx_territory_statistics_dominant_army (dominant_army_id),
  CONSTRAINT fk_territory_statistics_territory
    FOREIGN KEY (territory_id) REFERENCES territories(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_territory_statistics_dominant_army
    FOREIGN KEY (dominant_army_id) REFERENCES armies(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO armies (id, name, slug, default_faction, sort_order) VALUES
  ('f7fa6347-6d2d-4b44-8c29-5906ec9a8b01', 'Empire of Man', 'empire-of-man', 'FORCES_OF_FANTASY', 10),
  ('f7fa6347-6d2d-4b44-8c29-5906ec9a8b02', 'Dwarfen Mountain Holds', 'dwarfen-mountain-holds', 'FORCES_OF_FANTASY', 20),
  ('f7fa6347-6d2d-4b44-8c29-5906ec9a8b03', 'Orc & Goblin Tribes', 'orc-goblin-tribes', 'RAVAGING_HORDES', 30),
  ('f7fa6347-6d2d-4b44-8c29-5906ec9a8b04', 'Tomb Kings of Khemri', 'tomb-kings-of-khemri', 'UNDEAD', 40);

INSERT IGNORE INTO territories (id, name, slug, description, lore, map_path_id, sort_order) VALUES
  ('adfa6347-6d2d-4b44-8c29-5906ec9a8c01', 'Passo delle Corone', 'passo-delle-corone', 'Valico conteso tra fortezze imperiali, alture pietrose e rovine di torri sentinella.', 'Le armate che controllano questo passaggio decidono il ritmo dell intera campagna settentrionale.', 'north-pass', 10),
  ('adfa6347-6d2d-4b44-8c29-5906ec9a8c02', 'Piane Cineree', 'piane-cineree', 'Distesa bruciata da scorrerie e fuochi rituali, ideale per incursioni e schermaglie rapide.', 'Chi domina le Piane Cineree puo minacciare i convogli, le riserve e le retrovie nemiche.', 'ash-plains', 20),
  ('adfa6347-6d2d-4b44-8c29-5906ec9a8c03', 'Necropoli del Sole Nero', 'necropoli-del-sole-nero', 'Citta funeraria infestata da guardiani eterni e cripte che si risvegliano al crepuscolo.', 'Gli equilibri della campagna cambiano ogni volta che le sabbie della necropoli si muovono.', 'black-sun-necropolis', 30);
