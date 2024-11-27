<?php

namespace AcMarche\Notion\Lib;

use Symfony\Component\String\Slugger\AsciiSlugger;

class Menu
{
    public function getMenu(): array
    {
        $pages = [];
        $fetch = new PageGet();
        $slugger = new AsciiSlugger('fr_FR');

        try {
            $services = $fetch->fetchById($_ENV['NOTION_SERVICES_ID']);
            foreach ($services['child_pages'] as $page) {
                $slug = strtolower($slugger->slug($page['name']));
                $page['link'] = '/services/'.$slug.'/'.$page['id'];
                $pages [] = $page;
            }
        } catch (\Exception $e) {
        }
        try {
            $root = $fetch->fetchById($_ENV['NOTION_ROOT_ID']);
            foreach ($root['child_pages'] as $page) {
                if ($page['id'] === $_ENV['NOTION_SERVICES_ID']) {
                    continue;
                }
                $slug = strtolower($slugger->slug($page['name']));
                $page['link'] = '/'.$slug.'/'.$page['id'];
                $pages [] = $page;
            }
        } catch (\Exception $e) {
        }

        return $pages;
    }
}