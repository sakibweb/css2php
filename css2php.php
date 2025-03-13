#!/usr/bin/env python3
"""
CSS2PHP - Convert CSS files/URLs to PHP arrays
A versatile tool to convert any CSS file or URL to PHP array with advanced parsing.

Created by @sakibweb
GitHub: https://github.com/sakibweb/css2php
License: MIT
"""

import requests
import re
import time
import sys
import argparse
import os
import subprocess
from pathlib import Path
from cssselect import GenericTranslator
from collections import defaultdict
import cssutils
import logging
from typing import Optional, Dict, List, Union
from dataclasses import dataclass
from urllib.parse import urlparse
import hashlib
import json

__version__ = "1.0.1" # Version update
__author__ = "@sakibweb"
__repo__ = "https://github.com/sakibweb/css2php"

@dataclass
class ProcessingStats:
    """Statistics about CSS processing"""
    input_size: int
    output_size: int
    parsing_time: float
    class_count: int
    media_queries: int
    pseudo_classes: int
    file_path: str
    syntax_valid: bool
    syntax_error_message: str = "" # Added to store syntax error message

def get_safe_filename(url_or_path: str) -> str:
    """Generate a safe filename from URL or path"""
    if urlparse(url_or_path).scheme in ('http', 'https'):
        # Extract filename from URL
        path = urlparse(url_or_path).path
        filename = os.path.basename(path)
        if not filename or filename.endswith('/'):
            domain = urlparse(url_or_path).netloc.split('.')[0]
            filename = f"{domain}_styles"
        # Clean filename and remove .css extension
        filename = re.sub(r'[^\w\-_.]', '_', filename)
        filename = filename.replace('.css', '')
    else:
        filename = Path(url_or_path).stem
    return filename

