---
description: Naming conventions — English identifiers and comments everywhere, framework casing rules. French only for user-facing text.
paths:
  - "**/*.php"
  - "**/*.twig"
  - "**/*.js"
  - "**/config/**/*.yaml"
  - "**/*.css"
---

# Naming conventions

> **All code identifiers are in English.** French is reserved for **user-facing text only**
> (labels, buttons, headings, flash messages, emails). In this project that text is written
> **directly in the templates** — but it is still text, never an identifier name.

## English, always — the code

Everything a developer names is **English** and follows the framework's casing:

| Kind | Convention | Example |
|---|---|---|
| Class / interface / enum / trait | `PascalCase`, English | `CookbookApiService`, `PersonalProjectRepository` |
| Method / function | `camelCase`, verb-first, English | `findBySlug()`, `getWordOfTheDay()` |
| Variable / property / argument | `camelCase`, English | `$startDate`, `$techStack` |
| Constant / enum case | `UPPER_SNAKE_CASE` | `ROLE_ADMIN`, `WORDS` |
| Route **name** | `snake_case`, English, **no prefix** | `cookbook_recipes_json`, `motus_guess` |
| Route **path** | `kebab`/lowercase, English | `/app/cookbook/recipes` |
| Doctrine entity + column | English (`camelCase` property → framework maps the column) | `techStack`, `updatedAt` |
| Twig template / partial | `snake_case` file, `_` prefix for partials | `templates/components/_icon_motus.html.twig` |
| Stimulus controller file | `snake_case` file, `kebab-case` identifier | `motus_controller.js` → `app.register('motus', …)` |
| CSS class you author | `kebab-case`, English (rare — prefer utilities) | `.motus-tile` |

- **No French, no franglais, no abbreviations** in identifiers: not `$dateDebut`, not
  `getRecettes()`, not `$projetcs`. Use `$startDate`, `getRecipes()`, `$projects`.
- **No transliteration of accents** in code (`annee`, `duree`) — translate the concept
  (`year`, `duration`).
- Booleans read as predicates: `isRemote`, `hasNextPage`, `canEdit`.
- Collections are plural (`$recipes`); a single item is singular (`$recipe`).

> **Route names have no prefix here.** `homepage`, `taquin`, `cookbook`, `contact` — only
> `app_login`/`app_logout` carry `app_`, inherited from `make:auth`. Match the surrounding
> controller; don't introduce a new prefix scheme in a feature change.

## Comments — English, and only when they earn their place

Write every comment in **English**, whatever the syntax: `//`, `#`, `/* */`, `{# #}`. A comment is
addressed to a developer, so the French exception below never applies to it.

Keep a comment only when it carries what the surrounding code cannot show:

- an external constraint — `SPF and DKIM can only sign our own domain`;
- a decision and the option it rejects — `503 rather than an empty payload, so the client can tell
  an outage from "no result"`;
- a trap that costs a debugging session — `Messenger routes mails to an async transport: the
  message is queued, so assertEmailCount sees nothing`.

Delete the rest. A comment that paraphrases the next line, restates the name of the test it sits on,
or narrates history (`this used to be…`) is noise to maintain. Before committing, re-read each
comment you added and ask what it teaches that the code does not.

## French — the words users read

Labels, buttons, headings, validation messages and emails are **French**, authored inline in the
Twig templates. `translations/` holds **no catalogue** today, even though `default_locale` is `en`.
Introducing i18n is a decision of its own — don't start it as a side effect of another change.

## Migrating existing names

The codebase mixes casing on entity properties: `App::$techStack` (camelCase) against
`Experience::$start_date`, `PersonalProject::$file_name` and `$updated_at` (snake_case). The
standard is **`camelCase`**. When you touch such code:

- **Rename toward the convention** if the rename is safely scoped — update every usage, and add a
  Doctrine migration if a **column** name moves (not just the property).
- If a rename is too wide to do safely in the current change, leave a `// TODO(naming):` note and
  raise it — don't propagate the old style into new code.
- New code follows this rule from the start; consistency within a file wins for tiny local edits,
  the convention wins for anything new.

## See also
- Backend conventions: [backend-php.md](backend-php.md)
- Domain vocabulary: [../business/radiant.md](../business/radiant.md)
