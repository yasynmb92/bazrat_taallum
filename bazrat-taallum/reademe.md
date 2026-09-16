# تقرير حالة التشغيل ومتطلبات المشروع
# Runtime Status and Requirements Report

**تاريخ التقرير / Report date:** 2026-09-13

## الحالة الحالية / Current Status

### العربية

المشروع يعمل حاليًا على خادم Apache المحلي من خلال الرابط:

`http://localhost/bazrat-taallum/`

تم إصلاح اتصال التطبيق بقاعدة البيانات المحلية. مستخدم التطبيق `bazrat_app` يملك الآن الصلاحيات المطلوبة على قاعدة البيانات `bazrat_taallum`. لم يتم تضمين كلمات المرور أو أي أسرار في هذا التقرير.

نتائج الاختبار:

- الصفحة الرئيسية والدورات وتفاصيل الدورة وصفحات الدخول والتسجيل: `HTTP 200`.
- صفحات السلة والدفع والتعلم والإدارة للزائر غير المسجل: إعادة توجيه متوقعة إلى `login.php` بحالة `HTTP 302`.
- جميع الصفحات العامة المختبرة خالية من أخطاء PHP الظاهرة.
- تم فحص 72 ملف PHP، وجميعها اجتازت فحص الصياغة.
- خدمات Apache وMySQL الخاصة بـ XAMPP تعمل محليًا.
- تم اختبار تسجيل الدخول فعليًا بالحساب المحلي `admin@seed-learning.com`، وتم الوصول إلى لوحة الإدارة `admin/index.php` بنجاح.
- تم إصلاح جدول `auth_login_attempts` بعد اكتشاف أن عمود `id` كان يفتقد `AUTO_INCREMENT`.

### English

The project is currently running on the local Apache server at:

`http://localhost/bazrat-taallum/`

The application database connection has been fixed. The application user `bazrat_app` now has the required privileges on the `bazrat_taallum` database. Passwords and secrets are intentionally excluded from this report.

Test results:

- Home, courses, course details, login, and registration pages: `HTTP 200`.
- Cart, checkout, learning, and admin pages for unauthenticated visitors: expected redirect to `login.php` with `HTTP 302`.
- All tested public responses are free of visible PHP errors.
- All 72 PHP files passed the syntax check.
- XAMPP Apache and MySQL services are running locally.
- Interactive login was tested with the local account `admin@seed-learning.com`, and the admin dashboard `admin/index.php` loaded successfully.
- The `auth_login_attempts` table was fixed after detecting that its `id` column was missing `AUTO_INCREMENT`.

## المتطلبات / Requirements

### العربية

- نظام Windows.
- XAMPP يتضمن:
  - Apache.
  - MySQL أو MariaDB.
  - PHP 8.2 أو إصدار متوافق.
- إضافات PHP المطلوبة:
  - `mysqli`
  - `PDO`
  - `pdo_mysql`
  - `mbstring`
  - `curl`
  - `openssl`
  - `fileinfo`
- قاعدة بيانات باسم `bazrat_taallum`.
- ملف `.env` يحتوي على إعدادات التطبيق وقاعدة البيانات والبريد الإلكتروني.
- صلاحيات كتابة لمجلد `assets/uploads` عند استخدام رفع الملفات.

### English

- Windows.
- XAMPP including:
  - Apache.
  - MySQL or MariaDB.
  - PHP 8.2 or a compatible version.
- Required PHP extensions:
  - `mysqli`
  - `PDO`
  - `pdo_mysql`
  - `mbstring`
  - `curl`
  - `openssl`
  - `fileinfo`
- A database named `bazrat_taallum`.
- A `.env` file containing application, database, and mail settings.
- Write permission for `assets/uploads` when file uploads are enabled.

## التشغيل المحلي / Local Startup

### العربية

1. شغّل Apache وMySQL من لوحة تحكم XAMPP.
2. ضع المشروع داخل `C:\xampp\htdocs\bazrat-taallum`.
3. راجع ملف `.env` وتأكد من صحة `DB_HOST` و`DB_USER` و`DB_PASS` و`DB_NAME`.
4. افتح:

   `http://localhost/bazrat-taallum/`

