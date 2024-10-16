<?php

namespace AcMarche\Notion\Lib;

use Notion\Databases\Database;
use Notion\Databases\Query;
use Notion\Databases\Query\CompoundFilter;
use Notion\Databases\Query\DateFilter;
use Notion\Databases\Query\Sort;
use Notion\Databases\Query\StatusFilter;
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

    /**
     * @throws \Exception
     */
    public function getEvents(string $databaseId, ?string $rowId = null): array
    {
        $database = $this->getById($databaseId);
        $today = new \DateTime();

        $query = Query::create()
            ->changeFilter(
                CompoundFilter::and(
                    StatusFilter::property('Statut')->equals('Date validée (Public)'),
                    DateFilter::property('Date')->after($today->format('Y-m-d')),
                ),
            )
            ->addSort(Sort::property("Date")->ascending());

        return $this->query($database, $query, $rowId);
    }

    /**
     * @throws \Exception
     */
    public function getCoworkers(string $databaseId, ?string $rowId = null): array
    {
        $database = $this->getById($databaseId);

        $query = Query::create()
            ->changeFilter(
                CompoundFilter::and(
                    Query\MultiSelectFilter::property('Type de membre')->contains('Coworker'),
                    Query\StatusFilter::property('Convention coworking')->equals('Actif'),
                ),
            )
            ->addSort(Sort::property("Nom")->ascending());

        return $this->query($database, $query, $rowId, false);
    }
}