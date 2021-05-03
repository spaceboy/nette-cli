<?php
namespace Spaceboy\NetteCli;


class Format
{
    public const ESC = "\e[%sm";

    public const DEFAULT_COLOR = '39';

    public const BLACK = '30';

    public const RED = '31';

    public const GREEN = '32';

    public const YELLOW = '33';

    public const BLUE = '34';

    public const MAGENTA = '35';

    public const CYAN = '36';

    public const LIGHT_GRAY = '37';

    public const DARK_GRAY = '90';

    public const LIGHT_RED = '91';

    public const LIGHT_GREEN = '92';

    public const LIGHT_YELLOW = '93';

    public const LIGHT_BLUE = '94';

    public const LIGHT_MAGENTA = '95';

    public const LIGHT_CYAN = '96';

    public const WHITE = '97';

    public const BG_DEFAULT = '49';

    public const BG_BLACK = '40';

    public const BG_RED = '41';

    public const BG_GREEN = '42';

    public const BG_YELLOW = '43';

    public const BG_BLUE = '44';

    public const BG_MAGENTA = '45';

    public const BG_CYAN = '46';

    public const BG_LIGHT_GRAY = '47';

    public const BG_DARK_GRAY = '100';

    public const BG_LIGHT_RED = '101';

    public const BG_LIGHT_GREEN = '102';

    public const BG_LIGHT_YELLOW = '103';

    public const BG_LIGHT_BLUE = '104';

    public const BG_LIGHT_MAGENTA = '105';

    public const BG_LIGHT_CYAN = '106';

    public const BG_WHITE = '107';

    public const BOLD_START = '1';

    public const BOLD_END = '22';

    public const DIM_START = '2';

    public const DIM_END = '22';

    public const UNDERLINED_START = '4';

    public const UNDERLINED_END = '24';

    public const BLINK_START = '5';

    public const BLINK_END = '25';

    public const REVERSE_START = '7';

    public const REVERSE_END = '27';

    public const HIDDEN_START = '8';

    public const HIDDEN_END = '28';

    public const BELL = "\0x07";

    public const BACKSPACE = '\0x08';

    public const TAB = '\0x09';

    public const COLORS = [
        DEFAULT_COLOR,
        BLACK,
        RED,
        GREEN,
        YELLOW,
        BLUE,
        MAGENTA,
        CYAN,
        LIGHT_GRAY,
        DARK_GRAY,
        LIGHT_RED,
        LIGHT_GREEN,
        LIGHT_YELLOW,
        LIGHT_BLUE,
        LIGHT_MAGENTA,
        LIGHT_CYAN,
        WHITE,
        BG_DEFAULT,
        BG_BLACK,
        BG_RED,
        BG_GREEN,
        BG_YELLOW,
        BG_BLUE,
        BG_MAGENTA,
        BG_CYAN,
        BG_LIGHT_GRAY,
        BG_DARK_GRAY,
        BG_LIGHT_RED,
        BG_LIGHT_GREEN,
        BG_LIGHT_YELLOW,
        BG_LIGHT_BLUE,
        BG_LIGHT_MAGENTA,
        BG_LIGHT_CYAN,
        BG_WHITE,
    ];

    public static function reset(): string
    {
        return sprintf(static::ESC, 0);
    }

    public static function color(...$colors): string
    {
        return implode(
            '',
            array_map(
                function ($color) {
                    return sprintf(static::ESC, $color);
                },
                $colors
            )
        );
    }

    public static function bold(string $text): string
    {
        return sprintf(static::ESC, static::BOLD_START) . $text . sprintf(static::ESC, static::BOLD_END);
    }

    public static function dim(string $text): string
    {
        return sprintf(static::ESC, static::DIM_START) . $text . sprintf(static::ESC, static::DIM_END);
    }

    public static function underlined(string $text): string
    {
        return sprintf(static::ESC, static::UNDERLINED_START) . $text . sprintf(static::ESC, static::UNDERLINED_END);
    }

    public static function blink(string $text): string
    {
        return sprintf(static::ESC, static::BLINK_START) . $text . sprintf(static::ESC, static::BLINK_END);
    }

    public static function reverse(string $text): string
    {
        return sprintf(static::ESC, static::REVERSE_START) . $text . sprintf(static::ESC, static::REVERSE_END);
    }

    public static function hidden(string $text): string
    {
        return sprintf(static::ESC, static::HIDDEN_START) . $text . sprintf(static::ESC, static::HIDDEN_END);
    }

    public static function bell(): string
    {
        return "\007";
        return sprintf(static::ESC, "\007");
    }

    public static function backspace(): string
    {
        return sprintf(static::ESC, static::BACKSPACE);
    }

    public static function tab(): string
    {
        return sprintf(static::ESC, static::TAB);
    }

    public static function getConsoleColumns(): int
    {
        return (int)exec('tput cols');
    }

    public static function getConsoleLines(): int
    {
        return (int)exec('tput lines');
    }
}
