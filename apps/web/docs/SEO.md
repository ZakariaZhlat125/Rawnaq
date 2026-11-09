# Frontend SEO

## Dashboard

Open /app/frontend-seo (إعدادات SEO). Authentication and the existing
view_terms_and_conditions permission are required, matching the other frontend
content editors.

Choose a page and save its Arabic/English Title, Meta Description and Meta Keywords. Empty
fields use the displayed defaults. Maximum lengths are 160 and 320 characters.
Keywords accept up to 1000 characters, separated by commas. Empty keywords omit
the tag; navigating to a page without keywords removes the previous page’s tag.
Only the selected page is updated.

The list includes fixed Vue routes, active service categories and published
blog posts. Product and package detail routes do not exist in the current router.
Settings use the existing frontend_seo setting; no migration is needed.

## Runtime

GET /api/seo?path=/services/3&lang=en resolves saved values and content defaults.
Unknown/unpublished pages return 404. API responses are not persistently cached.
Reload the frontend after saving dashboard settings to see updated values.

src/composables/useSeo.js follows Vue navigation and language changes.
public/site-seo.js is shared with both mobile entry points. Iframe messages are
checked against the expected origin and source. Outdated requests cannot replace
newer page metadata. Failures leave localized fallback tags. Metadata is assigned
as text/attributes, not interpreted as HTML.

Run npm.cmd run build to update dist after changing code. Dashboard metadata
changes do not require rebuilding.

## Initial HTML

The current deployment serves a static Vite HTML shell through .htaccess.
Configured per-page tags are applied by JavaScript. Crawlers and preview services
that do not execute JavaScript will receive the generic initial fallback.

Per-page tags in the initial HTTP response require server rendering or a
prerendering integration in frontend hosting. AppServicesFrontendSeo::resolve
can be reused for this, with escaped template output. This implementation does
not change frontend hosting or implement SSR.

## Verification

- PHP syntax checks: configuration, service, controller and routes.
- php -d opcache.enable_cli=0 tests/Unit/FrontendSeoSmoke.php from sami-care: 16 isolated checks using
  storage doubles; does not write to a database.
- tests/seo.browser.test.cjs: 5 tests for navigation, language, both mobile
  entries, iframe synchronization, stale responses, safe output and fallbacks.
  Set PLAYWRIGHT_MODULE and LANGUAGE_TEST_URL to a Vite server.
- Production build completed.

Laravel vendor is absent locally, so a real dashboard/database save and framework
integration checks could not be run.