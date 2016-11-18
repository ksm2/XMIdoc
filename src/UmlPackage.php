<?php

namespace Moellers\XmiDoc;

class UmlPackage extends XmiElement
{

    private $uri;

    public function __construct(XmiFile $file, \SimpleXMLElement $element)
    {
        parent::__construct($file, null, $element, $this);
        $this->uri = $element['URI'];
    }

    public function getURI(): string
    {
        return $this->uri;
    }
}
