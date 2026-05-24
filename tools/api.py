#!/usr/bin/env python3
"""
Tools API - Python Version
Provides REST API endpoints to list and call all available tools.
Uses only standard library (no heavy dependencies).
"""

import json
import sys
import os
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from typing import Dict, Any, List

# Import tools
from file_manager import FileManager
from web_search import WebSearcher
from text_processor import TextProcessor


# Tool definitions with descriptions and modern icons
TOOLS_REGISTRY = {
    "file_manager": {
        "name": "File Manager",
        "description": "Full CRUD operations for files and folders with move, copy, and line number display",
        "version": "1.0.0",
        "language": "python",
        "icon": "📁",
        "icon_emoji": "folder",
        "icon_svg": "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'/></svg>",
        "tab_id": "file-manager-tab",
        "color": "#3B82F6",
        "methods": [
            {"name": "create_file", "description": "Create a new file with optional content", "params": ["path", "content"], "icon": "📄"},
            {"name": "read_file", "description": "Read file content with optional line numbers", "params": ["path", "show_line_numbers"], "icon": "👁️"},
            {"name": "update_file", "description": "Update existing file content", "params": ["path", "content"], "icon": "✏️"},
            {"name": "delete_file", "description": "Delete a file", "params": ["path"], "icon": "🗑️"},
            {"name": "create_folder", "description": "Create a new folder/directory", "params": ["path"], "icon": "📂"},
            {"name": "list_folder", "description": "List contents of a folder", "params": ["path", "show_line_numbers"], "icon": "📋"},
            {"name": "delete_folder", "description": "Delete a folder", "params": ["path", "recursive"], "icon": "🗑️"},
            {"name": "move_item", "description": "Move a file or folder to a new location", "params": ["source", "destination"], "icon": "➡️"},
            {"name": "copy_item", "description": "Copy a file or folder to a new location", "params": ["source", "destination"], "icon": "📑"},
            {"name": "get_info", "description": "Get detailed information about a file or folder", "params": ["path"], "icon": "ℹ️"}
        ]
    },
    "web_search": {
        "name": "Web Search",
        "description": "Search and fetch content from the internet",
        "version": "1.0.0",
        "language": "python",
        "icon": "🌐",
        "icon_emoji": "globe",
        "icon_svg": "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='2' y1='12' x2='22' y2='12'/><path d='M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z'/></svg>",
        "tab_id": "web-search-tab",
        "color": "#10B981",
        "methods": [
            {"name": "fetch_url", "description": "Fetch content from a URL", "params": ["url", "headers"], "icon": "📥"},
            {"name": "search_duckduckgo", "description": "Search using DuckDuckGo", "params": ["query", "num_results"], "icon": "🔍"},
            {"name": "search_wikipedia", "description": "Search Wikipedia", "params": ["query", "num_results"], "icon": "📚"},
            {"name": "get_webpage_summary", "description": "Get webpage summary", "params": ["url", "max_length"], "icon": "📝"},
            {"name": "check_url_status", "description": "Check if a URL is accessible", "params": ["url"], "icon": "✅"}
        ]
    },
    "text_processor": {
        "name": "Text Processor",
        "description": "Text manipulation, analysis, and transformation utilities",
        "version": "1.0.0",
        "language": "python",
        "icon": "⚙️",
        "icon_emoji": "gear",
        "icon_svg": "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='3'/><path d='M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z'/></svg>",
        "tab_id": "text-processor-tab",
        "color": "#F59E0B",
        "methods": [
            {"name": "count_words", "description": "Count words, characters, and lines", "params": ["text"], "icon": "📊"},
            {"name": "find_and_replace", "description": "Find and replace text", "params": ["text", "find", "replace", "case_sensitive"], "icon": "🔄"},
            {"name": "extract_emails", "description": "Extract email addresses from text", "params": ["text"], "icon": "📧"},
            {"name": "extract_urls", "description": "Extract URLs from text", "params": ["text"], "icon": "🔗"},
            {"name": "to_case", "description": "Convert text to different cases", "params": ["text", "case_type"], "icon": "🔤"},
            {"name": "hash_text", "description": "Generate hash of text", "params": ["text", "algorithm"], "icon": "🔐"},
            {"name": "remove_duplicates", "description": "Remove duplicate words or lines", "params": ["text", "by_line"], "icon": "✂️"},
            {"name": "reverse_text", "description": "Reverse text", "params": ["text", "by_word"], "icon": "🔃"},
            {"name": "truncate", "description": "Truncate text to maximum length", "params": ["text", "max_length", "suffix"], "icon": "📏"}
        ]
    }
}


