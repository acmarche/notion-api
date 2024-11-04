<?php

namespace AcMarche\Notion\Lib;

use Notion\Blocks\BlockInterface;
use Notion\Blocks\ChildPage;
use Notion\Pages\Page;
use Symfony\Component\String\Slugger\AsciiSlugger;

class PageUtils
{
    use ConnectTrait;

    public array $path = [];
    private AsciiSlugger $slugger;

    public function __construct()
    {
        self::initializeNotion();
        $this->slugger = new AsciiSlugger('fr_FR');
    }

    public function breadcrumb(Page $page): array
    {
        $this->path = [];
        $pages = [];

        $pages[] = [
            'id' => $page->id,
            'name' => $page->title()->toString(),
            'icon' => null,
        ];

        while ($page) {
            if ($page->parent->isPage()) {
                if ($page->parent->id == $_ENV['NOTION_ROOT_ID']) {
                    $page = null;
                } else {
                    try {
                        $page = $this->getNotion()->pages()->find($page->parent->id);
                        if ($page) {
                            $pages[] = [
                                'id' => $page->id,
                                'name' => $page->title()->toString(),
                                'icon' => null,
                            ];
                        }
                    } catch (\Exception $e) {
                        $page = null;
                    }
                }
            } else {
                $page = null;
            }
        }

        $pages = array_reverse($pages);

        return array_map(function ($page) {
            $slug = strtolower($this->slugger->slug($page['name']));
            $this->path[] = '/'.$slug.'/'.$page['id'];
            $page['link'] = join('', $this->path);
            $page['slug'] = $slug;

            return $page;
        }, $pages);
    }

    /**
     * @param Page $page
     * @param ChildPage[]|BlockInterface[] $blocks
     * @return array
     */
    public function childPages(array $blocks): array
    {
        $childPages = [];

        foreach ($blocks as $block) {
            if ($block->metadata()->type->value == "child_page") {
                //to get cover,icon
                $page = $this->getNotion()->pages()->find($block->metadata()->id);
                $breadcrumb = $this->breadcrumb($page);
                $cover = [];
                if ($page->cover) {
                    $cover = $page->cover->toArray();
                }
                $icon = [];
                if ($page->hasIcon()) {
                    $icon = $page->icon->toArray();
                }
                $link = end($breadcrumb)['link'];
                $slug = strtolower($this->slugger->slug($page->title()->toString()));
                if ($page->id === $_ENV['NOTION_ROOMS_PAGE_ID']) {
                    $link = '/services/'.$slug.'/'.$page->id; //hack
                }
                $childPages[] = [
                    'id' => $page->id,
                    'type' => 'page',
                    'name' => $page->title()->toString(),
                    'slug' => $slug,
                    'cover' => $cover,
                    'icon' => $icon,
                    'link' => $link,
                    'breadcrumb' => $breadcrumb,
                ];
            }
        }

        return $childPages;
    }
}