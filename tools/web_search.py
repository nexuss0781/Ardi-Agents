#!/usr/bin/env python3
"""
Web Search and Fetch Tool - Python Version
Provides web search and content fetching capabilities.
Uses only standard library (no heavy dependencies).
"""

import json
import urllib.request
import urllib.parse
import urllib.error
from typing import Dict, List, Any, Optional
import ssl


class WebSearcher:
    """Web search and content fetching tool."""
    
    def __init__(self, timeout: int = 10):
        self.timeout = timeout
        # Create SSL context that verifies certificates but allows some flexibility
        self.ssl_context = ssl.create_default_context()
    
    def fetch_url(self, url: str, headers: Optional[Dict[str, str]] = None) -> Dict[str, Any]:
        """Fetch content from a URL."""
        try:
            req_headers = {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language': 'en-US,en;q=0.5',
            }
            
            if headers:
                req_headers.update(headers)
            
            req = urllib.request.Request(url, headers=req_headers)
            
            with urllib.request.urlopen(req, timeout=self.timeout, context=self.ssl_context) as response:
                content = response.read().decode('utf-8', errors='ignore')
                status_code = response.status
                headers_dict = dict(response.headers)
                
                return {
                    "success": True,
                    "url": url,
                    "status_code": status_code,
                    "content": content,
                    "headers": headers_dict,
                    "content_length": len(content)
                }
        
        except urllib.error.HTTPError as e:
            return {
                "success": False,
                "error": f"HTTP Error {e.code}: {e.reason}",
                "status_code": e.code,
                "url": url
            }
        except urllib.error.URLError as e:
            return {
                "success": False,
                "error": f"URL Error: {str(e.reason)}",
                "url": url
            }
        except Exception as e:
            return {
                "success": False,
                "error": str(e),
                "url": url
            }
    
    def search_duckduckgo(self, query: str, num_results: int = 10) -> Dict[str, Any]:
        """
        Search using DuckDuckGo HTML interface.
        Returns search results without requiring API keys.
        """
        try:
            # DuckDuckGo HTML search
            base_url = "https://html.duckduckgo.com/html/"
            params = {'q': query}
            search_url = base_url + '?' + urllib.parse.urlencode(params)
            
            result = self.fetch_url(search_url)
            
            if not result["success"]:
                return result
            
            # Parse simple results from HTML
            content = result["content"]
            search_results = []
            
            # Simple parsing for result links
            lines = content.split('\n')
            for line in lines:
                if 'result__url' in line or 'result__title' in line:
                    # Extract URLs and titles
                    import re
                    urls = re.findall(r'href=["\'](.*?)["\']', line)
                    titles = re.findall(r'result__title[^>]*>(.*?)<', line)
                    
                    for url in urls:
                        if url.startswith('http') and 'duckduckgo.com' not in url:
                            search_results.append({
                                "url": url,
                                "title": titles[0] if titles else url
                            })
            
            # Limit to requested number
            search_results = search_results[:num_results]
            
            return {
                "success": True,
                "query": query,
                "results": search_results,
                "result_count": len(search_results),
                "search_engine": "DuckDuckGo"
            }
        
        except Exception as e:
            return {
                "success": False,
                "error": str(e),
                "query": query
            }
    
    def search_wikipedia(self, query: str, num_results: int = 5) -> Dict[str, Any]:
        """Search Wikipedia using their public API."""
        try:
            base_url = "https://en.wikipedia.org/w/api.php"
            params = {
                'action': 'query',
                'list': 'search',
                'srsearch': query,
                'format': 'json',
                'srlimit': num_results
            }
            
            url = base_url + '?' + urllib.parse.urlencode(params)
            result = self.fetch_url(url)
            
            if not result["success"]:
                return result
            
            data = json.loads(result["content"])
            search_results = []
            
            if 'query' in data and 'search' in data['query']:
                for item in data['query']['search']:
                    search_results.append({
                        "title": item['title'],
                        "url": f"https://en.wikipedia.org/wiki/{urllib.parse.quote(item['title'])}",
                        "snippet": item.get('snippet', ''),
                        "wordcount": item.get('wordcount', 0)
                    })
            
            return {
                "success": True,
                "query": query,
                "results": search_results,
                "result_count": len(search_results),
                "search_engine": "Wikipedia"
            }
        
        except Exception as e:
            return {
                "success": False,
                "error": str(e),
                "query": query
            }
    
    def get_webpage_summary(self, url: str, max_length: int = 2000) -> Dict[str, Any]:
        """Fetch and summarize a webpage by extracting main text content."""
        try:
            result = self.fetch_url(url)
            
            if not result["success"]:
                return result
            
            content = result["content"]
            
            # Simple HTML tag removal
            import re
            text = re.sub(r'<script[^>]*>.*?</script>', '', content, flags=re.DOTALL | re.IGNORECASE)
            text = re.sub(r'<style[^>]*>.*?</style>', '', text, flags=re.DOTALL | re.IGNORECASE)
            text = re.sub(r'<[^>]+>', '', text)
            text = re.sub(r'\s+', ' ', text)
            text = text.strip()
            
            # Truncate if needed
            if len(text) > max_length:
                text = text[:max_length] + "..."
            
            return {
                "success": True,
                "url": url,
                "summary": text,
                "original_length": len(content),
                "summary_length": len(text)
            }
        
        except Exception as e:
            return {
                "success": False,
                "error": str(e),
                "url": url
            }
    
    def check_url_status(self, url: str) -> Dict[str, Any]:
        """Check if a URL is accessible and get its status."""
        try:
            req = urllib.request.Request(url)
            req.add_header('User-Agent', 'Mozilla/5.0')
            
            response = urllib.request.urlopen(req, timeout=self.timeout, context=self.ssl_context)
            
            return {
                "success": True,
                "url": url,
                "status_code": response.status,
                "is_accessible": True,
                "content_type": response.headers.get('Content-Type', 'unknown'),
                "message": "URL is accessible"
            }
        
        except urllib.error.HTTPError as e:
            return {
                "success": True,
                "url": url,
                "status_code": e.code,
                "is_accessible": False,
                "message": f"HTTP Error: {e.reason}"
            }
        except Exception as e:
            return {
                "success": False,
                "url": url,
                "is_accessible": False,
                "error": str(e)
            }


# CLI interface for testing
if __name__ == "__main__":
    import sys
    
    searcher = WebSearcher()
    
    if len(sys.argv) < 2:
        print("Usage: python web_search.py <command> [args...]")
        print("Commands:")
        print("  fetch <url>           - Fetch content from URL")
        print("  search <query>        - Search using DuckDuckGo")
        print("  wiki <query>          - Search Wikipedia")
        print("  summary <url>         - Get webpage summary")
        print("  check <url>           - Check URL status")
        sys.exit(1)
    
    command = sys.argv[1]
    
    if command == "fetch":
        url = sys.argv[2] if len(sys.argv) > 2 else "https://example.com"
        result = searcher.fetch_url(url)
    elif command == "search":
        query = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "test"
        result = searcher.search_duckduckgo(query)
    elif command == "wiki":
        query = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "python"
        result = searcher.search_wikipedia(query)
    elif command == "summary":
        url = sys.argv[2] if len(sys.argv) > 2 else "https://example.com"
        result = searcher.get_webpage_summary(url)
    elif command == "check":
        url = sys.argv[2] if len(sys.argv) > 2 else "https://example.com"
        result = searcher.check_url_status(url)
    else:
        print(f"Unknown command: {command}")
        sys.exit(1)
    
    print(json.dumps(result, indent=2))
