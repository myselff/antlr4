<?php

namespace Antlr\v4;

class RuntimeMetaData
{
    const VERSION = '4.7.1';

    public static function checkVersion(string $generatingToolVersion, string $compileTimeVersion)
    {
        $runtimeConflictsWithGeneratingTool =
            self::VERSION !== $generatingToolVersion &&
            self::getMajorMinorVersion(self::VERSION) != self::getMajorMinorVersion($generatingToolVersion);

        $runtimeConflictsWithCompileTimeTool =
            self::VERSION !== $compileTimeVersion &&
            self::getMajorMinorVersion(self::VERSION) !== self::getMajorMinorVersion($compileTimeVersion);

        if ($runtimeConflictsWithGeneratingTool) {
            trigger_error(
                sprintf(
                    "ANTLR Tool version %s used for code generation does not match the current runtime version %s",
                    $generatingToolVersion,
                    static::VERSION
                ),
                E_USER_WARNING
            );

        }

        if ($runtimeConflictsWithCompileTimeTool) {
            trigger_error(
                sprintf(
                    "ANTLR Runtime version %s used for parser compilation does not match the current runtime version %s",
                    $compileTimeVersion,
                    static::VERSION
                ),
                E_USER_WARNING
            );

        }
    }

    private static function getMajorMinorVersion(string $version)
    {
        $firstDot = strpos($version, '.');
		$secondDot = $firstDot >= 0 ? strpos($version, '.', $firstDot + 1) : -1;
		$firstDash = strpos($version, '-');
		$referenceLength = strlen($version);
		if ($secondDot >= 0) {
            $referenceLength = min($referenceLength, $secondDot);
        }

		if ($firstDash >= 0) {
            $referenceLength = min($referenceLength, $firstDash);
        }

		return substr($version, 0, $referenceLength);
    }


}