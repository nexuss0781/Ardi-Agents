<?php
/**
 * Web Search and Fetch Tool - PHP Version
 * Provides web search and content fetching capabilities.
 * Compatible with InfinityFree hosting (uses only built-in functions).
 */

class WebSearcher {
    private $timeout = 10;
    
    public function __construct($timeout = 10) {
        $this->timeout = $timeout;
    }
    
    /**
     * Fetch content from a URL.
     */
    public function fetchUrl($url, $headers = null) {
        try {
            $defaultHeaders = array(
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5'
            );
            
            if ($headers && is_array($headers)) {
                foreach ($headers as $key => $value) {
                    $defaultHeaders[] = $key . ': ' . $value;
                }
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $content = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                return array(
                    "success" => false,
                    "error" => $error,
                    "url" => $url
                );
            }
            
            return array(
                "success" => true,
                "url" => $url,
                "status_code" => $statusCode,
                "content" => $content,
                "content_length" => strlen($content)
            );
            
        } catch (Exception $e) {
            return array(
                "success" => false,
                "error" => $e->getMessage(),
                "url" => $url
            );
        }
    }
    
    /**
     * Fetch content using file_get_contents (fallback for hosts without curl).
     */
    public function fetchUrlSimple($url) {
        try {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0\r\n" .
                               "Accept: text/html,application/xhtml+xml\r\n",
                    'timeout' => $this->timeout
                ),
                'ssl' => array(
                    'verify_peer' => true
                )
            ));
            
            $content = file_get_contents($url, false, $context);
            
            if ($content === false) {
                return array(
                    "success" => false,
                    "error" => "Failed to fetch URL",
                    "url" => $url
                );
            }
            
            return array(
                "success" => true,
                "url" => $url,
                "content" => $content,
                "content_length" => strlen($content)
            );
            
        } catch (Exception $e) {
            return array(
                "success" => false,
                "error" => $e->getMessage(),
                "url" => $url
            );
        }
    }
    
    /**
     * Search using DuckDuckGo HTML interface.
     */
    public function searchDuckDuckGo($query, $numResults = 10) {
        try {
            $baseUrl = "https://html.duckduckgo.com/html/";
            $searchUrl = $baseUrl . '?' . http_build_query(array('q' => $query));
            
            $result = $this->fetchUrl($searchUrl);
            
            if (!$result["success"]) {
                return $result;
            }
            
            $content = $result["content"];
            $searchResults = array();
            
            // Simple parsing for result links
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                if (strpos($line, 'result__url') !== false || strpos($line, 'result__title') !== false) {
                    // Extract URLs
                    preg_match_all('/href=["\'](.*?)["\']/', $line, $urlMatches);
                    preg_match_all('/result__title[^>]*>(.*?)</', $line, $titleMatches);
                    
                    foreach ($urlMatches[1] as $url) {
                        if (strpos($url, 'http') === 0 && strpos($url, 'duckduckgo.com') === false) {
                            $searchResults[] = array(
                                "url" => $url,
                                "title" => !empty($titleMatches[1]) ? $titleMatches[1][0] : $url
                            );
                        }
                    }
                }
            }
            
            // Limit results
            $searchResults = array_slice($searchResults, 0, $numResults);
            
            return array(
                "success" => true,
                "query" => $query,
                "results" => $searchResults,
                "result_count" => count($searchResults),
                "search_engine" => "DuckDuckGo"
            );
            
        } catch (Exception $e) {
            return array(
                "success" => false,
                "error" => $e->getMessage(),
                "query" => $query
            );
        }
    }
    
    /**
     * Search Wikipedia using their public API.
     */
    public function searchWikipedia($query, $numResults = 5) {
        try {
            $baseUrl = "https://en.wikipedia.org/w/api.php";
            $params = array(
                'action' => 'query',
                'list' => 'search',
                'srsearch' => $query,
                'format' => 'json',
                'srlimit' => $numResults
            );
            
            $url = $baseUrl . '?' . http_build_query($params);
            $result = $this->fetchUrl($url);
            
            if (!$result["success"]) {
                return $result;
            }
            
            $data = json_decode($result["content"], true);
            $searchResults = array();
            
            if (isset($data['query']['search']) && is_array($data['query']['search'])) {
                foreach ($data['query']['search'] as $item) {
                    $searchResults[] = array(
                        "title" => $item['title'],
                        "url" => "https://en.wikipedia.org/wiki/" . urlencode($item['title']),
                        "snippet" => isset($item['snippet']) ? $item['snippet'] : '',
                        "wordcount" => isset($item['wordcount']) ? $item['wordcount'] : 0
                    );
                }
            }
            
            return array(
                "success" => true,
                "query" => $query,
                "results" => $searchResults,
                "result_count" => count($searchResults),
                "search_engine" => "Wikipedia"
            );
            
        } catch (Exception $e) {
            return array(
                "success" => false,
                "error" => $e->getMessage(),
                "query" => $query
            );
        }
    }
    
    /**
     * Get webpage summary by extracting main text content.
     */
    public function getWebpageSummary($url, $maxLength = 2000) {
        try {
            $result = $this->fetchUrl($url);
            
            if (!$result["success"]) {
                return $result;
            }
            
            $content = $result["content"];
            
            // Remove script tags
            $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
            // Remove style tags
            $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $text);
            // Remove all HTML tags
            $text = strip_tags($text);
            // Normalize whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            // Truncate if needed
            if (strlen($text) > $maxLength) {
                $text = substr($text, 0, $maxLength) . "...";
            }
            
            return array(
                "success" => true,
                "url" => $url,
                "summary" => $text,
                "original_length" => strlen($content),
                "summary_length" => strlen($text)
            );
            
        } catch (Exception $e) {
            return array(
                "success" => false,
                "error" => $e->getMessage(),
                "url" => $url
            );
        }
    }
    
    /**
     * Check if a URL is accessible.
     */
    public function checkUrlStatus($url) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                return array(
                    "success" => false,
                    "url" => $url,
                    "is_accessible" => false,
                    "error" => $error
                );
            }
            
            return array(
                "success" => true,
                "url" => $url,
                "status_code" => $statusCode,
                "is_accessible" => ($statusCode >= 200 && $statusCode < 400),
                "message" => ($statusCode >= 200 && $statusCode < 400) ? "URL is accessible" : "HTTP Status: " . $statusCode
            );
            
        } catch (Exception $e) {
            return array(
                "success" => false,
                "url" => $url,
                "is_accessible" => false,
                "error" => $e->getMessage()
            );
        }
    }
}

