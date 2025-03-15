# Test Usage Report
```bash
$ python css2php.py https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/base.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/base.min.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/components.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/components.min.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-dark.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-dark.min.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-experimental.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-experimental.min.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/utilities.css https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/utilities.min.css --split-selectors
CSS2PHP v1.0.1 by @sakibweb
Processing 12 source(s)...

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/base.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/base.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/base.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/base.min.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/components.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/components.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/components.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/components.min.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/tailwind.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/tailwind.min.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-dark.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/tailwind-dark.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-dark.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/tailwind-dark.min.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-experimental.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/tailwind-experimental.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind-experimental.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/tailwind-experimental.min.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/utilities.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/utilities.php

Processing: https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/utilities.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/utilities.min.php

⚡ Complete! Processed 12/12 files in 1416.71s

📊 Overall Statistics (A-Z):
   • Average compression: 160.4%
   • Split Selectors: Yes
   • Total classes: 405924
   • Total input size: 30,794,969 bytes
   • Total media queries: 338310
   • Total output size: 49,381,065 bytes
   • Total processing time: 1416.71s
   • Total syntax errors: 0
   
$ python css2php.py https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css --split-selectors
CSS2PHP v1.0.1 by @sakibweb
Processing 1 source(s)...

Processing: https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css
Debug _validate_php_syntax: Initial PHP validation - Is Valid: True
Debug _validate_php_syntax: Returning is_valid: True (No Fixes)
Debug _validate_php_syntax: Finally - Returning is_valid: True
✅ Success! (PHP Syntax Valid)
📁 Output: output/bootstrap.min.php

⚡ Complete! Processed 1/1 files in 6.04s

📊 Overall Statistics (A-Z):
   • Average compression: 160.4%
   • Split Selectors: Yes
   • Total classes: 3391
   • Total input size: 232,800 bytes
   • Total media queries: 1615
   • Total output size: 373,424 bytes
   • Total processing time: 6.04s
   • Total syntax errors: 0
```
