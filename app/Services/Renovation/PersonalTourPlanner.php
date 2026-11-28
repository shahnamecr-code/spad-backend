<?php

namespace App\Services\Renovation;

use App\Services\SpadOpenAi;
use RuntimeException;

final class PersonalTourPlanner
{
    public function suggest(array $media): array
    {
        // Use opaque indices instead of sending account, project or media IDs.
        $content = [[
            'type' => 'input_text',
            'text' => 'Order these real estate images into a practical viewing sequence, starting with the visible entrance if available. '
                . 'Describe only visible spaces with short Persian room names. Do not infer physical connections or unseen rooms. '
                . 'Return valid JSON {"scenes":[{"image":1,"title":"ورودی"}]}, with each image number exactly once.',
        ]];
        $source = [];
        foreach ($media as $index => $item) {
            $image = PrivateRenovationImage::inspect($item->path);
            $number = $index + 1;
            $source[$number] = ['media_id' => (int) $item->id, 'kind' => $image['kind']];
            $content[] = ['type' => 'input_text', 'text' => 'Image ' . $number];
            $content[] = SpadOpenAi::image($image['jpeg'], 'low');
        }

        $result = SpadOpenAi::vision($content, 1600);
        $scenes = [];
        $seen = [];
        foreach ((array) ($result['scenes'] ?? []) as $scene) {
            $index = $scene['image'] ?? null;
            $title = $scene['title'] ?? null;
            if (!is_int($index) || !isset($source[$index]) || isset($seen[$index]) || !is_string($title)) {
                throw new RuntimeException('openai-invalid-tour-plan');
            }
            $name = trim(preg_replace('/[\x00-\x1f\x7f]/u', '', strip_tags($title)) ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
                throw new RuntimeException('openai-invalid-tour-title');
            }
            $seen[$index] = true;
            $scenes[] = $source[$index] + ['title' => $name, 'next_media_id' => null];
        }

        if (count($scenes) !== count($source)) {
            throw new RuntimeException('openai-incomplete-tour-plan');
        }

        foreach ($scenes as $index => &$scene) {
            $scene['next_media_id'] = $scenes[$index + 1]['media_id'] ?? null;
        }
        unset($scene);

        return $scenes;
    }
}
