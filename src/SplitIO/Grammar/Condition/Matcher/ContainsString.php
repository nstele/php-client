<?php
namespace SplitIO\Grammar\Condition\Matcher;

use SplitIO\Split as SplitApp;
use SplitIO\Grammar\Condition\Matcher;

class ContainsString extends AbstractMatcher
{
    protected $ContainsStringMatcherData = null;

    public function __construct($data, $negate = false, $attribute = null)
    {
        parent::__construct(Matcher::CONTAINS_STRING, $negate, $attribute);

        $this->containsStringMatcherData = $data;
    }

    protected function evalKey($key)
    {
        if (!is_array($this->containsStringMatcherData)) {
            return false;
        }

        foreach ($this->containsStringMatcherData as $item) {
            if (is_string($item) && strpos($item, $key) !== false) {
                return true;
            }
        }
    
        return false;
    }
}