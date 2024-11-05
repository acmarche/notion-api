<?php

namespace AcMarche\Notion\Lib;

use Notion\Databases\Database;
use Notion\Databases\Query;
use Notion\Databases\Query\CompoundFilter;
use Notion\Databases\Query\DateFilter;
use Notion\Databases\Query\Sort;
use Notion\Databases\Query\StatusFilter;
use Notion\Pages\Page;
use Notion\Pages\Properties\PropertyType;
use Notion\Pages\Properties\Relation;

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
        $fetch = new PageGet();
        $database = $this->getById($id);
        $pages = array_map(function (Page $page) use ($fetch) {
            $page = $page->toArray();
            $page['content'] = $fetch->fetchById($page['id']);

            return $page;
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
     * @param bool $fetchChildren
     * @param bool $addRelations
     * @return array{ database: Database, pages: Page[], relations: Page[]}
     */
    public function query(
        Database $database,
        Query $query,
        ?string $rowId = null,
        bool $fetchChildren = true,
        bool $addRelations = false,
    ): array {
        $result = $this->getNotion()->databases()->query($database, $query);
        $pages = [];
        if ($addRelations) {
            $relations = $this->getRelations($database, RelationsEnum::events);
        }
        foreach ($result->pages as $page) {
            if ($rowId && $page->id !== $rowId) {
                continue;
            }
            $blocks = [];
            if ($fetchChildren) {
                $children = $this->getNotion()->blocks()->findChildren($page->id);
                foreach ($children as $block) {
                    $blocks[] = Blocks::getBlocks($block);
                }
            }

            /**
             * add meta
             */
            $metas = [];
            if ($addRelations) {
                foreach ($page->properties as $propertyName => $property) {
                    /**
                     * @var Relation $property
                     */
                    if ($property->metadata()->type === PropertyType::Relation) {
                        if ($jfs = $this->propertyFind($property, $propertyName, $relations)) {
                            $metas[$propertyName] = $jfs;
                        }
                    }
                }
            }

            /**
             * end
             */
            $data = $page->toArray();
            $data['metas'] = $metas;
            $data['blocks'] = $blocks;
            $pages[] = $data;
        }

        return ['database' => $database->toArray(), 'pages' => $pages];
    }

    private function propertyFind(Relation $property, string $propertyName, array $relations): array
    {
        $values = [];
        if (isset($relations[$propertyName])) {
            foreach ($property->pageIds as $pageId) {
                foreach ($relations[$propertyName] as $room) {
                    if ($room['id'] == $pageId) {
                        $values[] = $room;
                        break;
                    }
                }
            }
        }

        return $values;
    }

    public function getRelations(Database $database, RelationsEnum $relationsEnum): array
    {
        $relations = [];
        foreach ($database->properties as $property) {
            if ($property->metadata()->type->value === 'relation') {
                $name = $property->metadata()->name;
                if (in_array($name, $relationsEnum->properties())) {
                    $relations[$name] = [];
                    $childDatabase = $this->getById($property->databaseId);
                    $pages = $this->getAllPagesByDatabase($childDatabase);
                    foreach ($pages as $page) {
                        $relations[$name][] = $page->toArray();
                    }
                }
            }
        }

        return $relations;
    }

    /**
     * @return array{ database: Database, pages: Page[], relations: Page[]}
     *
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

        return $this->query($database, $query, $rowId, true, true);
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