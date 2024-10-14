<?php

namespace AcMarche\Notion\Lib;

use Notion\Notion;

trait ConnectTrait
{
    private static ?Notion $notion = null;

    public static function initializeNotion(): void
    {
        if (self::$notion === null) {
            self::$notion = Notion::create($_ENV['NOTION_API_KEY']);
        }
    }

    public static function getNotionStatic(): ?Notion
    {
        self::initializeNotion();
        return self::$notion;
    }

    public  function getNotion(): ?Notion
    {
        return self::$notion;
    }
}