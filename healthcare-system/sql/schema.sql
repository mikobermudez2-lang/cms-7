CREATE DATABASE IF NOT EXISTS healthcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthcare_db;

DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS records;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'staff', 'doctor', 'patient') NOT NULL
);

CREATE TABLE doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  specialty VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL
);

CREATE TABLE patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  age INT NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL
);

CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  date DATE NULL,
  time TIME NULL,
  status ENUM('Waiting', 'Confirmed', 'Completed', 'Rejected', 'Cancelled') DEFAULT 'Waiting',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

CREATE TABLE records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  diagnosis TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role) VALUES
('admin', 'admin123', 'admin'),
('staff1', 'staff123', 'staff'),
('doctor1', 'doctor123', 'doctor');

INSERT INTO doctors (name, specialty, email, phone) VALUES
('Dr. John Rivera', 'Cardiology', 'doctor1@healthcare.com', '+1 (555) 101-2020'),
('Dr. Melissa Chan', 'Pediatrics', 'melissa.chan@healthcare.com', '+1 (555) 222-3333');

INSERT INTO patients (name, age, email, phone) VALUES
('Maria Delgado', 34, 'maria.delgado@example.com', '+1 (555) 444-1234');

INSERT INTO appointments (patient_id, doctor_id, date, time, status) VALUES
(1, 1, CURDATE(), '09:00:00', 'Waiting');

INSERT INTO announcements (message) VALUES
('COVID-19 booster shots are available every Friday.'),
('New MRI machine is now operational.');


