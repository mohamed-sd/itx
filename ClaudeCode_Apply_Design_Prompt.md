# 🛠️ برومت كلود كود — دمج التصميم الجديد "ITX New" مع كود ITX الحالي (PHP/MySQL)

> افتح كلود كود في مجلد المشروع `C:\xampp\htdocs\itx`. التصميم الجديد مفكوك بالفعل في مجلد `new-design/` (ملفات: `ITX Website.html`, `styles.css`, `script.js`, `assets/`, `uploads/`). ألصق البرومت التالي.

---

## 🟦 البرومت (انسخ من هنا)

```
أنت مطور PHP محترف. عندي موقع شركة "ITX" مبني بـ PHP + MySQL مع لوحة تحكم،
وحصلت على تصميم جديد احترافي ثنائي اللغة موجود في مجلد new-design/.
مطلوب: دمج التصميم الجديد في الكود الحالي **دون كسر أي وظيفة أو ربط بقاعدة البيانات**،
مع إبقاء كل المحتوى ديناميكياً يُسحب من قاعدة البيانات.

═══════════════════════════════════
■ وصف التصميم الجديد (new-design/)
═══════════════════════════════════
الملفات:
- "ITX Website.html" → صفحة كاملة، RTL، lang="ar". هي الواجهة المرجعية.
- styles.css → كل التنسيقات (415 سطر) معتمدة على CSS variables في :root.
- script.js → كل التفاعلات (203 سطر).
- assets/ → الشعار الجديد: itx-logo-full.png, itx-logo-alt.png, itx-mark.png (أيقونة).
- uploads/ → نسخ شفافة من الشعار (01/02/03 ITX New Transparent.png).

الهوية البصرية الجديدة (استخدمها حرفياً):
- الألوان (CSS vars):
  --navy-700:#0E2150 (أساسي), --navy-800:#0A1838, --navy-900:#081230,
  --navy-600:#16306e, --orange-500:#FF7A1A (تمييز), --orange-600:#e8650a,
  --bg:#F7F8FB, --surface:#ffffff, --ink:#0E2150, --text:#3a4566, --muted:#74809e.
  تدرّج العلامة: linear-gradient(120deg, navy-700, navy-600, orange-500).
  *** ممنوع استخدام الألوان القديمة #0FECC1 / #2FA8B9 / #3F4D60 نهائياً. ***
- الخطوط (Google Fonts): "IBM Plex Sans Arabic" للنصوص،
  و "Space Grotesk" للأرقام والعناوين الكبيرة (--ff-display).
- زوايا دائرية (12–32px)، ظلال ناعمة، أزرار pill، eyebrow chips برتقالية.

أقسام الصفحة بالترتيب (نفس بنية موقعي الحالي):
1. Navbar ثابت (id=nav) يتحول عند التمرير + شعار + روابط + زر تبديل لغة + burger للموبايل.
2. Hero (id=home): eyebrow، عنوان كبير فيه كلمة بتدرّج (.grad)، نص، زرّان
   (عرض الأعمال / تواصل معنا)، 3 أرقام ثقة، وعنصر بصري = شعار ITX متحرك
   (SVG مثلثات دوّارة + حلقات + chips مدارية)، وشريط marquee لأسماء عملاء.
3. About (id=about): نص + شارة (badge) + صورة.
4. Services (id=services): 6 بطاقات (.card) بأيقونات SVG وعنوان ووصف ورابط.
5. Stats: 4 أرقام متحركة (counter) عبر data-target و .num.
6. Portfolio (id=portfolio): أزرار فلترة (.filter-btn data-filter) + شبكة مشاريع.
7. Testimonials (id=testimonials): بطاقات شهادات باقتباس ونجوم.
8. Blog (id=blog): بطاقات أحدث المقالات.
9. Contact (id=contact): نموذج تواصل (#contactForm) + معلومات.
10. Footer + زر العودة لأعلى (#scrollTop).

ميزة ثنائية اللغة (مهمة):
- كل نص قابل للترجمة عليه سمتان data-ar="..." و data-en="...".
- script.js فيه زر #langToggle يبدّل بين ar/en، يغيّر html.dir بين rtl/ltr،
  ويحفظ الاختيار في localStorage (itx_lang). حافظ على هذه الآلية لكل النصوص الثابتة،
  لكن المحتوى القادم من قاعدة البيانات (عربي فقط حالياً) ضعه في data-ar
  أو اتركه نصاً مباشراً حسب الأنسب.

تفاعلات script.js (يجب أن تظل تعمل بعد الدمج):
- حالة Navbar عند scroll، قائمة burger للموبايل، تفعيل الرابط النشط (scroll spy).
- Scroll reveal عبر IntersectionObserver لعناصر .reveal (و .d1/.d2.. للتأخير).
- عدّادات الأرقام .num[data-target].
- فلترة الأعمال .filter-btn.
- إرسال نموذج التواصل، وزر العودة لأعلى، وتبديل اللغة.

═══════════════════════════════════
■ بنية مشروعي الحالي (لا تكسرها)
═══════════════════════════════════
- index.php → الصفحة الرئيسية. تسحب المحتوى من قاعدة البيانات عبر PDO:
  site_settings, hero_section, about_section, services, statistics,
  testimonials, contact_info, social_media, blog_posts(+blog_categories).
  دوال: e() (escaping), gs() (قراءة إعداد), site_media_url() (مسار صورة), nl2p().
  متغيرات جاهزة: $hero,$about,$services,$statistics,$testimonials,$contact,
  $socials,$blog_preview,$settings ($site_name,$logo_url,$wa_number...إلخ).
- blog.php / blog-post.php / page.php → صفحات بنفس النمط.
- config/db.php → getDB() ودوال مساعدة.
- admin/ → لوحة تحكم تدير كل الأقسام أعلاه. (لا تغيّر منطقها)
- api/ → get_projects/get_project/get_categories.
- database/itx_db.sql → السكيمة.

═══════════════════════════════════
■ المطلوب بالضبط (خطوة بخطوة)
═══════════════════════════════════
1. اعمل git commit للحالة الحالية أولاً (نقطة رجوع)، وخذ نسخة index.php.bak.
2. اقرأ index.php بالكامل وافهم كل متغير ومخرج من قاعدة البيانات.
3. اقرأ new-design/ (HTML+CSS+JS) وافهم البنية والكلاسات.
4. انقل ملفات التصميم لأماكنها الصحيحة:
   - styles.css و script.js → مجلد assets/ (أو css/ js/) وحدّث روابطها.
   - assets/* و الشعار الجديد → مجلد الموقع، وحدّث الإعداد site_logo في
     site_settings ليشير للشعار الجديد (أو استبدل logo.jpeg مع إبقاء المرجع).
5. أعد كتابة طبقة العرض (HTML) في index.php لتطابق التصميم الجديد، مع استبدال
   كل محتوى placeholder الثابت بالمتغيرات الديناميكية الموجودة:
   - Hero: $h_title,$h_sub,$h_note,$h_btn1t/$h_btn1l,$h_btn2t/$h_btn2l.
   - About: $ab_heading,$ab_content (عبر nl2p),$ab_image_url,$ab_skills.
   - Services: حلقة foreach على $services (icon,title,description).
   - Stats: حلقة على $statistics مع data-target من القيمة الرقمية.
   - Testimonials: حلقة على $testimonials.
   - Blog: حلقة على $blog_preview (title,excerpt,image,cat_name,slug,date).
   - Contact: $ct_phone,$ct_email,$ct_address,$ct_wa,$ct_map + نموذج التواصل القائم.
   - Footer/social: حلقة على $socials و $footer_text، وزر واتساب $wa_number/$wa_msg.
   - استخدم e() لكل إخراج، وحافظ على meta/OG/JSON-LD وحدّثها بقيم قاعدة البيانات.
6. طبّق نفس الهيدر/الفوتر والتصميم على blog.php و blog-post.php و page.php.
7. تأكد أن آلية تبديل اللغة وكل تفاعلات script.js تعمل مع المحتوى الديناميكي.

═══════════════════════════════════
■ قواعد العمل
═══════════════════════════════════
- اشتغل ملف ملف وابدأ بـ index.php. أرني الـ diff قبل المتابعة للملف التالي.
- لا تحذف أي كود وظيفي ولا تلمس منطق admin/, api/, config/.
- لا تكتب نصوصاً ثابتة مكان البيانات الديناميكية — استبدلها بـ <?= e($var) ?>.
- بعد كل ملف: شغّل php -l للتأكد من خلوّه من أخطاء، وتحقق أن RTL سليم.
- في النهاية: أعطني قائمة بكل ملف عدّلته/أضفته + خطوات اختبار محلي على XAMPP.

ابدأ الآن: اعمل commit، اقرأ index.php و new-design/، ثم اعرض خطة الدمج قبل التنفيذ.
```

---

## 💡 ملاحظات سريعة

- التصميم الجديد **HTML/CSS/JS عادي** (مش React) — وده ممتاز، الدمج مع PHP مباشر وسهل.
- الشعار الجديد جاهز في `new-design/assets/` و `new-design/uploads/` — كلود كود هيربطه بإعداد `site_logo`.
- التصميم **ثنائي اللغة** أصلاً؛ محتواك في الداتابيز عربي، فالأبسط إنه يحطه في `data-ar` ويترك `data-en` فاضي أو نفس القيمة لحد ما تضيف ترجمة.
