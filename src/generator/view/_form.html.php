<?php php_start_tag(); ?>

use Icebox\App;
<?php php_end_tag(); ?>


<form class="margin-bottom-20" action="<?php php_start_tag(); ?> echo $action; <?php php_end_tag(); ?>" method="post">

  <?php php_start_tag(); ?> if($method != 'post') { <?php php_end_tag(); ?>

    <input type="hidden" name="_method" value="<?php php_start_tag() ?> echo $method; <?php php_end_tag() ?>">
  <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

  <?php php_start_tag(); ?> if($<?php echo $singular ?>->errors != null) { <?php php_end_tag(); ?>

    <div class="error-messages">
      <?php php_start_tag(); ?> foreach($<?php echo $singular ?>->errors->full_messages() as $message) { <?php php_end_tag(); ?>

        <div class="text-danger"><?php php_start_tag(); ?> echo h($message); <?php php_end_tag(); ?></div>
      <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

    </div>
  <?php php_start_tag(); ?> } <?php php_end_tag(); ?>


<?php

  // generator_get_html_input_type($column_type);

    $arr = generator_split_attr_get_column_names($attrs);
    foreach ($arr as $column => $ar_type) { // $ar_type meand ActiveRecord Type
      // echo $column . '-' . $type . "\n";

      $col = generator_get_html_input_type($ar_type);


      if($col['html_tag'] == 'input') {
        echo $form_helper->input($singular, $column, $col['type']);
      } else if($col['html_tag'] == 'checkbox') {
        echo $form_helper->checkbox($singular, $column);
      } else if($col['html_tag'] == 'textarea') {
        echo $form_helper->textarea($singular, $column);
      }
    }
  // echo $form_helper::input($singular, 'title');
  // echo $form_helper::input($singular, 'content');
  // echo $form_helper::input($singular, 'tag');

?>

    <button type="submit" class="btn btn-primary"><?php php_start_tag(); ?> echo $button_text; <?php php_end_tag(); ?></button>

</form>
