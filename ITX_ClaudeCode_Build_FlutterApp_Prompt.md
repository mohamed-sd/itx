# 🛠️ برومت كلود كود — بناء تطبيق ITX بـ Flutter + كل الـ APIs

> افتح كلود كود في مجلد المشروع `C:\xampp\htdocs\itx`.  
> ضع ملفات التصميم الجاهزة (من كلود ديزاين) داخل مجلد `design/` في المشروع.  
> ثم ألصق البرومت أدناه. (نسخة عربية + English version).

---

## 🟦 النسخة العربية (للّصق المباشر)

```
أنت مهندس برمجيات Full-Stack محترف، خبير في Flutter (Dart) وفي PHP/MySQL.
عندي مشروع شركة "ITX" في هذا المجلد: موقع PHP + MySQL مع لوحة تحكم admin،
وحصلت على تصميم تطبيق جوال جاهز موجود في مجلد design/.
المطلوب شيئان معاً:
  (أ) إنشاء كل الـ APIs اللازمة في الباك إند الحالي (PHP) لتغذية التطبيق.
  (ب) بناء تطبيق جوال احترافي بـ Flutter يطابق التصميم ويستهلك هذه الـ APIs.

═══════════════════════════════════
■ افهم الموجود أولاً (لا تكسره)
═══════════════════════════════════
- اقرأ هذه الملفات قبل أي كتابة:
  • config/db.php  → دالة getDB() (PDO)، site_media_url()، get_base_url(),
    apply_security_headers().
  • api/get_projects.php, api/get_project.php, api/get_categories.php  → التزم
    بنفس نمطها بالضبط.
  • database/itx_db.sql  → السكيمة الكاملة وأسماء الأعمدة.
  • design/  → التصميم الجاهز (الشاشات، الألوان، الخطوط، التدفقات).
- الجداول المتاحة: site_settings, hero_section, about_section, services,
  statistics, testimonials, contact_info, social_media, categories,
  projects, project_media, blog_categories, blog_posts, content_pages, admins.
- معظم الجداول ثنائية اللغة (عمود عربي + عمود _en مثل title/title_en).
- لا تُعدّل لوحة admin ولا منطق الموقع الحالي. أضِف فقط ملفات API جديدة.

═══════════════════════════════════
■ (أ) الـ APIs المطلوبة — في مجلد api/
═══════════════════════════════════
اتبع **نفس اتفاقية الملفات الحالية بالحرف**:
- require config/db.php، تنظيف الـ output buffers، ثم:
  header('Content-Type: application/json; charset=utf-8');
- صيغة الرد الموحّدة: {"success": true, "data": ...} أو
  {"success": false, "message": "..."} مع http_response_code المناسب.
- استخدم getDB() وعبارات prepared، و JSON_UNESCAPED_UNICODE دائماً.
- **أضِف رؤوس CORS** (لأن المستهلك تطبيق جوال):
  Access-Control-Allow-Origin: *  و Allow-Methods: GET, OPTIONS  و
  تعامُل مع طلب OPTIONS المبكر (preflight) بإرجاع 204.
- **حوّل مسارات الصور إلى روابط مطلقة كاملة** باستخدام get_base_url() +
  site_media_url() حتى يستطيع التطبيق تحميلها (لا ترجع مسارات نسبية).
- رشّح دائماً بـ status='active'/'published' ورتّب بـ sort_order/التاريخ.

الـ Endpoints المطلوب إنشاؤها:
1. api/get_settings.php   → إعدادات الموقع من site_settings (الاسم، الشعار،
   رقم واتساب، رسالة واتساب الافتراضية، الوصف، الفوتر… كأزواج key/value).
2. api/get_home.php       → Endpoint مُجمّع لشاشة الرئيسية بنداء واحد:
   { hero, about, services[], statistics[], latest_posts[], featured_projects[] }.
3. api/get_hero.php       → hero_section (id=1).
4. api/get_about.php      → about_section (id=1).
5. api/get_services.php   → كل الخدمات active مرتبة.
6. api/get_statistics.php → كل الإحصائيات active.
7. api/get_testimonials.php → كل الشهادات active.
8. api/get_contact.php    → contact_info + social_media.
9. api/get_categories.php → (موجود — أبقِه كما هو).
10. api/get_projects.php  → (موجود) دعم ?category=ID للفلترة.
11. api/get_project.php   → (موجود) تفاصيل مشروع + project_media (المعرض).
12. api/get_blog_categories.php → فئات المدونة (blog_categories).
13. api/get_posts.php     → مقالات منشورة، مع دعم:
    ?category=ID للفلترة بالفئة، و ?q=نص للبحث في العنوان/المحتوى (LIKE)،
    و ?page=&limit= للتقسيم (pagination). أرجِع مع الـ data معلومات
    الصفحات: {data:[...], total, page, limit}.
14. api/get_post.php      → مقال مفرد بـ ?slug= أو ?id= + مقالات ذات صلة
    (نفس الفئة، حد 4).
ملاحظة: لا حاجة لـ endpoints للطلبات/الحجوزات — كل طلبات الخدمة في التطبيق
تتم عبر فتح واتساب (deep link)، لا عبر الباك إند.

بعد كتابة كل ملف: شغّل php -l للتأكد، واختبره بـ curl وتحقق أن JSON سليم
وأن روابط الصور مطلقة وتعمل.

═══════════════════════════════════
■ (ب) تطبيق Flutter — في مجلد app/
═══════════════════════════════════
أنشئ مشروع Flutter جديد داخل app/ يطابق design/ بدقة (نفس الألوان، الخطوط،
التخطيط، الحركات، الزوايا والظلال).

التقنيات والبنية:
- Flutter (أحدث مستقر) + Dart، إدارة حالة Riverpod (أو Provider).
- الشبكة: dio، مع طبقة ApiService وملف إعداد BASE_URL واحد قابل للتبديل
  (للمحاكي استخدم http://10.0.2.2/itx/api، وللجهاز الحقيقي IP الجهاز).
- بنية نظيفة:
  lib/core (theme, constants, config, utils, l10n)،
  lib/models (تطابق حقول الـ API ثنائية اللغة)،
  lib/services (ApiService + WhatsAppService)،
  lib/providers (حالة كل شاشة)،
  lib/screens، lib/widgets (مكوّنات قابلة لإعادة الاستخدام).
- الهوية (من design/): Navy #0E2150 + Orange #FF7A1A، تدرّج العلامة،
  الخطوط IBM Plex Sans Arabic للنص و Space Grotesk للأرقام/العناوين
  (أضِفها في pubspec وملفات assets/fonts).
- وضعان Light/Dark عبر ThemeData كامل مستمد من الهوية.
- ثنائي اللغة AR/EN: flutter_localizations + intl، ملفات ترجمة (ARB)،
  دعم RTL/LTR تلقائي، زر تبديل لغة من الإعدادات، وحفظ الاختيار واللون
  (shared_preferences).
- ربط الحقول ثنائية اللغة: اعرض title أو title_en حسب اللغة الحالية.

الشاشات (طابِق design/ بالكامل):
1. Splash + Onboarding (٣ شرائح).
2. Home: Hero بتدرّج + CTA واتساب، خدمات سريعة، أعمال مميّزة (carousel)،
   عدّادات أرقام، آخر المقالات، زر واتساب عائم. (نداء get_home.php).
3. Services: شبكة الخدمات الست. 4. تفاصيل الخدمة + زر "اطلب عبر واتساب".
5. Portfolio: شبكة مشاريع + فلترة بالفئات (get_categories + get_projects).
6. تفاصيل المشروع: معرض صور (get_project) + زر واتساب.
7. المدونة (Blog): حقل بحث + شارات فلترة بالفئات + قائمة بطاقات + ترقيم/تحميل
   متدرّج، وحالة "لا نتائج". (get_posts مع q/category/page).
8. تفاصيل المقال + مشاركة + مقالات ذات صلة (get_post).
9. آراء العملاء (get_testimonials).
10. تواصل: واتساب/اتصال/خريطة/سوشيال (get_contact). 11. الإشعارات.
12. الحساب/الإعدادات: لغة، وضع ليلي، تفضيلات، عن التطبيق.

التنقّل: Bottom Tab Bar (الرئيسية | الخدمات | الأعمال | المدونة | حسابي)
+ زر واتساب عائم.

ميزات تقنية مطلوبة:
- WhatsAppService: كل أزرار "اطلب/تواصل" تفتح واتساب عبر url_launcher برسالة
  جاهزة معبّأة (اسم الخدمة/المشروع) إلى رقم الشركة القادم من get_settings.
- صور بتخزين مؤقت (cached_network_image)، هياكل تحميل (shimmer/skeleton)،
  سحب للتحديث (pull-to-refresh)، ومعالجة حالات الخطأ والفراغ بذوق.
- url_launcher للاتصال والخريطة، share_plus لمشاركة المقالات.
- أداء جيد، كود نظيف ومنظّم، وإتاحة (أحجام لمس مريحة، تباين).

═══════════════════════════════════
■ خطة التنفيذ (نفّذ على مراحل واعرض كل مرحلة قبل التالية)
═══════════════════════════════════
1) اقرأ design/ والباك إند الحالي، ثم اعرض خطة موجزة + قائمة الـ endpoints
   والـ models قبل الكتابة.
2) أنشئ كل ملفات api/ واختبرها بـ curl (أرني عيّنات JSON).
3) أنشئ مشروع Flutter: pubspec، الثيم، الخطوط، الترجمة، طبقة الـ API والموديلز.
4) ابنِ الشاشات شاشة شاشة بالترتيب أعلاه، مطابِقاً design/.
5) اربط كل شاشة بالـ API الموافق وتحقق من RTL/LTR والوضع الليلي.
6) في النهاية: أعطني دليل تشغيل (إعداد BASE_URL، flutter pub get، التشغيل على
   محاكي/جهاز) + قائمة بكل ملف أنشأته.

قواعد: لا تكسر أي شيء في الباك إند الحالي؛ التزم باتفاقيات api/ الحالية؛
استخدم prepared statements؛ روابط صور مطلقة؛ كود Flutter منظّم وقابل للصيانة.
ابدأ الآن بالخطوة 1.
```

