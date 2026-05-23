-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 23, 2026 at 09:24 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `itx_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_section`
--

DROP TABLE IF EXISTS `about_section`;
CREATE TABLE IF NOT EXISTS `about_section` (
  `id` int NOT NULL DEFAULT '1',
  `heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_section`
--

INSERT INTO `about_section` (`id`, `heading`, `content`, `image`, `skills`, `updated_at`) VALUES
(1, 'مرحباً بك في ITX', 'شركة ITX متخصصة في تقديم حلول رقمية وتقنية متكاملة تجمع بين تطوير الحلول البرمجية المبتكرة وتركيب أنظمة كاميرات الأمان الحديثة.\n\nنؤمن بأهمية الجودة والابتكار والأمان في كل مشروع، ونسعى لتقديم حلول تتجاوز توقعات عملائنا وتساهم في نمو وحماية أعمالهم.\n\nمع خبرة واسعة، عملنا مع مئات الشركات والمؤسسات لتطوير أنظمة متقدمة وتوفير أمان فعال لمقرات أعمالهم.', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&h=400&fit=crop', 'تطوير المواقع,تطبيقات الجوال,كاميرات مراقبة,أنظمة الأمان,الأنظمة المحاسبية,نظام الكاشير,تحليل البيانات,الدعم الفني,الصيانة الدورية,الاستشارات التقنية', '2026-05-23 07:55:44');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `name`, `created_at`) VALUES
(1, 'admin', '$2y$12$Fvq.Vr9/sdEEIxXS49PPr.3ipXrjAGcqKByRfqLT8XI3fMqZ41duu', 'مدير النظام', '2026-05-23 08:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `sort_order`, `status`) VALUES
(1, 'تقنية وبرمجة', 'tech', 1, 'active'),
(2, 'أمن المعلومات', 'security', 2, 'active'),
(3, 'كاميرات المراقبة', 'cameras', 3, 'active'),
(4, 'نصائح وإرشادات', 'tips', 4, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'فريق ITX',
  `tags` text COLLATE utf8mb4_unicode_ci,
  `meta_title` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('published','draft') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_cat` (`category_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `category_id`, `title`, `slug`, `excerpt`, `content`, `thumbnail`, `author`, `tags`, `meta_title`, `meta_description`, `status`, `views`, `created_at`, `updated_at`) VALUES
(1, 1, 'أفضل تقنيات تطوير المواقع في 2025', 'best-web-technologies-2025', 'نستعرض في هذا المقال أبرز تقنيات تطوير المواقع التي يجب على كل مطور أن يعرفها خلال عام 2025.', '<h2>مقدمة</h2><p>تشهد صناعة تطوير الويب تطوراً متسارعاً، مع ظهور تقنيات وأدوات جديدة باستمرار. في هذا المقال نستعرض أبرز ما يجب على كل مطور إتقانه.</p><h2>أبرز التقنيات</h2><ul><li><strong>React / Next.js</strong> — لبناء واجهات مستخدم سريعة وتفاعلية.</li><li><strong>Tailwind CSS</strong> — لتصميم واجهات بصرية متميزة بسرعة.</li><li><strong>PHP 8+ / Laravel</strong> — للتطوير الخلفي الموثوق.</li></ul><p>الاستثمار في تعلم هذه التقنيات يضمن تميزك في سوق العمل.</p>', 'uploads/blog/img_6a116ec4c1a025.32939844.jpeg', 'فريق ITX', 'برمجة,تطوير,مواقع,تقنية', 'أفضل تقنيات تطوير المواقع في 2025 | ITX', 'دليل شامل لأفضل تقنيات تطوير المواقع الإلكترونية لعام 2025', 'published', 3, '2026-05-23 09:04:45', '2026-05-23 09:21:04'),
(2, 3, 'كيف تختار نظام كاميرات المراقبة المناسب لعملك', 'how-to-choose-cctv-system', 'دليل شامل لمساعدتك في اختيار نظام كاميرات المراقبة الأنسب لاحتياجاتك وميزانيتك.', '<h2>لماذا كاميرات المراقبة مهمة؟</h2><p>توفر كاميرات المراقبة الحديثة أماناً متكاملاً وتردع المتسللين وتساعد على متابعة العمليات.</p><h2>العوامل الأساسية عند الاختيار</h2><ul><li>عدد الكاميرات المطلوبة وأماكن تركيبها.</li><li>دقة الصورة (Full HD أو 4K).</li><li>الرؤية الليلية.</li><li>سعة التخزين ومدة الاحتفاظ بالتسجيلات.</li></ul><p>تواصل مع فريق ITX للحصول على استشارة مجانية.</p>', NULL, 'فريق ITX', 'كاميرات,أمن,مراقبة,حماية', 'كيف تختار نظام كاميرات المراقبة | ITX', 'دليل اختيار أفضل نظام كاميرات مراقبة لمنشأتك', 'published', 1, '2026-05-23 09:04:45', '2026-05-23 09:10:21'),
(3, 2, 'أهمية الأمن الرقمي للشركات الصغيرة والمتوسطة', 'digital-security-sme', 'تعرف على أهمية الأمن الرقمي وكيف يحمي شركتك من التهديدات الإلكترونية المتزايدة.', '<h2>التهديدات الرقمية في تزايد مستمر</h2><p>الاختراقات الإلكترونية لا تستهدف الشركات الكبرى فقط، بل أصبحت الشركات الصغيرة هدفاً رئيسياً.</p><h2>خطوات بسيطة للحماية</h2><ul><li>استخدام كلمات مرور قوية وفريدة لكل حساب.</li><li>تحديث الأنظمة والتطبيقات بانتظام.</li><li>تفعيل المصادقة الثنائية.</li><li>النسخ الاحتياطي الدوري للبيانات.</li></ul>', NULL, 'فريق ITX', 'أمن,حماية,شركات,رقمي', 'الأمن الرقمي للشركات الصغيرة | ITX', 'أهمية الأمن الرقمي وحماية بيانات الشركات الصغيرة والمتوسطة', 'published', 0, '2026-05-23 09:04:45', '2026-05-23 09:04:45'),
(4, 4, 'نصائح لتسريع موقعك الإلكتروني وتحسين تجربة المستخدم', 'website-speed-optimization-tips', 'أسرع موقعك وحسّن تجربة المستخدم باتباع هذه النصائح العملية السهلة التطبيق.', '<h2>لماذا السرعة مهمة؟</h2><p>المواقع البطيئة تخسر زوارها. 53% من المستخدمين يغادرون الموقع إذا لم يُحمّل خلال 3 ثوانٍ.</p><h2>نصائح للتسريع</h2><ul><li>ضغط الصور وتحويلها إلى صيغة WebP.</li><li>تفعيل التخزين المؤقت (Caching).</li><li>تقليص ملفات CSS و JavaScript.</li><li>استخدام شبكة توصيل محتوى (CDN).</li></ul>', NULL, 'فريق ITX', 'سرعة,موقع,تحسين,SEO,تجربة', 'نصائح تسريع الموقع وتحسين تجربة المستخدم | ITX', 'نصائح عملية لتسريع موقعك الإلكتروني وتحسين تجربة المستخدم', 'published', 1, '2026-05-23 09:04:45', '2026-05-23 09:21:54'),
(5, 1, 'دليل البدء في تطوير تطبيقات الجوال', 'mobile-app-development-guide', 'كل ما تحتاج معرفته للبدء في رحلة تطوير تطبيقات الجوال من الصفر حتى النشر.', '<h2>اختيار المسار الصحيح</h2><p>هل تريد تطبيقاً أصلياً (Native) أم هجيناً (Hybrid)؟ يعتمد القرار على ميزانيتك وجمهورك المستهدف.</p><h2>الأدوات الموصى بها</h2><ul><li><strong>React Native</strong> — للتطوير الهجين عالي الأداء.</li><li><strong>Flutter</strong> — لتصاميم جميلة على منصتين بوقت واحد.</li><li><strong>Swift / Kotlin</strong> — للتطوير الأصلي.</li></ul>', NULL, 'فريق ITX', 'تطبيقات,جوال,تطوير,برمجة', 'دليل البدء في تطوير تطبيقات الجوال | ITX', 'دليل شامل للبدء في تطوير تطبيقات الجوال لنظامي Android و iOS', 'published', 3, '2026-05-23 09:04:45', '2026-05-23 09:21:46'),
(6, 4, 'كيف تكتب محتوى يجذب العملاء لموقعك', 'content-writing-to-attract-clients', 'محتوى موقعك هو أول ما يراه عميلك المحتمل. تعلم كيف تكتب محتوى مقنعاً يحوّل الزوار إلى عملاء.', '<h2>أهمية المحتوى الجيد</h2><p>المحتوى الجيد يبني الثقة، يحسّن ترتيبك في محركات البحث، ويقنع الزوار باتخاذ قرار الشراء.</p><h2>مبادئ الكتابة الفعّالة</h2><ul><li>تحدث بلغة عميلك لا بلغة التقنيين.</li><li>ركز على الفوائد لا على المميزات.</li><li>استخدم عناوين واضحة وفقرات قصيرة.</li><li>اختم بدعوة إجراء (Call to Action) واضحة.</li></ul>', NULL, 'فريق ITX', 'محتوى,تسويق,كتابة,SEO', 'كيف تكتب محتوى يجذب العملاء | ITX', 'نصائح كتابة محتوى تسويقي يجذب العملاء ويحسن محركات البحث', 'published', 7, '2026-05-23 09:04:45', '2026-05-23 09:14:23');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fas fa-folder',
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `slug`, `sort_order`, `created_at`) VALUES
(1, 'برمجة', 'fas fa-code', 'programming', 1, '2026-05-23 07:13:06'),
(2, 'تصميم', 'fas fa-paint-brush', 'design', 2, '2026-05-23 07:13:06'),
(3, 'كاميرات المراقبة', 'fas fa-video', 'cameras', 3, '2026-05-23 07:13:06'),
(4, 'نقاط البيع', 'fas fa-cash-register', 'pos', 4, '2026-05-23 07:13:06'),
(5, 'دعم فني', 'fas fa-headset', 'support', 5, '2026-05-23 07:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `contact_info`
--

DROP TABLE IF EXISTS `contact_info`;
CREATE TABLE IF NOT EXISTS `contact_info` (
  `id` int NOT NULL DEFAULT '1',
  `phone` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(350) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `map_embed` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_info`
--

INSERT INTO `contact_info` (`id`, `phone`, `email`, `address`, `whatsapp`, `map_embed`, `updated_at`) VALUES
(1, '+966 50 123 4567', 'sudanit2015@gmail.com', 'الرياض، المملكة العربية السعودية', '966501234567', NULL, '2026-05-23 07:55:45');

-- --------------------------------------------------------

--
-- Table structure for table `content_pages`
--

DROP TABLE IF EXISTS `content_pages`;
CREATE TABLE IF NOT EXISTS `content_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `content_pages`
--

INSERT INTO `content_pages` (`id`, `slug`, `title`, `content`, `updated_at`) VALUES
(1, 'privacy', 'سياسة الخصوصية', '<h2>سياسة الخصوصية</h2><p>نحن في شركة ITX نلتزم بحماية خصوصية عملائنا وزوار موقعنا. تصف هذه السياسة كيفية جمع واستخدام وحماية معلوماتكم الشخصية.</p><h3>المعلومات التي نجمعها</h3><p>قد نجمع معلومات شخصية مثل الاسم وعنوان البريد الإلكتروني ورقم الهاتف عند تواصلكم معنا عبر النماذج الإلكترونية.</p><h3>كيف نستخدم المعلومات</h3><p>نستخدم المعلومات المجمعة للرد على استفساراتكم وتحسين خدماتنا وإرسال التحديثات المتعلقة بخدماتنا بموافقتكم.</p><h3>حماية المعلومات</h3><p>نطبق إجراءات أمنية صارمة لحماية معلوماتكم من الوصول غير المصرح به أو الإفصاح عنها.</p>', '2026-05-23 07:55:45'),
(2, 'terms', 'شروط الاستخدام', '<h2>شروط الاستخدام</h2><p>باستخدامك لموقع شركة ITX وخدماتنا، فإنك توافق على الالتزام بالشروط والأحكام التالية.</p><h3>استخدام الموقع</h3><p>يُسمح باستخدام هذا الموقع للأغراض المشروعة فقط. يُحظر استخدامه لأي نشاط غير قانوني أو ضار.</p><h3>الملكية الفكرية</h3><p>جميع المحتويات والتصاميم والصور والنصوص المنشورة على هذا الموقع هي ملك حصري لشركة ITX ومحمية بموجب قوانين حقوق الملكية الفكرية.</p><h3>المسؤولية</h3><p>لا تتحمل شركة ITX مسؤولية أي أضرار مباشرة أو غير مباشرة ناتجة عن استخدام أو عدم القدرة على استخدام خدماتنا.</p>', '2026-05-23 07:55:45');

-- --------------------------------------------------------

--
-- Table structure for table `hero_section`
--

DROP TABLE IF EXISTS `hero_section`;
CREATE TABLE IF NOT EXISTS `hero_section` (
  `id` int NOT NULL DEFAULT '1',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` text COLLATE utf8mb4_unicode_ci,
  `note` varchar(350) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btn1_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btn1_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btn2_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btn2_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_section`
--

INSERT INTO `hero_section` (`id`, `title`, `subtitle`, `note`, `btn1_text`, `btn1_link`, `btn2_text`, `btn2_link`, `updated_at`) VALUES
(1, 'شركة ITX للحلول الرقمية', 'متخصصون في تطوير المواقع والتطبيقات وتركيب أنظمة كاميرات الأمان', 'نقدم حلولاً تقنية متكاملة لأمان ورقمنة أعمالك', 'عرض أعمالنا', '#our-works', 'تواصل معنا', '#contact', '2026-05-23 07:55:44');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `short_desc` varchar(350) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_programming` tinyint(1) NOT NULL DEFAULT '0',
  `demo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_year` year DEFAULT NULL,
  `technologies` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_projects_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `category_id`, `title`, `description`, `short_desc`, `thumbnail`, `is_programming`, `demo_url`, `client_name`, `project_year`, `technologies`, `status`, `sort_order`, `created_at`) VALUES
(1, 1, 'منصة متجر إلكتروني متكامل', 'منصة تجارة إلكترونية متكاملة تشمل إدارة المنتجات والطلبات والمدفوعات، مع لوحة تحكم احترافية وتقارير مفصّلة. تدعم بوابات الدفع المحلية والدولية، وتأتي مع واجهة مستخدم سريعة الاستجابة.', 'منصة تجارة إلكترونية متكاملة مع إدارة المنتجات وبوابات الدفع ولوحة تحكم احترافية.', 'https://images.unsplash.com/photo-1557821552-17105176677c?w=600&h=400&fit=crop', 1, 'https://demo.itx-solutions.com/store', 'شركة الأفق التجارية', '2024', 'PHP, Laravel, MySQL, Vue.js, Stripe API', 'active', 1, '2026-05-23 07:13:07'),
(2, 1, 'نظام إدارة المدارس', 'نظام شامل لإدارة المدارس والمعاهد التعليمية يتضمن: إدارة الطلاب والمعلمين والجداول الدراسية والدرجات والحضور، مع تقارير تفصيلية وبوابة تواصل مع أولياء الأمور.', 'نظام متكامل لإدارة المدارس، الطلاب، المعلمين، والجداول الدراسية.', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=600&h=400&fit=crop', 1, 'https://demo.itx-solutions.com/school', 'مدرسة النجوم الدولية', '2024', 'PHP, CodeIgniter, MySQL, Bootstrap, jQuery', 'active', 2, '2026-05-23 07:13:07'),
(3, 1, 'تطبيق حجوزات المطاعم', 'تطبيق جوال احترافي لحجوزات المطاعم يتيح للعملاء الحجز وإدارة الطاولات وطلب الطعام مسبقاً، مع نظام إشعارات فوري ومتابعة حالة الطلبات لحظة بلحظة.', 'تطبيق جوال لإدارة حجوزات المطاعم وطلبات الطعام مع إشعارات فورية.', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600&h=400&fit=crop', 1, NULL, 'مطاعم الواحة', '2023', 'Flutter, Dart, Firebase, Node.js, MongoDB', 'active', 3, '2026-05-23 07:13:07'),
(4, 1, 'لوحة تحكم تحليل البيانات', 'لوحة تحكم تفاعلية لتحليل وعرض البيانات التجارية بشكل مرئي، تشمل رسوماً بيانية متقدمة وتقارير قابلة للتصدير وتحليل مؤشرات الأداء الرئيسية بتحديث فوري.', 'لوحة تحكم تفاعلية لتحليل الأداء التجاري مع رسوم بيانية ومؤشرات حية.', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600&h=400&fit=crop', 1, 'https://demo.itx-solutions.com/dashboard', 'مجموعة الخليج المالية', '2024', 'React.js, Chart.js, Python, FastAPI, PostgreSQL', 'active', 4, '2026-05-23 07:13:07'),
(5, 2, 'هوية بصرية لعلامة تجارية', 'تصميم هوية بصرية متكاملة تشمل الشعار والألوان والخطوط والدليل التصميمي، إضافةً إلى جميع المواد التسويقية الرقمية والمطبوعة.', 'هوية بصرية متكاملة من شعار وألوان ودليل تصميمي ومواد تسويقية.', 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=600&h=400&fit=crop', 0, NULL, 'شركة النور للتجزئة', '2024', 'Adobe Illustrator, Photoshop, Figma', 'active', 1, '2026-05-23 07:13:07'),
(6, 2, 'تصميم واجهة مستخدم تطبيق', 'تصميم واجهات مستخدم UX/UI سهلة الاستخدام لتطبيق جوال وفق أحدث معايير التصميم التفاعلي، مع نماذج أولية قابلة للنقر واختبار تجربة المستخدم.', 'تصميم UX/UI احترافي لتطبيق جوال مع نماذج تفاعلية.', 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=600&h=400&fit=crop', 0, NULL, 'شركة تقنية ناشئة', '2023', 'Figma, Adobe XD, Prototyping, User Testing', 'active', 2, '2026-05-23 07:13:07'),
(7, 3, 'منظومة أمان مجمع سكني', 'تركيب منظومة كاميرات مراقبة متكاملة لمجمع سكني تضم 50 كاميرا بدقة 4K مع أنظمة الكشف الذكي عن الحركة، التسجيل السحابي، وربطها بتطبيق جوال للمتابعة عن بعد.', 'تركيب 50 كاميرا 4K مع مراقبة ذكية وتسجيل سحابي لمجمع سكني.', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&h=400&fit=crop', 0, NULL, 'أبراج النخيل السكنية', '2024', 'Hikvision 4K, NVR, Motion Detection, Cloud Storage', 'active', 1, '2026-05-23 07:13:07'),
(8, 3, 'نظام مراقبة مصنع صناعي', 'تركيب نظام مراقبة متطور لمصنع صناعي يشمل كاميرات مقاومة للطقس والغبار، أنظمة إنذار مبكر، وربط كامل مع غرفة تحكم مركزية مجهزة بشاشات تتبع 24/7.', 'نظام مراقبة صناعي مقاوم للطقس مع غرفة تحكم مركزية 24/7.', 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?w=600&h=400&fit=crop', 0, NULL, 'مصنع الخليج الصناعي', '2023', 'Dahua IP Cameras, DVR, Weatherproof, VPN Access', 'active', 2, '2026-05-23 07:13:07'),
(9, 4, 'نظام كاشير سلسلة مطاعم', 'تطوير وتركيب نظام كاشير متكامل لسلسلة مطاعم تضم 10 فروع، يشمل إدارة الطلبات والمخزون والتقارير المالية، وربط الفروع ببعضها مع طباعة الفواتير الحرارية.', 'نظام كاشير متكامل لسلسلة 10 فروع مع إدارة مخزون وتقارير مالية.', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=400&fit=crop', 1, 'https://demo.itx-solutions.com/pos', 'سلسلة مطاعم الذواقة', '2024', 'PHP, Electron.js, MySQL, React, Thermal Printer API', 'active', 1, '2026-05-23 07:13:07'),
(10, 4, 'نظام إدارة صيدلية', 'نظام نقاط بيع مخصص للصيدليات يشمل إدارة الأدوية والوصفات الطبية والمخزون، مع تنبيهات انتهاء الصلاحية وربط ببيانات التأمين الصحي.', 'نظام POS متخصص للصيدليات مع إدارة الأدوية والتأمين الصحي.', 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&h=400&fit=crop', 1, NULL, 'صيدليات الشفاء', '2024', 'PHP, MySQL, Bootstrap, jQuery, REST API', 'active', 2, '2026-05-23 07:13:07'),
(11, 5, 'صيانة شبكات مكتبية', 'خدمات صيانة وإعداد شبكات مكتبية متكاملة لشركة كبرى تضم 200 جهاز، مع تحديث البنية التحتية وضمان الأمان والاستقرار وتوثيق الشبكة بالكامل.', 'صيانة وإعداد بنية شبكية متكاملة لـ 200 جهاز مع توثيق وتأمين.', 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=600&h=400&fit=crop', 0, NULL, 'شركة المستقبل للاستشارات', '2024', 'Cisco, Windows Server, Active Directory, VMware', 'active', 1, '2026-05-23 07:13:07'),
(12, 5, 'دعم فني مستمر لمستشفى', 'تقديم دعم فني طارئ وصيانة دورية لأجهزة الحاسوب والشبكات في مستشفى تخصصي، مع ضمان استمرارية الخدمات على مدار الساعة وبروتوكول تدخل سريع.', 'دعم فني 24/7 وصيانة دورية لأجهزة وشبكات مستشفى تخصصي.', 'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?w=600&h=400&fit=crop', 0, NULL, 'مستشفى الرحمة التخصصي', '2023', 'Hardware Maintenance, Networking, Windows Server, VPN', 'active', 2, '2026-05-23 07:13:07');

-- --------------------------------------------------------

--
-- Table structure for table `project_media`
--

DROP TABLE IF EXISTS `project_media`;
CREATE TABLE IF NOT EXISTS `project_media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `type` enum('image','video') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `url` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_media_project` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_media`
--

INSERT INTO `project_media` (`id`, `project_id`, `type`, `url`, `thumbnail`, `caption`, `sort_order`) VALUES
(1, 1, 'image', 'https://images.unsplash.com/photo-1557821552-17105176677c?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1557821552-17105176677c?w=400&h=250&fit=crop', 'الصفحة الرئيسية للمتجر', 1),
(2, 1, 'image', 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&h=250&fit=crop', 'صفحة عرض المنتجات', 2),
(3, 1, 'image', 'https://images.unsplash.com/photo-1556742031-c6961e8560b0?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1556742031-c6961e8560b0?w=400&h=250&fit=crop', 'عربة التسوق وإتمام الدفع', 3),
(4, 1, 'image', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&h=250&fit=crop', 'لوحة التحكم والتقارير', 4),
(5, 1, 'video', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1557821552-17105176677c?w=400&h=250&fit=crop', 'جولة فيديو في المتجر', 5),
(6, 2, 'image', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400&h=250&fit=crop', 'لوحة التحكم الرئيسية', 1),
(7, 2, 'image', 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&h=250&fit=crop', 'إدارة سجلات الطلاب', 2),
(8, 2, 'image', 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&h=250&fit=crop', 'الجداول الدراسية', 3),
(9, 2, 'video', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400&h=250&fit=crop', 'شرح ميزات النظام', 4),
(10, 4, 'image', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop', 'لوحة الرسوم البيانية', 1),
(11, 4, 'image', 'https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=400&h=250&fit=crop', 'تقارير المبيعات التفصيلية', 2),
(12, 4, 'video', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop', 'شرح ميزات لوحة التحليل', 3),
(13, 7, 'image', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=250&fit=crop', 'تركيب الكاميرات الخارجية', 1),
(14, 7, 'image', 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?w=400&h=250&fit=crop', 'غرفة المراقبة المركزية', 2),
(15, 7, 'image', 'https://images.unsplash.com/photo-1609136700044-c47250e33d33?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1609136700044-c47250e33d33?w=400&h=250&fit=crop', 'شاشة عرض الكاميرات', 3),
(16, 9, 'image', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=250&fit=crop', 'شاشة استقبال الطلبات', 1),
(17, 9, 'image', 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=400&h=250&fit=crop', 'إدارة قائمة الطعام', 2),
(18, 9, 'image', 'https://images.unsplash.com/photo-1556742031-c6961e8560b0?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1556742031-c6961e8560b0?w=400&h=250&fit=crop', 'تقارير المبيعات اليومية', 3),
(19, 9, 'video', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=250&fit=crop', 'جولة كاملة في النظام', 4),
(20, 10, 'image', 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&h=250&fit=crop', 'واجهة إدارة الأدوية', 1),
(21, 10, 'image', 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=1200&h=800&fit=crop', 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=400&h=250&fit=crop', 'شاشة الوصفات الطبية', 2);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-cog',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `icon`, `title`, `description`, `sort_order`, `status`, `created_at`) VALUES
(1, 'fas fa-globe', 'تطوير المواقع', 'إنشاء مواقع إلكترونية احترافية وسريعة وآمنة بأحدث التقنيات والمعايير العالمية', 1, 'active', '2026-05-23 07:55:44'),
(2, 'fas fa-mobile-alt', 'تطبيقات الجوال', 'تطوير تطبيقات جوال ذكية وسهلة الاستخدام لأنظمة iOS و Android', 2, 'active', '2026-05-23 07:55:44'),
(3, 'fas fa-video', 'كاميرات المراقبة', 'تركيب وتجهيز أنظمة كاميرات أمان حديثة بتقنية عالية الدقة والتقنيات الذكية', 3, 'active', '2026-05-23 07:55:44'),
(4, 'fas fa-lock', 'أنظمة الأمان', 'تصميم وتركيب أنظمة أمان متكاملة مع المراقبة الذكية والتنبيهات الفورية', 4, 'active', '2026-05-23 07:55:44'),
(5, 'fas fa-cogs', 'الأنظمة الإلكترونية', 'تطوير أنظمة كاشير ومحاسبية متقدمة وحلول تقنية مخصصة', 5, 'active', '2026-05-23 07:55:44'),
(6, 'fas fa-headset', 'الدعم الفني والصيانة', 'تقديم دعم فني متكامل وصيانة دورية لجميع الأنظمة والمشاريع', 6, 'active', '2026-05-23 07:55:44');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('footer_text', 'جميع الحقوق محفوظة | شركة ITX للحلول الرقمية', '2026-05-23 07:55:44'),
('site_description', 'شركة ITX للحلول الرقمية - متخصصون في تطوير المواقع والتطبيقات وتركيب أنظمة كاميرات الأمان', '2026-05-23 08:04:25'),
('site_keywords', 'تطوير المواقع, تطبيقات الجوال, كاميرات المراقبة, أنظمة الأمان', '2026-05-23 07:55:44'),
('site_logo', 'uploads/img_6a11628c7040f1.11600430.jpeg', '2026-05-23 08:17:16'),
('site_name', 'ITX', '2026-05-23 07:55:44'),
('site_tagline', 'حلول رقمية', '2026-05-23 07:55:44'),
('whatsapp_msg', 'مرحباً، أود التواصل معكم', '2026-05-23 07:55:44'),
('whatsapp_number', '966501234567', '2026-05-23 07:55:44');

-- --------------------------------------------------------

--
-- Table structure for table `social_media`
--

DROP TABLE IF EXISTS `social_media`;
CREATE TABLE IF NOT EXISTS `social_media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `platform` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fas fa-link',
  `url` varchar(350) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_media`
--

INSERT INTO `social_media` (`id`, `platform`, `icon`, `url`, `sort_order`, `status`) VALUES
(1, 'فيسبوك', 'fab fa-facebook-f', '#', 1, 'active'),
(2, 'تويتر', 'fab fa-twitter', '#', 2, 'active'),
(3, 'لينكد إن', 'fab fa-linkedin-in', '#', 3, 'active'),
(4, 'جيتهاب', 'fab fa-github', '#', 4, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `statistics`
--

DROP TABLE IF EXISTS `statistics`;
CREATE TABLE IF NOT EXISTS `statistics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `value` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics`
--

INSERT INTO `statistics` (`id`, `value`, `label`, `sort_order`, `status`) VALUES
(1, '500+', 'مشروع منجز', 1, 'active'),
(2, '15+', 'سنوات خبرة', 2, 'active'),
(3, '300+', 'عميل راضي', 3, 'active'),
(4, '99%', 'معدل الرضا', 4, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `author_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_role` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` tinyint DEFAULT '5',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `author_name`, `author_role`, `rating`, `content`, `sort_order`, `status`, `created_at`) VALUES
(1, 'محمد العتيبي', 'مالك متجر إلكتروني', 5, 'خدمة احترافية جداً، فريق ITX قام بتطوير موقعي بشكل متميز وجودة عالية جداً', 1, 'active', '2026-05-23 07:55:45'),
(2, 'فاطمة الشمري', 'مديرة عام شركة', 5, 'أنظمة الكاميرات التي قاموا بتركيبها رائعة جداً، وخدمة العملاء متفوقة', 2, 'active', '2026-05-23 07:55:45'),
(3, 'سارة الزهراني', 'صاحبة صالون تجميل', 5, 'استخدمت تطبيقهم للحجوزات وزاد الطلب بنسبة كبيرة، ممتازين جداً', 3, 'active', '2026-05-23 07:55:45'),
(4, 'علي الملحم', 'صاحب محل بقالة', 5, 'نظام الكاشير الخاص بهم حقق لي توفيراً في الوقت والجهد بشكل كبير', 4, 'active', '2026-05-23 07:55:45'),
(5, 'نور القحطاني', 'مدير تكنولوجيا', 5, 'الدعم الفني متواجد دائماً، وأي مشكلة يتم حلها بسرعة احترافية', 5, 'active', '2026-05-23 07:55:45'),
(6, 'محمود السهيمي', 'مدير مشروع', 5, 'أفضل شركة تعاملت معها، احترافية عالية وأسعار معقولة جداً', 6, 'active', '2026-05-23 07:55:45');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project_media`
--
ALTER TABLE `project_media`
  ADD CONSTRAINT `fk_media_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
