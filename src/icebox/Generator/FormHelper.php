<?php

namespace Icebox\Generator;

/**
 * FormHelper
 */
class FormHelper
{
  public function input($singular, $attr)
  {
    $html = '  <div class="form-group">' . "\n";
    $html .= '    <label for="' . $singular . '_' . $attr . '">' . ucfirst($attr) . '</label>' . "\n";
    $html .= '    <input type="text" class="form-control" id="' . $singular . '_' . $attr . '" name="' . $attr . '"' . "\n";
    $html .= '        placeholder="' . ucfirst($attr) . '" value="<?php echo h($' . $singular . '->' . $attr . '); ?>">' . "\n";
    $html .= '  </div>' . "\n\n";

    return $html;
  }
}
