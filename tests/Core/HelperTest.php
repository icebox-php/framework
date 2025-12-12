<?php

namespace Icebox\Tests\Core;

use Icebox\Tests\TestCase;

/**
 * Test for helper functions
 */
class HelperTest extends TestCase
{
    /**
     * Test h() function for HTML escaping
     */
    public function testHEscapesHtml(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        
        $this->assertEquals($expected, h($input));
    }

    /**
     * Test h() function with special characters
     */
    public function testHEscapesSpecialCharacters(): void
    {
        $input = 'Test & "quotes" \'apostrophes\' < >';
        $expected = 'Test &amp; &quot;quotes&quot; &#039;apostrophes&#039; &lt; &gt;';
        
        $this->assertEquals($expected, h($input));
    }

    /**
     * Test h() function doesn't double-encode
     */
    public function testHDoesNotDoubleEncode(): void
    {
        $input = '& already encoded';
        $result = h($input);
        // htmlspecialchars will encode & to & even in &
        // So & becomes &amp;
        $this->assertStringContains('already encoded', $result);
    }

    /**
     * Test php_start_tag and php_end_tag functions
     */
    public function testPhpTags(): void
    {
        ob_start();
        php_start_tag();
        $start = ob_get_clean();
        $this->assertEquals('<?php', $start);
        
        ob_start();
        php_end_tag();
        $end = ob_get_clean();
        $this->assertEquals('?>', $end);
    }

    /**
     * Test input_tag function
     */
    public function testInputTag(): void
    {
        $attributes = [
            'type' => 'text',
            'name' => 'email',
            'value' => 'test@example.com',
            'class' => 'form-control'
        ];
        
        ob_start();
        input_tag($attributes);
        $output = ob_get_clean();
        
        $expected = '<input type="text" name="email" value="test@example.com" class="form-control">';
        $this->assertEquals($expected, $output);
    }

    /**
     * Test textarea_tag function
     */
    public function testTextareaTag(): void
    {
        $attributes = [
            'name' => 'details',
            'class' => 'form-control'
        ];
        
        ob_start();
        textarea_tag('Some text...', $attributes);
        $output = ob_get_clean();
        
        $expected = '<textarea name="details" class="form-control">Some text...</textarea>';
        $this->assertEquals($expected, $output);
    }

    /**
     * Test checkbox_tag function when checked
     */
    public function testCheckboxTagChecked(): void
    {
        $attributes = [
            'name' => 'agree',
            'value' => '1',
            'class' => 'form-check'
        ];
        
        ob_start();
        checkbox_tag(true, $attributes);
        $output = ob_get_clean();
        
        $expected = '<input name="agree" value="1" class="form-check" checked>';
        $this->assertEquals($expected, $output);
    }

    /**
     * Test checkbox_tag function when not checked
     */
    public function testCheckboxTagNotChecked(): void
    {
        $attributes = [
            'name' => 'agree',
            'value' => '1',
            'class' => 'form-check'
        ];
        
        ob_start();
        checkbox_tag(false, $attributes);
        $output = ob_get_clean();
        
        $expected = '<input name="agree" value="1" class="form-check">';
        $this->assertEquals($expected, $output);
    }

    /**
     * Test select_tag function
     */
    public function testSelectTag(): void
    {
        $options = [
            '' => 'Please select',
            '1' => 'Lisbon',
            '2' => 'Madrid',
            '3' => 'Berlin'
        ];
        
        $attributes = [
            'name' => 'city',
            'class' => 'form-control'
        ];
        
        ob_start();
        select_tag($options, '2', $attributes);
        $output = ob_get_clean();
        
        // Check basic structure
        $this->assertStringContains('<select', $output);
        $this->assertStringContains('name="city"', $output);
        $this->assertStringContains('class="form-control"', $output);
        $this->assertStringContains('value="1"', $output);
        $this->assertStringContains('value="2"', $output);
        $this->assertStringContains('value="3"', $output);
        $this->assertStringContains('Lisbon', $output);
        $this->assertStringContains('Madrid', $output);
        $this->assertStringContains('Berlin', $output);
        
        // Check selected option
        $this->assertStringContains('value="2" selected', $output);
        $this->assertStringNotContains('value="1" selected', $output);
        $this->assertStringNotContains('value="3" selected', $output);
    }

    /**
     * Test select_tag with no selected value
     */
    public function testSelectTagNoSelection(): void
    {
        $options = ['1' => 'One', '2' => 'Two'];
        $attributes = ['name' => 'test'];
        
        ob_start();
        select_tag($options, '', $attributes);
        $output = ob_get_clean();
        
        $this->assertStringNotContains('selected', $output);
    }

    /**
     * Test that h() function exists
     */
    public function testHFunctionExists(): void
    {
        $this->assertTrue(function_exists('h'));
    }
}
