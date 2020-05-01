<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\Update;


use Bitsnbytes\Helpers\Configuration;
use Bitsnbytes\Models\Model;
use PDO;

class Update extends Model
{
    private Configuration $config;

    public function __construct(Configuration $config, PDO $pdo)
    {
        parent::__construct($pdo);
        $this->config = $config;
    }

    /**
     * Delete all files and folders in tmp dir except for folders containing a file called <i>empty</i>.
     */
    public function cleanupTmpFolder():array
    {
        return $this->deleteFilesInFolder($this->config->get('temp') . '');
    }

    /**
     * Delete all files and subfolders in Path, except for folders containing a file called 'empty'
     *
     * @param string $path Path without trailing slash
     *
     * @return string[] List of deleted files
     */
    private function deleteFilesInFolder(string $path): array
    {
        $deleted_files = [];
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $filepath = $path . '/' . $entry;
                    if (is_dir($filepath)) {
                        // delete all files in subfolder
                        array_push($deleted_files, ... $this->deleteFilesInFolder($filepath));
                        // Only delete folder if it is empty; scandir contains elements '.' and '..' if not empty.
                        // We can't check for the existence of the file empty as the empty-file may be within a
                        // subfolder, not directly und $filepath
                        if (count(scandir($filepath)) === 2) {
                            $deleted_files[] = realpath($filepath);
                            rmdir($filepath);
                        }
                    } elseif (is_file($filepath) && $entry !== 'empty') {
                        $deleted_files[] = realpath($filepath);
                        unlink($filepath);
                    }
                }
            }
        }
        closedir($handle);

        return $deleted_files;
    }
}