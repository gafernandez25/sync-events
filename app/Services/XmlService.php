<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;

class XmlService
{
    public function parseXml(string $xmlContent): SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        libxml_clear_errors();

        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Invalid XML received from external events provider.');
        }

        return $xml;
    }
}