class ToolsAPIHandler(BaseHTTPRequestHandler):
    """HTTP Request Handler for Tools API."""
    
    def _set_headers(self, status_code=200, content_type="application/json"):
        self.send_response(status_code)
        self.send_header("Content-Type", content_type)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()
    
    def do_OPTIONS(self):
        self._set_headers(200)
    
    def do_GET(self):
        parsed_path = urlparse(self.path)
        path = parsed_path.path
        query_params = parse_qs(parsed_path.query)
        
        if path == "/" or path == "/api":
            self._send_welcome()
        elif path == "/api/tools" or path == "/tools":
            self._list_tools()
        elif path.startswith("/api/tools/") or path.startswith("/tools/"):
            tool_name = path.split("/")[-1]
            self._get_tool_info(tool_name)
        else:
            self._send_error(404, "Not Found")
    
    def do_POST(self):
        parsed_path = urlparse(self.path)
        path = parsed_path.path
        
        content_length = int(self.headers.get("Content-Length", 0))
        post_data = self.rfile.read(content_length).decode("utf-8")
        
        try:
            request_body = json.loads(post_data) if post_data else {}
        except json.JSONDecodeError:
            request_body = {}
        
        if path.startswith("/api/call/") or path.startswith("/call/"):
            parts = path.split("/")
            if len(parts) >= 4:
                tool_name = parts[3]
                method_name = parts[4] if len(parts) > 4 else None
                self._call_tool(tool_name, method_name, request_body)
            else:
                self._send_error(400, "Invalid path format")
        else:
            self._send_error(404, "Not Found")
    
    def _send_welcome(self):
        response = {
            "message": "Welcome to Tools API",
            "version": "1.0.0",
            "endpoints": {
                "GET /api/tools": "List all available tools",
                "GET /api/tools/{tool_name}": "Get tool information",
                "POST /api/call/{tool_name}/{method}": "Call a tool method",
                "POST /api/call/{tool_name}": "Call a tool (auto-detect method)"
            }
        }
        self._set_headers()
        self.wfile.write(json.dumps(response, indent=2).encode())
    
    def _list_tools(self):
        tools_list = []
        for name, info in TOOLS_REGISTRY.items():
            tools_list.append({
                "name": name,
                "description": info["description"],
                "version": info["version"],
                "language": info["language"],
                "methods_count": len(info["methods"])
            })
        
        response = {
            "success": True,
            "tools": tools_list,
            "total": len(tools_list)
        }
        self._set_headers()
        self.wfile.write(json.dumps(response, indent=2).encode())
    
    def _get_tool_info(self, tool_name):
        if tool_name not in TOOLS_REGISTRY:
            self._send_error(404, f"Tool '{tool_name}' not found")
            return
        
        info = TOOLS_REGISTRY[tool_name]
        response = {
            "success": True,
            "tool": info
        }
        self._set_headers()
        self.wfile.write(json.dumps(response, indent=2).encode())
    
    def _call_tool(self, tool_name: str, method_name: str, params: Dict[str, Any]):
        if tool_name not in TOOLS_REGISTRY:
            self._send_error(404, f"Tool '{tool_name}' not found")
            return
        
        try:
            if tool_name == "file_manager":
                tool = FileManager()
            elif tool_name == "web_search":
                tool = WebSearcher()
            elif tool_name == "text_processor":
                tool = TextProcessor()
            else:
                self._send_error(404, f"Tool '{tool_name}' not implemented")
                return
            
            # If method_name not provided, try to auto-detect from params
            if not method_name:
                method_name = params.get("method", "")
            
            if not hasattr(tool, method_name):
                self._send_error(400, f"Method '{method_name}' not found in tool '{tool_name}'")
                return
            
            method = getattr(tool, method_name)
            
            # Filter params to only include what the method needs
            import inspect
            sig = inspect.signature(method)
            valid_params = {k: v for k, v in params.items() if k in sig.parameters}
            
            result = method(**valid_params)
            
            response = {
                "success": True,
                "tool": tool_name,
                "method": method_name,
                "result": result
            }
            self._set_headers()
            self.wfile.write(json.dumps(response, indent=2).encode())
        
        except Exception as e:
            self._send_error(500, f"Error executing method: {str(e)}")
    
    def _send_error(self, code: int, message: str):
        response = {
            "success": False,
            "error": message,
            "code": code
        }
        self._set_headers(code)
        self.wfile.write(json.dumps(response, indent=2).encode())
    
    def log_message(self, format, *args):
        print(f"[API] {args[0]}")


def run_server(host: str = "0.0.0.0", port: int = 8080):
    server_address = (host, port)
    httpd = HTTPServer(server_address, ToolsAPIHandler)
    print(f"Tools API Server running on http://{host}:{port}")
    print(f"Available endpoints:")
    print(f"  GET  /api/tools          - List all tools")
    print(f"  GET  /api/tools/{{name}}  - Get tool info")
    print(f"  POST /api/call/{{name}}/{{method}} - Call tool method")
    httpd.serve_forever()


if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8080
    host = sys.argv[2] if len(sys.argv) > 2 else "0.0.0.0"
    
    print(f"Starting Tools API on http://{host}:{port}")
    run_server(host, port)
