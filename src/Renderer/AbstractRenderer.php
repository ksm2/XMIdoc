<?php

namespace Moellers\XmiDoc\Renderer;

abstract class AbstractRenderer
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $dest;

    /**
     * @param \Twig_Environment $twig
     * @param string $dest
     */
    public function __construct(\Twig_Environment $twig, string $dest)
    {
        $this->twig = $twig;
        $this->dest = $dest;
    }

    /**
     * Copies all assets from a given source path
     *
     * @param string $sourcePath
     */
    public function copyAssets(string $sourcePath)
    {
        $dest = $this->dest;
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                if (!is_dir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                    mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }
}
