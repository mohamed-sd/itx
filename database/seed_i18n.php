<?php
/* ============================================================
   ITX — i18n seed (English translations of the existing content)
   Idempotent: only fills an _en field when it is currently NULL or
   empty, so it never overwrites translations edited from the admin.
   Run once (after migrate_i18n.php):  php database/seed_i18n.php
   ============================================================ */
require __DIR__ . '/../config/db.php';
$pdo = getDB();

/* Guarded UPDATE: set $col=$val on $table WHERE id=? only when empty. */
function set_en(PDO $pdo, string $table, $id, array $vals): void {
    $sets = [];
    foreach ($vals as $col => $_) {
        $sets[] = "`$col` = COALESCE(NULLIF(`$col`,''), :$col)";
    }
    $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = :__id";
    $stmt = $pdo->prepare($sql);
    foreach ($vals as $col => $val) $stmt->bindValue(":$col", $val);
    $stmt->bindValue(':__id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

/* Settings: insert only when the key is absent. */
function set_setting_en(PDO $pdo, string $key, string $val): void {
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value)
        SELECT ?, ? FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = ?)");
    $stmt->execute([$key, $val, $key]);
}

/* ── Settings ─────────────────────────────────────────────── */
set_setting_en($pdo, 'enable_english',      '1');
set_setting_en($pdo, 'site_tagline_en',     'Digital Solutions');
set_setting_en($pdo, 'site_description_en', 'ITX Digital Solutions — specialists in web & app development and security camera systems installation.');
set_setting_en($pdo, 'footer_text_en',      'All rights reserved | ITX Digital Solutions');

/* ── Hero ─────────────────────────────────────────────────── */
set_en($pdo, 'hero_section', 1, [
    'title_en'     => 'ITX Digital Solutions',
    'subtitle_en'  => 'Specialists in web & app development and security camera systems installation',
    'note_en'      => 'We deliver integrated tech solutions to secure and digitize your business',
    'btn1_text_en' => 'View our work',
    'btn2_text_en' => 'Get in touch',
]);

/* ── About ────────────────────────────────────────────────── */
set_en($pdo, 'about_section', 1, [
    'heading_en' => 'Welcome to ITX',
    'content_en' => "ITX specializes in delivering integrated digital and technical solutions that combine innovative software development with the installation of modern security camera systems.\n\nWe believe in the importance of quality, innovation, and security in every project, and we strive to deliver solutions that exceed our clients' expectations and contribute to the growth and protection of their businesses.\n\nWith extensive experience, we have worked with hundreds of companies and institutions to build advanced systems and provide effective protection for their premises.",
    'skills_en'  => 'Web Development,Mobile Apps,Surveillance Cameras,Security Systems,Accounting Systems,POS System,Data Analytics,Technical Support,Periodic Maintenance,Tech Consulting',
]);

/* ── Services ─────────────────────────────────────────────── */
$services_en = [
    1 => ['Web Development',                'Building professional, fast and secure websites with the latest technologies and global standards.'],
    2 => ['Mobile Apps',                    'Developing smart, easy-to-use mobile apps for iOS and Android.'],
    3 => ['Surveillance Cameras',           'Installing and setting up modern, high-definition security camera systems with smart technology.'],
    4 => ['Security Systems',               'Designing and installing integrated security systems with smart monitoring and instant alerts.'],
    5 => ['Electronic Systems',             'Developing advanced POS and accounting systems and custom tech solutions.'],
    6 => ['Technical Support & Maintenance','Providing integrated technical support and periodic maintenance for all systems and projects.'],
];
foreach ($services_en as $id => $v) set_en($pdo, 'services', $id, ['title_en' => $v[0], 'description_en' => $v[1]]);

/* ── Statistics ───────────────────────────────────────────── */
$stats_en = [1 => 'Projects delivered', 2 => 'Years of experience', 3 => 'Happy clients', 4 => 'Satisfaction rate'];
foreach ($stats_en as $id => $label) set_en($pdo, 'statistics', $id, ['label_en' => $label]);

