<?php

namespace Moellers\XmiDoc;

class Model
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $xmiType;

    /**
     * @var string
     */
    public $xmiId;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string|null
     */
    public $comment;

    /**
     * @var string|null
     */
    public $multiplicity;

    /**
     * @var Model[]
     */
    public $children;

    /**
     * @var Model[]
     */
    public $generalizations;

    /**
     * @var Model[]
     */
    public $specializations;

    /**
     * @var Model|null
     */
    public $parent;

    /**
     * @var string
     */
    public $filename;

    /**
     * @var bool
     */
    public $isReadOnly;

    /**
     * @var bool
     */
    public $isDerived;

    /**
     * @var string
     */
    public $defaultValue;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $package;

    /**
     * @var string
     */
    public $href;

    /**
     * @param string $type
     * @return Model[]
     */
    public function getChildrenOf(string $type): array
    {
        return array_filter($this->children, function (Model $model) use ($type) {
            return $model->xmiType === $type;
        });
    }

    /**
     * @return Model[]
     */
    public function getParents(): array
    {
        if (!$this->parent) {
            return [];
        }

        return array_merge($this->parent->getParents(), [$this->parent]);
    }

    public function getPropertyHash(): string
    {
        $props = [];

        if ($this->isReadOnly) $props[] = 'readOnly';

        if (count($props)) {
            return '{' . implode(', ', $props) . '}';
        }

        return '';
    }

    /**
     * Model constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->children = [];
        $this->generalizations = [];
        $this->specializations = [];
        $this->isReadOnly = false;
        $this->isDerived = false;
    }
}
