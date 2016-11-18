<?php

namespace Moellers\XmiDoc;

class Loader implements XmiHrefResolver
{

    private $global;
    private $resolvedFiles = array();
    private $resolvedElements = array();

    /**
     * Loader constructor.
     */
    public function __construct()
    {
        $model = new Model('Global');
        $model->filename = 'index.html';
        $model->name = 'Index';
        $model->metadata = [
            'name' => 'OMG XMI Documentation',
        ];

        $this->global = $model;
    }

    /**
     * @return Model
     */
    public function getGlobal(): Model
    {
        return $this->global;
    }

    /**
     * Parser constructor.
     * @param string $url
     */
    public function loadXmi(string $url)
    {
        $xml = $this->resolveXmiFile($url);
        foreach ($xml->getPackages() as $package) {
            $model = $this->loadElement($package);
            $model->parent = $this->global;
            $this->global->children[] = $model;
            $model->metadata = [
                'source' => $url,
                'name' => basename($url),
            ];
        }
    }

    public function loadElement(XmiElement $element): Model
    {
        $href = $element->getFile()->getUrl() . '#' . $element->getXmiId();
        if (isset($this->resolvedElements[$href])) {
            return $this->resolvedElements[$href];
        }

        $model = new Model($element->getName());
        $this->resolvedElements[$href] = $model;

        $model->parent = $element->getParent() ? $this->loadElement($element->getParent()) : $this->global;
        $model->href = $href;
        $model->xmiId = $element->getXmiId();
        $model->xmiType = $element->getXmiType();

        // Load comment, class, filename
        $type = $model->xmiType;
        $model->class = lcfirst(substr($type, 4));
        $model->package = $element->getPackageStr();
        $model->filename = $this->generateFilename($model);
        $model->comment = $this->loadElementComment($element);

        switch ($type) {
            case 'uml:Package':
            case 'uml:Profile':
                return $this->loadPackage($element, $model);
            case 'uml:Class':
                return $this->loadClass($element, $model);
            case 'uml:Enumeration':
                return $this->loadEnumeration($element, $model);
            case 'uml:Association':
                return $this->loadAssociation($element, $model);
            case 'uml:Property':
                return $this->loadProperty($element, $model);
            case 'uml:DataType':
                return $this->loadDataType($element, $model);
            case 'uml:EnumerationLiteral':
                return $model;
            case 'uml:PrimitiveType':
                return $model;
            case 'uml:Extension':
                return $model;
            case 'uml:Stereotype':
                return $model;
            default:
                throw new \Exception('Unknown type: ' . $type);
        }
    }

    public function loadPackage(XmiElement $package, Model $model): Model
    {
        // Load children
        $model->children = $this->loadElementChildren($package, $model);
        $model->URI = $package->getPackage()->getURI();

        return $model;
    }

    public function loadClass(XmiElement $class, Model $model): Model
    {
        $model->children = [];
        foreach ($class->getElements('ownedAttribute') as $attrib) {
            $child = $this->loadElement($attrib);
            $model->children[] = $child;
        }

        foreach ($class->getElements('generalization') as $generalization) {
            $model->generalizations[] = $this->loadElement($generalization->getElement('general'));
        }

        return $model;
    }

    public function loadDataType(XmiElement $dataType, Model $model): Model
    {
        $model->children = [];
        foreach ($dataType->getElements('ownedAttribute') as $attrib) {
            $model->children[] = $this->loadElement($attrib);
        }

        foreach ($dataType->getElements('generalization') as $generalization) {
            $model->generalizations[] = $this->loadElement($generalization->getElement('general'));
        }

        return $model;
    }

    public function loadEnumeration(XmiElement $enumeration, Model $model): Model
    {
        $model->children = [];
        foreach ($enumeration->getElements('ownedLiteral') as $literal) {
            $model->children[] = $this->loadElement($literal);
        }

        return $model;
    }

    public function loadAssociation(XmiElement $assoc, Model $model): Model
    {
        return $model;
    }

    public function loadProperty(XmiElement $property, Model $model): Model
    {
        $model->isReadOnly = $property->getBool('isReadOnly');
        $model->isDerived = $property->getBool('isDerived');
        $type = $property->getElement('type');
        $model->type = $this->loadElement($type);

        if ($model->isDerived) {
            $model->name = '/' . $model->name;
        }

        $defaultValue = $property->getElement('defaultValue');
        if ($defaultValue) {
            if ($defaultValue->has('value')) {
                $model->defaultValue = $defaultValue->getString('value');
            } elseif ($defaultValue->has('instance')) {
                $model->defaultValue = $defaultValue->getString('instance');
            }
        }

        return $model;
    }

    /**
     * @param XmiElement $element
     * @param Model $parent
     * @return array
     */
    private function loadElementChildren(XmiElement $element, Model $parent): array
    {
        $children = [];
        foreach ($element->getElements('packagedElement') as $element) {
            $children[] = $this->loadElement($element);
        }
        return $children;
    }

    private function loadElementComment(XmiElement $element)
    {
        $path = $element->getElement('ownedComment') ? $element->getElement('ownedComment')->getString('body') : false;
        if ($path) {
            return $path;
        }

        return null;
    }

    private function generateFilename(Model $model): string
    {
        if ($model->class === 'package') {
            return $model->class . '/' . $model->name . '.html';
        }

        $letter = $this->getShortLetter($model->class);
        return $model->class . '/' . $letter . $model->xmiId . '.html';
    }

    private function getShortLetter($class)
    {
        switch ($class) {
            case 'property': return 'Y';
            case 'enumerationLiteral': return 'L';
            case 'primitiveType': return 'T';
            default: return strtoupper($class[0]);
        }
    }

    /**
     * @return Model[]
     */
    public function flattenModels(): array
    {
        return $this->resolvedElements;
    }

    /**
     * Resolve the given hyper reference
     *
     * @param XmiHref $href
     * @return XmiElement
     */
    public function resolve(XmiHref $href): XmiElement
    {
        $file = $this->resolveXmiFile($href->getUrl());
        return $file->getElementById($href->getXmiId());
    }

    /**
     * @param string $url
     * @return XmiFile
     */
    public function resolveXmiFile(string $url): XmiFile
    {
        if (!array_key_exists($url, $this->resolvedFiles)) {
            $this->resolvedFiles[$url] = new XmiFile($this, $url);
        }

        return $this->resolvedFiles[$url];
    }
}