class CSS2PHPConverter:
    """Convert CSS files/URLs to PHP arrays with advanced parsing support"""

    def __init__(self, timeout: int = 30, skip_errors: bool = False, overwrite: bool = True):
        self.timeout = timeout
        self.skip_errors = skip_errors
        self.overwrite = overwrite
        cssutils.log.setLevel(logging.CRITICAL)

    def _fetch_css_content(self, source: str) -> Optional[str]:
        """Fetch CSS content from URL or file"""
        try:
            if urlparse(source).scheme in ('http', 'https'):
                response = requests.get(source, timeout=self.timeout)
                response.raise_for_status()
                return response.text
            elif source.endswith('.css') and os.path.exists(source):
                with open(source, 'r', encoding='utf-8') as f:
                    return f.read()
        except Exception as e:
            if not self.skip_errors:
                raise
            print(f"Warning: Failed to fetch {source}: {e}")
        return None

    def _parse_css(self, css_content: str) -> tuple[dict, ProcessingStats]:
        """Parse CSS content and return class map with statistics"""
        start_time = time.time()

        parser = cssutils.CSSParser(validate=False)
        sheet = parser.parseString(css_content)
        class_map = defaultdict(lambda: {'default': {}, 'media': {}})
        stats = ProcessingStats(
            input_size=len(css_content),
            output_size=0,
            parsing_time=0,
            class_count=0,
            media_queries=0,
            pseudo_classes=0,
            file_path="",
            syntax_valid=False # Initialized as False, will be updated after validation
        )

        def parse_rule(rule, media_query=None):
            if isinstance(rule, cssutils.css.CSSStyleRule):
                # Handle normal style rules
                selectors = [s.strip() for s in rule.selectorText.split(',')]
                for selector in selectors:
                    if selector.startswith('.'):
                        class_name = selector.strip('.')
                        if ':' in class_name:
                            base_class, pseudo = class_name.split(':', 1)
                            class_name = f"{base_class}:{pseudo}"
                            stats.pseudo_classes += 1

                        properties = {
                            prop.name: prop.value
                            for prop in rule.style
                            if prop.name and prop.value
                        }

                        if media_query:
                            if class_name not in class_map:
                                class_map[class_name] = {'default': {}, 'media': {}} # Ensure class entry exists
                            class_map[class_name]['media'][media_query] = properties
                            stats.media_queries += 1
                        else:
                            class_map[class_name]['default'].update(properties)
                        stats.class_count += 1

            elif isinstance(rule, cssutils.css.CSSMediaRule):
                media_query_text = rule.media.mediaText
                for subrule in rule:
                    parse_rule(subrule, media_query_text)

        # Parse all rules
        for rule in sheet:
            parse_rule(rule)

        stats.parsing_time = time.time()
        return dict(class_map), stats

    def _generate_php(self, class_map: dict, source: str, stats: ProcessingStats) -> str:
        """Generate PHP array code from class map with comprehensive header"""
        # Calculate additional stats
        compression_ratio = (stats.output_size / stats.input_size * 100) if stats.input_size > 0 else 0

        php_code = f"""<?php
/**
 * CSS to PHP Array Map
 * ==========================================
 * Generated by CSS2PHP Converter v{__version__}
 * Created by {__author__}
 * {__repo__}
 * ==========================================
 *
 * Source Information:
 * ------------------------------------------
 * File: {source}
 * Generated: {time.strftime('%Y-%m-%d %H:%M:%S')}
 *
 * Processing Statistics (A-Z):
 * ------------------------------------------
 * Classes found: {stats.class_count:,}
 * Compression ratio: {compression_ratio:.1f}%
 * Input size: {stats.input_size:,} bytes
 * Media queries: {stats.media_queries:,}
 * Output size: {stats.output_size:,} bytes
 * Processing time: {stats.parsing_time:.3f}s
 * Pseudo-classes: {stats.pseudo_classes:,}
 * Syntax validation: {'✅ Valid' if stats.syntax_valid else '❌ Invalid'}
 *
 * Configuration:
 * ------------------------------------------
 * Parse mode: Advanced (cssutils)
 * Skip errors: {'Yes' if self.skip_errors else 'No'}
 * Timeout: {self.timeout}s
 * Overwrite enabled: {'Yes' if self.overwrite else 'No'}
 *
 * File Structure:
 * ------------------------------------------
 * - Array keys are CSS class names
 * - Each class has 'default' and optional 'media' properties
 * - Media queries are nested under 'media' key
 * - All properties are sorted alphabetically
 * ==========================================
 */

return [
"""
        # Sort and format output
        for class_name, rules in sorted(class_map.items()):
            if rules['default'] or rules['media']:
                # Remove all backslashes from class name except escaped single quotes
                clean_class = class_name
                # Remove common Tailwind escape patterns
                clean_class = re.sub(r'\\([\.:/\-])', r'\1', clean_class)
                # Only escape single quotes for PHP
                escaped_class = clean_class.replace("'", "\\'")
                
                php_code += f"    '{escaped_class}' => [\n"

                if rules['default']:
                    if rules['default']:
                        php_code += "        'default' => '"
                        properties_default = '; '.join(
                            f"{k}: {v}" for k, v in sorted(rules['default'].items())
                        )
                        escaped_props_default = properties_default.replace("'", "\\'")
                        php_code += f"{escaped_props_default};',\n"

                if rules['media']:
                    php_code += "        'media' => [\n"
                    for media_query, properties in sorted(rules['media'].items()):
                        php_code += f"            '{media_query}' => '"
                        properties_media = '; '.join(
                            f"{k}: {v}" for k, v in sorted(properties.items())
                        )
                        escaped_props_media = properties_media.replace("'", "\\'")
                        php_code += f"{escaped_props_media};',\n"
                    php_code += "        ],\n"

                php_code += "    ],\n"

        php_code += "];\n"
        return php_code

    def _validate_php_syntax(self, php_code: str) -> tuple[bool, str, str]:
        """
        Validate PHP syntax and attempt to fix common issues
        Returns: (is_valid, error_message, fixed_code)
        """
        error_msg = "" # Initialize error_msg here
        is_valid = False # Initialize is_valid to False
        try:
            # First try original code
            temp_file = Path('temp_validation.php')
            temp_file.write_text(php_code, encoding='utf-8')

            result = subprocess.run(
                ['php', '-l', str(temp_file)],
                capture_output=True,
                text=True,
                check=False
            )

            if temp_file.exists():
                temp_file.unlink()

            is_valid = result.returncode == 0 and 'No syntax errors detected' in result.stdout
            print(f"Debug _validate_php_syntax: Initial PHP validation - Is Valid: {is_valid}") # Debug print


            if is_valid:
                print(f"Debug _validate_php_syntax: Returning is_valid: {is_valid} (No Fixes)") # Debug print
                return True, "", php_code

            # If invalid, try to fix common issues
            error_msg = result.stderr.strip() # Capture error message here
            fixed_code = php_code

            print(f"⚠️ Initial PHP Syntax Error Detected:\n{error_msg}\nAttempting to fix...") # Debug print

            # Common fixes - expanded and refined
            fixes = [
                # Fix 1: Missing comma after array item - improved regex
                (r']([\s\n]+)\[', r'],\n['),
                # Fix 2: Extra comma at end of array - improved regex
                (r',(\s*)\];', r'\n];'),
                # Fix 3: Missing semicolon after properties - improved regex
                (r'([^;])\'\,', r'\1;\', '),
                # Fix 4: Unescaped special chars - more comprehensive escaping
                (r'([^\\\\])([\'\"\$])', r'\1\\\2'), # Added backslash escaping
                # Fix 5: Invalid media query keys - more robust media query key handling
                (r'\'(\([^\)]+\))\'', lambda m: f"'{m.group(1).replace('(', '\\\\(').replace(')', '\\\\)')}'"),
                # Fix 6: Remove trailing commas in arrays - as a last resort before validation
                (r',(\s*)\n\s*]', r'\1\n    ]'), # Remove trailing comma before closing bracket
                (r',(\s*)\n\s*},\s*\n\s*]', r'\1\n    }\n    ]'), # Deeper level trailing comma
            ]

            for pattern, replacement in fixes:
                old_code = fixed_code # For debug comparison
                fixed_code = re.sub(pattern, replacement, fixed_code)
                # if old_code != fixed_code: # Debug: print if a fix was applied
                #     print(f"Applied fix: {pattern}")


            # Validate fixed code
            temp_file.write_text(fixed_code, encoding='utf-8')
            result = subprocess.run(
                ['php', '-l', str(temp_file)],
                capture_output=True,
                text=True,
                check=False
            )

            if temp_file.exists():
                temp_file.unlink()

            is_fixed = result.returncode == 0 and 'No syntax errors detected' in result.stdout
            fixed_error_msg = result.stderr.strip() # Capture error after fix attempt
            print(f"Debug _validate_php_syntax: After fix PHP validation - Is Fixed: {is_fixed}") # Debug print


            if is_fixed:
                print(f"Debug _validate_php_syntax: Returning is_valid: {is_fixed} (Fixed)") # Debug print
                return True, f"Fixed issues: {error_msg}\nDetails: {fixed_error_msg if fixed_error_msg else 'No further errors reported after fixes.'}", fixed_code
            else:
                final_error_msg = f"Original error: {error_msg}\nCould not fix automatically. Final error after fixes: {fixed_error_msg}"
                print(f"Debug _validate_php_syntax: Returning is_valid: {is_fixed} (Fix Failed)") # Debug print
                return False, final_error_msg, php_code # Return original code if fix failed

        except Exception as e:
            if temp_file.exists():
                temp_file.unlink()
            print(f"Debug _validate_php_syntax: Exception - Returning is_valid: False") # Debug print
            return False, str(e), php_code
        finally:
            print(f"Debug _validate_php_syntax: Finally - Returning is_valid: {is_valid}") # Debug print
            return is_valid, error_msg, php_code # Ensure is_valid is returned even in exceptions


    def _generate_report(self, stats: ProcessingStats, php_valid: bool) -> str:
        """Generate detailed processing report"""
        report = [
            "=" * 50,
            "CSS to PHP Conversion Report",
            "=" * 50,
            f"Timestamp: {time.strftime('%Y-%m-%d %H:%M:%S')}",
            f"Source: {stats.file_path}",
            "-" * 50,
            "Processing Statistics:",
            f"• Input Size: {stats.input_size:,} bytes",
            f"• Output Size: {stats.output_size:,} bytes",
            f"• Compression Ratio: {(stats.output_size / stats.input_size * 100):.1f}%",
            f"• Processing Time: {stats.parsing_time:.3f} seconds",
            "-" * 50,
            "Content Analysis:",
            f"• CSS Classes Found: {stats.class_count}",
            f"• Media Queries: {stats.media_queries}",
            f"• Pseudo-classes: {stats.pseudo_classes}",
            "-" * 50,
            "Validation Results:",
            f"• PHP Syntax: {'✅ Valid' if php_valid else '❌ Invalid'}",
            f"• PHP Syntax Error Details: {stats.syntax_error_message if not php_valid else 'No errors.'}", # Include error details
            "=" * 50
        ]
        return "\n".join(report)

    def convert(self, source: str, output_path: str = None, name_prefix: str = '') -> Optional[ProcessingStats]:
        """Convert CSS source to PHP array"""
        try:
            start_time = time.time()
            css_content = self._fetch_css_content(source)
            if not css_content:
                return None

            # Generate output path
            if not output_path:
                output_dir = Path('./output')
                output_dir.mkdir(exist_ok=True)
                safe_name = get_safe_filename(source)
                output_path = output_dir / f"{name_prefix}{safe_name}.php"

            output_path = Path(output_path)

            # Check if file exists and overwrite is disabled
            if output_path.exists() and not self.overwrite:
                raise FileExistsError(f"Output file already exists: {output_path}")

            output_path.parent.mkdir(parents=True, exist_ok=True)

            # Process CSS and generate PHP
            class_map, stats = self._parse_css(css_content)
            php_code = self._generate_php(class_map, source, stats)

            # Validate and try to fix PHP syntax
            is_valid, error_msg, fixed_code = self._validate_php_syntax(php_code)
            stats.syntax_valid = is_valid # Update stats.syntax_valid here
            stats.syntax_error_message = error_msg # Store error message

            if not is_valid:
                print(f"\n⚠️ PHP Syntax Issues:\n{error_msg}") # More verbose error output
                if not self.skip_errors:
                    raise ValueError("Generated PHP code has syntax errors")
            elif error_msg:  # Valid but needed fixes
                print(f"\n📝 {error_msg}") # Print fix details
                php_code = fixed_code  # Use the fixed version

            # Update output size AFTER validation and potential fixes
            stats.output_size = len(php_code) # Calculate output size AFTER validation and fixes

            # Regenerate PHP code with potentially fixed syntax and updated stats (Crucial fix)
            php_code_final = self._generate_php(class_map, source, stats) # Regenerate with updated stats


            # Save PHP file
            output_path.write_text(php_code_final, encoding='utf-8') # Save the final generated PHP code
            stats.file_path = str(output_path)

            return stats

        except Exception as e:
            if not self.skip_errors:
                raise
            print(f"Error processing {source}: {e}")
            return None

