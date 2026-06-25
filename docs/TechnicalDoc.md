# Technical Documentation

## Quick Start (Docker Network Example)

Docker example (use a `.env` file for MySQL environment variables):

```yaml
services:
  app:
    ports:
      - "0.0.0.0:80:80"

  db:
    ports:
      - "0.0.0.0:3306:3306"

  gui:
    ports:
      - "0.0.0.0:8080:80"
```

Start the application with:

```bash
docker compose up -d
```

## Project Structure

- `frontend/` — Pages, assets, and client-side scripts.
- `backend/` — Public actions (`*.php`) and OOP application code (`src/controllers`, `src/repositories`, `src/services`).
- `database/schema.sql` — Database schema.

## Example Dockerfile (Apache Server)

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql
```

## API Endpoints

- `backend/register_action.php` (POST) — User registration and session creation.
- `backend/get_profile_data_action.php` (GET) — Returns profile data and albums (JSON). Optional `id` parameter for public profiles.
- `backend/get_album_photos_action.php` (GET) — Retrieves photos from an album (`album_id`).
- `backend/get_follow_list_action.php` (GET) — Returns followers or following lists (`type=followers|following`, `profile_id=`).
- `backend/handle_comments_action.php` (GET/POST) — Handles comment retrieval, creation, and deletion depending on the request payload.
- `backend/get_albums_action.php` (GET) — Retrieves a user's albums (used by the dashboard and profile pages).
- `backend/search_photos_action.php` (GET) — Searches photos (`q=` parameter).

## Frontend API Calls

The frontend uses `fetch()` to communicate with PHP endpoints that return JSON responses.

Example:

```javascript
// Get profile data
fetch(`/backend/get_profile_data_action.php?id=${profileId}`)
  .then((r) => r.json())
  .then((data) => {
    /* display user and albums */
  });

// Add a comment
fetch("/backend/handle_comments_action.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ photo_id, content }),
})
  .then((r) => r.json())
  .then((resp) => {
    /* handle response */
  });
```

### Best Practices

- Always check `response.ok` and the `success` field in the JSON response.
- Handle network errors and display user-friendly messages.
- For file uploads (images), use `FormData` and do not manually set the `Content-Type` header.

## Database

The complete schema is available in `database/schema.sql`.

Main tables include:

- `users`
- `albums`
- `album_contributors`
- `photos`
- `comments`
- `favorites`
- `follows`
- `tags`

## Authentication and Session Management

- Authentication relies on `$_SESSION['user_id']`, which is created after registration or login.
- PHP pages verify the existence of `$_SESSION['user_id']` and redirect users when necessary.
- Controllers expose selected data to JavaScript through DOM bridge elements such as `#profile-bridge` and `#album-bridge`, using `data-*` attributes (e.g., `data-user-id`, `data-is-own`, `data-can-comment`).

## Important File References

### Frontend

- `frontend/pages/*`
- `frontend/assets/*`

### Backend

- `backend/*.php` — Public action endpoints
- `backend/src/controllers`
- `backend/src/repositories`
- `backend/src/services`

### Database

- `database/schema.sql`

### Deployment

- `compose.yaml`
- `Dockerfile`
