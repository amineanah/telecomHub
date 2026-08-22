-- ============================================================
-- TELECOMHUB DATABASE SCHEMA
-- Engine: MySQL 8.0+ (InnoDB, utf8mb4)
-- ============================================================

CREATE DATABASE IF NOT EXISTS telecomhub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE telecomhub;

-- ============================================================
-- USERS  (admins, editors, and newsletter/site subscribers who log in)
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,
    role            ENUM('admin','editor','member') NOT NULL DEFAULT 'member',
    avatar_url      VARCHAR(500)        NULL,
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES  (5G/4G, RAN, Fiber Optics, Transmission, IP & Core, etc.)
-- ============================================================
CREATE TABLE categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL,
    slug            VARCHAR(120)        NOT NULL UNIQUE,
    icon            VARCHAR(10)         NULL,          -- emoji shown on homepage (📶, 📡, ...)
    description     VARCHAR(255)        NULL
) ENGINE=InnoDB;

-- ============================================================
-- ARTICLES
-- ============================================================
CREATE TABLE articles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)        NOT NULL,
    slug            VARCHAR(280)        NOT NULL UNIQUE,
    excerpt         VARCHAR(500)        NULL,
    content          MEDIUMTEXT          NOT NULL,
    image_url       VARCHAR(500)        NULL,
    category_id     INT UNSIGNED        NULL,
    author_id       INT UNSIGNED        NULL,
    read_time_min   TINYINT UNSIGNED    NULL,
    is_featured     TINYINT(1)          NOT NULL DEFAULT 0,
    status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    views           INT UNSIGNED        NOT NULL DEFAULT 0,
    published_at    DATETIME            NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_articles_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_articles_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_articles_status_published (status, published_at),
    INDEX idx_articles_category (category_id)
) ENGINE=InnoDB;

-- ============================================================
-- TAGS + ARTICLE_TAGS (many-to-many, optional but handy for search/filtering)
-- ============================================================
CREATE TABLE tags (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(60)         NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE article_tags (
    article_id      INT UNSIGNED        NOT NULL,
    tag_id          INT UNSIGNED        NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    CONSTRAINT fk_at_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_at_tag     FOREIGN KEY (tag_id)     REFERENCES tags(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- COMPANIES  (employers posting jobs)
-- ============================================================
CREATE TABLE companies (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)        NOT NULL,
    logo_url        VARCHAR(500)        NULL,
    website         VARCHAR(255)        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- JOBS
-- ============================================================
CREATE TABLE jobs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200)        NOT NULL,
    company_id      INT UNSIGNED        NOT NULL,
    location        VARCHAR(150)        NOT NULL,
    job_type        ENUM('full_time','part_time','contract','internship') NOT NULL DEFAULT 'full_time',
    category_id     INT UNSIGNED        NULL,          -- e.g. RAN, Fiber Optics
    description     TEXT                NOT NULL,
    apply_url       VARCHAR(500)        NULL,
    status          ENUM('open','closed') NOT NULL DEFAULT 'open',
    posted_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME            NULL,

    CONSTRAINT fk_jobs_company
        FOREIGN KEY (company_id) REFERENCES companies(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_jobs_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL,

    INDEX idx_jobs_status_posted (status, posted_at)
) ENGINE=InnoDB;

-- ============================================================
-- NEWSLETTER SUBSCRIBERS
-- ============================================================
CREATE TABLE newsletter_subscribers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    subscribed_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME            NULL
) ENGINE=InnoDB;

-- ============================================================
-- SITE SETTINGS (homepage content and media selected in admin)
-- ============================================================
CREATE TABLE site_settings (
    setting_key     VARCHAR(100)        PRIMARY KEY,
    setting_value   TEXT                NULL,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- FEEDBACK & COMMENTS (visitor feedback and comments)
-- ============================================================
CREATE TABLE feedback (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL,
    message         TEXT                NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_feedback_status (status),
    INDEX idx_feedback_created (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA (matches the categories already shown on the homepage)
-- ============================================================
INSERT INTO categories (name, slug, icon) VALUES
    ('5G / 4G / 3G',    '5g-4g-3g',       '📶'),
    ('RAN',             'ran',            '📡'),
    ('Fiber Optics',    'fiber-optics',   '🔌'),
    ('Transmission',    'transmission',   '🛰️'),
    ('IP & Core',       'ip-core',        '🖥️'),
    ('Power & Energy',  'power-energy',   '⚡'),
    ('EHS',             'ehs',            '🦺'),
    ('IoT',             'iot',            '🌐');
