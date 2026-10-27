# Events API setup (XAMPP)

1. Start Apache and MySQL in the XAMPP Control Panel.
2. In phpMyAdmin, import `api/schema.sql`, then import `api/seed-events.sql` to move the current event content into the database. The seed uses stable slugs to preserve the existing `events.html#...` links and is safe to import again.
3. Copy `api/config.local.php.example` to `api/config.local.php` and set the MySQL credentials for this machine. Do not commit `config.local.php`.
4. From the project root, create an administrator using PHP CLI:

   ```text
   php api/create-admin.php admin@example.com
   ```

   Enter a unique password of at least 12 characters when prompted.
5. Open the site through Apache, not as a `file://` URL. The public pages call the API at `/api/`; the admin page is `admin/`.

By default, the frontend uses the same-origin API path. To host the API separately:

1. Set `window.EVENTS_API_BASE_URL` in `assets/js/api-config.js` to the directory from which the API endpoint files resolve, with a trailing slash. The clients append endpoint filenames such as `events.php` and `auth.php`. For example:

   ```js
   window.EVENTS_API_BASE_URL = "https://api.example.org/api/";
   ```

   The current value `https://api.leadstar.com.ng/samchuks/` therefore requests `https://api.leadstar.com.ng/samchuks/events.php`. If `/samchuks` is the deployed project root and the PHP files remain in its `api` subfolder, use `https://api.leadstar.com.ng/samchuks/api/` instead. Use the shorter value only if the host or rewrite rules expose the PHP endpoints directly under `/samchuks/`.

2. Set `window.EVENTS_ASSETS_BASE_URL` in the same file to the public website's asset root, with a trailing slash. Set `window.EVENTS_UPLOADS_BASE_URL` to the API host's project root where PHP writes uploaded files:

   ```js
   window.EVENTS_ASSETS_BASE_URL = "https://samchuksfoundation.com/";
   window.EVENTS_UPLOADS_BASE_URL = "https://api.leadstar.com.ng/samchuks/";
   ```

   Seed records keep portable relative paths such as `assets/img/new/orph.jpeg`; the pages resolve them to `https://samchuksfoundation.com/assets/img/new/orph.jpeg`. New admin uploads are written by PHP to `<API-project-root>/assets/uploads/events/` and their returned relative paths resolve to `https://api.leadstar.com.ng/samchuks/assets/uploads/events/<filename>`. When the PHP endpoint files are deployed in an `api` subfolder, the project root is its parent; when deployed directly in `/samchuks/`, that directory is the project root. Ensure the resulting uploads directory is writable by PHP and publicly serves images.

3. In the API host's `api/config.local.php`, set `cors_origins` to the exact origin(s) of the public site and admin UI. Include scheme and port if non-default; do not include a path or trailing slash:

   ```php
   'cors_origins' => [
       'https://www.example.org',
       'https://admin.example.org',
   ],
   ```

   The API returns credentialed CORS headers only for configured origins and handles preflight requests for `GET`, `POST`, `PUT`, and `DELETE`. It permits only the `Accept`, `Content-Type`, and `X-CSRF-Token` request headers; wildcard origins are not supported.

4. Keep the API on HTTPS. If the admin frontend and API are on different sites (not just different subdomains of the same site), set `'session_samesite' => 'None'` in the API config. This requires HTTPS; same-site deployments should retain the more restrictive `'Strict'` default. Browser third-party-cookie restrictions may still prevent admin sessions across unrelated domains, so hosting the admin UI and API on the same site is recommended.

The admin API uses credentialed session cookies and CSRF tokens for changes. Image uploads require PHP GD with WebP support. JPEG, PNG, and WebP uploads are center-cropped to a 4:3 aspect ratio, resized to fill a 1600 x 1200 canvas, then saved as WebP at quality 82. This fills the image container without blank bands, but can trim the edges of wide or tall images. File-size savings depend on the source image. Each event is limited to 4 distinct images total, including its cover. On a deployed site, use HTTPS, a dedicated MySQL account with access only to this database, and configure PHP upload limits to at least 5 MB.

Public event listings put the most recently updated published event first. Publishing a draft updates its timestamp, moving it to the top of the list.

## Endpoints

- `GET /api/events.php` — published events.
- `GET /api/events.php?limit=3&featured=1` — featured published events for the landing page.
- `GET /api/events.php?slug=event-slug` — one published event.
- `GET /api/auth.php` — current admin session status.
- `POST /api/auth.php` — admin login; accepts JSON `{ "email": "...", "password": "..." }`.
- `DELETE /api/auth.php` — admin logout; requires the `X-CSRF-Token` header.
- `GET|POST|PUT|DELETE /api/admin-events.php` — admin event management; writes require `X-CSRF-Token`.
- `POST /api/upload.php` — upload up to 4 JPEG, PNG, or WebP images (5 MB each); images are resized and converted to WebP; requires admin session and CSRF token.
