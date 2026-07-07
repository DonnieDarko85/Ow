USE my_mondodelleleggende;

CREATE TABLE IF NOT EXISTS territory_hex_assignments (
  hex_id VARCHAR(32) NOT NULL,
  territory_id CHAR(36) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (hex_id),
  KEY idx_territory_hex_assignments_territory (territory_id),
  CONSTRAINT fk_territory_hex_assignments_territory
    FOREIGN KEY (territory_id) REFERENCES territories(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
