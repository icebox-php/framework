<?php php_start_tag(); ?>

use Icebox\App;
<?php php_end_tag(); ?>


<h5>Edit <?php echo $singular; ?></h5>
<hr>

<?php php_start_tag(); ?>

$this->render('_form', array(
  '<?php echo $singular ?>' => $<?php echo $singular ?>,
  'action' => App::url('/<?php echo $plural ?>/:id', array(':id' => $<?php echo $singular ?>->id)),
  'method' => 'put',
  'button_text' => 'Update'
));
<?php php_end_tag(); ?>


<p><a href="<?php php_start_tag(); ?> echo App::url('/<?php echo $plural ?>'); <?php php_end_tag(); ?>">Back</a></p>
