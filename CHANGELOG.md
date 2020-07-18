# CHANGELOG

## 1.3.0
 - Added commands for generating and validating codeowner files
 - Replaces in composer files will now be used for validation of missing dependencies
 - Fixed deprecations from dependency-graph
 - Added `CodeOwnersGenerateCommand`
 - Added `CodeOwnersValidateCommand`

## 1.2.0
 - Added MissingReplaceRule and MissingReplaceFixer

## 1.1.3
 - Added default version to version-equalize command

## 1.1.2
 - Fixed colors in command line

## 1.1.1
 - Fixed phpstan errors

## 1.1.0
 - Removed DevelopmentOnlyRule
 - Removed DevelopmentOnlyViolation
 - Removed DevelopmentOnlyFixer
 - Added installed version to IncompatibleVersionConstraintViolation message
 - IncompatibleVersionFixer and MissingDependencyFixer now use the
   dependency graph to determine the correct version constraint instead
   of only the violations
 - Added `EqualizeVersionCommand`
 - Added `UpdateCommand`
 - Updated to dependency-graph `^2.1`

## 1.0.5
 - Fixed dev dependency check

## 1.0.4
 - Fixed undefined notice

## 1.0.3
 - Added colors to command line to improve readability
 - Fixed build failures

## 1.0.2
 - Removed minimum stability from composer.json

## 1.0.1
 - Allowed nusje2000/dependency-graph version ^2.0