def merge_php_files(merge_dir: str, output_dir: str, name_prefix: str, priority_file: Optional[str] = None) -> None:
    """Merge PHP files with property-level merging."""
    print(f"\n📦 Merging PHP files from {merge_dir}...")

    output_path = Path(output_dir)
    output_path.mkdir(parents=True, exist_ok=True)

    php_files = sorted(Path(merge_dir).glob('*.php'), key=lambda x: x.stat().st_size, reverse=True)
    if not php_files:
        print("❌ No PHP files found to merge")
        return

    if priority_file:
        priority_path = Path(priority_file)
        if priority_path in php_files:
            php_files.remove(priority_path)
            php_files.insert(0, priority_path)
            print(f"🎯 Priority file: {priority_path.name}")
        else:
            print(f"⚠️ Priority file not found: {priority_file}")

    merged_arrays = {}
    processed_files = []
    duplicates = defaultdict(list)

    for file in php_files:
        try:
            content = file.read_text(encoding='utf-8')
            parsed = parse_php_array_improved(content) # Use improved parser

            for class_name, class_data in parsed.items():
                if class_name in merged_arrays:
                    duplicates[class_name].append(file.name)
                    # Merge properties - Last file wins for conflicts
                    merged_arrays[class_name]['default'].update(class_data['default']) # Merge default properties
                    merged_media = merged_arrays[class_name]['media']
                    for mq, mq_props in class_data['media'].items(): # Merge media queries
                        if mq in merged_media:
                            merged_media[mq].update(mq_props) # Merge properties within media query
                        else:
                            merged_media[mq] = mq_props # Add new media query
                else:
                    merged_arrays[class_name] = class_data
            processed_files.append(file.name)
            print(f"✓ Processed: {file.name}")

        except Exception as e:
            print(f"❌ Error processing {file.name}: {e}")

    output_file = output_path / f"{name_prefix}.php"
    php_code = generate_merged_php_code(merged_arrays, __version__, __author__, __repo__, merge_dir, processed_files, duplicates, priority_file) # Use a function for code generation

    output_file.write_text(php_code)

    print("\n🔍 Validating merged file...")
    is_valid, error_msg, _ = CSS2PHPConverter()._validate_php_syntax(php_code)

    print(f"\n✨ Merge Complete!")
    print(f"📊 Summary:")
    print(f"   • Files processed: {len(processed_files)}")
    print(f"   • Unique classes: {len(merged_arrays)}")
    print(f"   • Duplicates skipped: {len(duplicates)}") # Still report duplicates (though now merged)
    print(f"   • Output file: {output_file}")
    print(f"   • PHP Syntax: {'✅ Valid' if is_valid else '❌ Invalid'}")

    if not is_valid:
        print(f"⚠️ Syntax validation error: {error_msg}")


