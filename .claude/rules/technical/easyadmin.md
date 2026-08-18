---
description: Back-office conventions — EasyAdmin 4 CRUD controllers, dashboard menu, and the JSON-column textarea pattern.
paths:
  - "**/src/Controller/Admin/**"
  - "**/src/Entity/**"
  - "**/config/packages/easy_admin*.yaml"
---

# Back-office — EasyAdmin 4

The admin at `/admin` is the **only** way content is edited. It is gated by
`access_control: { path: ^/admin, roles: ROLE_ADMIN }` — see [security.md](security.md).

## Anatomy

- **One `XxxCrudController` per entity** under `src/Controller/Admin`, extending
  `AbstractCrudController`, exposing `getEntityFqcn()` and `configureFields()`.
- **`DashboardController`** owns the entry point (`#[Route('/admin', name: 'admin')]`, redirecting to
  a default CRUD) and the menu.

## Adding an entity to the back-office

Three edits, all required — a CRUD controller with no menu entry is unreachable:

1. Create `src/Controller/Admin/<Entity>CrudController.php`.
2. Declare the fields in `configureFields()`.
3. **Add a `MenuItem` in `DashboardController::configureMenuItems()`**:
   ```php
   yield MenuItem::linkToCrud('Projects', 'fa-solid fa-list', PersonalProject::class);
   ```
   Menu labels are **English**, like the rest of the code (the admin is author-facing, not
   visitor-facing) — see [naming.md](naming.md). Icons are Font Awesome classes.

## The JSON-column textarea pattern (deliberate — don't refactor it away)

`App` and `Experience` carry JSON columns (`techStack`, `challenges`, `improvements`, `resources`,
`tags`). EasyAdmin has no first-class editor for them, so each entity exposes a **string accessor
pair** used only by the admin:

```php
TextareaField::new('jsonTechStack')
    ->setHelp('[{"name": "Symfony", "category": "Backend"}]'),
```

- The `getJsonX()` / `setJsonX()` accessors encode/decode around the real typed property.
- **`setHelp()` documents the expected shape** — it is the only contract the author sees. When you
  change the structure a template consumes, update the help string in the same change, or the next
  edit through the admin will silently produce data the drawer can't render.
- These accessors exist **for EasyAdmin**. Application code reads the typed property, never the
  string accessor.

If a JSON structure grows real invariants, the right fix is a **DTO + a custom field/form type with
validation**, not looser parsing — see [backend-php.md](backend-php.md).

## Notes

- Field lists are per-entity and explicit; `configureFields()` receives `$pageName`, so branch on it
  when index and form need different columns rather than showing everything everywhere.
- Uploads go through **VichUploader** (`PersonalProject`, mapping `personal_projects`). The binary
  lands on the server filesystem and is **not** in git or the image — see
  [deployment.md](deployment.md).
- Adding an `App` row through the admin is **not sufficient** on its own: the Stream Deck also needs
  a matching `templates/components/_icon_<slug>.html.twig` and a real route, or the homepage breaks.
  Use `/new-app`, which does the whole set. See [../business/radiant.md](../business/radiant.md).

## See also
- Entities & Doctrine: [backend-php.md](backend-php.md)
- Authorization: [security.md](security.md)
- Domain model: [../business/radiant.md](../business/radiant.md)
