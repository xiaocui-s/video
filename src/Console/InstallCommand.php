<?php

namespace Yhdccc\Video\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '视频组件';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Telescope Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'video-config']);

        $this->info('Telescope scaffolding installed successfully.');
    }
}
