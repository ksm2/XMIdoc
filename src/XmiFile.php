<?php

namespace Moellers\XmiDoc;

class XmiFile
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var XmiElement[]
     */
    private $ids = array();

    /**
     * @var XmiElement[]
     */
    private $packages;

    /**
     * @var XmiHrefResolver
     */
    private $resolver;

    public function __construct(XmiHrefResolver $resolver, string $url)
    {
        $this->resolver = $resolver;
        $this->url = $url;

        $filename = 'resources/' . preg_replace('/[^\w.]/', '-', $url);
        if (!file_exists($filename)) {
            echo 'Downloading ' . $url . PHP_EOL;
            file_put_contents($filename, file_get_contents($url));
        }

        /** @var XmiElement $xml */
        $xml = \simplexml_load_file($filename, \SimpleXMLElement::class, 0);

        /** @var XmiElement[] $elements */
        $packages = $xml->xpath('//uml:Package[@URI]');
        $this->packages = array_map(function (\SimpleXMLElement $package) {
           return new UmlPackage($this, $package);
        }, $packages);
    }

    /**
     * @return XmiElement[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    public function setElementById(string $id, XmiElement $element)
    {
        $this->ids[$id] = $element;
    }

    public function getElementById(string $id): XmiElement
    {
        if (empty($id) || !isset($this->ids[$id])) {
            throw new \LogicException('There is no element with XMI id #' . $id);
        }
        return $this->ids[$id];
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    public function resolve(XmiHref $href): XmiElement
    {
        return $this->resolver->resolve($href);
    }
}
