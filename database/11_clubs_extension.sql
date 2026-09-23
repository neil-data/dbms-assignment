-- ============================================================================
-- CEMS: College Event & Registration Management System
-- Script 11: Clubs & Community Additive Extension
-- Introduces Normalized Club Management & Student Membership Architecture
-- ============================================================================

USE cems_db;

-- 1. Create CLUB Table
CREATE TABLE IF NOT EXISTS club (
    club_id INT AUTO_INCREMENT PRIMARY KEY,
    club_name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    tagline VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    mission TEXT NULL,
    cover_image VARCHAR(255) NULL,
    logo_icon VARCHAR(50) NOT NULL DEFAULT 'groups',
    coordinator_name VARCHAR(100) NOT NULL,
    coordinator_email VARCHAR(120) NOT NULL,
    meeting_schedule VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create CLUB_MEMBERSHIP Table (Resolves Student M:M Club)
CREATE TABLE IF NOT EXISTS club_membership (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    club_id INT NOT NULL,
    role ENUM('MEMBER', 'COORDINATOR', 'LEAD') NOT NULL DEFAULT 'MEMBER',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_student_club (student_id, club_id),
    CONSTRAINT fk_membership_student FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_club FOREIGN KEY (club_id) REFERENCES club(club_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add club_id Column to EVENT Table (Nullable Foreign Key)
SET @exist := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'cems_db' 
      AND TABLE_NAME = 'event' 
      AND COLUMN_NAME = 'club_id'
);

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE event ADD COLUMN club_id INT NULL AFTER venue_id, ADD CONSTRAINT fk_event_club FOREIGN KEY (club_id) REFERENCES club(club_id) ON DELETE SET NULL', 
    'SELECT "club_id column already exists"'
);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Seed Canonical Campus Clubs
INSERT INTO club (club_id, club_name, slug, category, tagline, description, mission, cover_image, logo_icon, coordinator_name, coordinator_email, meeting_schedule)
VALUES 
(
    1,
    'Tech Club',
    'tech-club',
    'Technology',
    'Build. Code. Create.',
    'The premier collegiate hub for software engineers, systems hackers, and autonomous computing enthusiasts. We host weekly hack nights, production code reviews, and deep-dive architectural workshops.',
    'Empower campus developers to transition from theoretical computer science to engineering production-grade software and distributed systems.',
    'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop',
    'code',
    'Aarav Sharma',
    'tech.club@college.edu',
    'Every Tuesday & Thursday, 6:00 PM at Alan Turing Lab'
),
(
    2,
    'Photography Club',
    'photography-club',
    'Arts',
    'Capture. Express. Inspire.',
    'A collective of visual storytellers, street photographers, and cinematic filmmakers documenting campus life and mastering advanced lighting, darkroom techniques, and digital post-processing.',
    'Foster visual literacy and artistic expression through authentic photographic storytelling and exhibitions.',
    'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=1200&auto=format&fit=crop',
    'photo_camera',
    'Diya Patel',
    'photo.club@college.edu',
    'Every Wednesday, 5:00 PM at Media Arts Studio'
),
(
    3,
    'Music Club',
    'music-club',
    'Cultural',
    'Play. Perform. Belong.',
    'From classical chamber orchestrations to indie rock ensembles and electronic music production, Music Club connects vocalists, instrumentalists, and sound designers across all faculties.',
    'Unite student musicians, cultivate collaborative improvisation, and stage unforgettable live campus performances.',
    'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=1200&auto=format&fit=crop',
    'music_note',
    'Rohan Verma',
    'music.society@college.edu',
    'Every Monday & Friday, 6:30 PM at Main Auditorium'
),
(
    4,
    'Sports Club',
    'sports-club',
    'Sports',
    'Play. Compete. Grow.',
    'Campus athletics league promoting physical fitness, high-stakes university tournaments, inter-college leagues, and mental resilience through team sports and individual excellence.',
    'Instill discipline, teamwork, and healthy athletic competition across the university community.',
    'https://images.unsplash.com/photo-1546519638-68e109498ffc?q=80&w=1200&auto=format&fit=crop',
    'sports_basketball',
    'Vikram Singh',
    'sports.council@college.edu',
    'Daily 6:00 AM & 5:30 PM at Campus Sports Complex'
),
(
    5,
    'Robotics & AI Club',
    'robotics-club',
    'Technology',
    'Autonomous Minds. Physical Systems.',
    'Hands-on engineering lab building competitive autonomous rovers, quadcopters, microcontroller firmware, and embedded edge AI computer vision platforms.',
    'Bridge hardware and artificial intelligence to solve physical automation challenges.',
    'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?q=80&w=1200&auto=format&fit=crop',
    'precision_manufacturing',
    'Siddharth Rao',
    'robotics.lead@college.edu',
    'Every Saturday, 10:00 AM at Mechatronics Wing'
),
(
    6,
    'Design & Media Guild',
    'design-guild',
    'Arts',
    'Crafting Interfaces & Visual Stories.',
    'Design collective dedicated to typography, UI/UX systems design, 3D generative art, brand identity design, and human-computer interaction critique.',
    'Elevate visual aesthetics and user experience design across collegiate technical projects.',
    'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?q=80&w=1200&auto=format&fit=crop',
    'palette',
    'Ananya Iyer',
    'design.guild@college.edu',
    'Every Thursday, 5:30 PM at Design Studio B'
),
(
    7,
    'E-Cell (Entrepreneurship)',
    'e-cell',
    'Entrepreneurship',
    'From Campus Idea to Series A.',
    'The student startup incubator providing seed venture mentorship, investor pitch simulations, legal guidance, and product-market fit workshops.',
    'Nurture student founders and turn collegiate innovations into viable enterprise ventures.',
    'https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=1200&auto=format&fit=crop',
    'rocket_launch',
    'Arjun Reddy',
    'ecell@college.edu',
    'Bi-weekly Saturdays, 2:00 PM at Innovation Complex'
),
(
    8,
    'Literary & Debating Society',
    'literary-society',
    'Academic',
    'Sharp Words. Critical Discourse.',
    'Parliamentary debate chamber and literary forum fostering critical thinking, policy forensics, essay writing, and public elocution.',
    'Cultivate analytical rhetoric, articulate advocacy, and insightful campus discourse.',
    'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?q=80&w=1200&auto=format&fit=crop',
    'menu_book',
    'Kavya Nair',
    'litdeb@college.edu',
    'Every Friday, 5:00 PM at Ramanujan Lecture Hall'
)
ON DUPLICATE KEY UPDATE 
    tagline = VALUES(tagline),
    description = VALUES(description),
    cover_image = VALUES(cover_image);

-- 5. Associate Existing Events with Respective Hosting Clubs
UPDATE event SET club_id = 1 WHERE event_id IN (1, 5, 8); -- Hackathon, Cloud DevOps, Cyber Threat -> Tech Club
UPDATE event SET club_id = 2 WHERE event_id = 6;            -- Photography / Green Building Showcase
UPDATE event SET club_id = 3 WHERE event_id = 2;            -- Cultural Symphony Gala -> Music Club
UPDATE event SET club_id = 4 WHERE event_id IN (4, 7);      -- Chess, Badminton -> Sports Club
UPDATE event SET club_id = 5 WHERE event_id = 3;            -- AI & Robotics Workshop -> Robotics Club

-- 6. Seed Realistic Initial Club Memberships
INSERT IGNORE INTO club_membership (student_id, club_id, role, joined_at)
VALUES
(1, 1, 'LEAD', '2026-08-01 10:00:00'),        -- Aarav Sharma in Tech Club
(1, 5, 'MEMBER', '2026-08-10 11:00:00'),      -- Aarav Sharma in Robotics
(2, 2, 'LEAD', '2026-08-05 12:00:00'),        -- Diya Patel in Photography
(2, 3, 'MEMBER', '2026-08-12 14:00:00'),      -- Diya Patel in Music Club
(3, 1, 'MEMBER', '2026-08-08 09:30:00'),      -- Rohan Verma in Tech Club
(3, 4, 'MEMBER', '2026-08-15 17:00:00'),      -- Rohan Verma in Sports Club
(4, 6, 'LEAD', '2026-08-06 14:20:00'),        -- Ananya Iyer in Design Guild
(5, 8, 'LEAD', '2026-08-10 16:45:00'),        -- Kavya Nair in Literary Society
(6, 5, 'COORDINATOR', '2026-08-12 12:00:00'), -- Siddharth Rao in Robotics
(7, 4, 'LEAD', '2026-08-15 15:10:00'),        -- Vikram Singh in Sports Club
(8, 2, 'MEMBER', '2026-08-18 10:40:00'),      -- Meera Deshmukh in Photography
(9, 7, 'LEAD', '2026-08-20 13:25:00');        -- Arjun Reddy in E-Cell
