-- ============================================================
-- GlassTube — Seed data (categories, tags, settings)
-- Import after DATABASE.sql:
--   mysql -u root -p glass_tube < DATABASE.sql
--   mysql -u root -p glass_tube < seed.sql
-- Then create users/videos via the PHP seeder:
--   php database/seeds/seed.php
-- ============================================================

INSERT IGNORE INTO categories (name, slug, sort_order) VALUES
('Образование', 'education', 1),
('Технологии', 'technology', 2),
('Музыка', 'music', 3),
('Кино', 'film', 4),
('Искусство', 'art', 5),
('Наука', 'science', 6),
('Лекции', 'lectures', 7),
('Обзоры', 'reviews', 8),
('Творчество', 'creativity', 9),
('Другое', 'other', 10);

INSERT IGNORE INTO tags (name, slug) VALUES
('дизайн', 'design'),
('музыка', 'music'),
('обучение', 'learning'),
('природа', 'nature'),
('код', 'code'),
('калм', 'calm');

INSERT IGNORE INTO settings (`key`, value) VALUES
('enable_ai', '1'),
('require_video_approval', '0'),
('video_max_mb', '1024'),
('ai_daily_limit_auth', '100'),
('registration_open', '1');
