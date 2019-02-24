<?php echo '<?php'; ?>


namespace App\Controller;

use Icebox\Request;
use App\Controller\AppController;
use App\Model\<?php echo $model_name; ?>;
use Icebox\App;

class <?php echo $controller_name; ?> extends AppController
{
    public function index()
    {
        $<?php echo $plural; ?> = <?php echo $model_name; ?>::find('all');
        return $this->render(null, array( '<?php echo $plural; ?>' => $<?php echo $plural; ?> ));
    }

    public function new()
    {
        return $this->render(null, [ '<?php echo $singular; ?>' => new <?php echo $model_name; ?>() ]);
    }

    public function create()
    {
        $<?php echo $singular; ?> = new <?php echo $model_name; ?>($this-><?php echo $singular; ?>_params());
        $saved = $<?php echo $singular; ?>->save();

        if($saved) {
            $this->flash('success', 'Saved successfully');
            return $this->redirect(App::url('<?php echo $plural; ?>/:id', [':id' => $<?php echo $singular; ?>->id]));
        } else {
            return $this->render('new', array( '<?php echo $singular; ?>' => $<?php echo $singular; ?> ));
        }
    }

    public function show()
    {
        $<?php echo $singular; ?> = <?php echo $model_name; ?>::find(Request::params('id'));
        return $this->render(null, [ '<?php echo $singular; ?>' => $<?php echo $singular; ?> ]);
    }

    public function edit()
    {
        $<?php echo $singular; ?> = <?php echo $model_name; ?>::find(Request::params('id'));
        return $this->render(null, [ '<?php echo $singular; ?>' => $<?php echo $singular; ?> ]);
    }

    public function update()
    {
        $<?php echo $singular; ?> = <?php echo $model_name; ?>::find(Request::params('id'));
        $updated = $<?php echo $singular; ?>->update_attributes($this-><?php echo $singular; ?>_params());

        if($updated) {
            $this->flash('success', 'Updated successfully');
            return $this->redirect(App::url('<?php echo $plural; ?>/:id', [':id' => $<?php echo $singular; ?>->id]));
        } else {
            return $this->render('edit', array('<?php echo $singular; ?>' => $<?php echo $singular; ?>));
        }

    }

    public function delete()
    {
        $<?php echo $singular; ?> = <?php echo $model_name; ?>::find(Request::params('id'));
        $<?php echo $singular; ?>->delete();
        $this->flash('success', 'Deleted successfully');
        return $this->redirect(App::url('<?php echo $plural; ?>'));
    }

    private function <?php echo $singular; ?>_params() {
      return $this->filter_post_params(array('title', 'content'));
    }
}
