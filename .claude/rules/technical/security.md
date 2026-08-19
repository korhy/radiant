---
description: Security rules — authorization, secrets, input sanitization, auth lifecycle, third-party API credentials.
paths:
  - "**/*.php"
  - "**/config/packages/security.yaml"
  - "**/config/packages/*.yaml"
  - "**/templates/**/*.twig"
  - "**/.env*"
---

# Security

> Companion to [backend-php.md](backend-php.md). Security concerns are never optional — apply these
> rules to every controller, form, entity and template.

## Authorization (never bypass)
- **Always** enforce access through Symfony's security layer: `#[IsGranted]` attributes,
  `access_control` in `security.yaml`, voters, or `$this->isGranted()` / the `Security` service.
- Never hand-roll role checks with raw string comparisons in business code.
- The firewall gates `^/admin` to `ROLE_ADMIN`; **everything else is public**. That makes any new
  non-admin route publicly reachable by default — decide explicitly, and add
  `#[IsGranted('ROLE_ADMIN')]` (defence in depth) to anything that manages content.
- When authorization depends on the object, write a **Voter** rather than inlining the check. There
  are none yet — the first one sets the pattern.

## Authentication
- Users authenticate via `form_login` (`app_login` / `app_logout`) against `App\Entity\Admin`,
  with the provider resolving on **`username`**. CSRF on the login form stays enabled
  (`enable_csrf: true`).
- Passwords: never store or compare plaintext. Hashing is `auto` via the security bundle
  (`PasswordAuthenticatedUserInterface`); set the hashed value through the password hasher, never a
  raw column write.
- `eraseCredentials()` must clear any transient plaintext.

## Input handling & injection
- All user input passes through a **Form Type + Validator** (Assert constraints on the entity or a
  DTO) — `ContactType` + `ContactDTO` is the model. Never trust `$request` data directly.
- Public JSON endpoints (`cookbook_recipes_json`, `motus_guess`) take query/body parameters straight
  from the request: **cast and clamp them** the way `CookbookController` already does
  (`max(1, (int) $request->query->get('page', 1))`). Never forward a raw user value into an
  upstream API call or a query without validating it.
- Any HTML / rich text that will be rendered must be sanitized with `symfony/html-sanitizer` before
  persisting or rendering with `|raw`. See https://symfony.com/doc/current/html_sanitizer.html
- Doctrine: always use parameter binding (`setParameter`), never string-concatenate user input into
  DQL/SQL.
- CSRF protection stays enabled on all state-changing forms (default). Do not disable it for
  convenience.

## Secrets
- No secrets in code, fixtures, or templates. Use environment variables via `symfony/dotenv`.
- `.env` holds **non-secret defaults only**. Real values go in `.env.local` (git-ignored) — never
  commit them.
- **`COOKBOOK_API_USERNAME` / `COOKBOOK_API_PASSWORD`** are injected with `#[Autowire(env: …)]`
  into `CookbookApiService`. The JWT it obtains is cached under `cookbook_api_token`. Never log the
  credentials or the token, never echo them into a template or an exception message, and never widen
  their injection beyond that service.
- Deploy secrets (`SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, `DEPLOY_PATH`) live in GitHub Actions
  secrets — never inline them in a workflow.

## Output / templates
- Rely on Twig auto-escaping; only use `|raw` on content sanitized server-side.
- The four `App` JSON columns are **admin-authored** and rendered in the drawer — they are still
  data, so keep them escaped.
- Do not expose internal IDs / PII beyond what the view needs.
