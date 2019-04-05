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

    $arr = generator_split_attr_get_column_names($attrs);
    foreach ($arr as $item) {
      // echo $item . "\n";
      echo $form_helper::input($singular, $item);
    }
  // echo $form_helper::input($singular, 'title');
  // echo $form_helper::input($singular, 'content');
  // echo $form_helper::input($singular, 'tag');

?>

    <button type="submit" class="btn btn-primary"><?php php_start_tag(); ?> echo $button_text; <?php php_end_tag(); ?></button>

</form>
