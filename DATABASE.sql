-- ============================================================
-- GlassTube — Database schema (MySQL 8+)
-- ============================================================
-- Создание базы:
--   CREATE DATABASE glass_tube CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE glass_tube;
--   SOURCE DATABASE.sql;
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`         VARCHAR(40)  NOT NULL,
  `email`            VARCHAR(191) NOT NULL,
  `password_hash`    VARCHAR(255) NOT NULL,
  `avatar_path`      VARCHAR(255) NULL DEFAULT NULL,
  `bio`              TEXT         NULL DEFAULT NULL,
  `role`             ENUM('user','admin') NOT NULL DEFAULT 'user',
  `is_active`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at`    DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email`    (`email`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- channels
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `channels` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(80)  NOT NULL,
  `slug`        VARCHAR(100) NOT NULL,
  `description` TEXT         NULL DEFAULT NULL,
  `avatar_path` VARCHAR(255) NULL DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_channels_slug` (`slug`),
  KEY `ix_channels_user` (`user_id`),
  CONSTRAINT `fk_channels_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(60)  NOT NULL,
  `slug`       VARCHAR(80)  NOT NULL,
  `sort_order` INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- videos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `videos` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`             INT UNSIGNED NOT NULL,
  `channel_id`          INT UNSIGNED NOT NULL,
  `category_id`         INT UNSIGNED NULL DEFAULT NULL,
  `title`               VARCHAR(160) NOT NULL,
  `slug`                VARCHAR(200) NOT NULL,
  `description`         TEXT         NULL DEFAULT NULL,
  `original_filename`   VARCHAR(255) NULL DEFAULT NULL,
  `storage_path`        VARCHAR(512) NOT NULL,
  `mime_type`           VARCHAR(80)  NOT NULL,
  `size_bytes`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `duration_seconds`    INT UNSIGNED NULL DEFAULT NULL,
  `thumbnail_path`      VARCHAR(512) NULL DEFAULT NULL,
  `visibility`          ENUM('public','unlisted','private') NOT NULL DEFAULT 'public',
  `status`              ENUM('processing','published','pending_review','blocked','failed') NOT NULL DEFAULT 'processing',
  `view_count`          INT UNSIGNED NOT NULL DEFAULT 0,
  `like_count`          INT UNSIGNED NOT NULL DEFAULT 0,
  `comment_count`       INT UNSIGNED NOT NULL DEFAULT 0,
  `ai_moderation_status` VARCHAR(40) NULL DEFAULT NULL,
  `ai_summary`          TEXT         NULL DEFAULT NULL,
  `ai_metadata`         JSON         NULL DEFAULT NULL,
  `published_at`        DATETIME     NULL DEFAULT NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_videos_slug` (`slug`),
  KEY `ix_videos_status`      (`status`),
  KEY `ix_videos_visibility`  (`visibility`),
  KEY `ix_videos_published`   (`published_at`),
  KEY `ix_videos_channel`     (`channel_id`),
  KEY `ix_videos_category`    (`category_id`),
  KEY `ix_videos_user`        (`user_id`),
  CONSTRAINT `fk_videos_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_videos_channel`  FOREIGN KEY (`channel_id`)  REFERENCES `channels` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_videos_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- tags
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60)  NOT NULL,
  `slug` VARCHAR(80)  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- video_tags
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `video_tags` (
  `video_id` INT UNSIGNED NOT NULL,
  `tag_id`   INT UNSIGNED NOT NULL,
  PRIMARY KEY (`video_id`, `tag_id`),
  KEY `ix_videotags_tag` (`tag_id`),
  CONSTRAINT `fk_videotags_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_videotags_tag`   FOREIGN KEY (`tag_id`)   REFERENCES `tags` (`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- comments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `video_id`             INT UNSIGNED NOT NULL,
  `user_id`              INT UNSIGNED NOT NULL,
  `parent_id`            INT UNSIGNED NULL DEFAULT NULL,
  `body`                 TEXT         NOT NULL,
  `status`               ENUM('visible','hidden','pending','flagged') NOT NULL DEFAULT 'visible',
  `ai_moderation_status` VARCHAR(40)  NULL DEFAULT NULL,
  `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_comments_video`  (`video_id`),
  KEY `ix_comments_status` (`status`),
  KEY `ix_comments_user`   (`user_id`),
  KEY `ix_comments_parent` (`parent_id`),
  CONSTRAINT `fk_comments_video`  FOREIGN KEY (`video_id`)  REFERENCES `videos` (`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- likes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `likes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `video_id`   INT UNSIGNED NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_likes_user_video` (`user_id`, `video_id`),
  KEY `ix_likes_video` (`video_id`),
  CONSTRAINT `fk_likes_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_likes_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- subscriptions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscriber_user_id` INT UNSIGNED NOT NULL,
  `channel_id`         INT UNSIGNED NOT NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subs_user_channel` (`subscriber_user_id`, `channel_id`),
  KEY `ix_subs_channel` (`channel_id`),
  CONSTRAINT `fk_subs_user`    FOREIGN KEY (`subscriber_user_id`) REFERENCES `users` (`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_subs_channel` FOREIGN KEY (`channel_id`)         REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- playlists
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `playlists` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(140) NOT NULL,
  `description` TEXT         NULL DEFAULT NULL,
  `visibility`  ENUM('private','public') NOT NULL DEFAULT 'private',
  `is_watch_later` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_playlists_user` (`user_id`),
  KEY `ix_playlists_visibility` (`visibility`),
  CONSTRAINT `fk_playlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- playlist_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `playlist_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `playlist_id` INT UNSIGNED NOT NULL,
  `video_id`   INT UNSIGNED NOT NULL,
  `position`   INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_playlist_video` (`playlist_id`, `video_id`),
  KEY `ix_playlistitems_video` (`video_id`),
  CONSTRAINT `fk_playlistitems_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_playlistitems_video`   FOREIGN KEY (`video_id`)   REFERENCES `videos` (`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- watch_history
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `watch_history` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `video_id`         INT UNSIGNED NOT NULL,
  `progress_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_history_user_video` (`user_id`, `video_id`),
  KEY `ix_history_video` (`video_id`),
  CONSTRAINT `fk_history_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_history_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- reports
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `video_id`         INT UNSIGNED NULL DEFAULT NULL,
  `comment_id`       INT UNSIGNED NULL DEFAULT NULL,
  `reason`           VARCHAR(40)  NOT NULL,
  `details`          TEXT         NULL DEFAULT NULL,
  `status`           ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_reports_status` (`status`),
  KEY `ix_reports_video`   (`video_id`),
  KEY `ix_reports_comment` (`comment_id`),
  CONSTRAINT `fk_reports_user`    FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`)    ON DELETE SET NULL,
  CONSTRAINT `fk_reports_video`   FOREIGN KEY (`video_id`)   REFERENCES `videos` (`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_reports_comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ai_requests (лог)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_requests` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NULL DEFAULT NULL,
  `endpoint`         VARCHAR(60)  NOT NULL,
  `provider`         VARCHAR(40)  NOT NULL,
  `model`            VARCHAR(80)  NOT NULL,
  `input_hash`       VARCHAR(64)  NOT NULL,
  `status`           ENUM('ok','error') NOT NULL DEFAULT 'ok',
  `error_message`    TEXT         NULL DEFAULT NULL,
  `response_time_ms` INT UNSIGNED NULL DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_aireq_user` (`user_id`),
  KEY `ix_aireq_created` (`created_at`),
  CONSTRAINT `fk_aireq_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ai_cache
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_cache` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hash`          VARCHAR(64)  NOT NULL,
  `provider`      VARCHAR(40)  NOT NULL,
  `model`         VARCHAR(80)  NOT NULL,
  `request_json`  MEDIUMTEXT   NOT NULL,
  `response_json` MEDIUMTEXT   NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`    DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aicache_hash` (`hash`),
  KEY `ix_aicache_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`   VARCHAR(80)  NOT NULL,
  `value` TEXT         NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