/* ── Testimonials ─────────────────────────────────────────── */
$testi_en = [
    1 => ['E-commerce store owner', 'Very professional service — the ITX team built my website excellently and with very high quality.'],
    2 => ['Company General Manager','The camera systems they installed are excellent, and their customer service is outstanding.'],
    3 => ['Beauty salon owner',     'I used their booking app and demand increased significantly — truly excellent.'],
    4 => ['Grocery store owner',    'Their POS system saved me a great deal of time and effort.'],
    5 => ['Technology Manager',     'Technical support is always available, and any issue is resolved quickly and professionally.'],
    6 => ['Project Manager',        "The best company I've worked with — high professionalism and very reasonable prices."],
];
foreach ($testi_en as $id => $v) set_en($pdo, 'testimonials', $id, ['author_role_en' => $v[0], 'content_en' => $v[1]]);

/* ── Contact ──────────────────────────────────────────────── */
set_en($pdo, 'contact_info', 1, ['address_en' => 'Riyadh, Saudi Arabia']);

/* ── Blog categories ──────────────────────────────────────── */
$cats_en = [1 => 'Tech & Programming', 2 => 'Information Security', 3 => 'Surveillance Cameras', 4 => 'Tips & Guides'];
foreach ($cats_en as $id => $name) set_en($pdo, 'blog_categories', $id, ['name_en' => $name]);

/* ── Content pages ────────────────────────────────────────── */
set_en($pdo, 'content_pages', 1, [
    'title_en'   => 'Privacy Policy',
    'content_en' => '<h2>Privacy Policy</h2><p>At ITX we are committed to protecting the privacy of our clients and website visitors. This policy describes how we collect, use, and protect your personal information.</p><h3>Information We Collect</h3><p>We may collect personal information such as your name, email address, and phone number when you contact us through our online forms.</p><h3>How We Use Information</h3><p>We use the collected information to respond to your inquiries, improve our services, and send service-related updates with your consent.</p><h3>Protecting Information</h3><p>We apply strict security measures to protect your information from unauthorized access or disclosure.</p>',
]);
set_en($pdo, 'content_pages', 2, [
    'title_en'   => 'Terms of Use',
    'content_en' => '<h2>Terms of Use</h2><p>By using the ITX website and our services, you agree to comply with the following terms and conditions.</p><h3>Use of the Website</h3><p>This website may be used for lawful purposes only. Using it for any illegal or harmful activity is prohibited.</p><h3>Intellectual Property</h3><p>All content, designs, images, and text published on this website are the exclusive property of ITX and are protected under intellectual property laws.</p><h3>Liability</h3><p>ITX is not liable for any direct or indirect damages resulting from the use of, or inability to use, our services.</p>',
]);

