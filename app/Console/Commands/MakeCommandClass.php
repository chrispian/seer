<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeCommandClass extends Command
{
    protected $signature = 'make:command-class {name : The name of the command class}
                            {--type=general : The category/type of the command (orchestration, system, etc)}
                            {--component= : The frontend component to use}
                            {--usage= : The slash command usage (e.g., /sprints)}
                            {--description= : Brief description of what the command does}';

    protected $description = 'Create a new PHP command class for the slash command system';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $className = Str::studly($name) . 'Command';
        $slug = Str::kebab($name);
        
        // Get or prompt for required information
        $type = $this->option('type') ?: $this->ask('Command type (orchestration, system, general)', 'general');
        $component = $this->option('component') ?: $this->ask('Frontend component (optional, press Enter to skip)');
        $usage = $this->option('usage') ?: $this->ask('Slash command usage', "/{$slug}");
        $description = $this->option('description') ?: $this->ask('Description', "Execute {$slug} command");

        // Ensure usage starts with /
        if (!str_starts_with($usage, '/')) {
            $usage = '/' . $usage;
        }

        // Generate the command class
        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub, [
            'ClassName' => $className,
            'Name' => Str::title(str_replace('-', ' ', $name)),
            'Description' => $description,
            'Usage' => $usage,
            'Category' => Str::title($type),
            'Component' => $component ?: 'UniversalCommandModal',
            'Type' => Str::lower($type),
        ]);

        // Write the file
        $path = app_path("Commands/{$className}.php");
        
        if ($this->files->exists($path)) {
            $this->error("Command class {$className} already exists!");
            return 1;
        }

        $this->files->put($path, $content);

        // Update CommandRegistry
        $this->updateCommandRegistry($slug, $className);

        $this->info("Command class {$className} created successfully!");
        $this->info("File: {$path}");
        $this->info("Slug: {$slug}");
        $this->info("Usage: {$usage}");
        
        if ($component && $component !== 'UniversalCommandModal') {
            $this->warn("Remember to create the {$component} component if it doesn't exist!");
        }

        return 0;
    }

    protected function getStub(): string
    {
        return <<<'STUB'
<?php

namespace App\Commands;

class {{ClassName}} extends BaseCommand
{
    public function handle(): array
    {
        // TODO: Implement your command logic here
        // Example for data commands:
        // $data = Model::query()->get();
        // 
        // return [
        //     'type' => '{{Type}}',
        //     'component' => '{{Component}}',
        //     'data' => $data->toArray()
        // ];

        return [
            'type' => '{{Type}}',
            'component' => '{{Component}}',
            'data' => [
                'message' => 'Command executed successfully!',
                'placeholder' => 'Replace this with your actual data'
            ]
        ];
    }
    
    public static function getName(): string
    {
        return '{{Name}}';
    }
    
    public static function getDescription(): string
    {
        return '{{Description}}';
    }
    
    public static function getUsage(): string
    {
        return '{{Usage}}';
    }
    
    public static function getCategory(): string
    {
        return '{{Category}}';
    }
}
STUB;
    }

    protected function replaceStubVariables(string $stub, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $stub = str_replace("{{{$key}}}", $value, $stub);
        }
        return $stub;
    }

    protected function updateCommandRegistry(string $slug, string $className): void
    {
        $registryPath = app_path('Services/CommandRegistry.php');
        $content = $this->files->get($registryPath);

        // Add import
        $importLine = "use App\\Commands\\{$className};";
        if (!str_contains($content, $importLine)) {
            $content = str_replace(
                'use App\Commands\SprintListCommand;',
                "use App\\Commands\\{$className};\nuse App\Commands\SprintListCommand;",
                $content
            );
        }

        // Add to phpCommands array
        $newEntry = "        '{$slug}' => {$className}::class,";
        $content = str_replace(
            "        'sprints' => SprintListCommand::class,",
            "        'sprints' => SprintListCommand::class,\n{$newEntry}",
            $content
        );

        $this->files->put($registryPath, $content);
        $this->info("Updated CommandRegistry with new command mapping.");
    }
}