USE my_mondodelleleggende;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER' AFTER password_hash;

INSERT INTO territories (
  id,
  name,
  slug,
  description,
  lore,
  map_path_id,
  sort_order,
  is_active
) VALUES
  (
    'adfa6347-6d2d-4b44-8c29-5906ec9a8c11',
    'Porto delle Maree Rosse',
    'porto-delle-maree-rosse',
    'Approdo conteso tra moli fortificati, magazzini bruciati e acque segnate da relitti recenti.',
    'Chi domina il porto controlla sbarchi, rifornimenti e movimenti lungo tutta la baia.',
    'porto-delle-maree-rosse',
    110,
    1
  ),
  (
    'adfa6347-6d2d-4b44-8c29-5906ec9a8c12',
    'Alture di Ferrovento',
    'alture-di-ferrovento',
    'Creste rocciose spazzate dal vento, perfette per avvistamento, cannoni e imboscate.',
    'Le alture decidono chi vede arrivare il nemico e chi puo spezzarne l avanzata.',
    'alture-di-ferrovento',
    120,
    1
  ),
  (
    'adfa6347-6d2d-4b44-8c29-5906ec9a8c13',
    'Bosco dei Sussurri Neri',
    'bosco-dei-sussurri-neri',
    'Selva antica e inquieta, ricca di sentieri nascosti, pietre runiche e zone d ombra.',
    'Le pattuglie che entrano nel bosco raramente ne escono con la stessa idea di vittoria.',
    'bosco-dei-sussurri-neri',
    130,
    1
  )
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  lore = VALUES(lore),
  map_path_id = VALUES(map_path_id),
  sort_order = VALUES(sort_order),
  is_active = VALUES(is_active),
  updated_at = CURRENT_TIMESTAMP;

UPDATE users
SET role = 'ADMIN'
WHERE LOWER(email) IN ('admin@example.com')
   OR LOWER(nickname) IN ('admin');
