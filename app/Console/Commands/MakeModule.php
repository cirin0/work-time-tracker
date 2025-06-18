<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create module structure for the application';

    /**
     * Execute the console command.
     */
    public function handle(): void

    {
        $name = Str::studly($this->argument('name'));

        $this->generateModel($name);
        $this->generateController($name);
        $this->generateService($name);
        $this->generateRepository($name);
        $this->generateRequest($name);
        $this->generateResource($name);

        $this->info("Module '$name' created successfully.");
    }

    private function generateModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");
        if (!File::exists($path)) {
            File::put($path, str_replace('{{name}}', $name, $this->stub('model')));
        }
    }

    protected function stub(string $type): string
    {
        return File::get(base_path("stubs/module/{$type}.stub"));
    }

    private function generateController(string $name): void
    {
        $controller = $name . 'Controller';
        $path = app_path("Http/Controllers/Api/{$controller}.php");
        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, str_replace('{{name}}', $name, $this->stub('controller')));
        }
    }

    protected function generateService(string $name): void
    {
        $service = $name . 'Service';
        $path = app_path("Services/{$service}.php");
        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, str_replace('{{name}}', $name, $this->stub('service')));
        }
    }

    protected function generateRepository(string $name): void
    {
        $repository = $name . 'Repository';
        $path = app_path("Repositories/{$repository}.php");
        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, str_replace('{{name}}', $name, $this->stub('repository')));
        }
    }

    protected function generateRequest(string $name): void
    {
        $request = $name . 'Request';
        $path = app_path("Http/Requests/{$request}.php");
        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, str_replace('{{name}}', $name, $this->stub('request')));
        }
    }

    protected function generateResource(string $name): void
    {
        $resource = $name . 'Resource';
        $path = app_path("Http/Resources/{$resource}.php");
        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, str_replace('{{name}}', $name, $this->stub('resource')));
        }
    }
}
