<?php
/**
 * Text Processing Tool - PHP Version
 * Provides text manipulation, analysis, and transformation utilities.
 * Compatible with InfinityFree hosting (uses only built-in functions).
 */

class TextProcessor {
    
    /**
     * Count words, characters, and lines in text.
     */
    public static function countWords($text) {
        try {
            $words = preg_split('/\s+/', trim($text));
            $lines = explode("\n", $text);
            $paragraphs = array_filter(preg_split('/\n\n+/', $text), function($p) {
                return trim($p) !== '';
            });
            
            return array(
                "success" => true,
                "word_count" => count($words),
                "character_count" => strlen($text),
                "character_count_no_spaces" => strlen(str_replace(' ', '', $text)),
                "line_count" => count($lines),
                "paragraph_count" => count($paragraphs)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Find and replace text with optional case sensitivity.
     */
    public static function findAndReplace($text, $find, $replace, $caseSensitive = true) {
        try {
            if ($caseSensitive) {
                $count = substr_count($text, $find);
                $newText = str_replace($find, $replace, $text);
            } else {
                $pattern = '/' . preg_quote($find, '/') . '/i';
                $newText = preg_replace($pattern, $replace, $text);
                $count = preg_match_all($pattern, $text);
            }
            
            return array(
                "success" => true,
                "original_length" => strlen($text),
                "new_length" => strlen($newText),
                "replacements_made" => $count,
                "text" => $newText
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Extract email addresses from text.
     */
    public static function extractEmails($text) {
        try {
            $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
            preg_match_all($pattern, $text, $matches);
            $emails = array_unique($matches[0]);
            
            return array(
                "success" => true,
                "emails" => array_values($emails),
                "count" => count($emails),
                "total_found" => count($matches[0])
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Extract URLs from text.
     */
    public static function extractUrls($text) {
        try {
            $pattern = '/https?:\/\/[^\s<>"{}|\\\\^`\[\]]+/';
            preg_match_all($pattern, $text, $matches);
            $urls = array_unique($matches[0]);
            
            return array(
                "success" => true,
                "urls" => array_values($urls),
                "count" => count($urls),
                "total_found" => count($matches[0])
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Convert text to different cases.
     */
    public static function toCase($text, $caseType) {
        try {
            switch ($caseType) {
                case "upper":
                    $result = strtoupper($text);
                    break;
                case "lower":
                    $result = strtolower($text);
                    break;
                case "title":
                    $result = ucwords(strtolower($text));
                    break;
                case "sentence":
                    $sentences = preg_split('/([.!?]\s*)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                    $result = '';
                    foreach ($sentences as $s) {
                        $result .= ucfirst($s);
                    }
                    break;
                case "camel":
                    $words = preg_split('/\s+/', $text);
                    $result = strtolower($words[0]);
                    for ($i = 1; $i < count($words); $i++) {
                        $result .= ucfirst($words[$i]);
                    }
                    break;
                case "snake":
                    $words = preg_split('/\s+/', $text);
                    $result = strtolower(implode('_', $words));
                    break;
                case "kebab":
                    $words = preg_split('/\s+/', $text);
                    $result = strtolower(implode('-', $words));
                    break;
                default:
                    return array("success" => false, "error" => "Unknown case type: " . $caseType);
            }
            
            return array(
                "success" => true,
                "original" => $text,
                "converted" => $result,
                "case_type" => $caseType
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Generate hash of text.
     */
    public static function hashText($text, $algorithm = "sha256") {
        try {
            $algorithms = array(
                "md5" => "md5",
                "sha1" => "sha1",
                "sha256" => "sha256",
                "sha512" => "sha512"
            );
            
            if (!isset($algorithms[$algorithm])) {
                return array("success" => false, "error" => "Unsupported algorithm: " . $algorithm);
            }
            
            $hash = hash($algorithms[$algorithm], $text);
            
            return array(
                "success" => true,
                "algorithm" => $algorithm,
                "hash" => $hash,
                "input_length" => strlen($text)
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Remove duplicate words or lines.
     */
    public static function removeDuplicates($text, $byLine = false) {
        try {
            if ($byLine) {
                $lines = explode("\n", $text);
                $seen = array();
                $uniqueLines = array();
                foreach ($lines as $line) {
                    if (!in_array($line, $seen)) {
                        $seen[] = $line;
                        $uniqueLines[] = $line;
                    }
                }
                $result = implode("\n", $uniqueLines);
                $removedCount = count($lines) - count($uniqueLines);
            } else {
                $words = preg_split('/\s+/', $text);
                $seen = array();
                $uniqueWords = array();
                foreach ($words as $word) {
                    if (!in_array($word, $seen)) {
                        $seen[] = $word;
                        $uniqueWords[] = $word;
                    }
                }
                $result = implode(' ', $uniqueWords);
                $removedCount = count($words) - count($uniqueWords);
            }
            
            return array(
                "success" => true,
                "original_length" => strlen($text),
                "new_length" => strlen($result),
                "duplicates_removed" => $removedCount,
                "by_line" => $byLine,
                "text" => $result
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Reverse text either character by character or word by word.
     */
    public static function reverseText($text, $byWord = false) {
        try {
            if ($byWord) {
                $words = preg_split('/\s+/', $text);
                $result = implode(' ', array_reverse($words));
            } else {
                $result = strrev($text);
            }
            
            return array(
                "success" => true,
                "original" => $text,
                "reversed" => $result,
                "by_word" => $byWord
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
    
    /**
     * Truncate text to maximum length.
     */
    public static function truncate($text, $maxLength, $suffix = "...") {
        try {
            if (strlen($text) <= $maxLength) {
                return array(
                    "success" => true,
                    "original" => $text,
                    "truncated" => $text,
                    "was_truncated" => false,
                    "original_length" => strlen($text),
                    "max_length" => $maxLength
                );
            }
            
            $truncated = substr($text, 0, $maxLength) . $suffix;
            
            return array(
                "success" => true,
                "original" => $text,
                "truncated" => $truncated,
                "was_truncated" => true,
                "original_length" => strlen($text),
                "truncated_length" => strlen($truncated),
                "max_length" => $maxLength
            );
        } catch (Exception $e) {
            return array("success" => false, "error" => $e->getMessage());
        }
    }
}

// CLI interface for testing
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php text_processor.php <command> [args...]\n";
        echo "Commands:\n";
        echo "  count <text>              - Count words, chars, lines\n";
        echo "  replace <text> <find> <replace> - Find and replace\n";
        echo "  emails <text>             - Extract emails\n";
        echo "  urls <text>               - Extract URLs\n";
        echo "  case <text> <type>        - Convert case (upper/lower/title/snake/kebab/camel)\n";
        echo "  hash <text> [algorithm]   - Hash text (md5/sha1/sha256/sha512)\n";
        echo "  dedup <text> [--lines]    - Remove duplicates\n";
        echo "  reverse <text> [--words]  - Reverse text\n";
        echo "  truncate <text> <length>  - Truncate text\n";
        exit(1);
    }
    
    $command = $argv[1];
    
    switch ($command) {
        case "count":
            $text = implode(" ", array_slice($argv, 2));
            if (empty($text)) $text = "Sample text for counting";
            $result = TextProcessor::countWords($text);
            break;
        case "replace":
            if ($argc >= 5) {
                $text = $argv[2];
                $find = $argv[3];
                $replace = $argv[4];
            } else {
                $text = "Hello World Hello";
                $find = "Hello";
                $replace = "Hi";
            }
            $result = TextProcessor::findAndReplace($text, $find, $replace);
            break;
        case "emails":
            $text = implode(" ", array_slice($argv, 2));
            if (empty($text)) $text = "Contact us at test@example.com or support@test.org";
            $result = TextProcessor::extractEmails($text);
            break;
        case "urls":
            $text = implode(" ", array_slice($argv, 2));
            if (empty($text)) $text = "Visit https://example.com and http://test.org/page";
            $result = TextProcessor::extractUrls($text);
            break;
        case "case":
            $text = isset($argv[2]) ? $argv[2] : "Hello World";
            $caseType = isset($argv[3]) ? $argv[3] : "upper";
            $result = TextProcessor::toCase($text, $caseType);
            break;
        case "hash":
            $text = isset($argv[2]) ? $argv[2] : "test";
            $algo = isset($argv[3]) ? $argv[3] : "sha256";
            $result = TextProcessor::hashText($text, $algo);
            break;
        case "dedup":
            $text = implode(" ", array_slice($argv, 2));
            if (empty($text)) $text = "hello world hello test world";
            $byLine = in_array("--lines", $argv);
            $result = TextProcessor::removeDuplicates($text, $byLine);
            break;
        case "reverse":
            $text = implode(" ", array_slice($argv, 2));
            if (empty($text)) $text = "Hello World";
            $byWord = in_array("--words", $argv);
            $result = TextProcessor::reverseText($text, $byWord);
            break;
        case "truncate":
            $text = isset($argv[2]) ? $argv[2] : "This is a long text that needs truncation";
            $length = isset($argv[3]) ? intval($argv[3]) : 20;
            $result = TextProcessor::truncate($text, $length);
            break;
        default:
            echo "Unknown command: " . $command . "\n";
            exit(1);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
