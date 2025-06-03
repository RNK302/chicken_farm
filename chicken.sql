CREATE DATABASE chicken_farm;
USE chicken_farm;
-- Table for batches
CREATE TABLE chicken_batches (
    batch_no VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    status ENUM('incomplete', 'complete') NOT NULL,
    PRIMARY KEY (batch_no, year, month)
);

-- Table for daily chicken data
CREATE TABLE chicken_data (
    batch_no VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    day INT NOT NULL,
    death_in_day INT DEFAULT 0,
    alive_count INT DEFAULT 0,
    feed_taken INT DEFAULT 0,
    PRIMARY KEY (batch_no, year, month, day),
    CONSTRAINT fk_batch FOREIGN KEY (batch_no, year, month)
        REFERENCES chicken_batches(batch_no, year, month)
        ON DELETE CASCADE
);