/* ── Blog posts ───────────────────────────────────────────── */
$posts_en = [
    1 => [
        'title_en' => 'Best Web Development Technologies in 2025',
        'excerpt_en' => 'In this article we review the top web development technologies every developer should know in 2025.',
        'content_en' => '<h2>Introduction</h2><p>The web development industry is evolving rapidly, with new technologies and tools constantly emerging. In this article we review the most important things every developer should master.</p><h2>Top Technologies</h2><ul><li><strong>React / Next.js</strong> — for building fast, interactive user interfaces.</li><li><strong>Tailwind CSS</strong> — for designing distinctive visual interfaces quickly.</li><li><strong>PHP 8+ / Laravel</strong> — for reliable backend development.</li></ul><p>Investing in learning these technologies ensures you stand out in the job market.</p>',
        'meta_title_en' => 'Best Web Development Technologies in 2025 | ITX',
        'meta_description_en' => 'A comprehensive guide to the best web development technologies for 2025.',
    ],
    2 => [
        'title_en' => 'How to Choose the Right CCTV System for Your Business',
        'excerpt_en' => 'A comprehensive guide to help you choose the surveillance camera system best suited to your needs and budget.',
        'content_en' => '<h2>Why Are Surveillance Cameras Important?</h2><p>Modern surveillance cameras provide comprehensive security, deter intruders, and help monitor operations.</p><h2>Key Factors When Choosing</h2><ul><li>The number of cameras required and their installation locations.</li><li>Image resolution (Full HD or 4K).</li><li>Night vision.</li><li>Storage capacity and recording retention period.</li></ul><p>Contact the ITX team for a free consultation.</p>',
        'meta_title_en' => 'How to Choose a CCTV System | ITX',
        'meta_description_en' => 'A guide to choosing the best surveillance camera system for your facility.',
    ],
    3 => [
        'title_en' => 'The Importance of Digital Security for SMEs',
        'excerpt_en' => 'Learn the importance of digital security and how it protects your company from growing cyber threats.',
        'content_en' => '<h2>Digital Threats Are Constantly Growing</h2><p>Cyberattacks don\'t only target large enterprises — small businesses have become a primary target.</p><h2>Simple Steps for Protection</h2><ul><li>Use strong, unique passwords for every account.</li><li>Update systems and applications regularly.</li><li>Enable two-factor authentication.</li><li>Back up your data periodically.</li></ul>',
        'meta_title_en' => 'Digital Security for Small Businesses | ITX',
        'meta_description_en' => 'The importance of digital security and protecting data for small and medium businesses.',
    ],
    4 => [
        'title_en' => 'Tips to Speed Up Your Website and Improve User Experience',
        'excerpt_en' => 'Speed up your website and improve user experience by following these easy, practical tips.',
        'content_en' => '<h2>Why Does Speed Matter?</h2><p>Slow websites lose their visitors. 53% of users leave a site if it doesn\'t load within 3 seconds.</p><h2>Tips for Speeding Up</h2><ul><li>Compress images and convert them to WebP format.</li><li>Enable caching.</li><li>Minify CSS and JavaScript files.</li><li>Use a Content Delivery Network (CDN).</li></ul>',
        'meta_title_en' => 'Tips to Speed Up Your Site and Improve UX | ITX',
        'meta_description_en' => 'Practical tips to speed up your website and improve user experience.',
    ],
    5 => [
        'title_en' => "A Beginner's Guide to Mobile App Development",
        'excerpt_en' => 'Everything you need to know to start your mobile app development journey from scratch to launch.',
        'content_en' => '<h2>Choosing the Right Path</h2><p>Do you want a native app or a hybrid one? The decision depends on your budget and target audience.</p><h2>Recommended Tools</h2><ul><li><strong>React Native</strong> — for high-performance hybrid development.</li><li><strong>Flutter</strong> — for beautiful designs on both platforms at once.</li><li><strong>Swift / Kotlin</strong> — for native development.</li></ul>',
        'meta_title_en' => "A Beginner's Guide to Mobile App Development | ITX",
        'meta_description_en' => 'A comprehensive guide to start developing mobile apps for Android and iOS.',
    ],
    6 => [
        'title_en' => 'How to Write Content That Attracts Clients to Your Website',
        'excerpt_en' => 'Your website content is the first thing your potential client sees. Learn how to write persuasive content that turns visitors into clients.',
        'content_en' => '<h2>The Importance of Good Content</h2><p>Good content builds trust, improves your search engine ranking, and persuades visitors to make a purchase decision.</p><h2>Principles of Effective Writing</h2><ul><li>Speak your client\'s language, not technical jargon.</li><li>Focus on benefits, not features.</li><li>Use clear headings and short paragraphs.</li><li>End with a clear Call to Action.</li></ul>',
        'meta_title_en' => 'How to Write Content That Attracts Clients | ITX',
        'meta_description_en' => 'Tips for writing marketing content that attracts clients and improves SEO.',
    ],
];
foreach ($posts_en as $id => $v) set_en($pdo, 'blog_posts', $id, $v);

/* ── Works categories ─────────────────────────────────────── */
$wcats_en = [
    1 => 'Programming',
    2 => 'Design',
    3 => 'Surveillance Cameras',
    4 => 'Point of Sale',
    5 => 'Technical Support',
];
foreach ($wcats_en as $id => $name) set_en($pdo, 'categories', $id, ['name_en' => $name]);

