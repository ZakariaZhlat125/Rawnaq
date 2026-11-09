# Homepage content

Dashboard: Content -> ????? ???????? (app/home-page-content), using the existing view_terms_and_conditions permission.
Controls desktop hero text, three mobile hero slides, shared booking/explore button labels, the About section including four features, and About/home-service images.
Arabic and English are required. Images accept JPEG/PNG/WebP, maximum 2 MB. Saving without a file preserves the image. Files live in Laravel public/uploads/home-page; configure the public asset host correctly.
Settings key: home_page_content. No migration. Home/all exposes home_content with both languages. Text is rendered as plain text; mobile HTML escapes it and both renderers exclude it from static dictionary replacement.
Mobile now includes the homepage About section. Image tags in rendered mobile screens default to native lazy loading; the first homepage hero image stays eager/high priority. The home-service background is an actual lazy image. Native browser loading distance varies by browser.
Deploy backend changes and frontend dist. The local build and browser tests use API fixtures; real dashboard upload/save requires the nested Laravel dependencies and database.

Checks: tests/home-content.browser.test.cjs (desktop/mobile, language, image sources, loading attributes); Laravel tests/Unit/HomePageContentSmoke.php; npm run build.