5. لفحص الصياغة باستخدام PHP الخاص بـ XAMPP:

   ```powershell
   Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
   ```

### English

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Place the project in `C:\xampp\htdocs\bazrat-taallum`.
3. Check `.env` and verify `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME`.
4. Open:

   `http://localhost/bazrat-taallum/`

5. To run the PHP syntax check with the XAMPP PHP binary:

   ```powershell
   Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
   ```

## ملاحظات أمنية / Security Notes

### العربية

- لا ترفع ملف `.env` إلى Git أو إلى خادم عام.
- لا تستخدم حساب root في بيئة الإنتاج.
- استخدم كلمة مرور قوية وفريدة لمستخدم قاعدة البيانات.
- استخدم HTTPS في الإنتاج.
- اجعل صلاحيات الكتابة مقتصرة على `assets/uploads`.

### English

- Never commit `.env` to Git or expose it on a public server.
- Do not use the root database account in production.
- Use a strong, unique password for the application database user.
- Use HTTPS in production.
- Restrict write permissions to `assets/uploads` only.

## ملاحظة عن اختبارات HTTP / HTTP Test Note

ملفات الاختبار الحالية تستخدم مسارًا قديمًا هو `http://localhost/learning-seeds2/bazrat-taallum`. عند تشغيلها على هذا التثبيت، يجب استخدام المسار الحالي:

`http://localhost/bazrat-taallum`

The existing HTTP test scripts use the older base URL `http://localhost/learning-seeds2/bazrat-taallum`. For this installation, use the current base URL:

`http://localhost/bazrat-taallum`

## إعادة الاختبار التفاعلي / Interactive Retest

### العربية

تم تنفيذ اختبار تفاعلي جديد بعد تسجيل الخروج، وشمل:

- تسجيل الخروج ثم التأكد من أن الصفحات المحمية تعيد الزائر إلى تسجيل الدخول.
- إنشاء مستخدم تجريبي جديد، وتأكيد البريد باستخدام رمز التفعيل المحلي، ثم تسجيل الدخول.
- تسجيل المستخدم في دورة مجانية.
- فتح الدرس، إرسال اختبار الدورة بإجابات صحيحة، والحصول على نتيجة `100% (passed)`.
- إكمال الدرس والتحقق من وصول التقدم إلى `100%`.
- اختبار صفحات «تعلمي»، الملف الشخصي، السلة، الدفع، والدرس دون أخطاء ظاهرة.
- فحص صفحات الإدارة للزائر والتأكد من حمايتها بإعادة التوجيه إلى تسجيل الدخول.

أثناء الاختبار تم إصلاح مشكلات توافق في قاعدة البيانات، كما تم إصلاح إعادة التوجيه في `learn.php` التي كانت تسبب تحذير `Cannot modify header information` عند إرسال الاختبار.

ملاحظة: إرسال البريد الخارجي لم يكتمل لأن `smtp.example.com` قيمة تجريبية غير قابلة للوصول محليًا. تم اختبار التفعيل باستخدام رمز صندوق البريد المحلي. كما لم يتم تنفيذ شراء مدفوع لأن دورة الاختبار مجانية ولا يوجد بوابة دفع محلية مهيأة.

### English

A new interactive retest was completed after logout, covering:

- Logout followed by verification that protected pages redirect visitors to login.
- Creation of a new test user, local email-code verification, and user login.
- Enrollment in a free course.
- Opening the lesson, submitting the course quiz with correct answers, and receiving `100% (passed)`.
- Completing the lesson and verifying `100%` course progress.
- Testing My Learning, profile, cart, checkout, and lesson pages without visible errors.
- Checking admin pages as a guest and confirming that they are protected by redirecting to login.

Database compatibility issues found during the test were fixed. The POST redirect flow in `learn.php` was also fixed; it previously caused a `Cannot modify header information` warning when submitting the quiz.

