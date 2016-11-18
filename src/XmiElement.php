<?php

namespace Moellers\XmiDoc;

/**
 * @property string name
 */
class XmiElement
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $attr = array();

    /**
     * @var XmiFile
     */
    private $file;

    /**
     * @var UmlPackage
     */
    private $package;

    /**
     * @var XmiElement
     */
    private $parent;

    public function __construct(XmiFile $file, XmiElement $parent = null, \SimpleXMLElement $element, UmlPackage $package)
    {
        $this->file = $file;
        $this->parent = $parent;
        $this->package = $package;

        $xmiAttr = $element->attributes('xmi', true);
        $this->id = $xmiAttr['id'];
        $this->type = $xmiAttr['type'];
        $file->setElementById($this->id, $this);

        foreach ($element->attributes() as $name => $value) {
            $this->attr[$name] = (string) $value;
        }

        /** @var \SimpleXMLElement $child */
        foreach ($element->children() as $child) {
            $childXmiAttr = $child->attributes('xmi', true);
            $name = $child->getName();

            if ($childXmiAttr['id']) {
                if (!isset($this->attr[$name])) $this->attr[$name] = [];
                $this->attr[$name][] = new XmiElement($file, $this, $child, $package);
                continue;
            }

            if ($child['href']) {
                $this->attr[$name] = new XmiHref($child);
                continue;
            }

            if ($childXmiAttr['idref']) {
                $this->attr[$name] = new XmiIdref($child);
                continue;
            }

            $text = (string)$child;
            if ($child->count() === 0) {
                $this->attr[$name] = $text;
                continue;
            }

            throw new \LogicException('Could not interpret <' . $name . '/> in <' . $element->getName() . '/>!');
        }
    }

    public function has(string $attr): bool
    {
        return isset($this->attr[$attr]);
    }

    public function getBool(string $attr, bool $default = false): bool
    {
        if (!$this->has($attr)) return $default;
        return $this->attr[$attr] === 'true';
    }

    public function getNumber(string $attr, int $default = 0): int
    {
        if (!$this->has($attr)) return $default;
        return (int) $this->attr[$attr];
    }

    public function getString(string $attr, string $default = ''): string
    {
        if (!$this->has($attr)) return $default;
        if (!is_string($this->attr[$attr])) {
            throw new \LogicException($attr . ' is not a string: ' . gettype($this->attr[$attr]));
        }
        return (string) $this->attr[$attr];
    }

    /**
     * @param string $attr
     * @param XmiElement|null $default
     * @return XmiElement|null
     */
    public function getElement(string $attr, XmiElement $default = null)
    {
        if (!$this->has($attr)) return $default;
        $value = $this->attr[$attr];
        if (is_string($value)) {
            return $this->file->getElementById($value);
        }
        if (is_array($value)) {
            return $value[0];
        }
        if ($value instanceof XmiHref) {
            return $this->file->resolve($value);
        }
        if ($value instanceof XmiIdref) {
            return $this->file->getElementById($value->getXmiId());
        }

        return $value;
    }

    /**
     * @param string $attr
     * @param XmiElement[] $default
     * @return XmiElement[]
     */
    public function getElements(string $attr, array $default = array()): array
    {
        if (!$this->has($attr)) return $default;
        $value = $this->attr[$attr];
        if (!is_array($value)) {
            return $default;
        }

        return $value;
    }

    public function getXmiId(): string
    {
        return $this->id;
    }

    public function getXmiType(): string
    {
        return $this->type;
    }

    public function getPackage(): UmlPackage
    {
        return $this->package;
    }

    public function getFile(): XmiFile
    {
        return $this->file;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getName(): string
    {
        return $this->getString('name');
    }

    public function getPackageStr(): string
    {
        return $this->getPackage()->getName();
    }
}
