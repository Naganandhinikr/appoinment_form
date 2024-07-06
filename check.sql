CREATE TABLE form_submissions (
    firstname VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    location VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    slot VARCHAR(50) NOT NULL,
    looking_for VARCHAR(50) NOT NULL,
    image VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CREATE INDEX idx_users_date_slots ON form_submissions(date, slot);
