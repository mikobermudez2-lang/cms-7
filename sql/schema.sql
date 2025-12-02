-- Healthcare CMS - Database Schema
-- Run this to set up the database from scratch

CREATE DATABASE IF NOT EXISTS healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthcare_db;

-- ============================================
-- DROP TABLES (Clean install)
-- ============================================
DROP TABLE IF EXISTS post_tags;
DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
  id VARCHAR(36) PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  display_name VARCHAR(100) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME DEFAULT NULL,
  password_changed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CATEGORIES TABLE
-- ============================================
CREATE TABLE categories (
  id VARCHAR(36) PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  name_ph VARCHAR(100) DEFAULT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  description_ph TEXT DEFAULT NULL,
  color VARCHAR(7) DEFAULT '#2563EB',
  icon VARCHAR(50) DEFAULT 'bi-folder',
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_slug (slug),
  INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- POSTS TABLE
-- ============================================
CREATE TABLE posts (
  id VARCHAR(36) PRIMARY KEY,
  author_id VARCHAR(36) DEFAULT NULL,
  category_id VARCHAR(36) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  title_ph VARCHAR(255) DEFAULT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content LONGTEXT NOT NULL,
  content_ph LONGTEXT DEFAULT NULL,
  status ENUM('draft', 'scheduled', 'published') NOT NULL DEFAULT 'draft',
  scheduled_at DATETIME DEFAULT NULL,
  published_at DATETIME DEFAULT NULL,
  archived_at DATETIME DEFAULT NULL,
  view_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slug (slug),
  INDEX idx_status (status),
  INDEX idx_author_id (author_id),
  INDEX idx_category_id (category_id),
  INDEX idx_published_at (published_at),
  INDEX idx_archived_at (archived_at),
  FULLTEXT idx_search (title, content),
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- JOBS TABLE
-- ============================================
CREATE TABLE jobs (
  id VARCHAR(36) PRIMARY KEY,
  external_id VARCHAR(255) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  title_ph VARCHAR(255) DEFAULT NULL,
  department VARCHAR(120) DEFAULT NULL,
  location VARCHAR(120) DEFAULT NULL,
  employment_type VARCHAR(50) DEFAULT NULL,
  salary_range VARCHAR(100) DEFAULT NULL,
  summary TEXT DEFAULT NULL,
  summary_ph TEXT DEFAULT NULL,
  description LONGTEXT DEFAULT NULL,
  description_ph LONGTEXT DEFAULT NULL,
  image_url VARCHAR(500) DEFAULT NULL,
  status ENUM('draft', 'open', 'closed') NOT NULL DEFAULT 'draft',
  posted_at DATETIME DEFAULT NULL,
  closes_at DATETIME DEFAULT NULL,
  view_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_posted_at (posted_at),
  INDEX idx_external_id (external_id),
  FULLTEXT idx_search (title, summary, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOGIN ATTEMPTS (Rate Limiting)
-- ============================================
CREATE TABLE login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  username VARCHAR(50) DEFAULT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  INDEX idx_ip_address (ip_address),
  INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ACTIVITY LOGS
-- ============================================
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(36) DEFAULT NULL,
  action VARCHAR(50) NOT NULL,
  entity_type VARCHAR(50) DEFAULT NULL,
  entity_id VARCHAR(36) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_action (action),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SITE SETTINGS
-- ============================================
CREATE TABLE site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Default admin user
-- Username: admin
-- Email: admin@healthcare.local
-- Password: admin12345
INSERT INTO users (id, username, email, password, role, display_name) VALUES
('usr_admin_00000000000000000001', 'admin', 'admin@healthcare.local', '$2y$12$UNLLe74J80gjjw3/U.C/su/6jYLodon7ifagJvAClltSJX35DGeSK', 'admin', 'Administrator');

-- Default categories
INSERT INTO categories (id, name, name_ph, slug, description, description_ph, color, icon, sort_order) VALUES
('cat_health_tips_00000000000001', 'Health Tips', 'Mga Tip sa Kalusugan', 'health-tips', 'Tips for maintaining good health', 'Mga tip para sa malusog na pamumuhay', '#10B981', 'bi-heart-pulse', 1),
('cat_hospital_news_0000000000001', 'Hospital News', 'Balita ng Ospital', 'hospital-news', 'Latest news and updates from our hospital', 'Pinakabagong balita mula sa aming ospital', '#2563EB', 'bi-newspaper', 2),
('cat_medical_advances_000000001', 'Medical Advances', 'Mga Pagsulong sa Medisina', 'medical-advances', 'New developments in medical science', 'Mga bagong pag-unlad sa agham medikal', '#8B5CF6', 'bi-capsule', 3),
('cat_community_000000000000001', 'Community', 'Komunidad', 'community', 'Community programs and outreach', 'Mga programa at outreach sa komunidad', '#F59E0B', 'bi-people', 4),
('cat_wellness_0000000000000001', 'Wellness', 'Kagalingan', 'wellness', 'Mental health and wellness tips', 'Kalusugan ng isip at kagalingan', '#EC4899', 'bi-emoji-smile', 5),
('cat_other_00000000000000001', 'Other', 'Iba Pa', 'other', 'Miscellaneous posts', 'Iba pang mga post', '#6B7280', 'bi-three-dots', 99);

-- Default posts
INSERT INTO posts (id, author_id, category_id, title, title_ph, slug, content, content_ph, status, published_at, view_count) VALUES
(
  'post_welcome_0000000000000001',
  'usr_admin_00000000000000000001',
  'cat_hospital_news_0000000000001',
  'Welcome to Healthcare Center',
  'Maligayang Pagdating sa Healthcare Center',
  'welcome-to-healthcare-center',
  '<p>Healthcare Center is now a fully digital-first experience sharing health insights, wellness tips, and hospital updates. Explore our stories to stay informed.</p><p>We are committed to providing you with the latest information about healthcare services, medical breakthroughs, and community health programs.</p>',
  '<p>Ang Healthcare Center ay ngayon ay isang digital-first na karanasan na nagbabahagi ng mga health insights, wellness tips, at hospital updates. Mag-explore ng aming mga kwento para manatiling updated.</p>',
  'published',
  NOW(),
  150
),
(
  'post_technology_000000000000001',
  'usr_admin_00000000000000000001',
  'cat_medical_advances_000000001',
  'Latest Technology Spotlight',
  'Spotlight sa Pinakabagong Teknolohiya',
  'latest-technology-spotlight',
  '<p>Our team continuously invests in modern equipment to deliver world-class care. Discover how innovation helps improve every patient journey.</p><p>From advanced imaging systems to robotic-assisted surgery, we are at the forefront of medical technology.</p>',
  '<p>Ang aming team ay patuloy na nag-iinvest sa modernong kagamitan para maghatid ng world-class na pangangalaga. Alamin kung paano nakakatulong ang inobasyon sa bawat patient journey.</p>',
  'published',
  NOW(),
  89
);

-- Default jobs
INSERT INTO jobs (id, title, title_ph, department, location, employment_type, salary_range, summary, summary_ph, description, description_ph, status, posted_at) VALUES
(
  'job_nurse_emergency_0000000001',
  'Registered Nurse - Emergency Department',
  'Rehistradong Nars - Emergency Department',
  'Emergency Medicine',
  'Manila, Philippines',
  'Full-time',
  '₱35,000 - ₱50,000',
  'Join our emergency department team providing critical care to patients in need.',
  'Sumali sa aming emergency department team na nagbibigay ng critical care sa mga pasyenteng nangangailangan.',
  '<p>We are looking for a dedicated Registered Nurse to join our Emergency Department team.</p><ul><li>Bachelor of Science in Nursing (BSN) degree</li><li>Valid PRC license</li><li>Minimum 2 years of emergency department experience</li><li>BLS and ACLS certifications</li></ul>',
  '<p>Naghahanap kami ng dedikadong Rehistradong Nars para sumali sa aming Emergency Department team.</p>',
  'open',
  NOW()
);

-- Default site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Healthcare Center'),
('site_name_ph', 'Healthcare Center'),
('default_language', 'en'),
('posts_per_page', '10');
