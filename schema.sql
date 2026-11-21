-- CampusHub database
-- Import this file in phpMyAdmin (Import tab) or run: mysql -u root < schema.sql

DROP DATABASE IF EXISTS campushub;
CREATE DATABASE campushub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campushub;

-- ---------- TABLES ----------

CREATE TABLE colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    city VARCHAR(80) NOT NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    college_id INT NOT NULL,
    course VARCHAR(80) NOT NULL,
    year TINYINT NOT NULL,
    bio VARCHAR(300) DEFAULT '',
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id)
);

CREATE TABLE communities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(300) NOT NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id)
);

CREATE TABLE community_members (
    user_id INT NOT NULL,
    community_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, community_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    community_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    tag ENUM('Academics','Doubt','Project','General') NOT NULL DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    INDEX idx_posts_created (created_at),
    INDEX idx_posts_community (community_id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    body VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_comments_post (post_id)
);

CREATE TABLE votes (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    value TINYINT NOT NULL,  -- 1 = upvote, -1 = downvote
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE TABLE resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    semester TINYINT NOT NULL,
    college_id INT NOT NULL,
    type ENUM('Notes','PYQ','Book','Other') NOT NULL DEFAULT 'Notes',
    file_name VARCHAR(255) NULL,       -- random name stored in uploads/
    original_name VARCHAR(255) NULL,   -- name shown to the user
    downloads INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(id),
    INDEX idx_resources_subject (subject)
);

CREATE TABLE opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category ENUM('Internship','Hackathon','Scholarship','Event','Placement') NOT NULL,
    organization VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL,
    deadline DATE NOT NULL,
    link VARCHAR(255) NOT NULL,
    status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------- SEED DATA ----------

INSERT INTO colleges (name, city) VALUES
('Presidency University', 'Bengaluru'),
('IIT Delhi', 'New Delhi'),
('VIT Vellore', 'Vellore'),
('BITS Pilani', 'Pilani'),
('NIT Trichy', 'Tiruchirappalli'),
('Delhi University', 'New Delhi'),
('Manipal Institute of Technology', 'Manipal'),
('SRM Institute of Science and Technology', 'Chennai');

INSERT INTO communities (college_id, name, description) VALUES
(1, 'Presidency University', 'Everything for Presidency students: notes, doubts and campus life.'),
(2, 'IIT Delhi', 'Discussions, research chats and events from IIT Delhi.'),
(3, 'VIT Vellore', 'VITians sharing notes, FFCS tips and project ideas.'),
(4, 'BITS Pilani', 'BITSians talking academics, clubs and internships.'),
(5, 'NIT Trichy', 'NITT community for study help and placements.'),
(6, 'Delhi University', 'DU students: notes, society news and results.'),
(7, 'Manipal Institute of Technology', 'MIT Manipal students, one place for everything.'),
(8, 'SRM Institute of Science and Technology', 'SRM students sharing resources and hackathon news.');

-- Test accounts: admin@campushub.com / admin123   and   all others / user123
INSERT INTO users (name, email, password, college_id, course, year, bio, role) VALUES
('Admin', 'admin@campushub.com', '$2y$10$vGa7VY1XEzqIQ2MAgbspwe82WJ0UhpTnmK1TlK/eqWu8ekIIr0qm.', 1, 'B.Tech CSE', 3, 'CampusHub administrator.', 'admin'),
('Rahul Sharma', 'rahul@example.com', '$2y$10$iKGl9nQ98BKvgnvb0LBrMeYDlO2DK6wAuxNof80RJ/4FCyZCu49cq', 1, 'B.Tech CSE', 2, 'Web dev beginner. Loves cricket and chai.', 'user'),
('Ananya Iyer', 'ananya@example.com', '$2y$10$iKGl9nQ98BKvgnvb0LBrMeYDlO2DK6wAuxNof80RJ/4FCyZCu49cq', 3, 'B.Tech IT', 3, 'Interested in AI and open source.', 'user'),
('Mohit Verma', 'mohit@example.com', '$2y$10$iKGl9nQ98BKvgnvb0LBrMeYDlO2DK6wAuxNof80RJ/4FCyZCu49cq', 2, 'B.Tech EE', 4, 'Preparing for placements.', 'user'),
('Sneha Reddy', 'sneha@example.com', '$2y$10$iKGl9nQ98BKvgnvb0LBrMeYDlO2DK6wAuxNof80RJ/4FCyZCu49cq', 4, 'B.E. Mechanical', 2, 'Robotics club member.', 'user');

INSERT INTO community_members (user_id, community_id) VALUES
(1,1),(2,1),(2,3),(3,3),(3,2),(4,2),(4,4),(5,4),(5,1);

INSERT INTO posts (user_id, community_id, title, body, tag, created_at) VALUES
(2, 1, 'How do you prepare for Web Technology exams?', 'I am in 2nd year and finding DOM and JavaScript functions confusing. Any tips or good practice resources?', 'Doubt', NOW() - INTERVAL 2 DAY),
(3, 3, 'FFCS slot selection tips for next semester', 'Sharing my approach: pick the faculty first, then fix the slots. Avoid early morning clashes if you can.', 'Academics', NOW() - INTERVAL 3 DAY),
(4, 2, 'Placement prep roadmap for 4th year', 'Start with aptitude, then DSA on a platform of your choice, and do 2 mock interviews every week.', 'General', NOW() - INTERVAL 1 DAY),
(5, 4, 'Looking for teammates for a robotics project', 'We want to build a line-following robot. Need one person who knows Arduino and one for CAD.', 'Project', NOW() - INTERVAL 4 DAY),
(2, 1, 'Difference between let, const and var?', 'Can someone explain with a simple example when to use each one in JavaScript?', 'Doubt', NOW() - INTERVAL 5 HOUR),
(3, 3, 'Best free resources to learn Python', 'I found these helpful: official docs, a YouTube playlist and solving small problems daily.', 'Academics', NOW() - INTERVAL 6 DAY),
(4, 2, 'Anyone attending the coding club meetup?', 'It is this Saturday in the main auditorium. Free snacks!', 'General', NOW() - INTERVAL 8 HOUR),
(5, 4, 'Data Structures notes for Unit 3 (Trees)', 'I uploaded my handwritten notes in the Resources section. Hope it helps.', 'Academics', NOW() - INTERVAL 2 DAY),
(1, 1, 'Welcome to CampusHub!', 'This is a place for students to discuss, share notes and find opportunities. Be respectful and keep it useful.', 'General', NOW() - INTERVAL 7 DAY),
(2, 5, 'Which language should I learn first for web dev?', 'Everyone says JavaScript, but some say PHP is easier for backend. Opinions?', 'Doubt', NOW() - INTERVAL 1 DAY),
(3, 6, 'How to find previous year papers for DU?', 'Are there any official sources or should we rely on seniors?', 'Doubt', NOW() - INTERVAL 3 DAY),
(4, 7, 'Internship experience at a startup', 'Did a 2-month remote internship. Learned more than in two semesters. Happy to answer questions.', 'General', NOW() - INTERVAL 4 DAY),
(5, 8, 'Hackathon team needed for Smart India Hackathon', 'Need a frontend dev and a UI designer. DM me if interested.', 'Project', NOW() - INTERVAL 10 HOUR),
(2, 3, 'Is the MySQL join concept confusing for you too?', 'INNER vs LEFT join always trips me up. Drawing Venn diagrams helped a bit.', 'Academics', NOW() - INTERVAL 12 HOUR),
(3, 1, 'Mini project ideas for DBMS course', 'Library management, hostel allotment, student attendance. What else would be fun?', 'Project', NOW() - INTERVAL 2 HOUR);

INSERT INTO comments (post_id, user_id, body, created_at) VALUES
(1, 3, 'Practice small DOM tasks daily. Build a to-do list, it covers most things.', NOW() - INTERVAL 1 DAY),
(1, 4, 'Past papers helped me a lot. Solve at least 3 years.', NOW() - INTERVAL 20 HOUR),
(5, 3, 'Use const by default, let when the value changes, avoid var.', NOW() - INTERVAL 3 HOUR),
(3, 2, 'Thanks, this roadmap is really helpful!', NOW() - INTERVAL 10 HOUR),
(4, 4, 'I know some Arduino, sending you a message.', NOW() - INTERVAL 3 DAY),
(10, 3, 'Start with JavaScript, then add PHP when you need a backend.', NOW() - INTERVAL 20 HOUR),
(13, 2, 'Good luck! I can help with the frontend.', NOW() - INTERVAL 5 HOUR);

INSERT INTO votes (user_id, post_id, value) VALUES
(2,1,1),(3,1,1),(4,1,1),(5,1,1),
(2,2,1),(4,2,1),
(2,3,1),(3,3,1),(5,3,1),
(3,4,1),(4,4,1),
(3,5,1),
(2,6,1),(4,6,1),(5,6,1),
(2,7,1),
(2,8,1),(3,8,1),(4,8,1),
(2,9,1),(3,9,1),(4,9,1),(5,9,1),
(3,10,1),(4,10,-1),
(2,11,1),
(2,12,1),(3,12,1),
(2,13,1),
(3,14,1),
(2,15,1);

-- Seed resources have no real file, so the download page shows a "demo data" message.
INSERT INTO resources (user_id, title, subject, semester, college_id, type, downloads, created_at) VALUES
(5, 'Data Structures Unit 3 - Trees (handwritten)', 'Data Structures', 3, 4, 'Notes', 41, NOW() - INTERVAL 2 DAY),
(2, 'Web Technology Module 1 Summary', 'Web Technology', 3, 1, 'Notes', 27, NOW() - INTERVAL 5 DAY),
(3, 'DBMS Previous Year Paper 2025', 'DBMS', 4, 3, 'PYQ', 63, NOW() - INTERVAL 6 DAY),
(4, 'Engineering Mathematics II PYQ', 'Mathematics', 2, 2, 'PYQ', 88, NOW() - INTERVAL 9 DAY),
(3, 'Operating Systems Quick Revision', 'Operating Systems', 4, 3, 'Notes', 35, NOW() - INTERVAL 4 DAY),
(2, 'Computer Networks Routing Notes', 'Computer Networks', 5, 1, 'Notes', 19, NOW() - INTERVAL 1 DAY),
(5, 'Digital Electronics Notes', 'Digital Electronics', 3, 4, 'Notes', 22, NOW() - INTERVAL 7 DAY),
(4, 'Python Programming Reference Book', 'Python', 1, 2, 'Book', 54, NOW() - INTERVAL 10 DAY),
(3, 'Machine Learning Previous Year Paper', 'Machine Learning', 6, 3, 'PYQ', 31, NOW() - INTERVAL 3 DAY),
(2, 'JavaScript Cheat Sheet', 'Web Technology', 3, 1, 'Other', 76, NOW() - INTERVAL 2 DAY);

INSERT INTO opportunities (title, category, organization, description, deadline, link, status, created_by) VALUES
('Summer Internship 2027', 'Internship', 'Google', 'Software engineering internship for pre-final year students. Apply early.', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'https://careers.google.com/', 'approved', 1),
('Smart India Hackathon 2027', 'Hackathon', 'Government of India', 'Nationwide hackathon to solve real problem statements from ministries.', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'https://www.sih.gov.in/', 'approved', 1),
('National Scholarship Portal', 'Scholarship', 'Government of India', 'Central and state scholarships for eligible students across India.', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'https://scholarships.gov.in/', 'approved', 1),
('TechFest Coding Marathon', 'Event', 'IIT Bombay', '24-hour coding competition open to all colleges.', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'https://www.techfest.org/', 'approved', 1),
('Campus Placement Drive', 'Placement', 'Infosys', 'Mass recruitment drive for 2027 graduates. Register on the portal.', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'https://www.infosys.com/careers/', 'approved', 1),
('Microsoft Learn Student Ambassadors', 'Event', 'Microsoft', 'Join the student community, get mentorship and free resources.', DATE_ADD(CURDATE(), INTERVAL 50 DAY), 'https://studentambassadors.microsoft.com/', 'approved', 1),
('Web Development Internship', 'Internship', 'Internshala', 'Remote 3-month internship for HTML, CSS and JavaScript learners.', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'https://internshala.com/', 'approved', 1),
('Inspire Scholarship (SHE)', 'Scholarship', 'DST India', 'Scholarship for higher education in basic and natural sciences.', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'https://online-inspire.gov.in/', 'approved', 1),
('Hack the Mountain', 'Hackathon', 'Community', 'Weekend hackathon with prizes. Last date already passed (sample of expired badge).', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'https://devfolio.co/', 'approved', 1),
('Open Source Winter Program', 'Internship', 'GirlScript', 'Contribute to open source projects and get certificates.', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'https://www.gssoc.girlscript.tech/', 'approved', 1);