Note: External email delivery was not completed because `smtp.example.com` is a placeholder that is unreachable locally. Verification was tested using the local outbox code. A paid purchase was not executed because the test course is free and no local payment gateway is configured.

## كتالوج الدورات والبريد / Course Catalog and Email

### العربية

تمت مزامنة الدورات الموجودة في `bazrat_taallum.sql` من خلال:

`database/seeds/bazrat_dump_courses_seed.php`

وتشمل المزامنة 14 دورة منشورة، موزعة على الأعمار `8-12` و`13-15` و`16-18`، مع ملكية المستخدم الإداري الرئيسي. لكل دورة ثلاث محاضرات خارجية مناسبة للفئة العمرية، واختبار نهائي شامل من خمسة أسئلة، ومشروع تطبيقي بمعايير تقييم قابلة للتعديل.

يتم تشغيل المزامنة تلقائيًا عند إعداد قاعدة جديدة عبر:

`database/migrations/catalog_email_2026.php`

قالب رسالة التسجيل الأول موجود في `includes/system_settings.php`، ويحتوي على الترحيب، رابط المنصة، رمز التحقق، كلمة المرور المؤقتة، تنبيه أمني، وتوقيع المنصة. يستطيع الأدمن الرئيسي تعديله من:

`admin/settings.php`

وتأكد الاختبار من ظهور عنوان الرسالة والتوقيع وحقول القالب في لوحة الأدمن.

### English

The courses represented in `bazrat_taallum.sql` are synchronized through:

`database/seeds/bazrat_dump_courses_seed.php`

The synchronization provides 14 published courses across ages `8-12`, `13-15`, and `16-18`, owned by the main administrator. Each course has three age-appropriate external lectures, a five-question comprehensive final quiz, and an editable practical project rubric.

The synchronization runs automatically for a new database through:

`database/migrations/catalog_email_2026.php`

The first-registration email template is defined in `includes/system_settings.php`. It includes the welcome message, platform link, verification code, temporary password, security notice, and platform signature. The main administrator can edit it from:

`admin/settings.php`

The admin browser test confirmed that the subject, signature, and template fields are visible and editable.

## اختبار الأزرار والدفع / Button and Payment Testing

### العربية

تم اختبار المسارات الإدارية الرئيسية بحساب الأدمن الرئيسي، وجميع الصفحات المختبرة أعادت `HTTP 200` دون أخطاء ظاهرة. تم تنفيذ CRUD فعلي على دورة مؤقتة: إنشاء دورة، إضافة محاضرة، تعديل المحاضرة، حذف المحاضرة، ثم حذف الدورة. كما تم اختبار إضافة كوبون وتعديله، وتطبيق كوبون على السلة.

تم اختبار الدفع الحالي من البداية إلى النهاية باستخدام دورة مدفوعة وكوبون:

- السعر قبل الخصم: `$29.00`.
- الخصم: `$5.00`.
- الإجمالي: `$24.00`.
- بوابة الدفع الحالية: `sandbox` داخلية.
- النتيجة: الطلب `paid`، الدفعة `success`، والتسجيل `active`، وتم فتح أول درس للطالب.

مهم: هذه ليست بوابة دفع حقيقية بعد. لا يوجد تكامل Stripe أو PayPal أو بوابة بنكية، ولا يتم تحصيل أموال فعلية. يلزم إضافة مزود دفع ومفاتيح API وwebhook للتحقق من الدفع قبل الاستخدام الإنتاجي.

تم أيضًا إصلاح مشاكل ظهرت أثناء الاختبار: إعادة التوجيه بعد POST في صفحات الدخول والدورة والسلة والدفع، إنشاء دورة بدون مسار، تشغيل روابط المحاضرات الخارجية، تكرار الدروس، تكرار سجلات الدفع، ومفتاح سجل تدقيق الأدمن.

لم يتم الضغط على كل زر تدميري أو كل حالة خطأ ممكنة بشكل منفصل؛ تم اختبار الصفحات كاملة وأزرار CRUD الأساسية ومسار الدفع الفعلي، مع ترك البيانات الأصلية دون حذف.

### English

