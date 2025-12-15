<?php echo '<?php'; ?>


use Icebox\ActiveRecord\Connection;

class <?php echo $className; ?> 
{
    public function up()
    {
        $sql = "<?php echo $upSQL; ?>";
        Connection::query($sql);
    }

    public function down()
    {
        $sql = "<?php echo $downSQL; ?>";
        Connection::query($sql);
    }
}
