<?php

namespace Moellers\XmiDoc\Renderer;

use Moellers\XmiDoc\Model;

class TsdRenderer extends AbstractRenderer
{

    public function renderPackage(Model $package)
    {
        $html = $this->twig->render('package.ts.d.twig', ['model' => $package]);
        $filename = $this->dest . '/' . $package->name . '.d.ts';
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename));
        }
        file_put_contents($filename, $html);
    }
}
