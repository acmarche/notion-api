<?php

namespace AcMarche\Notion\Lib;

use Notion\Databases\Database;
use Notion\Databases\Query;
use Notion\Pages\Page;

class DatabaseGet
{
    use ConnectTrait;

    private PageUtils $pageUtils;

    public function __construct()
    {
        self::initializeNotion();
        $this->pageUtils = new PageUtils();
    }

    public function getByIdWithPages(string $id): array
    {
        $database = $this->getById($id);
        $pages = array_map(function (Page $page) {
            return $page->toArray();
        }, $this->getNotion()->databases()->queryAllPages($database));

        return ['database' => $database->toArray(), 'pages' => $pages];
    }

    public function getById(string $id): Database
    {
        return $this->getNotion()->databases()->find($id);
    }

    /**
     * @throws \Exception
     */
    public function query(Database $database, Query $query, ?string $rowId = null, bool $fetChildren = true): array
    {
        $result = $this->getNotion()->databases()->query($database, $query);
        $pages = [];
        foreach ($result->pages as $page) {
            if ($rowId && $page->id !== $rowId) {
                continue;
            }
            $blocks = [];
            if ($fetChildren) {
                $children = $this->getNotion()->blocks()->findChildren($page->id);
                foreach ($children as $block) {
                    $blocks[] = Blocks::getBlocks($block);
                }
            }
            $data = $page->toArray();
            $data['blocks'] = $blocks;
            $pages[] = $data;
        }

        return ['database' => $database->toArray(), 'pages' => $pages];
    }
}