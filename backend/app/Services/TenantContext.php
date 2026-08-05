<?php

namespace App\Services;

class TenantContext
{
    protected static ?int $tenantId = null;

    protected static ?string $role = null;

    public static function set(?int $tenantId, ?string $role = null): void
    {
        static::$tenantId = $tenantId;
        static::$role = $role;
    }

    public static function id(): ?int
    {
        return static::$tenantId;
    }

    public static function role(): ?string
    {
        return static::$role;
    }

    public static function flush(): void
    {
        static::$tenantId = null;
        static::$role = null;
    }
}
