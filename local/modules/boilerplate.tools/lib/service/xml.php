<?php

namespace Boilerplate\Tools\Service;

use Boilerplate\Tools\Exception\XmlException;

class Xml
{
    /**
     * Декодирует XML файл в объект
     * @return \$1|\SimpleXMLElement
     * @throws XmlException
     */
    public static function decodeFile($filePath)
    {
        // Ручная обработка ошибок
        libxml_use_internal_errors(true);

        $data = simplexml_load_file($filePath);

        if (!$data) {
            // Получим ошибки интерпретации XML в объект
            $errors = libxml_get_errors();
            $errorMessage = '';

            foreach ($errors as $error) {
                $errorMessage .= $error->level . ' - ' . $error->code . ' - ' . $error->message . ' (Line: ' . $error->line . ', Column: ' . $error->column . '); ';
            }

            throw new XmlException('XML: File interpretation error: ' . $errorMessage);
        }

        return $data;
    }

    /**
     * Декодирует XML строку в объект
     * @return \$1|\SimpleXMLElement
     * @throws XmlException
     */
    public static function decodeString($string)
    {
        // Ручная обработка ошибок
        libxml_use_internal_errors(true);

        $data = simplexml_load_string((string)$string);

        if (!$data) {
            // Получим ошибки интерпретации XML в объект
            $errors = libxml_get_errors();
            $errorMessage = '';

            foreach ($errors as $error) {
                $errorMessage .= $error->level . ' - ' . $error->code . ' - ' . $error->message . ' (Line: ' . $error->line . ', Column: ' . $error->column . '); ';
            }

            throw new XmlException('XML: String interpretation error: ' . $errorMessage);
        }

        return $data;
    }

    /**
     * Кодирует массив в XML строку
     */
    public static function encode(array $array, bool $formatOutput = false): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');

        $doc->formatOutput = $formatOutput;

        foreach ($array as $key => $value) {
            self::createDomNode($key, $value, $doc);
        }

        return $doc->saveXML();
    }

    /**
     * Рекурсивно добавляет в DOMDocument узлы из массива
     * @param $value
     * @param $root
     * @param $document
     * @return void
     */
    private static function createDomNode(string $key, $value, $root, $document = false)
    {
        if (!$document) {
            $document = $root;
        }

        if (!is_array($value)) {
            $root->appendChild($document->createElement($key))
                ->appendChild($document->createTextNode($value));
        } else {
            $subRoot = $root->appendChild($document->createElement($key));

            foreach ($value as $subKey => $subValue) {
                self::createDomNode($subKey, $subValue, $subRoot, $document);
            }
        }
    }
}
