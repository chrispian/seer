# Repository Guidelines

## Project Structure & Module Organization
Fragments Engine pairs a Laravel 12 backend with a React/Tailwind front end. Core application code lives in `app/` (domain services, jobs, Livewire endpoints) with AI-specific logic under `app/Ai` and queueable work under `app/Jobs`. Blade views and JSON schemas stay in `resources/views` and `resources/schemas`, while interactive islands, hooks, and utilities live in `resources/js/{components,islands,hooks,lib}`. Routes are grouped in `routes/` (web, api, console) and database migrations plus seeders sit in `database/`. Shared product collateral is kept in `docs/` and `delegation/`; update those alongside major behavioral shifts.

## Build, Test, and Development Commands
Run `composer install` and `npm install` after cloning. Use `composer run dev` for the full local stack (Laravel server, queue listener, logs, Vite). Execute `php artisan migrate --seed` when schema changes require data. Ship production assets with `npm run build`. Deployables should be generated through `php artisan config:cache` and `php artisan horizon:terminate` as needed, but avoid running them during regular development.

## Coding Style & Naming Conventions
Follow PSR-12 with 4-space indentation in PHP and run `./vendor/bin/pint` before committing. Place new services, DTOs, and queued jobs in the matching `app/{Services,DTOs,Jobs}` namespace and prefer explicit suffixes like `*Service` or `*Job`. React/TypeScript files in `resources/js` use 2-space indentation, PascalCase filenames for components, and camelCase for hooks beginning with `use`. Keep Tailwind utility strings lightweight and leverage shared styles from `resources/js/lib`.

## Testing Guidelines
All automated tests use Pest. Run `composer test` before every PR, and scope suites with `composer test:feature` or `composer test:unit` when iterating quickly. Prefer the Pest `it('does something')` style and place integration coverage in `tests/Feature` while isolating pure functions under `tests/Unit`. High-impact AI flows should include fixture-backed tests plus `RefreshDatabase` to ensure deterministic results. Check coverage with `composer test:coverage` when altering orchestration code.

## Commit & Pull Request Guidelines
Commits should follow the observed Conventional Commit pattern (`feat(ai):`, `fix(queue):`, `chore:`) with concise, imperative summaries; reserve `WIP` for local work only. Each PR needs a short problem statement, implementation notes, linked issues or project plan references, and screenshots or terminal output for UI/UX or console changes. Highlight migration or queue impacts explicitly and confirm the relevant test commands. Request review only after CI-equivalent commands pass locally.
