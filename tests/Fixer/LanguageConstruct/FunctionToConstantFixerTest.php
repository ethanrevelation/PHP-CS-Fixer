<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\LanguageConstruct;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author SpacePossum
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\LanguageConstruct\FunctionToConstantFixer
 */
final class FunctionToConstantFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideTestCases
     */
    public function testFix($expected, $input = null, array $config = [])
    {
        $this->fixer->configure($config);

        $this->doTest($expected, $input);
    }

    public function provideTestCases()
    {
        return [
            'Minimal case, alternative casing, alternative statement end.' => [
                '<?php echo PHP_VERSION?>',
                '<?php echo PHPversion()?>',
            ],
            'With embedded comment.' => [
                '<?php echo PHP_VERSION/**/?>',
                '<?php echo phpversion(/**/)?>',
            ],
            'With white space.' => [
                '<?php echo PHP_VERSION      ;',
                '<?php echo phpversion  (  )  ;',
            ],
            'With multi line whitespace.' => [
                '<?php echo
                PHP_VERSION
                '.'
                '.'
                ;',
                '<?php echo
                phpversion
                (
                )
                ;',
            ],
            'Global namespaced.' => [
                '<?php echo \PHP_VERSION;',
                '<?php echo \phpversion();',
            ],
            'Wrong number of arguments.' => [
                '<?php phpversion($a);',
            ],
            'Wrong namespace.' => [
                '<?php A\B\phpversion();',
            ],
            'Class creating.' => [
                '<?php new phpversion();',
            ],
            'Class static method call.' => [
                '<?php A::phpversion();',
            ],
            'Class method call.' => [
                '<?php $a->phpversion();',
            ],
            'Overridden function.' => [
                '<?php if (!function_exists("phpversion")){function phpversion(){}}?>',
            ],
            'phpversion only' => [
                '<?php echo PHP_VERSION; echo php_sapi_name(); echo pi();',
                '<?php echo phpversion(); echo php_sapi_name(); echo pi();',
                ['functions' => ['phpversion']],
            ],
            'php_sapi_name only' => [
                '<?php echo phpversion(); echo PHP_SAPI; echo pi();',
                '<?php echo phpversion(); echo php_sapi_name(); echo pi();',
                ['functions' => ['php_sapi_name']],
            ],
            'php_sapi_name in conditional' => [
                '<?php if ("cli" === PHP_SAPI && $a){ echo 123;}',
                '<?php if ("cli" === php_sapi_name() && $a){ echo 123;}',
                ['functions' => ['php_sapi_name']],
            ],
            'pi only' => [
                '<?php echo phpversion(); echo php_sapi_name(); echo M_PI;',
                '<?php echo phpversion(); echo php_sapi_name(); echo pi();',
                ['functions' => ['pi']],
            ],
            'multi line pi' => [
                '<?php
$a =
    $b
    || $c < M_PI
;',
                '<?php
$a =
    $b
    || $c < pi()
;',
                ['functions' => ['pi']],
            ],
            'phpversion and pi' => [
                '<?php echo PHP_VERSION; echo php_sapi_name(); echo M_PI;',
                '<?php echo phpversion(); echo php_sapi_name(); echo M_PI;',
                ['functions' => ['pi', 'phpversion']],
            ],
            'diff argument count than native allows' => [
                '<?php
                    echo phpversion(1);
                    echo php_sapi_name(1,2);
                    echo pi(1);
                ',
            ],
            'get_class => T_CLASS' => [
                '<?php
                    class A
                    {
                        public function echoClassName($notMe)
                        {
                            echo get_class($notMe);
                            echo __CLASS__/** 1 *//* 2 */;
                            echo __CLASS__;
                        }
                    }

                    trait A
                    {
                        public function A() {
                            echo get_called_class(); // not in the default
                            var_dump(__CLASS__);
                        }
                    }

                    class B
                    {
                        use A;
                    }
                ',
                '<?php
                    class A
                    {
                        public function echoClassName($notMe)
                        {
                            echo get_class($notMe);
                            echo get_class(/** 1 *//* 2 */);
                            echo GET_Class();
                        }
                    }

                    trait A
                    {
                        public function A() {
                            echo get_called_class(); // not in the default
                            var_dump(get_class());
                        }
                    }

                    class B
                    {
                        use A;
                    }
                ',
            ],
            'get_class with leading backslash' => [
                '<?php __CLASS__;',
                '<?php \get_class();',
            ],
            [
                '<?php class A { function B(){ echo static::class; }}',
                '<?php class A { function B(){ echo get_called_class(); }}',
                ['functions' => ['get_called_class']],
            ],
            [
                '<?php class A { function B(){
echo#.
#0
static::class#1
#2
#3
#4
#5
#6
;#7
}}
                ',
                '<?php class A { function B(){
echo#.
#0
get_called_class#1
#2
(#3
#4
)#5
#6
;#7
}}
                ',
                ['functions' => ['get_called_class']],
            ],
            'get_called_class with leading backslash' => [
                '<?php class A { function B(){echo static::class; }}',
                '<?php class A { function B(){echo \get_called_class(); }}',
                ['functions' => ['get_called_class']],
            ],
            'get_called_class overridden' => [
                '<?php echo get_called_class(1);',
                null,
                ['functions' => ['get_called_class']],
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidConfigurationKeysCases
     */
    public function testInvalidConfigurationKeys(array $config)
    {
        $this->expectException(\PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException::class);
        $this->expectExceptionMessageRegExp('#^\[function_to_constant\] Invalid configuration: The option "functions" with value array is invalid\.$#');

        $this->fixer->configure($config);
    }

    public function provideInvalidConfigurationKeysCases()
    {
        return [
            [['functions' => ['a']]],
            [['functions' => [false => 1]]],
            [['functions' => ['abc' => true]]],
        ];
    }

    public function testInvalidConfigurationValue()
    {
        $this->expectException(\PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException::class);
        $this->expectExceptionMessageRegExp('#^\[function_to_constant\] Invalid configuration: The option "0" does not exist\. Defined options are: "functions"\.$#');

        $this->fixer->configure(['pi123']);
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @requires PHP 7.0
     * @dataProvider provideFix70Cases
     */
    public function testFix70($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix70Cases()
    {
        return [
            [
                '<?php function &PHPversion(){} ?>',
            ],
        ];
    }
}
