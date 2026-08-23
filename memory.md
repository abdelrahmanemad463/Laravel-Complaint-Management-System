# Project Memory

## PWA implementation

The Complaint Desk Laravel application now includes production-safe Progressive Web App support. The implementation is intentionally limited to installability, app-like display, branded icons, static-asset caching, and graceful network failure behavior. Business logic, database tables, authentication flows, and complaint workflows were not changed.

### Added files and metadata

| Area | Implementation |
|---|---|
| Web app manifest | `public/manifest.json` with name `Complaint Desk`, short name `Complaints`, standalone display, relative `start_url` and `scope`, theme/background colors, responsive orientation, categories, and any/maskable icons. |
| Icons | `public/icons/icon-180x180.png`, `icon-192x192.png`, `icon-512x512.png`, and `icon-512x512-maskable.png`. |
| Service worker | `public/service-worker.js`, registered at the application base path. |
| Registration | The compiled `resources/js/app.js` registers the worker after page load, uses the manifest URL to calculate the correct subdirectory scope, and requests `updateViaCache: 'none'` plus an explicit update check. |
| HTML metadata | `resources/views/layouts/app.blade.php` now includes manifest, theme-color, mobile-web-app, Apple mobile-web-app, favicon, and Apple touch-icon metadata. |
| Verification | `tests/Feature/PwaTest.php` verifies layout metadata, manifest fields, icon/service-worker files, and the static-only service-worker boundary. |

### Caching and security policy

The service worker caches only hashed Vite assets under `build/` and static icon files under `icons/`. It does not intercept or cache HTML navigations, Laravel routes, API responses, login pages, session data, CSRF data, or authenticated/private content. This keeps authentication and user-specific information network-controlled and avoids serving stale private pages.

The Vite manifest is fetched with `cache: 'no-store'` during service-worker installation and is network-only at runtime. The worker uses the current versioned cache name `complaint-desk-static-v2` and deletes older versions during activation. The registration uses `updateViaCache: 'none'` and calls `registration.update()` so new deployments can obtain changed hashed assets rather than remaining on stale application bundles. `public/.htaccess` also sends no-cache headers for `manifest.json` and `service-worker.js`.

When the network is unavailable, the application remains a normal network-dependent Laravel application. Static CSS, JavaScript, and icon requests may still be served from the cache, while private pages are not fabricated from cached data. Full offline complaint creation, authentication, synchronization, and database access are intentionally not implemented yet.

### Installation and deployment notes

Installability requires the site to be served from HTTPS in production. `localhost` is treated as a secure development origin by supported browsers. The application must be built with `npm run build` before deployment so `public/build/manifest.json` and its hashed assets exist when the service worker installs.

On supported desktop browsers, the browser may show an install icon in the address bar or application menu. On Android browsers, the browser may offer “Add to Home screen” or “Install app.” On iOS, installation is performed through Safari’s Share menu where supported; iOS support is subject to the platform’s browser and operating-system limitations.

### Verification status

The PWA implementation was verified by compiling Blade views, successfully building Vite assets, validating manifest/icon/service-worker files through feature tests, and running the complete Laravel suite. Current result: **20 tests passed with 70 assertions**. Apache served the manifest, service worker, and icons successfully; the service-worker script passed Node syntax validation; and the service-worker/manifest responses returned no-cache headers. The service-worker route and application layout remain compatible with normal non-installed browser use. Direct visual browser testing from the sandbox could not connect to the user’s Windows `localhost`; the Windows XAMPP HTTP checks and Laravel tests passed.

## Role lifecycle and users administration

The roles administration page now supports creating custom roles through `roles.store` and deleting custom roles through `roles.destroy`. Creation requires `role.create`, uses the `web` guard, rejects duplicate and reserved baseline names, and leaves new roles with no permissions until configured. Deletion requires `role.delete`, is blocked for the protected baseline roles (`Super Admin`, `Admin`, `Customer Support`, and `Viewer`), and is also blocked server-side whenever the role is assigned to any user. The roles list displays permission and assigned-user counts and exposes only safe delete actions in the interface.

The users page now provides a GET filter for name/email text and an exact role selection. User results are eager-loaded with roles, ordered by name, paginated at 20 records, and retain filter query strings across pagination. The role filter options are loaded separately from the paginated user query, so the page does not load all users into memory.

The regression suite covers role creation, deletion of an unused role, refusal to delete an assigned role, and users-page text and role filters. After the latest changes, the complete suite reports **24 tests passed with 88 assertions**.
