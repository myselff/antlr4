<?php

namespace Antlr\V4\Runtime\Misc;

class Utils
{
    public static function escapeWhitespace(string $s, bool $escapeSpaces): string
    {
        $buf = '';
        foreach (str_split($s) as $c) {
            if ( $c == ' ' && $escapeSpaces ) $buf .= '\u00B7';
            else if ( $c == '\t' ) $buf .= "\\t";
            else if ( $c == '\n' ) $buf .= "\\n";
            else if ( $c == '\r' ) $buf .= "\\r";
            else $buf .= $c;
        }

        return $buf;
    }
}