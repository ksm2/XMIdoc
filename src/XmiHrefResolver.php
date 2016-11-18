<?php

namespace Moellers\XmiDoc;

interface XmiHrefResolver
{

    /**
     * Resolve the given hyper reference
     *
     * @param XmiHref $href
     * @return XmiElement
     */
    public function resolve(XmiHref $href): XmiElement;
}
