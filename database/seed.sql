INSERT INTO students (id, email, password, name, grade, bio, interests, friends, created_at)
VALUES (
    'stu_demo_001',
    'student@example.com',
    '$2y$10$k2fjrGk09V7n4vNnlPgQ5.8Yxqo5sQvIzwlnwPAw9VnSP06xHCi4q',
    'Demo Student',
    '10',
    'Welcome to Barkly East High School.',
    '["Sports", "Science"]',
    '[]',
    NOW()
)
ON DUPLICATE KEY UPDATE email = email;
