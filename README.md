# namespacedeprecator
Low-Level utilty to deprecate namespaces of classes

**WARNING:** This is destructive, please you only if you know what you are doing and have versioning or backups.

Example Usage:
```shell
./flow namespace:deprecate \
"/Volumes/CaseSensitive/Work/PhpstormProjects/neos-dev-master/Packages/Framework/Neos.Utility.Arrays" \
"Classes" \
"Classes" \
"TYPO3\Flow\Utility" \
"Neos\Flow\Utility" \
"Neos\Flow\Utility"
```
