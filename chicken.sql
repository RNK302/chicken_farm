CREATE DATABASE chicken_farm;
USE chicken_farm;
CREATE TABLE chicken_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    month INT NOT NULL,
    day INT NOT NULL,
    batch_no VARCHAR(50) NOT NULL,
    death_in_day INT DEFAULT 0,
    feed_taken INT DEFAULT 0,
    UNIQUE KEY (year, month, day, batch_no)
);
