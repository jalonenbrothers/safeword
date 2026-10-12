-- SafeWord migration 001 — core schema
-- Idempotent-ish: uses IF NOT EXISTS throughout.

CREATE TABLE IF NOT EXISTS families (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(64)  NOT NULL,
  passphrase_hash VARCHAR(255) NOT NULL,
  invite_token CHAR(32)     NOT NULL UNIQUE,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_family_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS members (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id   INT UNSIGNED NOT NULL,
  name        VARCHAR(64)  NOT NULL,
  phone       VARCHAR(32)  NULL,
  safe_word_hash  VARCHAR(255) NULL, -- password_hash() of safe word
  safe_word_hint VARCHAR(191) NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_members_family (family_id),
  CONSTRAINT fk_members_family FOREIGN KEY (family_id)
    REFERENCES families(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS challenges (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id   INT UNSIGNED NOT NULL,
  question    VARCHAR(191) NOT NULL,
  answer_hash VARCHAR(255) NOT NULL, -- password_hash() of normalized answer
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_challenges_member (member_id),
  CONSTRAINT fk_challenges_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS drills (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id   INT UNSIGNED NOT NULL,
  passed      TINYINT(1) NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY ix_drills_member (member_id),
  CONSTRAINT fk_drills_member FOREIGN KEY (member_id)
    REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
