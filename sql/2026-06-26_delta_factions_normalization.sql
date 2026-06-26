USE my_mondodelleleggende;

CREATE TABLE IF NOT EXISTS factions (
  id CHAR(36) NOT NULL,
  code ENUM('RAVAGING_HORDES', 'FORCES_OF_FANTASY', 'UNDEAD') NOT NULL,
  name VARCHAR(120) NOT NULL,
  color_hex VARCHAR(16) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_factions_code (code),
  UNIQUE KEY uq_factions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO factions (id, code, name, color_hex, sort_order, is_active) VALUES
  ('faa16347-6d2d-4b44-8c29-5906ec9a8f01', 'FORCES_OF_FANTASY', 'Forces of Fantasy', '#2f6fdd', 10, 1),
  ('faa16347-6d2d-4b44-8c29-5906ec9a8f02', 'RAVAGING_HORDES', 'Ravaging Hordes', '#b3181f', 20, 1),
  ('faa16347-6d2d-4b44-8c29-5906ec9a8f03', 'UNDEAD', 'Undead', '#777777', 30, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  color_hex = VALUES(color_hex),
  sort_order = VALUES(sort_order),
  is_active = VALUES(is_active),
  updated_at = CURRENT_TIMESTAMP;

ALTER TABLE armies
  ADD COLUMN IF NOT EXISTS faction_id CHAR(36) NULL AFTER slug;

UPDATE armies a
INNER JOIN factions f ON f.code = a.default_faction
SET a.faction_id = f.id
WHERE a.default_faction IS NOT NULL
  AND (a.faction_id IS NULL OR a.faction_id <> f.id);

ALTER TABLE armies
  ADD INDEX idx_armies_faction_id (faction_id);

ALTER TABLE armies
  ADD CONSTRAINT fk_armies_faction
  FOREIGN KEY (faction_id) REFERENCES factions(id)
  ON DELETE SET NULL
  ON UPDATE CASCADE;
