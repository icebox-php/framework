<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\ActiveRecord\lib\Utils;
use Icebox\Generator\FormHelper;

/**
 * Command to generate CRUD resources
 */
class GenerateCrudCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'generate crud';
    }

    public function getDescription(): string
    {
        return 'Generate CRUD resources (controller, model, views)';
    }

    public function execute(array $args): int
    {
        if (!isset($args[3])) {
            $this->error("Resource name is required");
            $this->help();
            return 1;
        }

        $singular = $args[3];
        $plural = Utils::pluralize($singular);
        $modelName = ucfirst($singular);
        $controllerName = ucfirst($plural) . 'Controller';

        // Get attributes from command
        $attrs = array_slice($args, 4);

        $this->info("Crud generator started");

        // Create controller
        $this->createController($controllerName, $modelName, $singular, $plural, $attrs);
        
        // Create model
        $this->createModel($modelName);
        
        // Create views
        $this->createViews($singular, $plural, $attrs);
        
        // Insert resource route
        $this->insertResourceRoute($plural);

        $this->success("CRUD resources generated successfully!");
        return 0;
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox generate crud <name> [attributes]\n\n";
        echo "Examples:\n";
        echo "  php icebox generate crud post title:string content:text\n";
        echo "  php icebox generate crud user name:string email:string password:string\n";
    }

    private function createController(string $controllerName, string $modelName, string $singular, string $plural, array $attrs): void
    {
        $controllerText = $this->generateControllerTemplate($controllerName, $modelName, $singular, $plural, $attrs);
        $this->createFile("app/Controller/$controllerName.php", $controllerText);
    }

    private function createModel(string $modelName): void
    {
        $modelText = $this->generateModelTemplate($modelName);
        $this->createFile("app/Model/$modelName.php", $modelText);
    }

    private function createViews(string $singular, string $plural, array $attrs): void
    {
        $viewFolder = ucfirst($plural);
        $viewDir = "app/View/$viewFolder";

        // Create view directory if it doesn't exist
        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        // Create form view
        $formHelper = new FormHelper();
        $formViewText = $this->generateFormViewTemplate($singular, $plural, $attrs, $formHelper);
        $this->createFile("$viewDir/_form.html.php", $formViewText);

        // Create other views
        $views = ['edit', 'index', 'new', 'show'];
        foreach ($views as $view) {
            $viewText = $this->generateViewTemplate($view, $singular, $plural);
            $this->createFile("$viewDir/$view.html.php", $viewText);
        }
    }

    private function insertResourceRoute(string $plural): void
    {
        $controller = ucfirst($plural);
        $routeFile = ROOT_DIR . '/config/routes.php';

        if (!file_exists($routeFile)) {
            $this->error("Routes file not found: $routeFile");
            return;
        }

        $routeText = "\n" . '$route->resource(\'' . $plural . '\', \'' . $controller . '\');' . "\n";

        $fp = fopen($routeFile, "r+");
        $startPos = ftell($fp);
        for ($i = 0; $i < 5; $i++) {
            fgets($fp);
        }

        $content = $routeText . stream_get_contents($fp);
        fseek($fp, $startPos);
        fwrite($fp, $content);
        fclose($fp);

        $this->info("Added resource route for '$plural'");
    }

    private function createFile(string $relativePath, string $content): void
    {
        $fullPath = ROOT_DIR . '/' . $relativePath;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($fullPath, $content)) {
            $this->info("create  $relativePath");
        } else {
            $this->error("Failed to create file: $relativePath");
        }
    }

    private function generateControllerTemplate(string $controllerName, string $modelName, string $singular, string $plural, array $attrs): string
    {
        $params = $this->generateControllerParams($attrs);
        
        return <<<PHP
<?php

class {$controllerName} extends Controller
{
    public function index()
    {
        \${$plural} = {$modelName}::all();
        \$this->render('{$plural}/index', ['{$plural}' => \${$plural}]);
    }

    public function show(\$id)
    {
        \${$singular} = {$modelName}::find(\$id);
        if (!\${$singular}) {
            throw new ResourceNotFoundException("{$singular} not found");
        }
        \$this->render('{$plural}/show', ['{$singular}' => \${$singular}]);
    }

    public function new()
    {
        \${$singular} = new {$modelName}();
        \$this->render('{$plural}/new', ['{$singular}' => \${$singular}]);
    }

    public function create()
    {
        \${$singular} = new {$modelName}(\$_POST['{$singular}']);
        if (\${$singular}->save()) {
            \$this->redirect('/{$plural}');
        } else {
            \$this->render('{$plural}/new', ['{$singular}' => \${$singular}]);
        }
    }

    public function edit(\$id)
    {
        \${$singular} = {$modelName}::find(\$id);
        if (!\${$singular}) {
            throw new ResourceNotFoundException("{$singular} not found");
        }
        \$this->render('{$plural}/edit', ['{$singular}' => \${$singular}]);
    }

    public function update(\$id)
    {
        \${$singular} = {$modelName}::find(\$id);
        if (!\${$singular}) {
            throw new ResourceNotFoundException("{$singular} not found");
        }
        if (\${$singular}->update(\$_POST['{$singular}'])) {
            \$this->redirect('/{$plural}');
        } else {
            \$this->render('{$plural}/edit', ['{$singular}' => \${$singular}]);
        }
    }

    public function destroy(\$id)
    {
        \${$singular} = {$modelName}::find(\$id);
        if (\${$singular}) {
            \${$singular}->destroy();
        }
        \$this->redirect('/{$plural}');
    }

    private function getParams()
    {
        return {$params};
    }
}
PHP;
    }

    private function generateControllerParams(array $attrs): string
    {
        $arr = [];
        foreach ($attrs as $value) {
            $temp = explode(':', $value);
            $arr[$temp[0]] = isset($temp[1]) ? $temp[1] : 'string';
        }

        $str = "array('";
        $str .= implode("', '", array_keys($arr));
        $str .= "')";

        if ($str == "array('')") {
            $str = "array()";
        }

        return $str;
    }

    private function generateModelTemplate(string $modelName): string
    {
        return <<<PHP
<?php

class {$modelName} extends Model
{
    // Model code will be generated here
    // You can add custom methods and validations
}
PHP;
    }

    private function generateFormViewTemplate(string $singular, string $plural, array $attrs, FormHelper $formHelper): string
    {
        $formFields = '';
        foreach ($attrs as $attr) {
            $temp = explode(':', $attr);
            $fieldName = $temp[0];
            $fieldType = isset($temp[1]) ? $temp[1] : 'string';
            
            $inputType = $this->getHtmlInputType($fieldType);
            
            if ($inputType['html_tag'] == 'input') {
                $formFields .= "    <div class=\"form-group\">\n";
                $formFields .= "        <label for=\"{$fieldName}\">" . ucfirst($fieldName) . "</label>\n";
                $formFields .= "        <input type=\"{$inputType['type']}\" name=\"{$singular}[{$fieldName}]\" id=\"{$fieldName}\" class=\"form-control\">\n";
                $formFields .= "    </div>\n";
            } elseif ($inputType['html_tag'] == 'textarea') {
                $formFields .= "    <div class=\"form-group\">\n";
                $formFields .= "        <label for=\"{$fieldName}\">" . ucfirst($fieldName) . "</label>\n";
                $formFields .= "        <textarea name=\"{$singular}[{$fieldName}]\" id=\"{$fieldName}\" class=\"form-control\"></textarea>\n";
                $formFields .= "    </div>\n";
            }
        }

        return <<<HTML
<?php \$form = new FormHelper(); ?>
<form method="post" action="<?php echo \$form->action(); ?>">
{$formFields}
    <div class="form-group">
        <input type="submit" value="Save" class="btn btn-primary">
    </div>
</form>
HTML;
    }

    private function generateViewTemplate(string $viewName, string $singular, string $plural): string
    {
        switch ($viewName) {
            case 'edit':
                return <<<HTML
<h1>Edit <?php echo ucfirst(\${$singular}->name); ?></h1>
<?php include('_form.html.php'); ?>
HTML;
            case 'index':
                return <<<HTML
<h1><?php echo ucfirst(\$plural); ?></h1>
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (\${$plural} as \${$singular}): ?>
        <tr>
            <td><?php echo \${$singular}->id; ?></td>
            <td><?php echo \${$singular}->name; ?></td>
            <td>
                <a href="/{$plural}/<?php echo \${$singular}->id; ?>">Show</a>
                <a href="/{$plural}/<?php echo \${$singular}->id; ?>/edit">Edit</a>
                <form method="post" action="/{$plural}/<?php echo \${$singular}->id; ?>/destroy" style="display: inline;">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<a href="/{$plural}/new">New <?php echo ucfirst(\$singular); ?></a>
HTML;
            case 'new':
                return <<<HTML
<h1>New <?php echo ucfirst(\$singular); ?></h1>
<?php include('_form.html.php'); ?>
HTML;
            case 'show':
                return <<<HTML
<h1><?php echo ucfirst(\${$singular}->name); ?></h1>
<p>ID: <?php echo \${$singular}->id; ?></p>
<a href="/{$plural}/<?php echo \${$singular}->id; ?>/edit">Edit</a>
<a href="/{$plural}">Back to list</a>
HTML;
            default:
                return '';
        }
    }

    private function getHtmlInputType(string $columnType): array
    {
        $supportedTypes = [
            'boolean' => ['html_tag' => 'checkbox', 'type' => ''],
            'date' => ['html_tag' => 'input', 'type' => 'date'],
            'datetime' => ['html_tag' => 'input', 'type' => 'datetime-local'],
            'decimal' => ['html_tag' => 'input', 'type' => 'number'],
            'float' => ['html_tag' => 'input', 'type' => 'number'],
            'integer' => ['html_tag' => 'input', 'type' => 'number'],
            'string' => ['html_tag' => 'input', 'type' => 'text'],
            'text' => ['html_tag' => 'textarea', 'type' => ''],
            'time' => ['html_tag' => 'input', 'type' => 'time'],
            'select' => ['html_tag' => 'select', 'type' => ''],
        ];

        return $supportedTypes[$columnType] ?? $supportedTypes['string'];
    }
}
