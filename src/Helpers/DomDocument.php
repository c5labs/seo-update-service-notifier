<?php
/**
 * Demonstration Helper File.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
namespace Concrete\Package\SeoUpdateServiceNotifier\Src\Helpers;

use DOMDocument as BaseDocument;

defined('C5_EXECUTE') or die('Access Denied.');

class DOMDocument extends BaseDocument
{
    /**
     * Recursivly add an array to this document.
     *
     * @param mixed $root
     * @param array  $contents
     */
    public function fillFromArray(array $array)
    {
        $this->addElements($this, $array);

        return $this;
    }

    /**
     * Recursivly add an array to a root element / document.
     *
     * @param mixed $root
     * @param array  $contents
     */
    protected function addElements($root, array $contents)
    {
        foreach ($contents as $k => $v) {
            if (is_array($v)) {

                if (is_string($k)) {
                    $element = $this->createElement($k);
                } else {
                    $element = $root;
                }

                $this->addElements($element, $v);
            } else {
                $element = $this->createElement($k, $v);
            }

            if ($element !== $root) {
                $root->appendChild($element);
            }
        }
    }
}