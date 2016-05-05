<?php

namespace jonag\OpenSubtitlesSDK\Helper;

/**
 * Implements the OpenSubtitles hash calculation.
 * Based on https://trac.opensubtitles.org/projects%3Cscript%20type=/opensubtitles/wiki/HashSourceCodes#PHP45
 */
class Hash
{
    /**
     * @param string $file
     * @return string
     */
    public static function calculateHash($file)
    {
        $handle = fopen($file, "rb");
        $fsize = filesize($file);

        $hash = [
            3 => 0,
            2 => 0,
            1 => ($fsize >> 16) & 0xFFFF,
            0 => $fsize & 0xFFFF,
        ];

        for ($i = 0; $i < 8192; $i++) {
            $tmp = self::readUINT64($handle);
            $hash = self::addUINT64($hash, $tmp);
        }

        $offset = $fsize - 65536;
        fseek($handle, $offset > 0 ? $offset : 0, SEEK_SET);

        for ($i = 0; $i < 8192; $i++) {
            $tmp = self::readUINT64($handle);
            $hash = self::addUINT64($hash, $tmp);
        }

        fclose($handle);

        return self::UINT64FormatHex($hash);
    }

    /**
     * @param $handle
     * @return array
     */
    private static function readUINT64($handle)
    {
        $u = unpack("va/vb/vc/vd", fread($handle, 8));

        return [0 => $u["a"], 1 => $u["b"], 2 => $u["c"], 3 => $u["d"]];
    }

    /**
     * @param $a
     * @param $b
     * @return array
     */
    private static function addUINT64($a, $b)
    {
        $o = [0 => 0, 1 => 0, 2 => 0, 3 => 0];

        $carry = 0;
        for ($i = 0; $i < 4; $i++) {
            if (($a[$i] + $b[$i] + $carry) > 0xffff) {
                $o[$i] += ($a[$i] + $b[$i] + $carry) & 0xffff;
                $carry = 1;
            } else {
                $o[$i] += ($a[$i] + $b[$i] + $carry);
                $carry = 0;
            }
        }

        return $o;
    }

    /**
     * @param $n
     * @return string
     */
    private static function UINT64FormatHex($n)
    {
        return sprintf("%04x%04x%04x%04x", $n[3], $n[2], $n[1], $n[0]);
    }
}
