<?php

namespace WPTasks;

/**
 * Pushes the local wordpress database to a remote server
 */
class DbPush extends \Rocketeer\Abstracts\AbstractTask
{
    protected $description = 'Push database from local to remote';

    public function execute()
    {
        // INIT
        $localDeployPath    = rtrim($this->paths->getBasePath(), '/');
        $remoteDeployPath   = rtrim($this->paths->getFolder(), '/');
        $localTmpPath       = $localDeployPath.'/'.$this->paths->getStoragePath().'/tmp';
        $localSqlFileName   = $this->rocketeer->getApplicationName().'.local.sql';
        $localSqlPath       = $localTmpPath.'/'.$localSqlFileName;
        $remoteTmpPath      = $remoteDeployPath.'/tmp';
        $remoteBackupPath   = $remoteDeployPath.'/backup';
        $backupSqlFilePath  = $remoteBackupPath.'/'.$this->getTimestamp().'-'.$this->rocketeer->getApplicationName().'.sql';
        $importSqlPath      = $remoteTmpPath.'/'.$localSqlFileName;
        $cmdWp              = $this->paths->getPath('wp');

        // get local site name, for replacement in sql
        $localSiteUrl = getenv('WP_HOME');

        if ($localSiteUrl === false && file_exists($localDeployPath.'/.env')) {
            Dotenv::load($localDeployPath);
            $localSiteUrl = getenv('WP_HOME');
        }

        // could not determine local site name, so stop (perhaps we can also prompt for it?)
        if (empty($localSiteUrl)) {
            $this->explainer->error('Could not determine local site name, because local environment variable WP_HOME is not set or empty');
            return false;
        }
        
        // get remote site url from .env vars
        $remoteSiteUrl = $this->runSilently(
            array(
                'cd '.$this->paths->getFolder('shared'),
                'source .env',
                'export $(cut -d= -f1 < .env)',
                'echo $WP_HOME'
            )
        );

        // could not determine remote site name, so stop
        if (empty($remoteSiteUrl)) {
            $this->explainer->error('Could not determine remote site name, because remote environment variable WP_HOME is not set or empty');
            return false;
        }


        // PREPARE

        // create tmp folder
        if (!file_exists($localTmpPath)) {
            $this->explainer->line('Create local tmp folder');
            if (!mkdir($localTmpPath)) {
                $this->explainer->error('Could not create local tmp folder');
                return false;
            }
        }

        // create tmp directory on remote
        if (!$this->fileExists($remoteTmpPath)) {
            $this->explainer->line('Create remote tmp folder');
            $output = $this->createFolder($remoteTmpPath);
            if (!$this->checkStatus('Could not create remote tmp folder.', $output)) {
                return false;
            }
        }

        // create backup directory on remote
        if (!$this->fileExists($remoteBackupPath)) {
            $this->explainer->line('Create remote backup folder');
            $output = $this->createFolder($remoteBackupPath);
            if (!$this->checkStatus('Could not create remote backup folder.', $output)) {
                return false;
            }   
        }


        // RUN LOCALY

    	$localStatus = $this->onLocal(function () use ($localSqlPath, $remoteTmpPath, $localSiteUrl, $remoteSiteUrl)
        {
            // dump db in temp folder
            $this->explainer->line('Export local db');
            $output = $this->run('vendor/bin/wp db export '.$localSqlPath);
            if (!$this->checkStatus('Failed exporting local db', $output)) {
                return false;
            }

            /**
             * Escape characters in search and replacement strings for sed
             * @see http://stackoverflow.com/questions/407523/escape-a-string-for-a-sed-replace-pattern
             */
            $searchAndReplaceUrls = $this->runSilently(
                array(
                    "echo '".$localSiteUrl."' | sed -e 's/[]\/$*.^|[]/\\\&/g'", // search string
                    "echo '".$remoteSiteUrl."' | sed -e 's/[\/&]/\\\&/g'"       // replacement string
                )
            );
            list($searchUrl, $replaceUrl) = explode("\n", $searchAndReplaceUrls);
            
            // replace url
            $this->explainer->line('Replace local url with remote url');
            $output = $this->run("sed -i 's/".$searchUrl."/".$replaceUrl."/g' $localSqlPath");
            if (!$this->checkStatus('Failed replacing urls', $output)) {
                return false;
            }

            return true;
        });

        // check local status and stop if something went wrong
        if (!$localStatus) {
            return;
        }
     
        // transfer db to remote
        $this->explainer->line('Transfer db to remote server');
        $output = $this->upload($localSqlPath, $remoteTmpPath.'/'.$localSqlFileName);


        // RUN ON REMOTE

        // backup remote db
        $this->explainer->line('Create remote db backup');
        $output = $this->runForCurrentRelease($cmdWp . ' db export '.$backupSqlFilePath);
        if (!$this->checkStatus('Failed backup remote db', $output)) {
            return false;
        }

        // import db
        $this->explainer->line('Import local db on remote');
        $output = $this->runForCurrentRelease($cmdWp . ' db import '.$importSqlPath);
        if (!$this->checkStatus('Failed importing local db', $output)) {
            return false;
        }


        // CLEANUP

        $this->explainer->line('Clean up files and folders');

        // remove local exports
        $this->onLocal(function () use ($localTmpPath) {
            // @TODO do some check to prevent deleting too much?
            $this->explainer->line('Remove local temp directory');
            $this->run('rm -rf '.$localTmpPath);
        });

        // remove remote import
        // @TODO do some check to prevent deleting too much?
        $this->explainer->line('Remove remote temp directory');
        $this->removeFolder($remoteTmpPath);

        // remove backups that exceed the limit
        $backupsToKeep = $this->rocketeer->getOption('wp_tasks.db_backups_keep');
        $backupsToKeep = empty($backupsToKeep) ? 4 : intval($backupsToKeep);
        $backups = explode("\n", $this->runSilently('ls -t '.$remoteBackupPath));

        if (is_array($backups) && count($backups) > $backupsToKeep) {
            $rmBackups = array_slice($backups, $backupsToKeep);
            $this->explainer->line((count($rmBackups) < 2 ? 'Remove 1 backup' : 'Remove '.count($rmBackups).' backups'));
            $this->runInFolder($remoteBackupPath, 'rm '.implode(' ', $rmBackups));
        }

        return true;
    }
}