def generate_merged_php_code(merged_arrays, version, author, repo, merge_dir, processed_files, duplicates, priority_file):
    """Generates the PHP code for the merged array."""
    php_code = f"""<?php
/**
 * Merged PHP Array Map
 * ==========================================
 * Generated by CSS2PHP Converter v{version}
 * Created by {author}
 * {repo}
 * ==========================================
 *
 * Merge Information:
 * ------------------------------------------
 * Source directory: {merge_dir}
 * Files processed: {len(processed_files)}
 * Unique classes: {len(merged_arrays)}
 * Duplicates found: {len(duplicates)} (Properties Merged - Last File Wins)
 * Priority file: {priority_file if priority_file else 'None (sorted by size)'}
 * Generated: {time.strftime('%Y-%m-%d %H:%M:%S')}
 *
 * Duplicate Information (Properties Merged):
 * ------------------------------------------
{format_duplicate_info(duplicates)}
 * ==========================================
 */

return [
"""

    for key in sorted(merged_arrays.keys()):
        value = merged_arrays[key]
        php_code += f"    '{key}' => [\n"

        if value['default']:
            props = '; '.join(f"{k}: {v}" for k, v in sorted(value['default'].items()))
            php_code += f"        'default' => '{props};',\n"

        if value['media']:
            php_code += "        'media' => [\n"
            for query, props in sorted(value['media'].items()):
                props_str = '; '.join(f"{k}: {v}" for k, v in sorted(props.items()))
                php_code += f"            '{query}' => '{props_str};',\n"
            php_code += "        ],\n"

        php_code += "    ],\n"

    php_code += "];\n"
    return php_code

