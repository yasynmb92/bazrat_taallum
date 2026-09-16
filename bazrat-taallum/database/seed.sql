INSERT INTO users (full_name, email, password_hash, role, language_pref) VALUES
('مدير النظام', 'admin@seed-learning.com', '$2y$10$iyjdgrbyDbqk0CAY0sZuBeCPdwY0lrEM3v3DNlv8hsE04gQ3ASDMO', 'admin', 'ar'),
('أحمد المدرب', 'instructor@seed-learning.com', '$2y$10$iyjdgrbyDbqk0CAY0sZuBeCPdwY0lrEM3v3DNlv8hsE04gQ3ASDMO', 'instructor', 'ar'),
('طالب تجريبي', 'student@seed-learning.com', '$2y$10$iyjdgrbyDbqk0CAY0sZuBeCPdwY0lrEM3v3DNlv8hsE04gQ3ASDMO', 'student', 'ar');

INSERT INTO categories (name_ar, name_en) VALUES
('البرمجة', 'Programming'),
('الذكاء الاصطناعي', 'Artificial Intelligence'),
('الأعمال', 'Business');

INSERT INTO courses (category_id, title_ar, title_en, description_ar, description_en, thumbnail, instructor_id, level, price, is_published) VALUES
(1, 'مدخل إلى تطوير الويب', 'Introduction to Web Development', 'تعلم أساسيات HTML و CSS و JavaScript.', 'Learn HTML, CSS, and JavaScript basics.', 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6', 2, 'beginner', 49.99, 1),
(2, 'مقدمة في تعلم الآلة', 'Machine Learning Fundamentals', 'تعلم أساسيات تعلم الآلة وتطبيقاته.', 'Learn core machine learning concepts and applications.', 'https://images.unsplash.com/photo-1677442136019-21780ecad995', 2, 'intermediate', 79.99, 1),
(3, 'إدارة المشاريع الرقمية', 'Digital Project Management', 'أدوات وأساليب إدارة المشاريع الحديثة.', 'Modern project management tools and methods.', 'https://images.unsplash.com/photo-1552664730-d307ca884978', 2, 'beginner', 59.99, 1);

INSERT INTO lessons (course_id, title_ar, title_en, content_ar, content_en, video_url, sort_order) VALUES
(1, 'مقدمة', 'Introduction', 'هذه مقدمة الدورة', 'Course introduction', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1),
(1, 'أساسيات HTML', 'HTML Basics', 'تعلم هيكل صفحة الويب', 'Learn webpage structure', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 2),
(2, 'ما هو تعلم الآلة؟', 'What is ML?', 'مفاهيم أساسية في تعلم الآلة', 'Basic ML concepts', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1);

INSERT INTO coupons (code, discount_type, discount_value, is_active) VALUES
('SEED10', 'percent', 10, 1),
('WELCOME5', 'fixed', 5, 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('platform_name_ar', 'بذرة تعلم'),
('platform_name_en', 'Learning Seeds'),
('platform_logo', 'assets/images/logo.png'),
('currency', 'USD'),
('default_lang', 'ar');
