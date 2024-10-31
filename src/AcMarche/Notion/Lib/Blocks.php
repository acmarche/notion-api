<?php

namespace AcMarche\Notion\Lib;

use Notion\Blocks\BlockInterface;
use Notion\Blocks\BlockType;
use Notion\Blocks\Callout;
use Notion\Blocks\ChildDatabase;
use Notion\Blocks\Column;
use Notion\Blocks\ColumnList;
use Notion\Blocks\Heading2;
use Notion\Blocks\Paragraph;
use Notion\Blocks\Video;

class Blocks
{
    use ConnectTrait;

    public static function getBlocks(BlockInterface $block): array
    {
        return match ($block->metadata()->type) {
            BlockType::Bookmark => self::renderBookmark($block),
            BlockType::Breadcrumb => self::BreadcrumbRenderer($block),
            BlockType::BulletedListItem => self::BulletedListItemRenderer($block),
            BlockType::Callout => self::CalloutRenderer($block),
            BlockType::ChildDatabase => self::ChildDatabaseRenderer($block),
            BlockType::ChildPage => self::ChildPageRenderer($block),
            BlockType::Code => self::CodeRenderer($block),
            BlockType::Column => self::ColumnRenderer($block),
            BlockType::ColumnList => self::ColumnListRenderer($block),
            BlockType::Divider => self::DividerRenderer($block),
            BlockType::Embed => self::EmbedRenderer($block),
            BlockType::Equation => self::EquationRenderer($block),
            BlockType::File => self::FileRenderer($block),
            BlockType::Heading1 => self::Heading1Renderer($block),
            BlockType::Heading2 => self::Heading2Renderer($block),
            BlockType::Heading3 => self::Heading3Renderer($block),
            BlockType::Image => self::ImageRenderer($block),
            BlockType::LinkPreview => self::LinkPreviewRenderer($block),
            BlockType::NumberedListItem => self::NumberedListItemRenderer($block),
            BlockType::Paragraph => self::ParagraphRenderer($block),
            BlockType::Pdf => self::PdfRenderer($block),
            BlockType::Quote => self::QuoteRenderer($block),
            BlockType::TableOfContents => self::TableOfContentsRenderer($block),
            BlockType::ToDo => self::ToDoRenderer($block),
            BlockType::Toggle => self::ToggleRenderer($block),
            BlockType::Video => self::VideoRenderer($block),
            default => ['not found'],
        };
    }

    private static function NumberedListItemRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function LinkPreviewRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function EmbedRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function DividerRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function ImageRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function FileRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function ParagraphRenderer(Paragraph|BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function QuoteRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function TableOfContentsRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function ToDoRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function ToggleRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function ColumnListRenderer(ColumnList|BlockInterface $block): array
    {
        /**
         * @var Column[] $columns
         */
        $columns = array_map(function ($column) {
            $children = [];
            if ($column->metadata()->hasChildren) {
                try {
                    $blocks = self::getNotionStatic()->blocks()->findChildren($column->metadata()->id);
                    foreach ($blocks as $blockChild) {
                        $children[] = $blockChild->toArray();
                        $column->addChild($blockChild);
                    }
                } catch (\Exception $e) {
                }
            }
            $column->blocks = $children;//todo dynamic properties

            return $column;
        }, self::getNotionStatic()->blocks()->findChildren($block->metadata()->id));

        $t = [];
        $i = 0;
        foreach ($columns as $column) {
            $t[$i] = $column->toArray();
            $t[$i]['blocks'] = $column->blocks;
            $i++;
        }
        $data = $block->toArray();
        $data['columns'] = $t;

        return $data;
    }

    private static function ColumnRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function ChildPageRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function CodeRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function EquationRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function VideoRenderer(BlockInterface|Video $block): array
    {
        return $block->toArray();
    }

    private static function ChildDatabaseRenderer(BlockInterface|ChildDatabase $block): array
    {
        return $block->toArray();
    }

    private static function CalloutRenderer(BlockInterface|Callout $block): array
    {
        $blocks = self::getNotionStatic()->blocks()->findChildren($block->metadata()->id);
        $callout = $block->toArray();
        $callout['content'] = $blocks;//to get description event
        return $callout;
    }

    private static function PdfRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function Heading1Renderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function Heading2Renderer(Heading2|BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function Heading3Renderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function BulletedListItemRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function BreadcrumbRenderer(BlockInterface $block): array
    {
        return $block->toArray();
    }

    private static function renderBookmark(BlockInterface $block): array
    {
        return $block->toArray();
    }

}