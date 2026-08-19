---
description: Backend conventions — PHP 8.2+, Symfony 7.4, Doctrine ORM 3 on PostgreSQL, DI, typing.
paths:
  - "**/*.php"
  - "**/composer.json"
  - "**/composer.lock"
  - "**/config/**/*.yaml"
  - "**/config/**/*.php"
---

# Backend PHP / Symfony

## Base
- **PHP 8.2 is the floor.** `composer.json` requires `>=8.2` and **CI runs 8.2**, while the Docker
  image ships 8.3. Anything newer than 8.2 syntax breaks the build — no `#[\Override]`, no typed
  class constants, no `json_validate()`.
- `declare(strict_types=1)` at the top of **every** PHP file. Most of the existing `src/` predates
  this rule: add it to any file you touch.
- Strict typing on params and returns; typed properties.
- `final` classes by default; open only when a class is genuinely meant to be extended. Doctrine
  entities are **not** `final` (proxies) — follow the existing `App\Entity\*`.
- Prefer DTOs, enums and Value Objects over loose arrays for structured data. `App\DTO\ContactDTO`
  is the model to follow.

## Symfony
- Constructor injection everywhere; autowire/autoconfigure (`config/services.yaml`). No
  `ContainerAware`, no `new` of a service that has dependencies.
- Environment values reach services through `#[Autowire(env: 'SOME_VAR')]` on the constructor or the
  action argument — see `CookbookApiService`, qui reçoit les quatre `COOKBOOK_API_*` ainsi.
- **Thin controllers**: orchestration only — read the request, call a service/repository, render.
  No Doctrine queries and no business rules in the controller.
- Move real logic into dedicated services under `src/Service/<Domain>/`; a controller action should
  read as a short sequence of calls. `MotusService` is the reference: stateless, pure, unit-testable.
- All user input goes through a **Form Type + Validator** (Assert constraints on the entity /
  DTO) — never trust `$request` data directly. There is no `src/Validator/` yet; create it when a
  business rule needs a custom constraint rather than inlining the check.

## Doctrine
- **PostgreSQL 16.** Queries live in the **Repository** (or a dedicated query service), never in a
  controller or template. `AppRepository::findBySlug()` / `findAllOrderedByPosition()` are the shape
  to follow.
- Avoid N+1: `join`/`addSelect` or configure fetch mode for the fields the view needs.
- Cohesive entities: mapping + light derived getters. No heavy application logic on the entity.
- Schema changes go through a migration (`make:migration`) — **review the generated SQL** before
  applying it, never hand-edit the DB. Migrations run **automatically on every production
  deploy**, so they must be backward-safe and non-interactive: see
  [deployment.md](deployment.md).

## Conventions in this repo
- Entities use Postgres identity integer IDs (`#[ORM\GeneratedValue] #[ORM\Column] private ?int $id`);
  keep that unless there's a reason to change.
- Collections initialised in the constructor (`new ArrayCollection()`), with `add*/remove*` keeping
  both sides of the association in sync.
- Repositories extend `ServiceEntityRepository` with the `@extends` docblock.
- `config/reference.php` is **auto-generated** and excluded from PHPStan, php-cs-fixer and git —
  never edit it by hand.

## See also
- Security (authorization, secrets, input sanitization): [security.md](security.md)
- Testing strategy: [testing.md](testing.md)
- Linting gate: [linting.md](linting.md)
- Back-office conventions: [easyadmin.md](easyadmin.md)
- Domain model, roles, naming: [../business/radiant.md](../business/radiant.md)
