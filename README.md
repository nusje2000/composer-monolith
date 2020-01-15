# Composer monolith

### Installation
Using composer:
```
composer require nusje2000/composer-monolith --dev
```

## Validation rules
### IncompatibleVersionRule
This rule checks the version constraint of dependencies in subpackages and compares them with the version constraint on that same dependency in the root composer.json file. Example:
 - Sub-package `foo/foo-package` has a dependency on `bar/bar-package ^1.0`
 - Root composer.json defines a dependency on `bar/bar-package ^2.0`
 - Rule will trigger because version `^1.0` is not compatible with version `^2.0`

### MissingDependencyRule
This rule will check if the dependencies of sub-packages are all present in the root composer definition.

### MissingReplaceRule
This rule will check if all the sub-packages are defined as `replaced` in the root composer definition. See: https://getcomposer.org/doc/04-schema.md#replace.

## Commands
### Validating project structure
Within the root of your project you can use `vendor/bin/composer-monolith validate`
to validate the project structure.

### Autofixing
When running `vendor/bin/composer-monolith validate` you can add the
`--autofix` option and then the validator will try to fix the
problems outputed by the validator.

### Version equalizer
When in a monolithic repository it might come in helpfull to equalize
the version of a dependency across all sub packages. To do this, you
can use `vendor/bin/composer-monolith equalize`. This can only be used
on dependencies with inconsistent versions.

### Version updater
To update a dependency you can use
`vendor/bin/composer-monolith update <dependency_name> <version_constraint>`.
This will make sure the version constraints in all sub- and rootpackages
match the provided version_constraint. This will not actually update the
package using composer, this must still be done manually.
