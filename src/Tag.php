<?php
namespace Tiny;

class Tag
{
    private $vDom = array();

    private function __construct($children)
    {
        $this->vDom['children'] = array();
        $this->appendChildren($children);
    }

    public static function __callStatic($tag, $children)
    {
        return (new self($children))->tag($tag);
    }

    public function __call($attr, $args)
    {
        $attr = str_replace('_', '-', $attr);
        $value = current($args);
        if(array_key_exists($attr, $this->vDom)) {
            $this->vDom[$attr] .= " $value";
        }
        $this->vDom[$attr] = $value;
        return $this;
    }

    /**
     * @param string $tagName
     * @return Tag
     */
    private function tag($tagName)
    {
        $this->vDom['tag'] = $tagName;
        return $this;
    }

    /**
     * @param array $children
     * @return Tag
     */
    private function appendChildren($children)
    {
        for($i = 0, $size = count($children); $i < $size; $i++) {
            $child = $children[$i];

            if(is_null($child)) {
                continue;
            }

            if(is_array($child)) {
                $this->appendChildren($child);
                continue;
            }

            if($child instanceof self) {
                $this->vDom['children'][] = $child->vDom;
                continue;
            }

            $this->vDom['children'][] = $child;
        }
    }

    /**
     * @param array $props
     * @return Tag
     */
    public function attrs($props)
    {
        if(!is_array($props)) {
            throw new \Error("attrs must be array: $props.");
        }
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
