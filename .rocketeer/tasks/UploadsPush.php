<?php

namespace WPTasks;

class UploadsPush extends AbstractFilePush
{
    protected $description = 'Push uploads to remote';

    /**
     * Define files to push
     */
    protected function getLocalFiles ()
    {
        return $this->getFilesFromConfigOption('wp_tasks.uploads_dir');
    }
}