---

## 🟩 English Version (alternative)

```
You are a professional Full-Stack engineer, expert in Flutter (Dart) and
PHP/MySQL. I have my company "ITX" project in this folder: a PHP + MySQL website
with an admin panel, and I have a ready mobile-app design in the design/ folder.
I need TWO things together:
  (A) Create all required backend APIs in the existing PHP backend.
  (B) Build a professional Flutter app that matches the design and consumes them.

UNDERSTAND THE EXISTING CODE FIRST (don't break it)
- Read before writing: config/db.php (getDB() PDO, site_media_url(),
  get_base_url()), api/get_projects.php, api/get_project.php,
  api/get_categories.php (follow their pattern EXACTLY), database/itx_db.sql,
  and design/.
- Tables: site_settings, hero_section, about_section, services, statistics,
  testimonials, contact_info, social_media, categories, projects, project_media,
  blog_categories, blog_posts, content_pages, admins. Most are bilingual
  (e.g. title / title_en). Do NOT modify the admin panel or existing site logic.

(A) APIS — in api/
Follow the existing file convention exactly: require config/db.php, clear output
buffers, JSON header, response shape {"success":true,"data":...} (or success:false
+ message + status code), getDB() with prepared statements, always
JSON_UNESCAPED_UNICODE. ADD CORS headers (mobile client): Access-Control-Allow-
Origin: *, GET/OPTIONS, handle OPTIONS preflight with 204. Convert image paths to
ABSOLUTE URLs via get_base_url() + site_media_url(). Filter active/published,
order by sort_order/date.
Endpoints: get_settings.php (site_settings incl. logo + WhatsApp number/message),
get_home.php (aggregated: hero, about, services, statistics, latest_posts,
featured_projects), get_hero.php, get_about.php, get_services.php,
get_statistics.php, get_testimonials.php, get_contact.php (+ socials),
get_categories.php (exists), get_projects.php (exists, ?category=), get_project.php
(exists, + project_media gallery), get_blog_categories.php, get_posts.php
(?category=, ?q= search, ?page=&limit= pagination → {data,total,page,limit}),
get_post.php (?slug=/?id= + related, same category, limit 4).
No order/booking endpoints — all service requests open WhatsApp via deep link.
After each file: run php -l and test with curl; verify valid JSON and absolute
working image URLs.

(B) FLUTTER APP — in app/
New Flutter project under app/, matching design/ precisely (colors, fonts, layout,
motion, radii, shadows).
Stack: latest stable Flutter/Dart, Riverpod (or Provider), dio with one ApiService
and a single switchable BASE_URL (emulator http://10.0.2.2/itx/api; real device =
machine IP). Clean structure: lib/core (theme, config, utils, l10n), lib/models
(match bilingual API fields), lib/services (ApiService + WhatsAppService),
lib/providers, lib/screens, lib/widgets.
Identity (from design/): Navy #0E2150 + Orange #FF7A1A, brand gradient, fonts IBM
Plex Sans Arabic (text) + Space Grotesk (numbers/headings) added to pubspec/assets.
Full Light/Dark ThemeData. Bilingual AR/EN: flutter_localizations + intl + ARB,
auto RTL/LTR, language toggle in settings, persist language & theme
(shared_preferences). Show title vs title_en per current locale.
Screens (match design/): Splash+Onboarding (3), Home (get_home.php; hero gradient
+ WhatsApp CTA, quick services, featured works carousel, stat counters, latest
posts, floating WhatsApp), Services, Service detail (+ "Request on WhatsApp"),
Portfolio (filter via get_categories + get_projects), Project detail (gallery via
get_project + WhatsApp), Blog (search field + category chips + card list +
pagination + empty state, via get_posts q/category/page), Article reader (+ share +
related via get_post), Testimonials, Contact (WhatsApp/call/map/socials),
Notifications, Account/Settings (language, dark mode, about).
Navigation: bottom tab bar (Home | Services | Work | Blog | Account) + floating
WhatsApp button.
Tech features: WhatsAppService — all "request/contact" buttons open WhatsApp via
url_launcher with a pre-filled message (service/project name) to the company
number from get_settings. cached_network_image, shimmer skeletons, pull-to-refresh,
tasteful error/empty states. url_launcher for call/map, share_plus for articles.
Good performance, clean maintainable code, accessibility.

PLAN (work in stages, present each before the next)
1) Read design/ + backend, present a brief plan + endpoint/model list before
   writing. 2) Create all api/ files, test with curl (show JSON samples).
3) Scaffold Flutter: pubspec, theme, fonts, l10n, API layer, models.
4) Build screens one by one in the order above, matching design/.
5) Wire each screen to its API; verify RTL/LTR and dark mode.
6) Finally: give a run guide (set BASE_URL, flutter pub get, run on emulator/device)
   + list every file created.
Rules: don't break the existing backend; follow current api/ conventions; prepared
statements; absolute image URLs; organized Flutter code. Start now with step 1.
```

---

## 💡 ملاحظات سريعة
- **مجلد التصميم**: ضع مخرجات كلود ديزاين في `design/` قبل تشغيل البرومت (أو غيّر المسار في النص).
- **BASE_URL على المحاكي**: المحاكي لا يرى `localhost` — استخدم `http://10.0.2.2/itx/api`. على جهاز حقيقي استخدم IP حاسوبك على نفس الشبكة.
- **رقم واتساب**: يأتي ديناميكياً من `get_settings` (جدول `site_settings`)، فلن تحتاج كتابته داخل التطبيق.
- نفّذ على مراحل: الـ APIs أولاً ثم الشاشات — يعطي جودة وثباتاً أعلى.
