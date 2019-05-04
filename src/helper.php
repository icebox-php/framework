<?php

if (! function_exists('h')) {
  function h($text) {
    return htmlspecialchars($text);
  }
}

function php_start_tag() {
  echo '<?php';
}

function php_end_tag() {
  echo '?>';
}

/*

<?php input_tag(array('type' => 'text', 'name'=>'email', 'value'=>'me@torovin.com', 'class'=>'form-control')); ?>

-------- output -------

<input type="text" name="email" value="me@torovin.com" class="form-control">

*/

function input_tag($attributes) {
  echo '<input';
  foreach($attributes as $attr => $val) {
      echo ' ' . $attr . '="'. $val .'"';
  }
  echo '>';
}

/*

<?php textarea_tag('Some text...', array('name'=>'details', 'class'=>'form-control')); ?>

-------- output -------

<textarea name="details" class="form-control">Some text...</textarea>

*/

function textarea_tag($value, $attributes) {
  echo '<textarea';
  foreach($attributes as $attr => $val) {
      echo ' ' . $attr . '="'. $val .'"';
  }
  echo '>';
  echo $value;
  echo '</textarea>';
}



/*

<?php checkbox_tag(1, array('name'=>'details', 'class'=>'form-control')); ?>

-------- output -------

<input type="checkbox" name="details" value="1" class="form-control" checked>

*/

function checkbox_tag($checked, $attributes) {
  echo '<input';
  foreach($attributes as $attr => $val) {
      echo ' ' . $attr . '="'. $val .'"';
  }

  if($checked == 1 || $checked == true) { echo ' checked'; }

  echo '>';
}

/*

<?php $options = array(''=>'Please select', '1'=>'Lisbon', '2'=>'Madrid', '3'=>'Berlin'); ?>
<?php select_tag($options, array('name'=>'details', 'class'=>'form-control')); ?>

-------- output -------

<select name="details" class="form-control">
  <option value="">Please select</option>
  <option value="1">Lisbon</option>
  <option value="2">Madrid</option>
  <option value="3">Berlin</option>
</select>

*/

function select_tag($options, $value, $attributes) {
  echo '<select';
  foreach($attributes as $attr => $val) {
      echo ' ' . $attr . '="'. $val .'"';
  }
  echo ">\n";

  foreach ($options as $val => $txt) {
    $selected = ($val == $value) ? ' selected' : '';
    echo '  <option value="' . h($val) . '"'.$selected.'>'. h($txt) ."</option>\n";
  }

  echo '</select>';
}
