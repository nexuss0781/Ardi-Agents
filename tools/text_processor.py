#!/usr/bin/env python3
"""
Text Processing Tool - Python Version
Provides text manipulation, analysis, and transformation utilities.
Uses only standard library (no heavy dependencies).
"""

import json
import re
import hashlib
from typing import Dict, List, Any, Optional
from datetime import datetime


class TextProcessor:
    """Text processing and analysis tool."""
    
    @staticmethod
    def count_words(text: str) -> Dict[str, Any]:
        """Count words, characters, and lines in text."""
        try:
            words = text.split()
            lines = text.split('\n')
            
            return {
                "success": True,
                "word_count": len(words),
                "character_count": len(text),
                "character_count_no_spaces": len(text.replace(' ', '')),
                "line_count": len(lines),
                "paragraph_count": len([p for p in text.split('\n\n') if p.strip()])
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def find_and_replace(text: str, find: str, replace: str, case_sensitive: bool = True) -> Dict[str, Any]:
        """Find and replace text with optional case sensitivity."""
        try:
            if case_sensitive:
                new_text = text.replace(find, replace)
                count = text.count(find)
            else:
                pattern = re.compile(re.escape(find), re.IGNORECASE)
                new_text = pattern.sub(replace, text)
                count = len(pattern.findall(text))
            
            return {
                "success": True,
                "original_length": len(text),
                "new_length": len(new_text),
                "replacements_made": count,
                "text": new_text
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def extract_emails(text: str) -> Dict[str, Any]:
        """Extract email addresses from text."""
        try:
            pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
            emails = re.findall(pattern, text)
            unique_emails = list(set(emails))
            
            return {
                "success": True,
                "emails": unique_emails,
                "count": len(unique_emails),
                "total_found": len(emails)
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def extract_urls(text: str) -> Dict[str, Any]:
        """Extract URLs from text."""
        try:
            pattern = r'https?://[^\s<>"{}|\\^`\[\]]+'
            urls = re.findall(pattern, text)
            unique_urls = list(set(urls))
            
            return {
                "success": True,
                "urls": unique_urls,
                "count": len(unique_urls),
                "total_found": len(urls)
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def to_case(text: str, case_type: str) -> Dict[str, Any]:
        """Convert text to different cases."""
        try:
            if case_type == "upper":
                result = text.upper()
            elif case_type == "lower":
                result = text.lower()
            elif case_type == "title":
                result = text.title()
            elif case_type == "sentence":
                sentences = re.split(r'([.!?]\s*)', text)
                result = ''.join(s.capitalize() for s in sentences)
            elif case_type == "camel":
                words = re.findall(r'\w+', text)
                result = words[0].lower() + ''.join(word.capitalize() for word in words[1:])
            elif case_type == "snake":
                result = '_'.join(re.findall(r'\w+', text)).lower()
            elif case_type == "kebab":
                result = '-'.join(re.findall(r'\w+', text)).lower()
            else:
                return {"success": False, "error": f"Unknown case type: {case_type}"}
            
            return {
                "success": True,
                "original": text,
                "converted": result,
                "case_type": case_type
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def hash_text(text: str, algorithm: str = "sha256") -> Dict[str, Any]:
        """Generate hash of text."""
        try:
            algorithms = {
                "md5": hashlib.md5,
                "sha1": hashlib.sha1,
                "sha256": hashlib.sha256,
                "sha512": hashlib.sha512
            }
            
            if algorithm not in algorithms:
                return {"success": False, "error": f"Unsupported algorithm: {algorithm}"}
            
            hash_obj = algorithms[algorithm](text.encode('utf-8'))
            
            return {
                "success": True,
                "algorithm": algorithm,
                "hash": hash_obj.hexdigest(),
                "input_length": len(text)
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def remove_duplicates(text: str, by_line: bool = False) -> Dict[str, Any]:
        """Remove duplicate words or lines."""
        try:
            if by_line:
                lines = text.split('\n')
                seen = set()
                unique_lines = []
                for line in lines:
                    if line not in seen:
                        seen.add(line)
                        unique_lines.append(line)
                result = '\n'.join(unique_lines)
                removed_count = len(lines) - len(unique_lines)
            else:
                words = text.split()
                seen = set()
                unique_words = []
                for word in words:
                    if word not in seen:
                        seen.add(word)
                        unique_words.append(word)
                result = ' '.join(unique_words)
                removed_count = len(words) - len(unique_words)
            
            return {
                "success": True,
                "original_length": len(text),
                "new_length": len(result),
                "duplicates_removed": removed_count,
                "by_line": by_line,
                "text": result
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def reverse_text(text: str, by_word: bool = False) -> Dict[str, Any]:
        """Reverse text either character by character or word by word."""
        try:
            if by_word:
                words = text.split()
                result = ' '.join(reversed(words))
            else:
                result = text[::-1]
            
            return {
                "success": True,
                "original": text,
                "reversed": result,
                "by_word": by_word
            }
        except Exception as e:
            return {"success": False, "error": str(e)}
    
    @staticmethod
    def truncate(text: str, max_length: int, suffix: str = "...") -> Dict[str, Any]:
        """Truncate text to maximum length."""
        try:
            if len(text) <= max_length:
                return {
                    "success": True,
                    "original": text,
                    "truncated": text,
                    "was_truncated": False,
                    "original_length": len(text),
                    "max_length": max_length
                }
            
            truncated = text[:max_length] + suffix
            
            return {
                "success": True,
                "original": text,
                "truncated": truncated,
                "was_truncated": True,
                "original_length": len(text),
                "truncated_length": len(truncated),
                "max_length": max_length
            }
        except Exception as e:
            return {"success": False, "error": str(e)}


# CLI interface for testing
if __name__ == "__main__":
    import sys
    
    tp = TextProcessor()
    
    if len(sys.argv) < 2:
        print("Usage: python text_processor.py <command> [args...]")
        print("Commands:")
        print("  count <text>              - Count words, chars, lines")
        print("  replace <text> <find> <replace> - Find and replace")
        print("  emails <text>             - Extract emails")
        print("  urls <text>               - Extract URLs")
        print("  case <text> <type>        - Convert case (upper/lower/title/snake/kebab/camel)")
        print("  hash <text> [algorithm]   - Hash text (md5/sha1/sha256/sha512)")
        print("  dedup <text> [--lines]    - Remove duplicates")
        print("  reverse <text> [--words]  - Reverse text")
        print("  truncate <text> <length>  - Truncate text")
        sys.exit(1)
    
    command = sys.argv[1]
    
    if command == "count":
        text = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "Sample text for counting"
        result = tp.count_words(text)
    elif command == "replace":
        if len(sys.argv) >= 5:
            text = sys.argv[2]
            find = sys.argv[3]
            replace = sys.argv[4]
        else:
            text = "Hello World Hello"
            find = "Hello"
            replace = "Hi"
        result = tp.find_and_replace(text, find, replace)
    elif command == "emails":
        text = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "Contact us at test@example.com or support@test.org"
        result = tp.extract_emails(text)
    elif command == "urls":
        text = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "Visit https://example.com and http://test.org/page"
        result = tp.extract_urls(text)
    elif command == "case":
        text = sys.argv[2] if len(sys.argv) > 2 else "Hello World"
        case_type = sys.argv[3] if len(sys.argv) > 3 else "upper"
        result = tp.to_case(text, case_type)
    elif command == "hash":
        text = sys.argv[2] if len(sys.argv) > 2 else "test"
        algo = sys.argv[3] if len(sys.argv) > 3 else "sha256"
        result = tp.hash_text(text, algo)
    elif command == "dedup":
        text = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "hello world hello test world"
        by_line = "--lines" in sys.argv
        result = tp.remove_duplicates(text, by_line)
    elif command == "reverse":
        text = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else "Hello World"
        by_word = "--words" in sys.argv
        result = tp.reverse_text(text, by_word)
    elif command == "truncate":
        text = sys.argv[2] if len(sys.argv) > 2 else "This is a long text that needs truncation"
        length = int(sys.argv[3]) if len(sys.argv) > 3 else 20
        result = tp.truncate(text, length)
    else:
        print(f"Unknown command: {command}")
        sys.exit(1)
    
    print(json.dumps(result, indent=2))
