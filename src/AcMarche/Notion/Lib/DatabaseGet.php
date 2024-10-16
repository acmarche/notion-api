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
        }, $this->getAllPagesByDatabase($database));

        return ['database' => $database->toArray(), 'pages' => $pages];
    }

    public function getById(string $id): Database
    {
        return $this->getNotion()->databases()->find($id);
    }

    /**
     * @param Database $database
     * @return Page[]
     */
    public function getAllPagesByDatabase(Database $database): array
    {
        return $this->getNotion()->databases()->queryAllPages($database);
    }

    /**
     * @param Database $database
     * @param Query $query
     * @param string|null $rowId
     * @param bool $fetChildren
     * @return array
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

    public function addRelations(Database $database): array
    {
        $relations = [];
        foreach ($database->properties as $property) {
            if ($property->metadata()->type->value === 'relation') {
                $relations[$property->metadata()->name] = [];
                $childDatabase = $this->getById($property->databaseId);
                $pages = $this->getAllPagesByDatabase($childDatabase);
                foreach ($pages as $page) {
                    $relations[$property->metadata()->name][] = $page->toArray();
                }
            }
        }

        return $relations;
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