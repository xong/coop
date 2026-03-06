<?php

namespace App\Service;

class SlugService
{
    public function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', trim($text));
        return trim($text, '-') ?: 'untitled';
    }

    public function uniqueSlug(string $text, callable $existsCheck): string
    {
        $slug = $this->slugify($text);
        $original = $slug;
        $i = 2;
        while ($existsCheck($slug)) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