def parse_php_array_improved(content: str) -> Dict[str, Dict]:
    """Parse PHP array content with enhanced accuracy and robustness."""
    try:
        if 'return [' not in content or '];' not in content:
            return {}

        array_text = content.split('return [')[1].split('];')[0].strip()

        result = {}
        current_class_name = None
        current_class_data = {"default": {}, "media": {}}
        mode = 'class' # 'class', 'default_props', 'media_props', 'media_query'
        current_media_query = None

        for line in array_text.split('\n'):
            line = line.strip()
            if not line or line.startswith('*') or line.startswith('//'): # Skip comments and empty lines
                continue

            if line.endswith('=> ['): # Start of a class or media block
                key_match = re.search(r"'([^']*)' => \[", line)
                if key_match:
                    key = key_match.group(1)
                    if mode == 'class':
                        current_class_name = key
                        current_class_data = {"default": {}, "media": {}}
                        mode = 'default_props'
                    elif mode == 'media_props':
                        current_media_query = key
                        mode = 'media_query'

            elif line == '],\n' or line == '],' or line == '],': # End of a block
                if mode == 'default_props':
                    mode = 'class'
                    if current_class_name:
                        result[current_class_name] = current_class_data
                        current_class_name = None
                elif mode == 'media_query':
                    mode = 'media_props'
                    current_media_query = None

            elif mode == 'default_props' and "'default' => '" in line:
                props_str_match = re.search(r"'default' => '([^']*)'", line)
                if props_str_match:
                    props_str = props_str_match.group(1)
                    props = parse_properties_string(props_str)
                    current_class_data["default"] = props

            elif mode == 'media_props' and "'media' => [" in line:
                mode = 'media_props' # Stay in media_props mode

            elif mode == 'media_query' and '=>' in line and line.endswith("',"): # Media query properties
                prop_line_match = re.search(r"'([^']*)' => '([^']*)',", line) # Adjusted regex
                if prop_line_match:
                    mq_prop_name = prop_line_match.group(1)
                    mq_prop_value = prop_line_match.group(2)
                    props = parse_properties_string(mq_prop_value + ';') # Add semicolon to ensure parsing
                    if current_media_query:
                        current_class_data["media"][current_media_query] = props # Assign props directly

        if current_class_name and current_class_data: # Handle last class
             result[current_class_name] = current_class_data

        return result

    except Exception as e:
        print(f"⚠️ Error parsing array: {e}")
        return {}

