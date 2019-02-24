<?php php_start_tag(); ?>

use Icebox\App;
<?php php_end_tag(); ?>


This is <?php echo $plural; ?>#index page <br><br>

File location: <?php php_start_tag(); ?> echo __FILE__ <?php php_end_tag(); ?>


<br><br>

<hr>

<h5><?php echo ucfirst($plural); ?></h5>

<hr>

<a href="<?php php_start_tag(); ?> echo App::url('/<?php echo $plural; ?>/new'); <?php php_end_tag(); ?>">Add new <?php echo $singular; ?></a>

<table class="table">
  <tr>
    <th>id</th>
    <th>title</th>
    <th>content</th>
    <th></th>
  </tr>

  <?php php_start_tag(); ?> foreach($<?php echo $plural; ?> as $<?php echo $singular; ?>) { <?php php_end_tag(); ?>

    <tr>
      <td>
        <a href="<?php php_start_tag(); ?> echo App::url('<?php echo $plural; ?>/:id', [':id' => $<?php echo $singular; ?>->id]); <?php php_end_tag(); ?>">
          <?php php_start_tag(); ?> echo $<?php echo $singular; ?>->id; <?php php_end_tag(); ?>

        </a>
      </td>
      <td>
        <a href="<?php php_start_tag(); ?> echo App::url('<?php echo $plural; ?>/:id', [':id' => $<?php echo $singular; ?>->id]); <?php php_end_tag(); ?>">
          <?php php_start_tag(); ?> echo h($<?php echo $singular; ?>->title); <?php php_end_tag(); ?>

        </a>
      </td>
      <td><?php php_start_tag(); ?> echo h($<?php echo $singular; ?>->content); <?php php_end_tag(); ?></td>
      <td>
        <a class="btn btn-primary btn-sm" href="<?php php_start_tag(); ?> echo App::url('/<?php echo $plural; ?>/:id/edit', array(':id' => $<?php echo $singular; ?>->id)); <?php php_end_tag(); ?>">Edit</a>

        <form class="confirm-delete-form inline-block" action="<?php php_start_tag(); ?> echo App::url('/<?php echo $plural; ?>/:id', array(':id' => $<?php echo $singular; ?>->id)); <?php php_end_tag(); ?>" method="post">
          <input type="hidden" name="_method" value="delete">
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </td>
    </tr>
  <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

</table>
