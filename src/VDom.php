<?php
namespace Tiny;

class VDom {
    /**
     * @var \DOMDocument
     */
    public $document;

    public function __construct()
    {
        $this->document = new \DOMDocument();
    }

    /**
     * @param \DOMElement $dom
     * @param array|string $children
     */
    private function appendChildren(&$dom, $children)
    {
        if(array_key_exists(0, $children)) {
            foreach($children as $child) {
                $node = $this->render($child);
                if(!empty($node)) $dom->appendChild($node);
            }
            return;
        }
        $node = $this->render($children);
        if(!empty($node)) $dom->appendChild($node);
    }

    /**
     * @param \DOMElement $dom
     * @param array $vDom
     */
    private function appendAttr(&$dom, $vDom)
    {
        foreach($vDom as $key => $value) {
            if($key === 'children') {
                $this->appendChildren($dom, $value);
                continue;
            }

            $attrNode = $this->document->createAttribute($key);
            if (!$attrNode) {
                trigger_error("attribute $key is invalid", E_USER_WARNING);
                continue;
            }

            $attrNode->value = $value;
            $dom->appendChild($attrNode);
        }
    }

    /**
     * @param array|string $vDom
     * @return \DOMElement
     */
    private function render($vDom)
    {
        if (is_array($vDom) && count($vDom) === 1) $vDom = current($vDom);
        if(!is_array($vDom)) return $this->document->createTextNode((string)$vDom);
        if(empty($vDom)) return;

        $tagName = $vDom['tag'];
        if(empty($tagName)) throw new \Error('vDom must has a tag: '. var_export($vDom, true) . '.');

        $dom = $this->document->createElement($tagName);
        if(!$dom) throw new \Error("tag $tagName is invalid.");

        unset($vDom['tag']);
        $this->appendAttr($dom, $vDom);
        return $dom;
    }

    /**
     * @param Tag $view
     * @return \DOMElement
     */
    public function h($view)
    {
        return $this->render($view->getVDom());
    }

    /**
     * @param \DOMNode $node
     * @return string|false
     */
    public function saveHTML($node = null)
    {
        return $this->document->saveHTML($node);
    }

    /**
     * @param string $filename
     * @return int|false
     */
    public function saveHTMLFile($filename)
    {
        return $this->document->saveHTMLFile($filename);
    }

    /**
     * @param string $filename
     * @param int $options
     * @return int|false
     */
    public function save($filename, $options = 0)
    {
        return $this->document->save($filename, $options);
    }

    /**
     * @param \DOMNode $node
     * @param int $options
     * @return string|false
     */
    public function saveXML($node = null, $options = 0)
    {
        return $this->document->saveXML($node, $options);
    }
}
