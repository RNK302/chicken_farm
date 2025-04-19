CREATE DATABASE chicken_farm;
USE chicken_farm;
CREATE TABLE chicken_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_no VARCHAR(50),
    year INT,
    month INT,
    status VARCHAR(20),
    UNIQUE KEY unique_batch (batch_no, year, month)
);

CREATE TABLE chicken_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_no VARCHAR(50),
    year INT,
    month INT,
    day INT,
    death_in_day INT,
    alive_count INT,
    feed_taken INT,
    UNIQUE KEY unique_day (batch_no, year, month, day)
);