def parse_properties_string(props_str: str) -> Dict[str, str]:
    """Parses a CSS properties string into a dictionary."""
    properties = {}
    for prop_pair in props_str.strip(';').split(';'):
        if ':' in prop_pair:
            k, v = prop_pair.split(':', 1)
            properties[k.strip()] = v.strip()
    return properties

def indent_array_content(content: str) -> str:
    """Format array content with proper indentation"""
    lines = content.split('\n')
    indent_level = 0
    formatted_lines = []
    
    for line in lines:
        line = line.strip()
        if not line:
            continue
            
        # Adjust indentation based on brackets
        indent_level += line.count('[') - line.count(']')
        spaces = '    ' * indent_level
        formatted_lines.append(spaces + line)
        
        if ']' in line:
            indent_level = max(0, indent_level - line.count(']'))
    
    return '\n'.join(formatted_lines)

def format_duplicate_info(duplicates: Dict[str, List[str]]) -> str:
    """Format duplicate information for header comment"""
    if not duplicates:
        return " * No duplicates found"
    
    lines = []
    for key, files in sorted(duplicates.items()):
        files_str = ', '.join(files)
        lines.append(f" * '{key}' found in: {files_str}")
    
    return '\n'.join(lines)

def main():
    parser = argparse.ArgumentParser(
        prog='css2php',
        description="""
CSS2PHP - Convert CSS files/URLs to PHP arrays
A versatile tool that converts CSS files or URLs into PHP arrays with advanced parsing support.

Features:
- Supports both local CSS files and remote URLs
- Handles media queries and pseudo-classes
- Validates PHP syntax with auto-fix
- Detailed statistics and reporting
- Customizable output paths and file naming

Created by @sakibweb
GitHub: https://github.com/sakibweb/css2php
""",
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    
    # Keep existing arguments
    parser.add_argument('sources', nargs='*', help="CSS file paths or URLs")
    parser.add_argument('-o', '--output', help="Output directory path")
    parser.add_argument('-n', '--name-prefix', default='', help="Output filename prefix")
    parser.add_argument('-t', '--timeout', type=int, default=30, help="Timeout in seconds")
    parser.add_argument('-s', '--skip-errors', action='store_true', help="Skip errors and continue")
    parser.add_argument('-r', '--no-overwrite', action='store_true', help="Don't overwrite existing files")
    # Add new merge-related arguments
    parser.add_argument('-m', '--merge', action='store_true', help="Merge PHP files from specified directory")
    parser.add_argument('-md', '--merge-dir', default='./output', help="Directory containing PHP files to merge (default: ./output)")
    parser.add_argument('-p', '--priority-file', help="Priority file to process first when merging")
    parser.add_argument('-mo', '--merge-output', default='./output', help="Output directory for merged file (default: ./output)")
    parser.add_argument('-mn', '--merge-name', default='main', help="Name prefix for merged file (default: main)")
    parser.add_argument('-v', '--version', action='version', version=f'%(prog)s {__version__}\nCreated by {__author__}\n{__repo__}')
    parser.add_argument('-i', '--info', action='store_true', help="Show detailed info")
    args = parser.parse_args()

    # Handle merge operation if requested
    if args.merge:
        merge_php_files(
            merge_dir=args.merge_dir,
            output_dir=args.merge_output,
            name_prefix=args.merge_name,
            priority_file=args.priority_file
        )
        return

    # Continue with existing conversion logic if not merging
    if not args.sources:
        parser.print_help()
        return
        
    converter = CSS2PHPConverter(
        timeout=args.timeout,
        skip_errors=args.skip_errors,
        overwrite=not args.no_overwrite
    )

    print(f"CSS2PHP v{__version__} by {__author__}")
    print(f"Processing {len(args.sources)} source(s)...")

    results = []
    total_start_time = time.time()

    for source in args.sources:
        print(f"\nProcessing: {source}")
        try:
            stats = converter.convert(source, args.output, args.name_prefix)
            if stats:
                results.append(stats)
                if stats.syntax_valid:
                    print("✅ Success! (PHP Syntax Valid)")
                else:
                    print("⚠️ Success with Fixes! (PHP Syntax Fixed)") # Indicate if fixes were applied

                if args.info:
                    # Sort properties alphabetically for consistent output
                    print(f"📊 Statistics (A-Z):")
                    info = {
                        'Classes found': stats.class_count,
                        'Compression ratio': f"{(stats.output_size/stats.input_size*100):.1f}%",
                        'Input size': f"{stats.input_size:,} bytes",
                        'Media queries': stats.media_queries,
                        'Output file': stats.file_path,
                        'Output size': f"{stats.output_size:,} bytes",
                        'Processing time': f"{stats.parsing_time:.3f}s",
                        'Pseudo-classes': stats.pseudo_classes,
                        'Syntax validation': '✅ Valid' if stats.syntax_valid else '❌ Invalid',
                        'Syntax error details': stats.syntax_error_message # Show error details in info
                    }
                    for key, value in sorted(info.items()):
                        print(f"   • {key}: {value}")
                else:
                    print(f"📁 Output: {stats.file_path}")
            else:
                print("❌ Failed to process source.") # Indicate source processing failure
        except Exception as e:
            print(f"❌ Error: {e}")
            if not args.skip_errors:
                sys.exit(1)

    total_time = time.time() - total_start_time
    print(f"\n⚡ Complete! Processed {len(results)}/{len(args.sources)} files in {total_time:.2f}s")

    # Print summary
    if results:
        total_input = sum(s.input_size for s in results)
        total_output = sum(s.output_size for s in results)
        print("\n📊 Overall Statistics (A-Z):")

        # Fix: Remove extra colon in format specifier
        summary = {
            'Average compression': f"{(total_output/total_input*100):.1f}%",  # Fixed format specifier
            'Total classes': sum(s.class_count for s in results),
            'Total input size': f"{total_input:,} bytes",
            'Total media queries': sum(s.media_queries for s in results),
            'Total output size': f"{total_output:,} bytes",
            'Total processing time': f"{total_time:.2f}s",
            'Total syntax errors': sum(1 for s in results if not s.syntax_valid) # Count total syntax errors
        }

        for key, value in sorted(summary.items()):
            print(f"   • {key}: {value}")
        if summary['Total syntax errors'] > 0:
            print(f"\n⚠️ {summary['Total syntax errors']} files had PHP syntax issues, some may have been auto-fixed.")


if __name__ == '__main__':
    main()
