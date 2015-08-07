<?php

require_once (__DIR__ . DIRECTORY_SEPARATOR . "NeoPHP" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "ClassLoader.php");
$classLoader = NeoPHP\core\ClassLoader::getInstance();
$classLoader->register();
$includePaths = $classLoader->getIncludePaths();

$frameworkDirAdded = false;
foreach ($includePaths as $includePath)
{
    if ($includePath === __DIR__)
    {
        $frameworkDirAdded = true;
        break;
    }
}
if (!$frameworkDirAdded)
    $classLoader->addIncludePath (__DIR__);
?>