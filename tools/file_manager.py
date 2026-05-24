#!/usr/bin/env python3
"""
File and Folder CRUD Tool - Python Version
Provides full CRUD operations for files and folders with move, copy, and line number display.
"""

import os
import shutil
import json
from typing import Dict, List, Any, Optional


class FileManager:
    """File and folder management tool with CRUD operations."""
    
    @staticmethod
    def create_file(path: str, content: str = "") -> Dict[str, Any]:
        """Create a new file with optional content."""
        try:
            # Ensure parent directory exists
            parent_dir = os.path.dirname(path)
            if parent_dir and not os.path.exists(parent_dir):
                os.makedirs(parent_dir, exist_ok=True)
            
            if os.path.exists(path):
                return {"success": False, "error": f"File already exists: {path}"}
            
            with open(path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            return {
                "success": True,
                "message": f"File created successfully",
                "path": os.path.abspath(path),
                "size": len(content.encode('utf-8'))
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def read_file(path: str, show_line_numbers: bool = False) -> Dict[str, Any]:
        """Read file content with optional line numbers."""
        try:
            if not os.path.exists(path):
                return {"success": False, "error": f"File not found: {path}"}
            
            if os.path.isdir(path):
                return {"success": False, "error": f"Path is a directory: {path}"}
            
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            if show_line_numbers:
                lines = content.split('\n')
                numbered_content = '\n'.join([f"{i+1:6d}: {line}" for i, line in enumerate(lines)])
                return {
                    "success": True,
                    "path": os.path.abspath(path),
                    "content": numbered_content,
                    "line_count": len(lines),
                    "showing_line_numbers": True
                }
            
            return {
                "success": True,
                "path": os.path.abspath(path),
                "content": content,
                "size": len(content.encode('utf-8'))
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def update_file(path: str, content: str) -> Dict[str, Any]:
        """Update existing file content."""
        try:
            if not os.path.exists(path):
                return {"success": False, "error": f"File not found: {path}"}
            
            if os.path.isdir(path):
                return {"success": False, "error": f"Path is a directory: {path}"}
            
            with open(path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            return {
                "success": True,
                "message": f"File updated successfully",
                "path": os.path.abspath(path),
                "size": len(content.encode('utf-8'))
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def delete_file(path: str) -> Dict[str, Any]:
        """Delete a file."""
        try:
            if not os.path.exists(path):
                return {"success": False, "error": f"File not found: {path}"}
            
            if os.path.isdir(path):
                return {"success": False, "error": f"Path is a directory: {path}"}
            
            os.remove(path)
            return {
                "success": True,
                "message": f"File deleted successfully",
                "path": path
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def create_folder(path: str) -> Dict[str, Any]:
        """Create a new folder/directory."""
        try:
            if os.path.exists(path):
                return {"success": False, "error": f"Path already exists: {path}"}
            
            os.makedirs(path, exist_ok=True)
            return {
                "success": True,
                "message": f"Folder created successfully",
                "path": os.path.abspath(path)
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def list_folder(path: str, show_line_numbers: bool = False) -> Dict[str, Any]:
        """List contents of a folder."""
        try:
            if not os.path.exists(path):
                return {"success": False, "error": f"Folder not found: {path}"}
            
            if not os.path.isdir(path):
                return {"success": False, "error": f"Path is not a directory: {path}"}
            
            items = []
            for idx, item in enumerate(sorted(os.listdir(path)), 1):
                item_path = os.path.join(path, item)
                item_info = {
                    "name": item,
                    "path": os.path.abspath(item_path),
                    "is_directory": os.path.isdir(item_path)
                }
                if show_line_numbers:
                    item_info["line_number"] = idx
                
                if os.path.isfile(item_path):
                    item_info["size"] = os.path.getsize(item_path)
                
                items.append(item_info)
            
            return {
                "success": True,
                "path": os.path.abspath(path),
                "items": items,
                "total_count": len(items),
                "showing_line_numbers": show_line_numbers
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def delete_folder(path: str, recursive: bool = False) -> Dict[str, Any]:
        """Delete a folder."""
        try:
            if not os.path.exists(path):
                return {"success": False, "error": f"Folder not found: {path}"}
            
            if not os.path.isdir(path):
                return {"success": False, "error": f"Path is not a directory: {path}"}
            
            if recursive:
                shutil.rmtree(path)
            else:
                os.rmdir(path)
            
            return {
                "success": True,
                "message": f"Folder deleted successfully",
                "path": path,
                "recursive": recursive
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def move_item(source: str, destination: str) -> Dict[str, Any]:
        """Move a file or folder to a new location."""
        try:
            if not os.path.exists(source):
                return {"success": False, "error": f"Source not found: {source}"}
            
            # Ensure destination parent directory exists
            dest_parent = os.path.dirname(destination)
            if dest_parent and not os.path.exists(dest_parent):
                os.makedirs(dest_parent, exist_ok=True)
            
            shutil.move(source, destination)
            return {
                "success": True,
                "message": f"Moved successfully",
                "source": source,
                "destination": os.path.abspath(destination)
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def copy_item(source: str, destination: str) -> Dict[str, Any]:
        """Copy a file or folder to a new location."""
        try:
            if not os.path.exists(source):
                return {"success": False, "error": f"Source not found: {source}"}
            
            # Ensure destination parent directory exists
            dest_parent = os.path.dirname(destination)
            if dest_parent and not os.path.exists(dest_parent):
                os.makedirs(dest_parent, exist_ok=True)
            
            if os.path.isdir(source):
                shutil.copytree(source, destination)
            else:
                shutil.copy2(source, destination)
            
            return {
                "success": True,
                "message": f"Copied successfully",
                "source": source,
                "destination": os.path.abspath(destination)
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def get_info(path: str) -> Dict[str, Any]:
        """Get detailed information about a file or folder."""
        try:
            if not os.path.exists(path):
                return {"success": False, "error": f"Path not found: {path}"}
            
            stat_info = os.stat(path)
            return {
                "success": True,
                "path": os.path.abspath(path),
                "name": os.path.basename(path),
                "is_directory": os.path.isdir(path),
                "size": os.path.getsize(path) if os.path.isfile(path) else None,
                "created_time": stat_info.st_ctime,
                "modified_time": stat_info.st_mtime,
                "accessed_time": stat_info.st_atime
            }
        except Exception as e:
            return {"success": False, "error": str(e)}


# CLI interface for testing
if __name__ == "__main__":
    import sys
    
    fm = FileManager()
    
    if len(sys.argv) < 2:
        print("Usage: python file_manager.py <command> [args...]")
        print("Commands: create_file, read_file, update_file, delete_file,")
        print("          create_folder, list_folder, delete_folder, move, copy, info")
        sys.exit(1)
    
    command = sys.argv[1]
    
    if command == "create_file":
        path = sys.argv[2] if len(sys.argv) > 2 else "test.txt"
        content = sys.argv[3] if len(sys.argv) > 3 else ""
        result = fm.create_file(path, content)
    elif command == "read_file":
        path = sys.argv[2] if len(sys.argv) > 2 else "test.txt"
        show_lines = "--lines" in sys.argv
        result = fm.read_file(path, show_lines)
    elif command == "update_file":
        path = sys.argv[2] if len(sys.argv) > 2 else "test.txt"
        content = sys.argv[3] if len(sys.argv) > 3 else ""
        result = fm.update_file(path, content)
    elif command == "delete_file":
        path = sys.argv[2] if len(sys.argv) > 2 else "test.txt"
        result = fm.delete_file(path)
    elif command == "create_folder":
        path = sys.argv[2] if len(sys.argv) > 2 else "test_folder"
        result = fm.create_folder(path)
    elif command == "list_folder":
        path = sys.argv[2] if len(sys.argv) > 2 else "."
        show_lines = "--lines" in sys.argv
        result = fm.list_folder(path, show_lines)
    elif command == "delete_folder":
        path = sys.argv[2] if len(sys.argv) > 2 else "test_folder"
        recursive = "--recursive" in sys.argv
        result = fm.delete_folder(path, recursive)
    elif command == "move":
        source = sys.argv[2] if len(sys.argv) > 2 else "source.txt"
        dest = sys.argv[3] if len(sys.argv) > 3 else "dest.txt"
        result = fm.move_item(source, dest)
    elif command == "copy":
        source = sys.argv[2] if len(sys.argv) > 2 else "source.txt"
        dest = sys.argv[3] if len(sys.argv) > 3 else "dest.txt"
        result = fm.copy_item(source, dest)
    elif command == "info":
        path = sys.argv[2] if len(sys.argv) > 2 else "."
        result = fm.get_info(path)
    else:
        print(f"Unknown command: {command}")
        sys.exit(1)
    
    print(json.dumps(result, indent=2))
