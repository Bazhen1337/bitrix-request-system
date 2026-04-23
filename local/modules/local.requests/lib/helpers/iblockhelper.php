<?php

namespace Local\Requests\helpers;

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;

final class IBlockHelper
{
    private static ?array $cachedIDByCode = null;

    public static function getID(string $code, bool $resetCache = false): int
    {
        if (!$code) return 0;

        if ($resetCache) {
            self::$cachedIDByCode = null;
        }

        if (isset(self::$cachedIDByCode)) {
            return self::$cachedIDByCode[$code] ?? 0;
        }

        self::$cachedIDByCode = [];

        if (!Loader::includeModule('iblock')) {
            return 0;
        }

        $res = IblockTable::getList(['select' => ['ID', 'CODE']]);

        while ($item = $res->fetch()) {
            self::$cachedIDByCode[$item['CODE']] = (int)$item['ID'];
        }

        return self::$cachedIDByCode[$code] ?? 0;
    }
}