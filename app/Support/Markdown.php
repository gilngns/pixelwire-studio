<?php

namespace App\Support;

class Markdown
{
    /**
     * Teks dari klien yang masuk ke email atau Discord (keduanya merender markdown).
     * Kurung siku di-escape supaya isian seperti "[Bayar di sini](https://...)" atau
     * "![](https://...)" tampil sebagai teks biasa, bukan link atau gambar.
     */
    public static function escapeLinks(?string $text): string
    {
        return str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], (string) $text);
    }
}
