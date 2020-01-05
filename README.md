# composer-monolithic-repository

### Installation
Using composer:
```
composer require nusje2000/composer-monolithic-repository
```

### Validating project structure
Within the root of your project you can use `vendor/bin/composer-monolith validate`
to validate the project structure.

### Autofixing
When running `vendor/bin/composer-monolith validate` you can add the
`--autofix` option and then the validator will try to fix the
problems outputed by the validator.
