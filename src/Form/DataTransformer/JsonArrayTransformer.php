<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class JsonArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if (null === $value || [] === $value) {
            return '[]';
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function reverseTransform(mixed $value): array
    {
        if (null === $value || '' === $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
