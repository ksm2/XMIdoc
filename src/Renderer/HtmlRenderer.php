<?php

namespace Moellers\XmiDoc\Renderer;

use Moellers\XmiDoc\Model;

class HtmlRenderer extends AbstractRenderer
{

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
