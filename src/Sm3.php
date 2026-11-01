<?php

declare(strict_types=1);

namespace Wuwx\Sm3;

/**
 * 国密 SM3 散列（GB/T 32905-2016），纯 PHP 实现。
 */
final class Sm3
{
    /**
     * 32 位字一律拆成 hi/lo 两个 16 位半字运算，
     * 换成 32 位整数加 0xFFFFFFFF 掩码在 32 位 PHP 上会溢出成 float。
     */
    private const IV = [
        0x7380, 0x166f, 0x4914, 0xb2b9, 0x1724, 0x42d7, 0xda8a, 0x0600,
        0xa96f, 0x30bc, 0x1631, 0x38aa, 0xe38d, 0xee4d, 0xb0fb, 0x0e4e,
    ];

    private static ?array $t = null;

    public static function hash(string $data): string
    {
        [$th, $tl] = self::tTable();

        $bitLength = strlen($data) * 8;
        $data .= "\x80";
        $data .= str_repeat("\0", (56 - strlen($data) % 64 + 64) % 64);
        $data .= pack('J', $bitLength);

        [
            $v0h, $v0l, $v1h, $v1l, $v2h, $v2l, $v3h, $v3l,
            $v4h, $v4l, $v5h, $v5l, $v6h, $v6l, $v7h, $v7l,
        ] = self::IV;

        $total = strlen($data);

        for ($offset = 0; $offset < $total; $offset += 64) {
            $halves = unpack('n*', substr($data, $offset, 64));

            $wh = $wl = [];
            for ($i = 0; $i < 16; $i++) {
                $wh[$i] = $halves[2 * $i + 1];
                $wl[$i] = $halves[2 * $i + 2];
            }

            for ($j = 16; $j < 68; $j++) {
                $r3h = (($wh[$j - 3] << 15) | ($wl[$j - 3] >> 1)) & 0xFFFF;
                $r3l = (($wl[$j - 3] << 15) | ($wh[$j - 3] >> 1)) & 0xFFFF;
                $xh = $wh[$j - 16] ^ $wh[$j - 9] ^ $r3h;
                $xl = $wl[$j - 16] ^ $wl[$j - 9] ^ $r3l;

                $p15h = (($xh << 15) | ($xl >> 1)) & 0xFFFF;
                $p15l = (($xl << 15) | ($xh >> 1)) & 0xFFFF;
                $p23h = (($xl << 7) | ($xh >> 9)) & 0xFFFF;
                $p23l = (($xh << 7) | ($xl >> 9)) & 0xFFFF;

                $r13h = (($wh[$j - 13] << 7) | ($wl[$j - 13] >> 9)) & 0xFFFF;
                $r13l = (($wl[$j - 13] << 7) | ($wh[$j - 13] >> 9)) & 0xFFFF;

                $wh[$j] = $xh ^ $p15h ^ $p23h ^ $r13h ^ $wh[$j - 6];
                $wl[$j] = $xl ^ $p15l ^ $p23l ^ $r13l ^ $wl[$j - 6];
            }

            $ah = $v0h; $al = $v0l;
            $bh = $v1h; $bl = $v1l;
            $ch = $v2h; $cl = $v2l;
            $dh = $v3h; $dl = $v3l;
            $eh = $v4h; $el = $v4l;
            $fh = $v5h; $fl = $v5l;
            $gh = $v6h; $gl = $v6l;
            $hh = $v7h; $hl = $v7l;

            for ($j = 0; $j < 64; $j++) {
                $a12h = (($ah << 12) | ($al >> 4)) & 0xFFFF;
                $a12l = (($al << 12) | ($ah >> 4)) & 0xFFFF;

                $sl = $a12l + $el + $tl[$j];
                $sh = $a12h + $eh + $th[$j] + ($sl >> 16);
                $sl &= 0xFFFF;
                $sh &= 0xFFFF;

                $ss1h = (($sh << 7) | ($sl >> 9)) & 0xFFFF;
                $ss1l = (($sl << 7) | ($sh >> 9)) & 0xFFFF;
                $ss2h = $ss1h ^ $a12h;
                $ss2l = $ss1l ^ $a12l;

                $wph = $wh[$j] ^ $wh[$j + 4];
                $wpl = $wl[$j] ^ $wl[$j + 4];

                if ($j < 16) {
                    $ffh = $ah ^ $bh ^ $ch;
                    $ffl = $al ^ $bl ^ $cl;
                    $ggh = $eh ^ $fh ^ $gh;
                    $ggl = $el ^ $fl ^ $gl;
                } else {
                    $ffh = ($ah & $bh) | ($ah & $ch) | ($bh & $ch);
                    $ffl = ($al & $bl) | ($al & $cl) | ($bl & $cl);
                    $ggh = ($eh & $fh) | ((~$eh & 0xFFFF) & $gh);
                    $ggl = ($el & $fl) | ((~$el & 0xFFFF) & $gl);
                }

                $t1l = $ffl + $dl + $ss2l + $wpl;
                $t1h = $ffh + $dh + $ss2h + $wph + ($t1l >> 16);
                $t1l &= 0xFFFF;
                $t1h &= 0xFFFF;

                $t2l = $ggl + $hl + $ss1l + $wl[$j];
                $t2h = $ggh + $hh + $ss1h + $wh[$j] + ($t2l >> 16);
                $t2l &= 0xFFFF;
                $t2h &= 0xFFFF;

                $dh = $ch; $dl = $cl;
                $ch = (($bh << 9) | ($bl >> 7)) & 0xFFFF;
                $cl = (($bl << 9) | ($bh >> 7)) & 0xFFFF;
                $bh = $ah; $bl = $al;
                $ah = $t1h; $al = $t1l;

                $hh = $gh; $hl = $gl;
                $gh = (($fl << 3) | ($fh >> 13)) & 0xFFFF;
                $gl = (($fh << 3) | ($fl >> 13)) & 0xFFFF;
                $fh = $eh; $fl = $el;

                $e9h = (($t2h << 9) | ($t2l >> 7)) & 0xFFFF;
                $e9l = (($t2l << 9) | ($t2h >> 7)) & 0xFFFF;
                $e17h = (($t2l << 1) | ($t2h >> 15)) & 0xFFFF;
                $e17l = (($t2h << 1) | ($t2l >> 15)) & 0xFFFF;
                $eh = $t2h ^ $e9h ^ $e17h;
                $el = $t2l ^ $e9l ^ $e17l;
            }

            $v0h ^= $ah; $v0l ^= $al;
            $v1h ^= $bh; $v1l ^= $bl;
            $v2h ^= $ch; $v2l ^= $cl;
            $v3h ^= $dh; $v3l ^= $dl;
            $v4h ^= $eh; $v4l ^= $el;
            $v5h ^= $fh; $v5l ^= $fl;
            $v6h ^= $gh; $v6l ^= $gl;
            $v7h ^= $hh; $v7l ^= $hl;
        }

        return sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x%04x',
            $v0h, $v0l, $v1h, $v1l, $v2h, $v2l, $v3h, $v3l,
            $v4h, $v4l, $v5h, $v5l, $v6h, $v6l, $v7h, $v7l
        );
    }

    private static function tTable(): array
    {
        if (self::$t !== null) {
            return self::$t;
        }

        $th = $tl = [];
        for ($j = 0; $j < 64; $j++) {
            $n = $j % 32;
            [$h, $l] = $j < 16 ? [0x79cc, 0x4519] : [0x7a87, 0x9d8a];

            if ($n === 0) {
                $th[$j] = $h;
                $tl[$j] = $l;
            } elseif ($n < 16) {
                $th[$j] = (($h << $n) | ($l >> (16 - $n))) & 0xFFFF;
                $tl[$j] = (($l << $n) | ($h >> (16 - $n))) & 0xFFFF;
            } else {
                $m = $n - 16;
                $th[$j] = (($l << $m) | ($h >> (16 - $m))) & 0xFFFF;
                $tl[$j] = (($h << $m) | ($l >> (16 - $m))) & 0xFFFF;
            }
        }

        return self::$t = [$th, $tl];
    }
}