The main admin routes were tested with the main administrator account. All tested pages returned `HTTP 200` without visible errors. A real CRUD flow was executed on a temporary course: create course, add lecture, edit lecture, delete lecture, then delete course. Coupon creation and editing were also tested, as well as applying a coupon in the cart.

The current payment flow was tested end to end with a paid course and coupon:

- Price before discount: `$29.00`.
- Discount: `$5.00`.
- Final total: `$24.00`.
- Current gateway: internal `sandbox`.
- Result: order `paid`, payment `success`, enrollment `active`, and the first lesson opened for the student.

Important: this is not a real payment gateway yet. There is no Stripe, PayPal, or bank gateway integration, and no real money is charged. A production integration requires a payment provider, API keys, and a verified webhook.

Not every destructive button and every possible error state was clicked independently; all main admin routes, core CRUD buttons, and the end-to-end payment path were tested while preserving the original catalog data.

## مشغل المحاضرات الداخلي / Internal Lecture Player

### العربية

تم إصلاح تشغيل المحاضرات داخل الموقع. روابط القنوات مثل `youtube.com/@...` لا تمثل فيديو محددًا ولا يمكن تشغيلها داخل `iframe`، لذلك تم تحويل المحاضرات المزروعة التجريبية إلى ملف MP4 محلي يعمل عبر مشغل HTML5 داخل صفحة الدرس. تم التحقق من:

- ملف الفيديو المحلي يعيد `HTTP 200` ونوع `video/mp4`.
- المحاضرات الثلاث في دورة الاختبار تعرض `<video controls>` داخل الموقع.
- لا يظهر زر «فتح المحاضرة الخارجية» للمحاضرات الداخلية.
- روابط YouTube/Vimeo المحددة بصيغة فيديو أو `embed` ما زالت مدعومة، وتظهر داخل `iframe` الموقع.

الفيديو المحلي الحالي هو مادة تجريبية مشتركة للبيانات المزروعة. لاستبداله بمحاضرات حقيقية منفصلة، يستخدم الأدمن صفحة `admin/lessons.php` أو `admin/lesson-edit.php` ويرفع ملف MP4 أو يضع رابط فيديو محددًا، وليس رابط قناة.

### English

Lecture playback inside the site has been fixed. Channel URLs such as `youtube.com/@...` are not specific videos and cannot be played inside an `iframe`, so the seeded demo lectures now use a local MP4 served by the platform's native HTML5 player. Verified:

- The local video returns `HTTP 200` with `video/mp4`.
- All three lessons in the test course render a `<video controls>` element inside the site.
- The external-lecture button is not shown for internal lessons.
- Specific YouTube/Vimeo video or `embed` URLs remain supported inside the platform iframe.

The current local video is shared demo content for the seeded catalog. For distinct real lectures, the main administrator can use `admin/lessons.php` or `admin/lesson-edit.php` to upload an MP4 or enter a specific video URL, rather than a channel URL.

## إصلاحات اختبار الإدارة / Admin Retest Fixes

### العربية

أثناء اختبار شامل لاحق، تم اكتشاف وإصلاح خطأ في `admin/user-edit.php` كان يمنع حفظ بيانات المستخدم بسبب عدم تطابق أنواع `bind_param` مع المتغيرات. كما تم إصلاح ظهور رسائل النجاح بعد تعديل الفئات والدورات والكوبونات، وإصلاح ترتيب معالجة POST في صفحة تعديل الفئات.

تمت إعادة اختبار تعديل مستخدم وتعديل فئة من المتصفح بنجاح، ثم فحص 20 صفحة إدارية بحالة `HTTP 200` ودون أخطاء PHP ظاهرة.

### English

During a later comprehensive admin retest, a defect in `admin/user-edit.php` was found and fixed. User updates failed because the `bind_param` type definition did not match the bound variables. Success messages after editing categories, courses, and coupons were also fixed, and POST processing was moved before output in the category editor.

User editing and category editing were retested successfully in the browser. Twenty admin routes were then checked and returned `HTTP 200` without visible PHP errors.
