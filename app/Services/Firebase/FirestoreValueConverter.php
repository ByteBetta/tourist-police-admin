<?php

namespace App\Services\Firebase;

class FirestoreValueConverter
{
    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public static function fromFirestore(array $fields): array
    {
        $data = [];

        foreach ($fields as $key => $value) {
            $data[$key] = self::decodeValue($value);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function toFirestore(array $data): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            if ($key === 'id' || $key === '__key') {
                continue;
            }

            $fields[$key] = self::encodeValue($value);
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function decodeValue(array $value): mixed
    {
        if (array_key_exists('nullValue', $value)) {
            return null;
        }

        if (array_key_exists('booleanValue', $value)) {
            return (bool) $value['booleanValue'];
        }

        if (array_key_exists('integerValue', $value)) {
            return (int) $value['integerValue'];
        }

        if (array_key_exists('doubleValue', $value)) {
            return (float) $value['doubleValue'];
        }

        if (array_key_exists('timestampValue', $value)) {
            return $value['timestampValue'];
        }

        if (array_key_exists('stringValue', $value)) {
            return $value['stringValue'];
        }

        if (array_key_exists('arrayValue', $value)) {
            $values = $value['arrayValue']['values'] ?? [];

            return array_map(fn (array $item): mixed => self::decodeValue($item), $values);
        }

        if (array_key_exists('mapValue', $value)) {
            return self::fromFirestore($value['mapValue']['fields'] ?? []);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function encodeValue(mixed $value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_array($value)) {
            if ($value === []) {
                return ['arrayValue' => ['values' => []]];
            }

            $isList = array_is_list($value);

            if ($isList) {
                return [
                    'arrayValue' => [
                        'values' => array_map(fn (mixed $item): array => self::encodeValue($item), $value),
                    ],
                ];
            }

            return [
                'mapValue' => [
                    'fields' => self::toFirestore($value),
                ],
            ];
        }

        return ['stringValue' => (string) $value];
    }
}
