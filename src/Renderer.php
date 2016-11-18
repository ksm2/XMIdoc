<?php

namespace Moellers\XmiDoc;

class Renderer
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $dest;

    /**
     * XmiRenderer constructor.
     * @param \Twig_Environment $twig
     * @param string $dest
     */
    public function __construct(\Twig_Environment $twig, string $dest)
    {
        $this->twig = $twig;
        $this->dest = $dest;
    }

    public function copyAssets($source)
    {
        $dest = $this->dest;
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
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

    public function renderGlobal(Model $model, array $metadata)
    {
        $this->render($model, $metadata, 'global.html.twig');
    }

    public function renderIds(array $ids, array $metadata)
    {
        $html = $this->twig->render('ids.html.twig', ['ids' => $ids, 'metadata' => $metadata]);
        $filename = $this->dest . '/ids.html';
        file_put_contents($filename, $html);
    }

    public function renderElements(array $models, array $metadata)
    {
        foreach ($models as $model) {
            $this->renderElement($model, $metadata);
        }
    }

    private function renderElement(Model $model, array $metadata)
    {
        $this->render($model, $metadata, $model->class . '.html.twig');
        foreach ($model->children as $child) {
            $this->renderElement($child, $metadata);
        }
    }

    public function render(Model $model, array $metadata, string $template)
    {
        $html = $this->twig->render($template, ['model' => $model, 'metadata' => $metadata]);
        $filename = $this->dest . '/' . $model->filename;
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename));
        }
        file_put_contents($filename, $html);
    }
}
