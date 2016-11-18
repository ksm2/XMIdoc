<?php

namespace Moellers\XmiDoc;

class XmiIdref
{

    /**
     * @var string
     */
    private $xmiId;

    public function __construct(\SimpleXMLElement $element)
    {
        $this->xmiId = $element->attributes('xmi', true)['idref'];
    }

    public function getXmiId(): string
    {
        return $this->xmiId;
    }
}
