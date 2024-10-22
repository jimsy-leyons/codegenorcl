<?php

// Function to convert string to camel case
function convertToCamelCase($str)
{
    $str = str_replace('_', ' ', $str);
    $str = ucwords($str);
    $str = str_replace(' ', '', $str);
    $str = lcfirst($str);
    return $str;
}

// Function to get the Dart data type for a MySQL column type
function getColumnDartType($type)
{
    // Match the type and optional size
    preg_match('/([a-zA-Z]+)(?:\((\d+)\))?/', $type, $matches);

    // Normalize type to lowercase and get the size if it exists
    $type = strtolower($matches[1]);
    $size = isset($matches[2]) ? (int) $matches[2] : null;

    switch ($type) {
        case 'number':
        case 'integer':
        case 'float':
        case 'binary_float':
        case 'binary_double':
            return 'double'; // Dart double type

        case 'varchar2':
        case 'char':
        case 'nvarchar2':
        case 'nchar':
        case 'clob':
            return 'String'; // Dart String type

        case 'date':
        case 'timestamp':
        case 'timestamp with time zone':
        case 'timestamp with local time zone':
            return 'DateTime'; // Dart DateTime type

        case 'raw':
        case 'long raw':
            return 'Uint8List'; // Dart type for binary data

        case 'blob':
            return 'Uint8List'; // Dart type for binary large objects

        default:
            return 'dynamic'; // Fallback type for unsupported types
    }
}


// Function to get the parsing method for a JSON value based on the column type
function getParseJsonMethod($column, $field_name)
{
    $dart_type = getColumnDartType($column['DATA_TYPE']);
    switch ($dart_type) {
        case "int":
            return "json['$field_name'] as int";
        case "double":
            return "json['$field_name'] as double";
        case "bool":
            return "json['$field_name'] as bool";
        case "DateTime":
            return "DateTime.parse(json['$field_name'])";
        default:
            return "json['$field_name'] as String";
    }
}


// Function to generate Dart code for each table
function generatePropertyList($table, $columns)
{
    $dartCode = "final List<String> propertyList = [\n";
    foreach ($columns as $column) {
        $field_name = $column['COLUMN_NAME'];
        $fieldName = convertToCamelCase($field_name);
        $dartCode .= "  '$fieldName',\n";
    }
    $dartCode .= "];\n\n";
    $path = "build/flutter/properties";
    $filename = $table . ".dart";
    writeFile($path, $filename, $dartCode);
}


// Function to generate Dart code for each table
function generateDartModelCode($table, $columns)
{
    $dartCode = "final List<DisplayField> {$table}FieldMaps = [\n";
    foreach ($columns as $column) {
        $field_name = $column['COLUMN_NAME'];
        $fieldName = convertToCamelCase($field_name);
        $display_name = ucwords(str_replace("_", " ", $field_name));
        $dartCode .= "  DisplayField(name: '$fieldName', fieldName: '$field_name', displayName: '$display_name'),\n";
    }
    $dartCode .= "];\n\n";

    $dartCode .= "class $table {\n";
    foreach ($columns as $column) {
        $field_name = $column['COLUMN_NAME'];
        $fieldName = convertToCamelCase($field_name);
        $dart_type = getColumnDartType($column['DATA_TYPE']);
        $dartCode .= "  {$dart_type}? $fieldName;\n";
    }
    $dartCode .= "\n";

    $dartCode .= "  $table({\n";
    foreach ($columns as $column) {
        $field_name = $column['COLUMN_NAME'];
        $fieldName = convertToCamelCase($field_name);
        $dartCode .= "    this.$fieldName,\n";
    }
    $dartCode .= "  });\n\n";

    $dartCode .= "  factory $table.fromJson(Map<String, dynamic> json) {\n";
    $dartCode .= "    return $table(\n";
    foreach ($columns as $column) {
        $field_name = $column['COLUMN_NAME'];
        $fieldName = convertToCamelCase($field_name);
        $parse_json_method = getParseJsonMethod($column, $field_name);
        $dartCode .= "      $fieldName: {$parse_json_method},\n";
    }
    $dartCode .= "    );\n";
    $dartCode .= "  }\n\n";

    $dartCode .= "  Map<String, dynamic> toJson({bool isViewOnly = false}) {\n";
    $dartCode .= "    var jsonData = {\n";
    foreach ($columns as $column) {
        $field_name = $column['COLUMN_NAME'];
        $fieldName = convertToCamelCase($field_name);
        $dartCode .= "      '$field_name': $fieldName,\n";
    }
    $dartCode .= "    };\n";
    $dartCode .= "    return jsonData;\n";
    $dartCode .= "  }\n";

    $dartCode .= "}\n\n";

    $path = "build/flutter/models";
    $filename = $table . ".dart";
    writeFile($path, $filename, $dartCode);
}

function generateSqliteCode($table, $columns)
{
    $sqliteColumns = [];

    foreach ($columns as $column) {
        // Convert MySQL column types to SQLite column types if necessary
        // Add any additional conversions you may need
        $columnName = $column['COLUMN_NAME'];
        $columnType = $column['DATA_TYPE'];
        $sqliteColumnType = strtoupper($columnType);
        if (stripos($columnType, 'varchar') !== false) {
            $sqliteColumnType = 'TEXT';
        } elseif (stripos($columnType, 'decimal') !== false) {
            $sqliteColumnType = 'REAL';
        } elseif ((stripos($columnType, 'int(11)') !== false)) {
            $sqliteColumnType = 'INTEGER';
        } elseif ((stripos($columnType, 'timestamp') !== false)) {
            $sqliteColumnType = 'TEXT';
        } elseif ((stripos($columnType, 'tinyint(4)') !== false)) {
            $sqliteColumnType = 'INTEGER';
        }

        $sqliteColumns[] = "{$columnName} {$sqliteColumnType}";
    }

    $sqliteTableDefinition = implode(', ', $sqliteColumns);
    $sqliteTableStatement = "CREATE TABLE {$table} ({$sqliteTableDefinition});";
    $path = "build/flutter/sqlite";
    $filename = $table . ".dart";
    writeFile($path, $filename, $sqliteTableStatement);
}

function generateFlutterCode($table, $columns)
{
    echo "<li>Flutter code generation started. ->";
    generateSqliteCode($table, $columns);
    generateDartModelCode($table, $columns);
    generatePropertyList($table, $columns);
    echo "Finished flutter code genration.</li>";
}
