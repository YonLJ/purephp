<?php
namespace Tiny;

class Htm
{
    private $vDom = array();

    private function __construct($children)
    {
        $this->vDom['children'] = $this->convert(array_filter($children));
    }

    public static function __callStatic($tag, $children)
    {
        return (new self($children))->tag($tag);
    }

    public function __call($attr, $args)
    {
        $attr = str_replace('_', '-', $attr);
        $value = current($args);
        if(array_key_exists($attr, $this->vDom)) $this->vDom[$attr] .= " $value";
        $this->vDom[$attr] = $value;
        return $this;
    }

    /**
     * @param string $tagName
     * @return Htm
     */
    private function tag($tagName)
    {
        $this->vDom['tag'] = $tagName;
        return $this;
    }

    /**
     * @param array $children
     * @return Htm
     */
    private function convert($children)
    {
        return array_map(function($child) {
            return $child instanceof self
                ? $child->vDom
                : $child;
        }, $children);
    }

    /**
     * @param array $props
     * @return Htm
     */
    public function attrs($props)
    {
        if(!is_array($props)) throw new \Error("attrs must be array: $props.");
        $this->vDom = array_merge($this->vDom, $props);
        return $this;
    }

    /**
     * @return array
     */
    public function getVDom()
    {
        return $this->vDom;
    }
}
