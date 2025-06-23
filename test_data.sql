USE greyshot;

-- Test Users (password hash for 'password' using PASSWORD_DEFAULT)
INSERT INTO users (user_id, username, password_hash, icon_seed) VALUES
('u1', 'MysticWave123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u2', 'CosmicStar456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u3', 'SilentMoon789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u4', 'GentleWind234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u5', 'WildRiver567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u6', 'SereneForest890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u7', 'BrightOcean123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u8', 'HiddenStorm456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u9', 'NobleLight789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND())),
('u10', 'BraveMountain012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', MD5(RAND()));

-- Test Posts
INSERT INTO posts (post_id, user_id, content, created_at) VALUES
('p1', 'u1', 'Sometimes I wonder if my dreams are too big, but then I remember that''s exactly what makes them worth chasing.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('p2', 'u2', 'Today I helped a stranger with their groceries. Their smile made my entire week.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('p3', 'u3', 'I''ve been pretending to like my job for years. Today I finally admitted to myself it''s time for a change.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('p4', 'u4', 'The hardest part about growing up is realizing your parents are human too, with their own struggles and fears.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('p5', 'u5', 'I secretly love rainy days more than sunny ones. The sound of rain helps me feel less alone.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('p6', 'u6', 'Sometimes I feel like I''m not doing enough, but today I realized that simply trying my best is enough.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
('p7', 'u7', 'I''ve been learning to cook, and even though I''m terrible at it, it brings me joy to create something.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('p8', 'u8', 'The older I get, the more I appreciate the simple moments of silence and peace.', DATE_SUB(NOW(), INTERVAL 8 DAY)),
('p9', 'u9', 'Today I finally forgave someone who hurt me years ago. I didn''t do it for them - I did it for myself.', DATE_SUB(NOW(), INTERVAL 9 DAY)),
('p10', 'u10', 'I''m scared of failing, but I''m more scared of never trying.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('p11', 'u1', 'Sometimes the bravest thing we can do is admit we''re not okay and ask for help.', DATE_SUB(NOW(), INTERVAL 11 DAY)),
('p12', 'u2', 'I miss the person I used to be, but I''m learning to love who I''m becoming.', DATE_SUB(NOW(), INTERVAL 12 DAY)),
('p13', 'u3', 'Today I chose happiness over being right, and it felt liberating.', DATE_SUB(NOW(), INTERVAL 13 DAY)),
('p14', 'u4', 'The more I learn, the more I realize how much I don''t know.', DATE_SUB(NOW(), INTERVAL 14 DAY)),
('p15', 'u5', 'I''ve been practicing gratitude, and it''s slowly changing how I see the world.', DATE_SUB(NOW(), INTERVAL 15 DAY)),
('p16', 'u6', 'Sometimes the smallest acts of kindness leave the biggest impressions.', DATE_SUB(NOW(), INTERVAL 16 DAY)),
('p17', 'u7', 'I''m learning that it''s okay to outgrow people and situations that no longer help me grow.', DATE_SUB(NOW(), INTERVAL 17 DAY)),
('p18', 'u8', 'Today I realized that my biggest critic has always been myself.', DATE_SUB(NOW(), INTERVAL 18 DAY)),
('p19', 'u9', 'The hardest battles we fight are often the ones no one sees.', DATE_SUB(NOW(), INTERVAL 19 DAY)),
('p20', 'u10', 'I''m starting to understand that self-care isn''t selfish - it''s necessary.', DATE_SUB(NOW(), INTERVAL 20 DAY));

-- Add some upvotes
INSERT INTO upvotes (post_id, user_id) VALUES
('p1', 'u2'), ('p1', 'u3'), ('p1', 'u4'),
('p2', 'u1'), ('p2', 'u3'), ('p2', 'u5'),
('p3', 'u1'), ('p3', 'u2'), ('p3', 'u4'),
('p4', 'u5'), ('p4', 'u6'), ('p4', 'u7'),
('p5', 'u8'), ('p5', 'u9'), ('p5', 'u10');

-- Add some reactions
INSERT INTO reactions (post_id, user_id, reaction_type) VALUES
('p1', 'u5', 'relate'), ('p1', 'u6', 'needed'), ('p1', 'u7', 'thanks'),
('p2', 'u6', 'relate'), ('p2', 'u7', 'needed'), ('p2', 'u8', 'thanks'),
('p3', 'u7', 'relate'), ('p3', 'u8', 'needed'), ('p3', 'u9', 'thanks'),
('p4', 'u8', 'relate'), ('p4', 'u9', 'needed'), ('p4', 'u10', 'thanks'),
('p5', 'u1', 'relate'), ('p5', 'u2', 'needed'), ('p5', 'u3', 'thanks');

-- Add some comments
INSERT INTO comments (post_id, user_id, content, is_approved) VALUES
('p1', 'u5', 'This resonates with me so much. Keep dreaming big!', TRUE),
('p1', 'u6', 'You inspired me to chase my own dreams.', TRUE),
('p2', 'u7', 'Small acts of kindness make the world better.', TRUE),
('p2', 'u8', 'This made me smile. We need more people like you.', TRUE),
('p3', 'u9', 'Brave of you to admit this. First step to change.', TRUE),
('p4', 'u10', 'This hit home. Thank you for sharing.', TRUE),
('p5', 'u1', 'I thought I was the only one who felt this way!', TRUE);

-- Add some reading history
INSERT INTO reading_history (user_id, post_id) VALUES
('u1', 'p2'), ('u1', 'p3'), ('u1', 'p4'),
('u2', 'p1'), ('u2', 'p3'), ('u2', 'p5'),
('u3', 'p1'), ('u3', 'p2'), ('u3', 'p4'),
('u4', 'p1'), ('u4', 'p2'), ('u4', 'p3'),
('u5', 'p1'), ('u5', 'p2'), ('u5', 'p3'); 