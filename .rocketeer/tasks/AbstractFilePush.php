<?php

namespace WPTasks;

/**
 * Abstract class for pushing files to remote
 */
abstract class AbstractFilePush extends \Rocketeer\Abstracts\AbstractTask
{
    protected $local = true;

    /**
     * Define the files to push to the remote
     *
     * @return array
     */
    abstract protected function getLocalFiles ();

    /**
     * Get files array from config option
     *
     * @param string
     */
    protected function getFilesFromConfigOption ($option)
    {
        $files = $this->rocketeer->getOption($option);

        if (is_string($files)) {
            return [$files];
        }
        elseif (is_array($files)) {
            return $files;
        }

        $this->explainer->error('Wrong value for '.$option.', string or array expected '.gettype($dirs).' given');

        return [];
    }

    /**
     * Define the remote destination which receives the files
     *
     * @return string
     */
    protected function getRemoteDestination () 
    {
        return $this->releasesManager->getCurrentReleasePath().'/';
    }

    /**
     * Push local files to remote destination using rsync
     *
     * @return boolean
     */
    public function execute () 
    {
        $localFiles = $this->getLocalFiles();

        // sync directories
        if (count($localFiles) < 1) {        
            return false;
        }

        // get rsync command
        $rsync = $this->binary('Rocketeer\Plugins\Wordpress\Binaries\Rsync');
        $cmd = $rsync->push(
            $this->getLocalFiles(), 
            $this->getRemoteDestination(),
            ['--relative' => null]
        );

        // and run it
        $this->run('cd '.$this->paths->getBasePath());
        $this->run($cmd);

        return true;
    }
}