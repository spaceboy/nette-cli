<?php
namespace Spaceboy\NetteCli;

class Helper
{
    /**
     * Replacement for *str_starts_with* function (PHP 8).
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        if (function_exists('str_starts_with')) {
            return str_starts_with($haystack, $needle);
        }
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }

    /**
     * Finds common part of PATH (or URI) from the left.
     * @param string $addr1
     * @param string $addr2
     * @param ?string $separator
     * @return string
     */
    public static function getCommonPathPart(string $addr1, string $addr2, string $separator = DIRECTORY_SEPARATOR): string
    {
        $common = '';
        foreach (\array_filter(\explode($separator, $addr2)) as $part) {
            $commonNew = $common . $separator . $part;
            if (!static::startsWith($addr1, $commonNew)) {
                return $common;
            }
            $common = $commonNew;
        }
        return $common;
    }

    public static function getRelativePath(string $from, string $to): string
    {
        $common = static::getCommonPathPart($from, $to);
        $len = \strlen($common);
        return
            join(
                DIRECTORY_SEPARATOR,
                \array_fill(
                    0,
                    count(
                        array_filter(
                            explode(
                                DIRECTORY_SEPARATOR,
                                \substr($from, $len)
                            )
                        )
                    ),
                    '..'
                )
            )
            . \substr($to, $len);
    }
}