/* ── Projects ─────────────────────────────────────────────── */
$projects_en = [
    1 => [
        'title_en' => 'Integrated E-commerce Store Platform',
        'short_desc_en' => 'A complete e-commerce platform with product management, payment gateways, and a professional dashboard.',
        'description_en' => 'A complete e-commerce platform covering product, order, and payment management, with a professional dashboard and detailed reports. It supports local and international payment gateways and comes with a fast, responsive interface.',
    ],
    2 => [
        'title_en' => 'School Management System',
        'short_desc_en' => 'An integrated system to manage schools, students, teachers, and class schedules.',
        'description_en' => 'A comprehensive system for managing schools and educational institutes, including student, teacher, schedule, grade, and attendance management, with detailed reports and a parent communication portal.',
    ],
    3 => [
        'title_en' => 'Restaurant Booking App',
        'short_desc_en' => 'A mobile app to manage restaurant reservations and food orders with instant notifications.',
        'description_en' => 'A professional mobile app for restaurant reservations that lets customers book, manage tables, and pre-order food, with an instant notification system and real-time order tracking.',
    ],
    4 => [
        'title_en' => 'Data Analytics Dashboard',
        'short_desc_en' => 'An interactive dashboard for business performance analysis with charts and live metrics.',
        'description_en' => 'An interactive dashboard to visually analyze and present business data, including advanced charts, exportable reports, and real-time key performance indicator analysis.',
    ],
    5 => [
        'title_en' => 'Brand Visual Identity',
        'short_desc_en' => 'A complete visual identity: logo, colors, design guide, and marketing materials.',
        'description_en' => 'Designing a complete visual identity including logo, colors, fonts, and a design guide, in addition to all digital and printed marketing materials.',
    ],
    6 => [
        'title_en' => 'App UI/UX Design',
        'short_desc_en' => 'Professional UX/UI design for a mobile app with interactive prototypes.',
        'description_en' => 'Designing easy-to-use UX/UI interfaces for a mobile app following the latest interactive design standards, with clickable prototypes and user experience testing.',
    ],
    7 => [
        'title_en' => 'Residential Complex Security System',
        'short_desc_en' => 'Installing 50 4K cameras with smart monitoring and cloud recording for a residential complex.',
        'description_en' => 'Installing an integrated surveillance system for a residential complex with 50 4K cameras, smart motion detection, cloud recording, and integration with a mobile app for remote monitoring.',
    ],
    8 => [
        'title_en' => 'Industrial Factory Surveillance System',
        'short_desc_en' => 'A weatherproof industrial surveillance system with a 24/7 central control room.',
        'description_en' => 'Installing an advanced surveillance system for an industrial factory including weather- and dust-resistant cameras, early-warning alarms, and full integration with a central control room equipped with 24/7 monitoring screens.',
    ],
    9 => [
        'title_en' => 'Restaurant Chain POS System',
        'short_desc_en' => 'An integrated POS system for a 10-branch chain with inventory management and financial reports.',
        'description_en' => 'Developing and installing an integrated POS system for a 10-branch restaurant chain, including order, inventory, and financial report management, linking branches together with thermal invoice printing.',
    ],
    10 => [
        'title_en' => 'Pharmacy Management System',
        'short_desc_en' => 'A specialized pharmacy POS with medication management and health insurance integration.',
        'description_en' => 'A POS system tailored for pharmacies, including medication, prescription, and inventory management, with expiry alerts and health insurance data integration.',
    ],
    11 => [
        'title_en' => 'Office Network Maintenance',
        'short_desc_en' => 'Setting up and maintaining a complete network infrastructure for 200 devices with documentation and security.',
        'description_en' => 'Comprehensive office network maintenance and setup services for a large company with 200 devices, including infrastructure upgrades, security and stability assurance, and full network documentation.',
    ],
    12 => [
        'title_en' => 'Continuous IT Support for a Hospital',
        'short_desc_en' => '24/7 technical support and periodic maintenance for a specialized hospital\'s devices and networks.',
        'description_en' => 'Providing emergency technical support and periodic maintenance for computers and networks at a specialized hospital, ensuring 24/7 service continuity and a rapid intervention protocol.',
    ],
];
foreach ($projects_en as $id => $v) set_en($pdo, 'projects', $id, $v);

/* ── Project media captions ───────────────────────────────── */
$media_en = [
    1 => 'Store homepage', 2 => 'Product listing page', 3 => 'Cart & checkout', 4 => 'Dashboard & reports', 5 => 'Store video tour',
    6 => 'Main dashboard', 7 => 'Student records management', 8 => 'Class schedules', 9 => 'System features walkthrough',
    10 => 'Charts dashboard', 11 => 'Detailed sales reports', 12 => 'Analytics dashboard walkthrough',
    13 => 'Outdoor camera installation', 14 => 'Central control room', 15 => 'Camera display screen',
    16 => 'Order intake screen', 17 => 'Menu management', 18 => 'Daily sales reports', 19 => 'Full system tour',
    20 => 'Medication management interface', 21 => 'Prescriptions screen',
];
foreach ($media_en as $id => $cap) set_en($pdo, 'project_media', $id, ['caption_en' => $cap]);

echo "i18n seed complete.\n";
