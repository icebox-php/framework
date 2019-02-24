<?php php_start_tag(); ?>

use Icebox\App;
<?php php_end_tag(); ?>


<h5 class="margin-bottom-20">Show <?php echo $singular; ?></h5>

<table class="table table-responsive">

  <?php php_start_tag(); ?> foreach($<?php echo $singular; ?>->attributes() as $attr => $text) { <?php php_end_tag(); ?>

    <tr>
      <th><?php php_start_tag(); ?> echo $attr; <?php php_end_tag(); ?></th>
      <td><?php php_start_tag(); ?> echo $text; <?php php_end_tag(); ?></td>
    </tr>
  <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

</table>

<p>
  <a href="<?php php_start_tag(); ?> echo App::url('/<?php echo $plural; ?>/:id/edit', array(':id' => $<?php echo $singular; ?>->id)); <?php php_end_tag(); ?>">Edit</a> |
  <a href="<?php php_start_tag(); ?> echo App::url('/<?php echo $plural; ?>'); <?php php_end_tag(); ?>">Back</a>
</p>
