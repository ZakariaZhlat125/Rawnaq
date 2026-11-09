# Service detail content

Dashboard: main services/categories list -> محتوى الصفحة beside the edit button.
The editor lives at /app/categories/{id}/page-content and requires edit_category.
It edits why-choose cards, benefits/image, FAQs and the closing booking banner.
Each text has Arabic and English fields. Rows can be added/deleted, and each
section can be hidden. Blank heading fields use the current service name.
The benefits image accepts an HTTPS/HTTP URL or a root-relative path; blank uses the service image.
The closing banner uses a file picker (JPEG, PNG or WebP, maximum 2 MB).
Saving without a new file preserves the existing banner image. Uploads are stored
in Laravel public/uploads/category-pages and exposed through an absolute asset URL.

Defaults are restored from archive/sami2-main/src/data/serviceDetails.js and
stored in resources/data/service-page-defaults.json in the Laravel project.
English translations accompany the original Arabic copy.
Edits use the existing settings table: category_page_{category id}. No migration
or manual database seed is necessary. Other category fields remain unchanged.

Home/categories and Home/all include page_content once per main category.
The shared service-page-content renderer and CSS drive Vue service details and
both mobile HTML entries. An older API without page_content uses bundled
legacy defaults until backend changes are deployed.

Deploy backend files and the rebuilt frontend dist together for dashboard edits
to reach the frontend. Dashboard edits take effect on the next content load;
no frontend rebuild is needed for individual content updates.

Tests:
- php -d opcache.enable_cli=0 tests/Unit/CategoryPageContentSmoke.php (isolated storage doubles)
- tests/service-content.browser.test.cjs with Playwright and LANGUAGE_TEST_URL
- production build and PHP/JavaScript syntax checks

Live dashboard/database saving requires the Laravel dependencies, unavailable
in the local nested backend vendor directory during this change.