-- Create ground_bookings table with merged queries
CREATE TABLE ground_bookings (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    players_count INTEGER NOT NULL,
    booking_slot TIMESTAMP NOT NULL,
    ground_type VARCHAR(50),
    group_type TEXT,
    gender VARCHAR(10) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create an index on email for faster lookup
CREATE INDEX idx_ground_bookings_email ON ground_bookings(email);

-- Create user_images table for managing user images with soft delete
CREATE TABLE user_images (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES ground_bookings(id),
    image_path VARCHAR(255) NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE, -- Soft delete flag
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create suggestions table for managing suggestions linked to user
CREATE TABLE suggestions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES ground_bookings(id),
    suggestion TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
