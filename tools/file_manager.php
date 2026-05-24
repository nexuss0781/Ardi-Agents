<?php
/**
 * File and Folder CRUD Tool - PHP Version
 * Provides full CRUD operations for files and folders with move, copy, and line number display.
 * Compatible with InfinityFree hosting.
 */

class FileManager {
    /**
     * Create a new file with optional content.
     */
    public static function createFile($path, $content = "") {
        try {
            // Ensure parent directory exists
            $parentDir = dirname($path);
            if ($parentDir && !file_exists($parentDir)) {
                mkdir($parentDir, 0755, true);
            }
            
            if (file_exists($path)) {
                return array("success" => false, "error" => "File already exists: " . $path);
            }
            
            file_put_contents($path, $content);
            
            return array(
                "success" => true,
                "message" => "File created successfully",
                "path" => realpath($path),
                "size" => strlen($content)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Read file content with optional line numbers.
     */
    public static function readFile($path, $showLineNumbers = false) {
        try {
            if (!file_exists($path)) {
                return array("success" => false, "error" => "File not found: " . $path);
            }
            
            if (is_dir($path)) {
                return array("success" => false, "error" => "Path is a directory: " . $path);
            }
            
            $content = file_get_contents($path);
            
            if ($showLineNumbers) {
                $lines = explode("\n", $content);
                $numberedContent = array();
                foreach ($lines as $i => $line) {
                    $numberedContent[] = sprintf("%6d: %s", $i + 1, $line);
                }
                $content = implode("\n", $numberedContent);
                
                return array(
                    "success" => true,
                    "path" => realpath($path),
                    "content" => $content,
                    "line_count" => count($lines),
                    "showing_line_numbers" => true
                );
            }
            
            return array(
                "success" => true,
                "path" => realpath($path),
                "content" => $content,
                "size" => strlen($content)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Update existing file content.
     */
    public static function updateFile($path, $content) {
        try {
            if (!file_exists($path)) {
                return array("success" => false, "error" => "File not found: " . $path);
            }
            
            if (is_dir($path)) {
                return array("success" => false, "error" => "Path is a directory: " . $path);
            }
            
            file_put_contents($path, $content);
            
            return array(
                "success" => true,
                "message" => "File updated successfully",
                "path" => realpath($path),
                "size" => strlen($content)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Delete a file.
     */
    public static function deleteFile($path) {
        try {
            if (!file_exists($path)) {
                return array("success" => false, "error" => "File not found: " . $path);
            }
            
            if (is_dir($path)) {
                return array("success" => false, "error" => "Path is a directory: " . $path);
            }
            
            unlink($path);
            
            return array(
                "success" => true,
                "message" => "File deleted successfully",
                "path" => $path
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Create a new folder/directory.
     */
    public static function createFolder($path) {
        try {
            if (file_exists($path)) {
                return array("success" => false, "error" => "Path already exists: " . $path);
            }
            
            mkdir($path, 0755, true);
            
            return array(
                "success" => true,
                "message" => "Folder created successfully",
                "path" => realpath($path)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * List contents of a folder.
     */
    public static function listFolder($path, $showLineNumbers = false) {
        try {
            if (!file_exists($path)) {
                return array("success" => false, "error" => "Folder not found: " . $path);
            }
            
            if (!is_dir($path)) {
                return array("success" => false, "error" => "Path is not a directory: " . $path);
            }
            
            $items = array();
            $dirItems = scandir($path);
            sort($dirItems);
            
            $idx = 1;
            foreach ($dirItems as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $itemPath = $path . DIRECTORY_SEPARATOR . $item;
                $itemInfo = array(
                    "name" => $item,
                    "path" => realpath($itemPath),
                    "is_directory" => is_dir($itemPath)
                );
                
                if ($showLineNumbers) {
                    $itemInfo["line_number"] = $idx;
                }
                
                if (is_file($itemPath)) {
                    $itemInfo["size"] = filesize($itemPath);
                }
                
                $items[] = $itemInfo;
                $idx++;
            }
            
            return array(
                "success" => true,
                "path" => realpath($path),
                "items" => $items,
                "total_count" => count($items),
                "showing_line_numbers" => $showLineNumbers
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Delete a folder.
     */
    public static function deleteFolder($path, $recursive = false) {
        try {
            if (!file_exists($path)) {
                return array("success" => false, "error" => "Folder not found: " . $path);
            }
            
            if (!is_dir($path)) {
                return array("success" => false, "error" => "Path is not a directory: " . $path);
            }
            
            if ($recursive) {
                self::deleteDirRecursive($path);
            } else {
                rmdir($path);
            }
            
            return array(
                "success" => true,
                "message" => "Folder deleted successfully",
                "path" => $path,
                "recursive" => $recursive
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Helper function to recursively delete a directory.
     */
    private static function deleteDirRecursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                self::deleteDirRecursive($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }
    
    /**
     * Move a file or folder to a new location.
     */
    public static function moveItem($source, $destination) {
        try {
            if (!file_exists($source)) {
                return array("success" => false, "error" => "Source not found: " . $source);
            }
            
            // Ensure destination parent directory exists
            $destParent = dirname($destination);
            if ($destParent && !file_exists($destParent)) {
                mkdir($destParent, 0755, true);
            }
            
            rename($source, $destination);
            
            return array(
                "success" => true,
                "message" => "Moved successfully",
                "source" => $source,
                "destination" => realpath($destination)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Copy a file or folder to a new location.
     */
    public static function copyItem($source, $destination) {
        try {
            if (!file_exists($source)) {
                return array("success" => false, "error" => "Source not found: " . $source);
            }
            
            // Ensure destination parent directory exists
            $destParent = dirname($destination);
            if ($destParent && !file_exists($destParent)) {
                mkdir($destParent, 0755, true);
            }
            
            if (is_dir($source)) {
                self::copyDirRecursive($source, $destination);
            } else {
                copy($source, $destination);
            }
            
            return array(
                "success" => true,
                "message" => "Copied successfully",
                "source" => $source,
                "destination" => realpath($destination)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Helper function to recursively copy a directory.
     */
    private static function copyDirRecursive($source, $dest) {
        if (!is_dir($source)) {
            return;
        }
        
        if (!file_exists($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $files = array_diff(scandir($source), array('.', '..'));
        foreach ($files as $file) {
            $srcPath = $source . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dest . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcPath)) {
                self::copyDirRecursive($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    
    /**
     * Get detailed information about a file or folder.
     */
    public static function getInfo($path) {
        try {
            if (!file_exists($path)) {
                return array("success" => false, "error" => "Path not found: " . $path);
            }
            
            $stat = stat($path);
            
            return array(
                "success" => true,
                "path" => realpath($path),
                "name" => basename($path),
                "is_directory" => is_dir($path),
                "size" => is_file($path) ? filesize($path) : null,
                "created_time" => $stat['ctime'],
                "modified_time" => $stat['mtime'],
                "accessed_time" => $stat['atime']
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
}

// CLI interface for testing
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php file_manager.php <command> [args...]\n";
        echo "Commands: create_file, read_file, update_file, delete_file,\n";
        echo "          create_folder, list_folder, delete_folder, move, copy, info\n";
        exit(1);
    }
    
    $command = $argv[1];
    $fm = new FileManager();
    
    switch ($command) {
        case "create_file":
            $path = isset($argv[2]) ? $argv[2] : "test.txt";
            $content = isset($argv[3]) ? $argv[3] : "";
            $result = $fm::createFile($path, $content);
            break;
        case "read_file":
            $path = isset($argv[2]) ? $argv[2] : "test.txt";
            $showLines = in_array("--lines", $argv);
            $result = $fm::readFile($path, $showLines);
            break;
        case "update_file":
            $path = isset($argv[2]) ? $argv[2] : "test.txt";
            $content = isset($argv[3]) ? $argv[3] : "";
            $result = $fm::updateFile($path, $content);
            break;
        case "delete_file":
            $path = isset($argv[2]) ? $argv[2] : "test.txt";
            $result = $fm::deleteFile($path);
            break;
        case "create_folder":
            $path = isset($argv[2]) ? $argv[2] : "test_folder";
            $result = $fm::createFolder($path);
            break;
        case "list_folder":
            $path = isset($argv[2]) ? $argv[2] : ".";
            $showLines = in_array("--lines", $argv);
            $result = $fm::listFolder($path, $showLines);
            break;
        case "delete_folder":
            $path = isset($argv[2]) ? $argv[2] : "test_folder";
            $recursive = in_array("--recursive", $argv);
            $result = $fm::deleteFolder($path, $recursive);
            break;
        case "move":
            $source = isset($argv[2]) ? $argv[2] : "source.txt";
            $dest = isset($argv[3]) ? $argv[3] : "dest.txt";
            $result = $fm::moveItem($source, $dest);
            break;
        case "copy":
            $source = isset($argv[2]) ? $argv[2] : "source.txt";
            $dest = isset($argv[3]) ? $argv[3] : "dest.txt";
            $result = $fm::copyItem($source, $dest);
            break;
        case "info":
            $path = isset($argv[2]) ? $argv[2] : ".";
            $result = $fm::getInfo($path);
            break;
        default:
            echo "Unknown command: " . $command . "\n";
            exit(1);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
