<?php

namespace App\Traits;

trait EnumToArray
{
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toArray(): array
    {
        return array_combine(self::names(), self::values());
    }

    public static function toArrayReverse(): array
    {
        return array_combine(self::values(), self::names());
    }

    public static function fromName(string $name): string
    {
        foreach (self::cases() as $status) {
            if ($name === $status->name) {
                return (string) $status->value;
            }
        }

        throw new \ValueError("{$name} is not a valid backing value for enum ".self::class);
    }

    public static function fromValue(string $value)
    {
        foreach (self::cases() as $status) {
            if ((string) $value === (string) $status->value) {
                return (string) $status->name;
            }
        }

        throw new \ValueError("{$value} is not a valid backing value for enum ".self::class);
    }

    public static function tryFromName(string $name): ?string
    {
        try {
            return self::fromName($name);
        } catch (\ValueError $error) {
            return null;
        }
    }
}
