<?php

namespace AcMarche\Notion\Lib;

enum RelationsEnum: string
{
    case events = 'events';

    /**
     * Give list properties name to get relations
     * @return string[]
     */
    public function properties(): array
    {
        return match ($this) {
            RelationsEnum::events => ['Salles'],
        };
    }
}
