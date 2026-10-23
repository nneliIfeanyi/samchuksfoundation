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

2. Set `window.EVENTS_ASSETS_BASE_URL` in the same file to the public project asset root, with a trailing slash. It is configured separately so image files can be served from the API host even if the API endpoints use a different path:

   ```js
   window.EVENTS_ASSETS_BASE_URL = "https://api.leadstar.com.ng/samchuks/";
   ```

   Seed records keep portable relative paths such as `assets/img/new/orph.jpeg`; the pages resolve them to `https://api.leadstar.com.ng/samchuks/assets/img/new/orph.jpeg`. New admin uploads are written by PHP to `<project-root>/assets/uploads/events/` and their returned relative paths resolve to `https://api.leadstar.com.ng/samchuks/assets/uploads/events/<filename>`. Ensure that directory is writable by PHP and publicly serves images.

3. In the API host's `api/config.local.php`, set `cors_origins` to the exact origin(s) of the public site and admin UI. Include scheme and port if non-default; do not include a path or trailing slash:

   ```php
   'cors_origins' => [
       'https://www.example.org',
       'https://admin.example.org',
   ],
   ```

   The API returns credentialed CORS headers only for configured origins and handles preflight requests for `GET`, `POST`, `PUT`, and `DELETE`. It permits only the `Accept`, `Content-Type`, and `X-CSRF-Token` request headers; wildcard origins are not supported.

4. Keep the API on HTTPS. If the admin frontend and API are on different sites (not just different subdomains of the same site), set `'session_samesite' => 'None'` in the API config. This requires HTTPS; same-site deployments should retain the more restrictive `'Strict'` default. Browser third-party-cookie restrictions may still prevent admin sessions across unrelated domains, so hosting the admin UI and API on the same site is recommended.

The admin API uses credentialed session cookies and CSRF tokens for changes. On a deployed site, use HTTPS, a dedicated MySQL account with access only to this database, and configure PHP upload limits to at least 5 MB.

## Endpoints

- `GET /api/events.php` — published events.
- `GET /api/events.php?limit=3&featured=1` — featured published events for the landing page.
- `GET /api/events.php?slug=event-slug` — one published event.
- `GET /api/auth.php` — current admin session status.
- `POST /api/auth.php` — admin login; accepts JSON `{ "email": "...", "password": "..." }`.
- `DELETE /api/auth.php` — admin logout; requires the `X-CSRF-Token` header.
- `GET|POST|PUT|DELETE /api/admin-events.php` — admin event management; writes require `X-CSRF-Token`.
- `POST /api/upload.php` — upload up to 10 JPEG, PNG, WebP, or GIF images (5 MB each); requires admin session and CSRF token.
