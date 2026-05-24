# Tools Documentation

## Overview

This project provides a comprehensive set of tools implemented in both **Python** and **PHP**, ensuring maximum compatibility across different hosting environments. The tools are designed to be synchronized between both languages, with Python as the primary deployment target and PHP as a fallback for environments like InfinityFree hosting.

## Project Principles

- **Dual Implementation**: Every tool is implemented in both Python and PHP with identical functionality
- **No Heavy Dependencies**: Python implementations use only standard library modules
- **InfinityFree Compatible**: PHP implementations work on shared hosting without special extensions
- **API Integration**: Both Python and PHP versions expose RESTful APIs
- **Modern UI Support**: Each tool includes modern icons (emoji + SVG) and unique tab identifiers for chat interfaces

---

## Available Tools

### 1. 📁 File Manager

**Tab ID**: `file-manager-tab`  
**Color**: `#3B82F6` (Blue)  
**Icon**: 📁

Full CRUD operations for files and folders with move, copy, and line number display.

#### Methods

| Method | Icon | Description | Parameters |
|--------|------|-------------|------------|
| `create_file` / `createFile` | 📄 | Create a new file with optional content | `path`, `content` |
| `read_file` / `readFile` | 👁️ | Read file content with optional line numbers | `path`, `show_line_numbers` |
| `update_file` / `updateFile` | ✏️ | Update existing file content | `path`, `content` |
| `delete_file` / `deleteFile` | 🗑️ | Delete a file | `path` |
| `create_folder` / `createFolder` | 📂 | Create a new folder/directory | `path` |
| `list_folder` / `listFolder` | 📋 | List contents of a folder | `path`, `show_line_numbers` |
| `delete_folder` / `deleteFolder` | 🗑️ | Delete a folder | `path`, `recursive` |
| `move_item` / `moveItem` | ➡️ | Move a file or folder to a new location | `source`, `destination` |
| `copy_item` / `copyItem` | 📑 | Copy a file or folder to a new location | `source`, `destination` |
| `get_info` / `getInfo` | ℹ️ | Get detailed information about a file or folder | `path` |

#### API Endpoints

```bash
# Python API
GET  http://localhost:8080/api/tools/file_manager
POST http://localhost:8080/api/call/file_manager/read_file
{
  "path": "/path/to/file.txt",
  "show_line_numbers": true
}

# PHP API
GET  http://your-domain.com/tools/api.php/tools/file_manager
POST http://your-domain.com/tools/api.php/api/call/file_manager/readFile
{
  "path": "/path/to/file.txt",
  "showLineNumbers": true
}
```

---

### 2. 🌐 Web Search

**Tab ID**: `web-search-tab`  
**Color**: `#10B981` (Green)  
**Icon**: 🌐

Search and fetch content from the internet using various sources.

#### Methods

| Method | Icon | Description | Parameters |
|--------|------|-------------|------------|
| `fetch_url` / `fetchUrl` | 📥 | Fetch content from a URL | `url`, `headers` |
| `search_duckduckgo` / `searchDuckDuckGo` | 🔍 | Search using DuckDuckGo | `query`, `num_results` |
| `search_wikipedia` / `searchWikipedia` | 📚 | Search Wikipedia | `query`, `num_results` |
| `get_webpage_summary` / `getWebpageSummary` | 📝 | Get webpage summary | `url`, `max_length` |
| `check_url_status` / `checkUrlStatus` | ✅ | Check if a URL is accessible | `url` |

#### API Endpoints

```bash
# Python API
POST http://localhost:8080/api/call/web_search/search_duckduckgo
{
  "query": "Python programming",
  "num_results": 5
}

# PHP API
POST http://your-domain.com/tools/api.php/api/call/web_search/searchDuckDuckGo
{
  "query": "Python programming",
  "numResults": 5
}
```

---

### 3. ⚙️ Text Processor

**Tab ID**: `text-processor-tab`  
**Color**: `#F59E0B` (Amber)  
**Icon**: ⚙️

Text manipulation, analysis, and transformation utilities.

#### Methods

| Method | Icon | Description | Parameters |
|--------|------|-------------|------------|
| `count_words` / `countWords` | 📊 | Count words, characters, and lines | `text` |
| `find_and_replace` / `findAndReplace` | 🔄 | Find and replace text | `text`, `find`, `replace`, `case_sensitive` |
| `extract_emails` / `extractEmails` | 📧 | Extract email addresses from text | `text` |
| `extract_urls` / `extractUrls` | 🔗 | Extract URLs from text | `text` |
| `to_case` / `toCase` | 🔤 | Convert text to different cases | `text`, `case_type` |
| `hash_text` / `hashText` | 🔐 | Generate hash of text | `text`, `algorithm` |
| `remove_duplicates` / `removeDuplicates` | ✂️ | Remove duplicate words or lines | `text`, `by_line` |
| `reverse_text` / `reverseText` | 🔃 | Reverse text | `text`, `by_word` |
| `truncate` / `truncate` | 📏 | Truncate text to maximum length | `text`, `max_length`, `suffix` |

