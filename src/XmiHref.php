<?php

namespace Moellers\XmiDoc;

class XmiHref
{

    /**
     * @var string
     */
    private $xmiId;

    /**
     * @var string
     */
    private $xmiType;

    /**
     * @var string
     */
    private $url;

    public function __construct(\SimpleXMLElement $element)
    {
        $this->xmiType = $element->attributes('xmi', true)['type'];
        $href = $element['href'];
        $pos = strrpos($href, '#');
        $this->url = substr($href, 0, $pos);
        $this->xmiId = substr($href, $pos + 1);
    }

    /**
     * @return string
     */
    public function getXmiId(): string
    {
        return $this->xmiId;
    }

    /**
     * @return string
     */
    public function getXmiType(): string
    {
        return $this->xmiType;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
