<?php

namespace AcMarche\Notion\Lib;

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

    private function factoryPage(Page $page, bool $setChildren = true, bool $setBlocks = true): array
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
        $breadcrumb = $this->pageUtils->breadcrumb($page);
        $data['link'] = end($breadcrumb)['link'];
        if (count($breadcrumb) > 1) {
            unset($breadcrumb[count($breadcrumb) - 1]);//don't display current page
        }
        $data['breadcrumb'] = $breadcrumb;
        $data['child_pages'] = [];
        $data['blocks'] = [];
        if ($setChildren) {
            $children = [];
            try {
                $children = $this->getNotion()->blocks()->findChildren($page->id);
            } catch (\Exception $e) {
            }
            $data['child_pages'] = $this->pageUtils->childPages($children);
            if ($setBlocks) {
                $blocks = [];
                foreach ($children as $block) {
                    $blocks[] = Blocks::getBlocks($block);
                }
                $data['blocks'] = $blocks;
            }
        }
        $data['excerpt'] = null;
        if (count($data['blocks']) > 0) {
            if ($data['blocks'][0]['type'] === 'paragraph') {
                $data['excerpt'] = $data['blocks'][0];
                unset($data['blocks'][0]);
                $data['blocks'] = array_values($data['blocks']);
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
        $data['children'] = $this->getNotion()->blocks()->findChildren($blockId);

        return $data;
    }


}