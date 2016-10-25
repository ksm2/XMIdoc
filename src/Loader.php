<?php

namespace Moellers\XmiDoc;

class Loader
{

    /**
     * Parser constructor.
     * @param string $filename
     * @return Model
     */
    public function loadXmi(string $filename): Model
    {
        $xml = simplexml_load_file($filename, \SimpleXMLElement::class, 0, 'uml', true);

        $model = new Model('Global');
        $model->filename = 'index.html';
        $model->metadata = $xml->getNamespaces(true);
        $model->metadata['source'] = $filename;
        $model->metadata['name'] = basename($filename);

        $child = $this->loadElement($xml->Package);
        $child->parent = $model;
        $model->children[] = $child;

        return $model;
    }

    public function loadElement(\SimpleXMLElement $element): Model
    {
        $model = new Model((string) $element->attributes()->name);

        $this->loadXmiAttributes($element, $model);

        // Load comment, class, filename
        $model->comment = $this->loadElementComment($element);
        $type = $model->xmiType;
        $model->class = lcfirst(substr($type, 4));
        $model->filename = $this->generateFilename($model);

        switch ($type) {
            case 'uml:Package':
                return $this->loadPackage($element, $model);
            case 'uml:Class':
                return $this->loadClass($element, $model);
            case 'uml:Enumeration':
                return $this->loadEnumeration($element, $model);
            case 'uml:Association':
                return $this->loadAssociation($element, $model);
            case 'uml:Property':
                return $this->loadProperty($element, $model);
            case 'uml:EnumerationLiteral':
                return $model;
            default:
                throw new \Exception('Unknown type: ' . $type);
        }
    }

    public function loadPackage(\SimpleXMLElement $package, Model $model): Model
    {
        // Load children
        $model->children = $this->loadElementChildren($package, $model);

        return $model;
    }

    public function loadClass(\SimpleXMLElement $class, Model $model): Model
    {
        $model->children = [];
        foreach ($class->ownedAttribute as $attrib) {
            $child = $this->loadElement($attrib);
            $child->parent = $model;
            $model->children[] = $child;
        }

        foreach ($class->generalization as $generalization) {
            $model->generalizations[] = (string) $generalization['general'];
        }

        return $model;
    }

    public function loadEnumeration(\SimpleXMLElement $enumeration, Model $model): Model
    {
        $model->children = [];
        foreach ($enumeration->ownedLiteral as $literal) {
            $child = $this->loadElement($literal);
            $child->parent = $model;
            $model->children[] = $child;
        }

        return $model;
    }

    public function loadAssociation(\SimpleXMLElement $assoc, Model $model): Model
    {
        return $model;
    }

    public function loadProperty(\SimpleXMLElement $property, Model $model): Model
    {
        $model->isReadOnly = $property['isReadOnly'] ? (string) $property['isReadOnly'] === 'true' : false;
        $model->isDerived = $property['isDerived'] ? (string) $property['isDerived'] === 'true' : false;
        $model->type = $property['type'] ? (string) $property['type'] : null;

        if ($model->isDerived) {
            $model->name = '/' . $model->name;
        }

        if ($property->defaultValue) {
            if ($property->defaultValue['instance']) {
                $model->defaultValue = (string) $property->defaultValue['instance'];
            }
        }

        return $model;
    }

    /**
     * @param \SimpleXMLElement $element
     * @param Model $parent
     * @return array
     */
    private function loadElementChildren(\SimpleXMLElement $element, Model $parent): array
    {
        $children = [];
        foreach ($element->children() as $element) {
            if ($element->getName() === 'packagedElement') {
                $child = $this->loadElement($element);
                $child->parent = $parent;
                $children[] = $child;
            }
        }
        return $children;
    }

    private function loadElementComment(\SimpleXMLElement $element)
    {
        $path = $element->ownedComment->body;
        if ($path) {
            return (string) $path;
        }

        return null;
    }

    private function loadXmiAttributes(\SimpleXMLElement $element, Model $model)
    {
        foreach ($element->attributes('xmi', true) as $key => $value) {
            $model->{'xmi' . ucfirst($key)} = (string)$value;
        }
    }

    private function generateFilename(Model $model): string
    {
        $letter = $this->getShortLetter($model->class);
        return $model->class . '/' . $letter . $model->xmiId . '.html';
    }

    private function getShortLetter($class)
    {
        switch ($class) {
            case 'property': return 'Y';
            case 'enumerationLiteral': return 'L';
            default: return strtoupper($class[0]);
        }
    }

    /**
     * @param Model $global
     * @return Model[]
     */
    public function flattenModels(Model $global): array
    {
        $array = [$global];
        foreach ($global->children as $child) {
            $array = array_merge($array, $this->flattenModels($child));
        }
        return $array;
    }
}
