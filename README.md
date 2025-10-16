# INI encode/decode

[![QA](https://github.com/Sweetchuck/ini-serializer/actions/workflows/qa.yml/badge.svg?branch=2.x)](https://github.com/Sweetchuck/ini-serializer/actions/workflows/qa.yml)
[![codecov](https://codecov.io/gh/Sweetchuck/ini-serializer/branch/master/graph/badge.svg)](https://codecov.io/gh/Sweetchuck/ini-serializer)

A flexible PHP library for parsing and generating INI files with flexible type handling,
customizable formatting options, and full control over serialization behavior.


## Features

- **Intelligent Type Conversion**: Automatically converts values to proper PHP types
  (integers, floats, booleans, null)
- **Customizable Type Aliases**: Define your own values for `null`, `true`, and `false`
- **Comment Support**: Handles both `;` and `#` style comments (configurable)
- **Grouped Sections**: Full support for INI sections `[group_name]`
- **Flexible Formatting**: Control quote usage and spacing around equal signs
- **Bidirectional**: Parse INI strings to PHP arrays and emit PHP arrays back to INI format
- **UTF-8 Ready**: Built-in mbstring support for proper Unicode handling


## Why Use This Over PHP's Native `parse_ini_file()`?


### 1. Predictable Type Conversion
   - This library explicitly converts typed values (bool, null, int, float)
   - You can configure which string values represent `true`, `false`, and `null`

### 2. Complete Bidirectional Support
   - Native `parse_ini_file()` only reads INI files
   - This library can generate INI files from PHP arrays, maintaining structure

### 3. Formatting Control
   - Configure spacing around equal signs
   - Control whether strings are quoted
   - Generate consistently formatted INI files

### 4. Customizable Comment Characters
   - Change which characters are treated as comments
   - Useful for different INI dialects

### 5. Better Boolean Handling
   - Recognizes multiple boolean representations:
     - True: `true`, `on`, `yes`
     - False: `false`, `off`, `no`
   - All case-insensitive

### 6. Proper Null Handling
   - Distinguishes between empty strings and null values
   - Recognizes `null` and `nil` as null values

### 7. Group Name Escaping
   - Properly handles special characters in section names `[group_name]`


## Limitations

⚠️ **Important**: This library does **not** resolve PHP constants or environment variables.

- PHP constants like `\PHP_VERSION` or `\E_ALL` are treated as regular strings
- Environment variables are not interpolated during parsing
- If you need constant/environment variable support, you should handle that
  separately before parsing or after parsing

Example:
```php
$ini = 'debug=DEBUG_MODE'; // DEBUG_MODE constant won't be resolved
$data = $serializer->parse($ini);
$data['debug']; // Returns string 'DEBUG_MODE', not the constant value

// If you need this, resolve it manually:
if (defined($data['debug'])) {
    $data['debug'] = constant($data['debug']);
}
```


## Usage


### Basic Parsing

```php
use Sweetchuck\IniSerializer\IniSerializer;

$ini = <<<INI
[database]
host=localhost
port=3306
debug=true
password=

[cache]
enabled=yes
ttl=3600
max_size=null
INI;

$serializer = new IniSerializer();
$data = $serializer->parse($ini);

// Result:
// [
//     'database' => [
//         'host' => 'localhost',
//         'port' => 3306,
//         'debug' => true,
//         'password' => null,
//     ],
//     'cache' => [
//         'enabled' => true,
//         'ttl' => 3600,
//         'max_size' => null,
//     ],
// ]
```

### Generating INI Files

```php
$data = [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'debug' => true,
        'timeout' => 30.5,
    ],
    'cache' => [
        'enabled' => false,
        'ttl' => 3600,
    ],
];

$serializer = new IniSerializer();
$iniString = $serializer->emit($data);

echo $iniString;
// Output:
// [database]
// host=localhost
// port=3306
// debug=true
// timeout=30.5
//
// [cache]
// enabled=false
// ttl=3600
//
```


### Formatting Options

```php
$serializer = new IniSerializer();

// Add spaces around equal signs
$serializer->setSpaceAroundEqualSign(true);

// Quote all string values
$serializer->setQuoteStrings(true);

$data = ['section' => ['key' => 'value']];
$ini = $serializer->emit($data);

// Output:
// [section]
// key = "value"
//
```


### Customizing Type Values

```php
$serializer = new IniSerializer();

// Customize what values represent booleans
$serializer->setValueBoolTrue(['yes', 'on', 'enabled', '1']);
$serializer->setValueBoolFalse(['no', 'off', 'disabled', '0']);

// Customize what values represent null
$serializer->setValueNull(['null', 'nil', 'none']);

// Or set all options at once
$serializer->setOptions([
    'valueBoolTrue' => ['yes', 'on', '1'],
    'valueBoolFalse' => ['no', 'off', '0'],
    'valueNull' => ['null', 'nil'],
    'commentChars' => [';', '#'],
    'quoteStrings' => false,
    'spaceAroundEqualSign' => false,
]);

$iniString = 'enabled=yes
is_prod=no
api_key=null';

$data = $serializer->parse($iniString);
// Result: ['enabled' => true, 'is_prod' => false, 'api_key' => null]
```


### Working with Existing Files

```php
$serializer = new IniSerializer();

// Read and parse
$iniContent = file_get_contents('config.ini');
$config = $serializer->parse($iniContent);

// Modify
$config['database']['host'] = 'newhost.com';

// Write back
$updatedIni = $serializer->emit($config);
file_put_contents('config.ini', $updatedIni);
```
