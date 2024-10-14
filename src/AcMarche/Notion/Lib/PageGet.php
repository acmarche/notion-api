<?php

namespace AcMarche\Notion\Lib;

use Notion\Blocks\BlockInterface;
use Notion\Pages\Page;

class PageGet
{
    use ConnectTrait;

    private PageUtils $pageUtils;
    private FileUtils $fileUtils;

    public function __construct()
    {
        self::initializeNotion();
        $this->pageUtils = new PageUtils();
        $this->fileUtils = new FileUtils();
    }

    public function fetchById(string $pageId): array
    {
        $page = $this->getNotion()->pages()->find($pageId);

        return $this->factoryPage($page);
    }

    public function factoryPage(Page $page, bool $setChildren = true, bool $setBlocks = true): array
    {
        $data = [
            'id' => $page->id,
            'title' => $page->title()?->toString(),
            'lastEditedTime' => $page->lastEditedTime->format('Y-m-d H:i:s'),
            'archived' => $page->archived,
        ];
        $data['cover'] = [];
        if ($page->cover) {
            $data['cover'] = $page->cover->toArray();
        }
        $data['icon'] = [];
        if ($page->hasIcon()) {
            $data['icon'] = $page->icon->toArray();
        }
        $data['breadcrumb'] = $this->pageUtils->breadcrumb($page);
        $data['link'] = end($data['breadcrumb'])['link'];
        $data['child_pages'] = [];
        $data['blocks'] = [];
        if ($setChildren) {
            $children = $this->getNotion()->blocks()->findChildren($page->id);
            $data['child_pages'] = $this->pageUtils->childPages($children);
            if ($setBlocks) {
                $blocks = [];
                foreach ($children as $block) {
                    $blocks[] = Blocks::getBlocks($block);
                }
                $data['blocks'] = $blocks;
            }
        }

        //$data = $this->fileUtils->treatment($data);

        $data['fetchDate'] = (new \DateTime())->format('Y-m-d H:i');

        return $data;
    }

    public function getBlock(string $blockId): array
    {
        $block = $this->getNotion()->blocks()->find($blockId);
        $data = $block->toArray();
        $this->getNotion()->blocks()->findChildren($blockId);

        return $data;
    }


}