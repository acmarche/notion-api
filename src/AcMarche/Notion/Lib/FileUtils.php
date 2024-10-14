<?php

namespace AcMarche\Notion\Lib;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class FileUtils
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function treatment(array $data): array
    {
        $data = $this->do($data);

        /*  $data['child_pages'] = array_map(function ($childPage) {
              return $this->do($childPage);
          }, $data['child_pages']);*/

        return $data;
    }

    private function do(array $data): array
    {
        if (!isset($data['cover'])) {
            return $data;
        }
        $cover = $data['cover'];
        if (isset($cover['file']['url'])) {
            $cover = $this->copyLocal($data['id'], $data['cover']);
        }
        $data['cover'] = $cover;
        if (!isset($childPage['icon'])) {
            return $data;
        }

        $icon = $data['icon'];
        if ($icon['type'] === 'file') {
            $icon = $this->copyLocal($data['id'], $data['icon']);
        }
        $data['icon'] = $icon;

        return $data;
    }

    private function copyLocal(string $id, array $data): array
    {
        $type = $data['type'];
        $tmpName = $type.'-'.$id;

        if (isset($data['file']['extension'])) {
            $localFile = $_ENV['IMAGE_DIRECTORY'].DIRECTORY_SEPARATOR.$tmpName.'.'.$data['file']['extension'];
            if (is_readable($localFile)) {
                return $data;
            }
        }

        $url = $data['file']['url'];
        try {
            $stream = fopen($url, 'r');
            $content = '';
            while (!feof($stream)) {
                $content .= fread($stream, 1024); // Reading by 1KB from file
                flush(); // Forcing output to buffer
            }
            fclose($stream); // Closing the stream
            $this->filesystem->dumpFile($_ENV['IMAGE_DIRECTORY'].DIRECTORY_SEPARATOR.$tmpName, $content);
            $file = new File($_ENV['IMAGE_DIRECTORY'].DIRECTORY_SEPARATOR.$tmpName);
            $extension = $file->guessExtension();
            $name = $tmpName.'.'.$extension;
            $file->move($_ENV['IMAGE_DIRECTORY'].DIRECTORY_SEPARATOR, $name);
            $url = $_ENV['URL_SITE'].'/notion-php/var/'.$name;
            $data['file']['url'] = $url;
            $data['file']['extension'] = $extension;
        } catch (IOExceptionInterface|\Exception $e) {
        } finally {
            return $data;
        }
    }
}