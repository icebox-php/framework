<?php

namespace Icebox\Generator;

/**
 * FormHelper
 */
class FormHelper
{
  public function input($singular, $attr, $type)
  {
    $html_attrs = array(
      '\'type\''        => '\'' . $type . '\'',
      '\'class\''       => '\'form-control\'',
      '\'id\''          => '\'' . $singular . '_' . $attr . '\'',
      '\'name\''        => '\'' . $attr . '\'',
      '\'placeholder\'' => '\'' . ucfirst($attr) . '\'',
      '\'value\''       => 'h($' . $singular . '->' . $attr . ')',
    );

    $input_html = '';
    foreach($html_attrs as $html_attr => $attr_val) {
      $input_html .= $html_attr . ' => ' . $attr_val . ',';
    }

    $html = '  <div class="form-group">' . "\n";
    $html .= '    <label for="' . $singular . '_' . $attr . '">' . ucfirst($attr) . '</label>' . "\n";
    /*
    $html .= '    <input type="text" class="form-control" id="' . $singular . '_' . $attr . '" name="' . $attr . '"' . "\n";
    $html .= '        placeholder="' . ucfirst($attr) . '" value="<?php echo h($' . $singular . '->' . $attr . '); ?>">' . "\n";
    */


    $html .= "    <?php input_tag(array(";
    // $html .= "'type' => 'text', 'name'=>'email', 'value'=>'me@torovin.com', 'class'=>'form-control'";
    $html .= $input_html;
    $html .= ")); ?>" . "\n";


    $html .= '  </div>' . "\n\n";

    return $html;
  }

  public function checkbox($singular, $attr) {
    $html_attrs = array(
      '\'type\''        => '\'checkbox\'',
      // '\'class\''       => '\'form-control\'',
      '\'id\''          => '\'' . $singular . '_' . $attr . '\'',
      '\'name\''        => '\'' . $attr . '\'',
      // '\'placeholder\'' => '\'' . ucfirst($attr) . '\'',
      '\'value\''       => '1',
    );

    $input_html = '';
    foreach($html_attrs as $html_attr => $attr_val) {
      $input_html .= $html_attr . ' => ' . $attr_val . ',';
    }

    $html = '  <div class="form-group">' . "\n";
    $html .= '    <label for="' . $singular . '_' . $attr . '">' . ucfirst($attr) . '</label>' . "\n";
    /*
    $html .= '    <input type="text" class="form-control" id="' . $singular . '_' . $attr . '" name="' . $attr . '"' . "\n";
    $html .= '        placeholder="' . ucfirst($attr) . '" value="<?php echo h($' . $singular . '->' . $attr . '); ?>">' . "\n";
    */


    $html .= "    <?php checkbox_tag(";
    $html .= 'h($' . $singular . '->' . $attr . ')';
    $html .= ", array(";
    // $html .= "'type' => 'text', 'name'=>'email', 'value'=>'me@torovin.com', 'class'=>'form-control'";
    $html .= $input_html;
    $html .= ")); ?>" . "\n";


    $html .= '  </div>' . "\n\n";

    return $html;
  }

  public function textarea($singular, $attr) {
    $html_attrs = array(
      // '\'type\''        => '\'' . $type . '\'',
      '\'class\''       => '\'form-control\'',
      '\'id\''          => '\'' . $singular . '_' . $attr . '\'',
      '\'name\''        => '\'' . $attr . '\'',
      '\'placeholder\'' => '\'' . ucfirst($attr) . '\'',
      // '\'value\''       => 'h($' . $singular . '->' . $attr . ')',
    );
    $value = 'h($' . $singular . '->' . $attr . ')';

    $input_html = '';
    foreach($html_attrs as $html_attr => $attr_val) {
      $input_html .= $html_attr . ' => ' . $attr_val . ',';
    }

    $html = '  <div class="form-group">' . "\n";
    $html .= '    <label for="' . $singular . '_' . $attr . '">' . ucfirst($attr) . '</label>' . "\n";
    /*
    $html .= '    <input type="text" class="form-control" id="' . $singular . '_' . $attr . '" name="' . $attr . '"' . "\n";
    $html .= '        placeholder="' . ucfirst($attr) . '" value="<?php echo h($' . $singular . '->' . $attr . '); ?>">' . "\n";
    */


    $html .= "    <?php textarea_tag(";
    $html .= $value;
    $html .= ", array(";
    // $html .= "'type' => 'text', 'name'=>'email', 'value'=>'me@torovin.com', 'class'=>'form-control'";
    $html .= $input_html;
    $html .= ")); ?>" . "\n";


    $html .= '  </div>' . "\n\n";

    return $html;
  }

  public function select($singular, $attr) {
    $html_attrs = array(
      // '\'type\''        => '\'' . $type . '\'',
      '\'class\''       => '\'form-control\'',
      '\'id\''          => '\'' . $singular . '_' . $attr . '\'',
      '\'name\''        => '\'' . $attr . '\'',
      // '\'placeholder\'' => '\'' . ucfirst($attr) . '\'',
      // '\'value\''       => 'h($' . $singular . '->' . $attr . ')',
    );

    $input_html = '';
    foreach($html_attrs as $html_attr => $attr_val) {
      $input_html .= $html_attr . ' => ' . $attr_val . ',';
    }

    $html = '  <div class="form-group">' . "\n";
    $html .= '    <label for="' . $singular . '_' . $attr . '">' . ucfirst($attr) . '</label>' . "\n";
    /*
    $html .= '    <input type="text" class="form-control" id="' . $singular . '_' . $attr . '" name="' . $attr . '"' . "\n";
    $html .= '        placeholder="' . ucfirst($attr) . '" value="<?php echo h($' . $singular . '->' . $attr . '); ?>">' . "\n";
    */

    $html .= '<?php $options = array(""=>"Please select", "1"=>"Lisbon", "2"=>"Madrid", "3"=>"Berlin"); ?>'. "\n";

    $html .= "    <?php select_tag(";
    $html .= '$options';
    $html .= ', $' . $singular . '->' . $attr . '';
    $html .= ", array(";
    // $html .= "'type' => 'text', 'name'=>'email', 'value'=>'me@torovin.com', 'class'=>'form-control'";
    $html .= $input_html;
    $html .= ")); ?>" . "\n";


    $html .= '  </div>' . "\n\n";

    return $html;
  }
}
