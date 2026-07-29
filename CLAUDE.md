## Tech Stack
php 8.4, Vue v3, Inertia.js v3, Tailwind v4, Laravel, Sqlite

## Planning instructions:
For big changes always create a detailed plan and add to end of the plan:

1. Architecture overview of the solution
2. Task list that is needed to achieve the plan

## remote server:
This project is running on a production remote server
The server credentials can be found in .env file
The server deployment files can be found under ./devops/ directory
files: @devops/deploy.sh, @devops/provision.sh

## Exceptions:
Keep to best practices by avoiding silent fails and always log errors.

# Code Formatting

Run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
Run `vendor/bin/pint --format agent` to fix any formatting issues.

## Pest & Testing

Create tests: `php artisan make:test --pest {name}`.
Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
Do NOT delete tests without approval.

## Constructors

Use PHP 8 constructor property promotion in `__construct()`.
`public function __construct(public GitHub $github) { }`
Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

Use appropriate PHP type hints for method parameters and return types

example:
```php
protected function myFunc(Obj $obj, ?string $str = null): bool
```



