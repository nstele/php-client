<?php
namespace SplitIO\Test\Suite\Sdk;

use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use SplitIO\Component\Cache\SegmentCache;
use SplitIO\Component\Cache\SplitCache;
use SplitIO\Grammar\Condition\Matcher;
use SplitIO\Grammar\Condition\Matcher\DataType\DateTime;

class MatcherTest extends \PHPUnit_Framework_TestCase
{
    private function setupSplitApp()
    {
        $parameters = ['scheme' => 'redis',
            'host' => "localhost",
            'port' => 6379,
            'timeout' => 881
        ];
        $options = ['prefix' => ''];
        $sdkConfig = array(
            'log' => array('adapter' => 'stdout', 'level' => 'info'),
            'cache' => array('adapter' => 'predis',
                'parameters' => $parameters,
                'options' => $options
            )
        );

        $splitFactory = \SplitIO\Sdk::factory('apikey', $sdkConfig);
        $splitFactory->client();
    }

    private function populateSegmentCache()
    {
        SegmentCache::registerSegment('segmentA');
        $segmentCache = new SegmentCache();
        $segmentCache->addToSegment('segmentA', array('id1', 'id2', 'id3'));
    }

    public function testSartsWithMatcher()
    {
        $this->setupSplitApp();

        $condition = array(
            'matcherType' => 'STARTS_WITH',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'some',
                    'another',
                    'yetAnother',
                )
            )
        );

        $matcher = Matcher::factory($condition);
        $this->assertEquals($matcher->evaluate('someItem'), true);
        $this->assertEquals($matcher->evaluate('anotherItem'), true);
        $this->assertEquals($matcher->evaluate('yetAnotherItem'), true);
        $this->assertEquals($matcher->evaluate('withoutPrefix'), false);
        $this->assertEquals($matcher->evaluate(''), false);
        $this->assertEquals($matcher->evaluate(null), false);
    }

    public function testEndsWithMatcher()
    {
        $this->setupSplitApp();

        $condition = array(
            'matcherType' => 'ENDS_WITH',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'ABC',
                    'DEF',
                    'GHI',
                )
            )
        );

        $matcher = Matcher::factory($condition);
        $this->assertEquals($matcher->evaluate('testABC'), true);
        $this->assertEquals($matcher->evaluate('testDEF'), true);
        $this->assertEquals($matcher->evaluate('testGHI'), true);
        $this->assertEquals($matcher->evaluate('testJKL'), false);
        $this->assertEquals($matcher->evaluate(''), false);
        $this->assertEquals($matcher->evaluate(null), false);
    }

    public function testContainsStringMatcher()
    {
        $this->setupSplitApp();

        $condition = array(
            'matcherType' => 'CONTAINS_STRING',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'Lorem',
                    'dolor',
                    'consectetur',
                )
            )
        );

        $matcher = Matcher::factory($condition);
        $this->assertEquals($matcher->evaluate('LoremIpsum'), true);
        $this->assertEquals($matcher->evaluate('WEdolor2f'), true);
        $this->assertEquals($matcher->evaluate('Iconsectetur'), true);
        $this->assertEquals($matcher->evaluate('Curabitur'), false);
        $this->assertEquals($matcher->evaluate(''), false);
        $this->assertEquals($matcher->evaluate(null), false);
    }


    public function testAllKeysMatcher()
    {
        $this->setupSplitApp();

        $condition = array(
            'matcherType' => 'ALL_KEYS',
        );

        $matcher = Matcher::factory($condition);
        $this->assertEquals($matcher->evaluate('Ipsum'), true);
        $this->assertEquals($matcher->evaluate('SitAmet'), true);
        $this->assertEquals($matcher->evaluate('sectetur'), true);
        $this->assertEquals($matcher->evaluate('Curabitur'), true);
        $this->assertEquals($matcher->evaluate(''), true);      // review this with @sarrubia
        $this->assertEquals($matcher->evaluate(null), true);    // same here
    }

    public function testInSegmentMatcher()
    {
        $this->setupSplitApp();
        $this->populateSegmentCache();
        $condition = array(
            'matcherType' => 'IN_SEGMENT',
            'userDefinedSegmentMatcherData' => array(
                'segmentName' => 'segmentA'
            )
        );

        $matcher = Matcher::factory($condition);
        $this->assertEquals($matcher->evaluate('id1'), true);
        $this->assertEquals($matcher->evaluate('id2'), true);
        $this->assertEquals($matcher->evaluate('id3'), true);
        $this->assertEquals($matcher->evaluate('id4'), false);
        $this->assertEquals($matcher->evaluate(''), false);
        $this->assertEquals($matcher->evaluate(null), false);
    }

    public function testWhitelistMatcher()
    {
        $condition = array(
            'matcherType' => 'WHITELIST',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'LoremIpsum',
                    'dolorSitAmet',
                    'consectetur',
                )
            )
        );

        $matcher = Matcher::factory($condition);
        $this->assertEquals($matcher->evaluate('LoremIpsum'), true);
        $this->assertEquals($matcher->evaluate('dolorSitAmet'), true);
        $this->assertEquals($matcher->evaluate('consectetur'), true);
        $this->assertEquals($matcher->evaluate('Curabitur'), false);
        $this->assertEquals($matcher->evaluate(''), false);
        $this->assertEquals($matcher->evaluate(null), false);
    }

    public function testEqualToMatcher()
    {
        $condition1 = array(
            'matcherType' => 'EQUAL_TO',
            'unaryNumericMatcherData' => array(
                'value' => 7,
                'dataType' => 'NUMBER'
            )
        );

        $condition2 = array(
            'matcherType' => 'EQUAL_TO',
            'unaryNumericMatcherData' => array(
                'value' => 456678987,
                'dataType' => 'DATETIME'
            )
        );


        $matcher1 = Matcher::factory($condition1);
        $this->assertEquals($matcher1->evaluate(7), true);
        $this->assertEquals($matcher1->evaluate(12), false);
        $this->assertEquals($matcher1->evaluate(-7), false);
        $this->assertEquals($matcher1->evaluate('someString'), false);
        $this->assertEquals($matcher1->evaluate(null), false);

        $matcher2 = Matcher::factory($condition2);
        $this->assertEquals($matcher2->evaluate(456678), true);
        $this->assertEquals($matcher2->evaluate(123456), false);
        $this->assertEquals($matcher2->evaluate('some string'), false);
        $this->assertEquals($matcher2->evaluate(''), false);
        $this->assertEquals($matcher2->evaluate(null), false);
    }

    public function testGreaterThanOrEqualToMatcher()
    {
        $condition1 = array(
            'matcherType' => 'GREATER_THAN_OR_EQUAL_TO',
            'unaryNumericMatcherData' => array(
                'value' => 7,
                'dataType' => 'NUMBER'
            )
        );

        $condition2 = array(
            'matcherType' => 'GREATER_THAN_OR_EQUAL_TO',
            'unaryNumericMatcherData' => array(
                'value' => 456678987,
                'dataType' => 'DATETIME'
            )
        );


        $matcher1 = Matcher::factory($condition1);
        $this->assertEquals($matcher1->evaluate(7), true);
        $this->assertEquals($matcher1->evaluate(12), true);
        $this->assertEquals($matcher1->evaluate(-7), false);
        $this->assertEquals($matcher1->evaluate('someString'), false);
        $this->assertEquals($matcher1->evaluate(''), false);
        $this->assertEquals($matcher1->evaluate(null), false);

        $matcher2 = Matcher::factory($condition2);
        $this->assertEquals($matcher2->evaluate(456678), true);
        $this->assertEquals($matcher2->evaluate(456688), true);
        $this->assertEquals($matcher2->evaluate(123456), false);
        $this->assertEquals($matcher2->evaluate('some string'), false);
        $this->assertEquals($matcher2->evaluate(''), false);
        $this->assertEquals($matcher2->evaluate(null), false);
    }

    public function testLessThanOrEqualToMatcher()
    {
        $condition1 = array(
            'matcherType' => 'LESS_THAN_OR_EQUAL_TO',
            'unaryNumericMatcherData' => array(
                'value' => 7,
                'dataType' => 'NUMBER'
            )
        );

        $condition2 = array(
            'matcherType' => 'LESS_THAN_OR_EQUAL_TO',
            'unaryNumericMatcherData' => array(
                'value' => 456678987,
                'dataType' => 'DATETIME'
            )
        );


        $matcher1 = Matcher::factory($condition1);
        $this->assertEquals($matcher1->evaluate(7), true);
        $this->assertEquals($matcher1->evaluate(12), false);
        $this->assertEquals($matcher1->evaluate(-7), true);
        $this->assertEquals($matcher1->evaluate('someString'), false);
        $this->assertEquals($matcher1->evaluate(''), false);
        $this->assertEquals($matcher1->evaluate(null), false);

        $matcher2 = Matcher::factory($condition2);
        $this->assertEquals($matcher2->evaluate(456678), true);
        $this->assertEquals($matcher2->evaluate(456668), true);
        $this->assertEquals($matcher2->evaluate(123456), true);
        $this->assertEquals($matcher2->evaluate('some string'), false);
        $this->assertEquals($matcher2->evaluate(''), false);
        $this->assertEquals($matcher2->evaluate(null), false);
    }

    public function testBetweenMatcher()
    {
        $condition1 = array(
            'matcherType' => 'BETWEEN',
            'betweenMatcherData' => array(
                'start' => -7,
                'end' => 7,
                'dataType' => 'NUMBER'
            )
        );

        $condition2 = array(
            'matcherType' => 'BETWEEN',
            'betweenMatcherData' => array(
                'start' => 454678987,
                'end' => 456678987,
                'dataType' => 'DATETIME'
            )
        );


        $matcher1 = Matcher::factory($condition1);
        $this->assertEquals($matcher1->evaluate(7), true);
        $this->assertEquals($matcher1->evaluate(-7), true);
        $this->assertEquals($matcher1->evaluate(5), true);
        $this->assertEquals($matcher1->evaluate(12), false);
        $this->assertEquals($matcher1->evaluate(-12), false);
        $this->assertEquals($matcher1->evaluate('someString'), false);
        $this->assertEquals($matcher1->evaluate(''), false);
        $this->assertEquals($matcher1->evaluate(null), false);

        $matcher2 = Matcher::factory($condition2);
        $this->assertEquals($matcher2->evaluate(454678), true);
        $this->assertEquals($matcher2->evaluate(456678), true);
        $this->assertEquals($matcher2->evaluate(455558), true);
        $this->assertEquals($matcher2->evaluate(123456), false);
        $this->assertEquals($matcher2->evaluate(458768), false);
        $this->assertEquals($matcher2->evaluate('some string'), false);
        $this->assertEquals($matcher2->evaluate(''), false);
        $this->assertEquals($matcher2->evaluate(null), false);
    }

    public function testContainsAllOfSetMatcher()
    {
        $condition = array(
            'matcherType' => 'CONTAINS_ALL_OF_SET',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'LoremIpsum',
                    'dolorSitAmet',
                    'consectetur',
                )
            )
        );
        $matcher = Matcher::factory($condition);
        $this->assertTrue(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur')
            )
        );
        $this->assertTrue(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur', 'extra')
            )
        );
        $this->assertFalse($matcher->evaluate(array('LoremIpsum', 'dolorSitAmet')));
        $this->assertFalse($matcher->evaluate(array()));
        $this->assertFalse($matcher->evaluate(null));
    }

    public function testContainsAnyOfSetMatcher()
    {
        $condition = array(
            'matcherType' => 'CONTAINS_ANY_OF_SET',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'LoremIpsum',
                    'dolorSitAmet',
                    'consectetur',
                )
            )
        );
        $matcher = Matcher::factory($condition);
        $this->assertTrue(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur')
            )
        );
        $this->assertTrue(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur', 'extra')
            )
        );
        $this->assertTrue($matcher->evaluate(array('LoremIpsum', 'dolorSitAmet')));
        $this->assertFalse($matcher->evaluate(array('extra')));
        $this->assertFalse($matcher->evaluate(array()));
        $this->assertFalse($matcher->evaluate(null));
    }

    public function testIsEqualToSetMatcher()
    {
        $condition = array(
            'matcherType' => 'EQUAL_TO_SET',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'LoremIpsum',
                    'dolorSitAmet',
                    'consectetur',
                )
            )
        );
        $matcher = Matcher::factory($condition);
        $this->assertTrue(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur')
            )
        );
        $this->assertFalse(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur', 'extra')
            )
        );
        $this->assertFalse($matcher->evaluate(array('LoremIpsum', 'dolorSitAmet')));
        $this->assertFalse($matcher->evaluate(array('extra')));
        $this->assertFalse($matcher->evaluate(array()));
        $this->assertFalse($matcher->evaluate(null));
    }

    public function testIsPartOfSetMatcher()
    {
        $condition = array(
            'matcherType' => 'PART_OF_SET',
            'whitelistMatcherData' => array(
                'whitelist' => array(
                    'LoremIpsum',
                    'dolorSitAmet',
                    'consectetur',
                )
            )
        );
        $matcher = Matcher::factory($condition);
        $this->assertTrue(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur')
            )
        );
        $this->assertFalse(
            $matcher->evaluate(
                array('LoremIpsum', 'dolorSitAmet', 'consectetur', 'extra')
            )
        );
        $this->assertTrue($matcher->evaluate(array('LoremIpsum', 'dolorSitAmet')));
        $this->assertFalse($matcher->evaluate(array('extra')));
        $this->assertFalse($matcher->evaluate(array()));
        $this->assertFalse($matcher->evaluate(null));
    }
}
