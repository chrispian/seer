# Types CRUD + Sushi Adapter

## CRUD
- Include routes:
```php
require base_path('routes/types_crud.php');
```
- After running `types:codegen Invoice`, CRUD endpoints:
  - GET/POST `/api/v2/types/Invoice`
  - GET/PUT/DELETE `/api/v2/types/Invoice/{id}`

## Sushi-backed Types
- Seed demo:
```bash
php artisan db:seed --class=TypesSushiDemoSeeder
```
- Use `TypeResolverEx` (bind it to replace the basic resolver if desired):
```php
$this->app->bind(App\Services\Types\TypeResolver::class, App\Services\Types\TypeResolverEx::class);
```
