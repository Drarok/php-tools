<?php

namespace Drarok\PHPTools;

class Config
{
    private $data;

    public static function getInstance()
    {
        return new static(__DIR__ . '/../config.json');
    }

    private function __construct($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exist', $filePath));
        }

        $this->data = json_decode(file_get_contents($filePath), true);
    }

    public function getVersions()
    {
        return $this->getArrayByKey('versions');
    }

    public function getPackages()
    {
        return $this->getArrayByKey('packages');
    }

    private function getArrayByKey($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : [];
    }
}
