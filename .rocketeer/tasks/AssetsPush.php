<?php

namespace WPTasks;

class AssetsPush extends AbstractFilePush
{
    protected $description = 'Push assets to remote';

    /**
     * Define files to push
     */
    protected function getLocalFiles ()
    {
        return $this->getFilesFromConfigOption('wp_tasks.asset_dirs');
    }
}