#### API Endpoints

```bash
# Python API
POST http://localhost:8080/api/call/text_processor/count_words
{
  "text": "Hello world! This is a test."
}

# PHP API
POST http://your-domain.com/tools/api.php/api/call/text_processor/countWords
{
  "text": "Hello world! This is a test."
}
```

---

## API Reference

### List All Tools

Returns a list of all available tools with their metadata including icons and tab IDs.

```bash
# Python
GET http://localhost:8080/api/tools

# PHP
GET http://your-domain.com/tools/api.php/tools
```

**Response Example:**

```json
{
  "success": true,
  "tools": [
    {
      "name": "file_manager",
      "description": "Full CRUD operations for files and folders...",
      "version": "1.0.0",
      "language": "python",
      "icon": "📁",
      "tab_id": "file-manager-tab",
      "color": "#3B82F6",
      "methods_count": 10
    },
    {
      "name": "web_search",
      "description": "Search and fetch content from the internet",
      "version": "1.0.0",
      "language": "python",
      "icon": "🌐",
      "tab_id": "web-search-tab",
      "color": "#10B981",
      "methods_count": 5
    },
    {
      "name": "text_processor",
      "description": "Text manipulation, analysis, and transformation utilities",
      "version": "1.0.0",
      "language": "python",
      "icon": "⚙️",
      "tab_id": "text-processor-tab",
      "color": "#F59E0B",
      "methods_count": 9
    }
  ],
  "total": 3
}
```

### Get Tool Information

Returns detailed information about a specific tool including all methods with icons.

```bash
# Python
GET http://localhost:8080/api/tools/file_manager

# PHP
GET http://your-domain.com/tools/api.php/tools/file_manager
```

### Call Tool Method

Execute a specific tool method with provided parameters.

```bash
# Python
POST http://localhost:8080/api/call/{tool_name}/{method_name}
Content-Type: application/json

{
  "param1": "value1",
  "param2": "value2"
}

# PHP
POST http://your-domain.com/tools/api.php/api/call/{tool_name}/{methodName}
Content-Type: application/json

{
  "param1": "value1",
  "param2": "value2"
}
```

---

## Chat Interface Integration

Each tool is designed to integrate seamlessly into chat interfaces with:

- **Unique Tab ID**: Use the `tab_id` to create separate tabs for each tool
- **Custom Icons**: Use the `icon` emoji or `icon_svg` for visual representation
- **Brand Colors**: Use the `color` hex code for theming

### Example Frontend Integration

```javascript
// Fetch tools and render tabs
fetch('/api/tools')
  .then(response => response.json())
  .then(data => {
    data.tools.forEach(tool => {
      const tab = document.createElement('div');
      tab.className = 'tool-tab';
      tab.id = tool.tab_id;
      tab.style.backgroundColor = tool.color;
      tab.innerHTML = `
        <span class="icon">${tool.icon}</span>
        <span class="name">${tool.name}</span>
      `;
      document.getElementById('tabs-container').appendChild(tab);
    });
  });
```

---

## Running the APIs

### Python API

```bash
cd tools/
python api.py 8080
```

Server will start at `http://localhost:8080`

### PHP API

Upload the `tools/` directory to your web server or InfinityFree hosting. Access via:

```
http://your-domain.com/tools/api.php
```

---

## Testing

### CLI Testing (Python)

```bash
# File Manager
python file_manager.py create_file test.txt "Hello World"
python file_manager.py read_file test.txt --lines
python file_manager.py list_folder . --lines

# Text Processor
python text_processor.py count "Hello world! This is a test."
python text_processor.py extract_emails "Contact us at info@example.com"

# Web Search
python web_search.py fetch https://example.com
python web_search.py search_duckduckgo "Python programming" 5
```

### API Testing

```bash
# List all tools
curl http://localhost:8080/api/tools

# Get tool info
curl http://localhost:8080/api/tools/file_manager

# Call a method
curl -X POST http://localhost:8080/api/call/text_processor/count_words \
  -H "Content-Type: application/json" \
  -d '{"text": "Hello world! This is a test."}'
```

---

## File Structure

```
tools/
├── api.py                 # Python API server
├── api.php                # PHP API server
├── file_manager.py        # Python file manager tool
├── file_manager.php       # PHP file manager tool
├── web_search.py          # Python web search tool
├── web_search.php         # PHP web search tool
├── text_processor.py      # Python text processor tool
├── text_processor.php     # PHP text processor tool
└── __pycache__/           # Python cache directory

Prompt/
└── Tool.md                # This documentation file
```

---

## Notes

- **Line Numbers**: When reading files or listing folders, use the `show_line_numbers` parameter to display line numbers
- **Recursive Operations**: For folder deletion, set `recursive=true` to delete non-empty folders
- **Case Sensitivity**: Text processing methods support case-sensitive and case-insensitive operations
- **Hash Algorithms**: Supported algorithms: md5, sha1, sha256, sha512
- **URL Fetching**: Web search tools use cURL (PHP) and urllib (Python) - no external dependencies

---

## License

This project is provided as-is for educational and practical use.
