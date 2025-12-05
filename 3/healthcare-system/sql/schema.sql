CREATE DATABASE IF NOT EXISTS healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthcare_db;

-- Drop old/unused tables first
DROP TABLE IF EXISTS records;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS doctors;

-- Drop current tables (will be recreated)
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id VARCHAR(36) PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
  id VARCHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content LONGTEXT NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  published_at DATETIME DEFAULT NULL,
  archived_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slug (slug),
  INDEX idx_status (status),
  INDEX idx_published_at (published_at),
  INDEX idx_archived_at (archived_at),
  INDEX idx_status_published (status, published_at),
  INDEX idx_status_archived (status, archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
  id VARCHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  department VARCHAR(120) DEFAULT NULL,
  location VARCHAR(120) DEFAULT NULL,
  employment_type VARCHAR(50) DEFAULT NULL,
  summary TEXT DEFAULT NULL,
  description LONGTEXT DEFAULT NULL,
  status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
  posted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_posted_at (posted_at),
  INDEX idx_status_posted (status, posted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, username, password, role) VALUES
(CONCAT('usr_', SUBSTRING(MD5(CONCAT(RAND(), NOW())), 1, 28)), 'admin', '$2y$10$9.KnoU5QSJ3wPz92ekkbfutzb.Xyh3cY1WELGC3uYPkAKnb9LTbqm', 'admin'),
(CONCAT('usr_', SUBSTRING(MD5(CONCAT(RAND(), NOW())), 1, 28)), 'editor', '$2y$10$Rh4wikGAJ4mJv1p3Vl.gIOW4TNLyF1w2nycVHEQbstyOlYIqUdOZG', 'staff');

INSERT INTO posts (id, title, slug, content, status, published_at) VALUES
(
  CONCAT('post_', SUBSTRING(MD5(CONCAT(RAND(), NOW())), 1, 28)),
  'Welcome to Healthcare Center',
  'welcome-to-healthcare-center',
  '<p>Healthcare Center is now a fully digital-first experience sharing health insights, wellness tips, and hospital updates. Explore our stories to stay informed.</p>',
  'published',
  NOW()
),
(
  CONCAT('post_', SUBSTRING(MD5(CONCAT(RAND(), NOW())), 1, 28)),
  'Latest Technology Spotlight',
  'latest-technology-spotlight',
  '<p>Our team continuously invests in modern equipment to deliver world-class care. Discover how innovation helps improve every patient journey.</p>',
  'published',
  NOW()
);

INSERT INTO jobs (id, title, department, location, employment_type, summary, description, status, posted_at) VALUES
(
  CONCAT('job_', SUBSTRING(MD5(CONCAT(RAND(), NOW())), 1, 28)),
  'Registered Nurse - Emergency Department',
  'Emergency Medicine',
  'Manila, Philippines',
  'Full-time',
  'Join our emergency department team providing critical care to patients in need. We seek experienced RNs with strong clinical skills and a passion for emergency medicine.',
  '<p>We are looking for a dedicated Registered Nurse to join our Emergency Department team. The ideal candidate will have:</p><ul><li>Bachelor of Science in Nursing (BSN) degree</li><li>Valid Philippine Professional Regulation Commission (PRC) license</li><li>Minimum 2 years of emergency department experience</li><li>BLS and ACLS certifications</li><li>Strong critical thinking and decision-making skills</li></ul><p>Responsibilities include triage, patient assessment, medication administration, and collaboration with multidisciplinary teams.</p>',
  'open',
  NOW()
),
(
  CONCAT('job_', SUBSTRING(MD5(CONCAT(RAND(), NOW())), 1, 28)),
  'Medical Laboratory Technologist',
  'Laboratory Services',
  'Manila, Philippines',
  'Full-time',
  'Perform diagnostic testing and analysis in our state-of-the-art laboratory facility. Ideal for detail-oriented professionals with strong analytical skills.',
  '<p>We are seeking a qualified Medical Laboratory Technologist to join our Laboratory Services team. Requirements:</p><ul><li>Bachelor\'s degree in Medical Technology or related field</li><li>Valid PRC license as Medical Technologist</li><li>Experience with automated analyzers and quality control procedures</li><li>Knowledge of laboratory safety protocols</li></ul>',
  'open',
  NOW()
);

