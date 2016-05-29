<?php
namespace Neos\NamespaceDeprecator\Command;

use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\PhpAnalyzer;

/**
 *
 */
class NamespaceCommandController extends CommandController
{
    /**
     * @param string $absolutePackagePath
     * @param string $relativeOldClassesPath
     * @param string $relativeNewClassesPath
     * @param string $oldNamespace
     * @param string $newNamespace
     * @param string $namespacePartIgnoredInPath
     */
    public function deprecateCommand($absolutePackagePath, $relativeOldClassesPath, $relativeNewClassesPath, $oldNamespace, $newNamespace, $namespacePartIgnoredInPath)
    {
        $oldNamespace = ltrim($oldNamespace, '\\');
        $newNamespace = ltrim($newNamespace, '\\');
        $namespacePartIgnoredInPath = ltrim($namespacePartIgnoredInPath, '\\');
        $absoluteOldClassesPath = Files::concatenatePaths([$absolutePackagePath, $relativeOldClassesPath]);
        $absoluteNewClassesPath = Files::concatenatePaths([$absolutePackagePath, $relativeNewClassesPath]);

        foreach (Files::getRecursiveDirectoryGenerator($absoluteOldClassesPath, '.php') as $oldPhpFile) {
            $this->deprecatePhpFile($oldPhpFile, $oldNamespace, $newNamespace, $absoluteNewClassesPath, $namespacePartIgnoredInPath);
        }
    }

    /**
     * @param string $oldPhpFile
     * @param string $oldNamespace
     * @param string $newNamespace
     * @param string $absoluteNewClassesPath
     * @param string $namespacePartIgnoredInPath
     * @return boolean
     */
    protected function deprecatePhpFile($oldPhpFile, $oldNamespace, $newNamespace, $absoluteNewClassesPath, $namespacePartIgnoredInPath)
    {
        $oldPhpFileContent = file_get_contents($oldPhpFile);
        $analyzer = new PhpAnalyzer($oldPhpFileContent);
        $oldFileNamespace = $analyzer->extractNamespace();
        $className = $analyzer->extractClassName();
        if (strpos($oldFileNamespace, $oldNamespace) !== 0) {
            $this->outputLine('The file "%s" was not moved because the namespace "%s" is not the given old namespace.', [
                $oldPhpFile,
                $oldNamespace
            ]);
            return false;
        }

        $newFileNamespace = str_replace($oldNamespace, $newNamespace, $oldFileNamespace);
        $newFullyQualifiedClassname = $newFileNamespace . '\\' . $className;
        $namespaceFilePath = ltrim(substr($newFileNamespace, strlen($namespacePartIgnoredInPath)), '\\');
        $newFilePath = Files::concatenatePaths([$absoluteNewClassesPath, $namespaceFilePath]);
        $newFileName = Files::concatenatePaths([$newFilePath, basename($oldPhpFile)]);
        Files::createDirectoryRecursively($newFilePath);

        $newFileContent = preg_replace('/^(<\?php\h)?\h*namespace\s+([^;\s]*);[^\v]*$/m', '${1}namespace ' . $newFileNamespace . ';', $oldPhpFileContent, 1);
        file_put_contents($newFileName, $newFileContent);
        $this->outputLine('Created replacement class "%s".', [$newFullyQualifiedClassname]);

        $deprecatedFileContent = str_replace([
            '{{oldNamespace}}',
            '{{newNamespace}}',
            '{{className}}',
            '{{newPhpFile}}'
        ],
            [
                $oldFileNamespace,
                $newFileNamespace,
                $className,
                $newFileName
            ], $this->getDeprecatedFileTemplate());

        file_put_contents($oldPhpFile, $deprecatedFileContent);

        return true;
    }

    protected function getDeprecatedFileTemplate()
    {
        return <<<'EOD'
<?php
namespace {{oldNamespace}}

/*
 * This class namespace was deprecated and moved to {{newNamespace}},
 * find the replacement here: {{newPhpFile}}
 */
 
/**
 * @deprecated This class is superseded by \{{newNamespace}}\{{className}}
 * @see \{{newNamespace}}\{{className}}
 */
class {{className}} extends \{{newNamespace}}\{{className}} {
}

EOD;
    }
}
