<?php php_start_tag(); ?>

use Icebox\App;
<?php php_end_tag(); ?>

<br><br>

<form class="margin-bottom-20" action="<?php php_start_tag(); ?> echo $action; <?php php_end_tag(); ?>" method="post">

  <?php php_start_tag(); ?> if($method != 'post') { <?php php_end_tag(); ?>

    <input type="hidden" name="_method" value="<?php php_start_tag() ?> echo $method; <?php php_end_tag() ?>">
  <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

  <?php php_start_tag(); ?> if($post->errors != null) { <?php php_end_tag(); ?>

    <div class="error-messages">
      <?php php_start_tag(); ?> foreach($post->errors->full_messages() as $message) { <?php php_end_tag(); ?>

        <div class="text-danger"><?php php_start_tag(); ?> echo h($message); <?php php_end_tag(); ?></div>
      <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

    </div>
  <?php php_start_tag(); ?> } <?php php_end_tag(); ?>

  <div class="form-group">
      <label for="post_title_input">Title</label>
      <input type="text" class="form-control" id="post_title_input" name="title"
          aria-describedby="title_help" placeholder="Title" value="<?php php_start_tag(); ?> echo h($post->title); <?php php_end_tag(); ?>">
      <!-- <small id="title_help" class="form-text text-muted">We'll never share your email with anyone else.</small> -->
    </div>
    <div class="form-group">
      <label for="post_content_input">Content</label>
      <textarea class="form-control" id="post_content_input" name="content"
          placeholder="Content"><?php php_start_tag(); ?> echo h($post->content); <?php php_end_tag(); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><?php php_start_tag(); ?> echo $button_text; <?php php_end_tag(); ?></button>

</form>