// CLI interface for testing
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php web_search.php <command> [args...]\n";
        echo "Commands:\n";
        echo "  fetch <url>           - Fetch content from URL\n";
        echo "  search <query>        - Search using DuckDuckGo\n";
        echo "  wiki <query>          - Search Wikipedia\n";
        echo "  summary <url>         - Get webpage summary\n";
        echo "  check <url>           - Check URL status\n";
        exit(1);
    }
    
    $command = $argv[1];
    $searcher = new WebSearcher();
    
    switch ($command) {
        case "fetch":
            $url = isset($argv[2]) ? $argv[2] : "https://example.com";
            $result = $searcher->fetchUrl($url);
            break;
        case "search":
            $query = implode(" ", array_slice($argv, 2));
            if (empty($query)) $query = "test";
            $result = $searcher->searchDuckDuckGo($query);
            break;
        case "wiki":
            $query = implode(" ", array_slice($argv, 2));
            if (empty($query)) $query = "python";
            $result = $searcher->searchWikipedia($query);
            break;
        case "summary":
            $url = isset($argv[2]) ? $argv[2] : "https://example.com";
            $result = $searcher->getWebpageSummary($url);
            break;
        case "check":
            $url = isset($argv[2]) ? $argv[2] : "https://example.com";
            $result = $searcher->checkUrlStatus($url);
            break;
        default:
            echo "Unknown command: " . $command . "\n";
            exit(1);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>
