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
            case 'uml:Operation':
                return $this->loadOperation($element, $model);
            case 'uml:DataType':
                return $this->loadDataType($element, $model);
            case 'uml:EnumerationLiteral':
                return $model;
            case 'uml:Parameter':
                return $this->loadParameter($element, $model);
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
        $model = $this->loadClassifier($class, $model);

        foreach ($class->getElements('ownedOperation') as $operation) {
            $model->children[] = $this->loadElement($operation);
        }

        return $model;
    }

    public function loadDataType(XmiElement $dataType, Model $model): Model
    {
        return $this->loadClassifier($dataType, $model);
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

        $min = null;
        $lowerValue = $property->getElement('lowerValue');
        if ($lowerValue) {
            $min = $lowerValue->getString('value', '0');
        }

        $max = null;
        $upperValue = $property->getElement('upperValue');
        if ($upperValue) {
            $max = $upperValue->getString('value');
        }

        if ($min !== null && $max !== null) {
            $model->multiplicity = "[$min..$max]";
        } elseif ($max !== null) {
            $model->multiplicity = "[0..$max]";
        } elseif ($min !== null) {
            $model->multiplicity = "[$min..1]";
        }

        return $model;
    }

    public function loadOperation(XmiElement $operation, Model $model): Model
    {
        $model->isQuery = $operation->getBool('isQuery');

        $model->children = [];
        foreach ($operation->getElements('ownedParameter') as $parameter) {
            $direction = $parameter->getString('direction');
            if ('return' === $direction) {
                $model->type = $this->loadElement($parameter->getElement('type'));
                continue;
            }
            $model->children[] = $this->loadElement($parameter);
        }

        return $model;
    }

    public function loadParameter(XmiElement $element, Model $model): Model
    {
        $model->type = $this->loadElement($element->getElement('type'));

        return $model;
    }

    public function loadClassifier(XmiElement $classifier, Model $model): Model
    {
        $model->children = [];
        foreach ($classifier->getElements('ownedAttribute') as $attribute) {
            $model->children[] = $this->loadElement($attribute);
        }

        foreach ($classifier->getElements('generalization') as $generalization) {
            $model->generalizations[] = $this->loadElement($generalization->getElement('general'));
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
            case 'parameter': return 'M';
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
