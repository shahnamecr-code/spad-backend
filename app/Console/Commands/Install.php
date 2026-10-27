<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Install extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install {--m|migration} {--A|all} {--s|subdomain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'install shop';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $subFolder = null;
        if ($this->option('subdomain')) {
            $subFolder = $this->ask('Enter subdomain');
        }
        if ($this->option('all')) {
            $this->call('key:generate');
            $this->call('storage:link');
        }

        if ($this->option('migration')) {
            $this->call('migrate:fresh', ['--seed']);
        }

        $files = $this->laravel->make('files');
        $laravel_folder = $subFolder ? '../' . basename(base_path()) : basename(base_path());
        $path = $subFolder ? '../public_html' . "/" . $subFolder : '../public_html';
        $files->copyDirectory(public_path(), base_path($path));
        $this->info("public folder copied to given path");
        $files->replaceInFile('../', '../' . $laravel_folder . '/', $path . '/index.php');
        $this->info("info replaced in index.php");


        return 0;
    }
}
