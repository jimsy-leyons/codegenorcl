<?php
// Function to convert column type to TypeScript data type
function convertColumnType($type)
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
            return 'number'; // Angular/TypeScript number type

        case 'varchar2':
        case 'char':
        case 'nvarchar2':
        case 'nchar':
        case 'clob':
            return 'string'; // Angular/TypeScript string type

        case 'date':
        case 'timestamp':
        case 'timestamp with time zone':
        case 'timestamp with local time zone':
            return 'Date'; // Angular/TypeScript Date type

        case 'raw':
        case 'long raw':
            return 'ArrayBuffer'; // Angular/TypeScript for binary data

        case 'blob':
            return 'Blob'; // Angular/TypeScript for binary large objects

        case 'boolean':
            return 'boolean'; // For Oracle boolean if applicable

        default:
            return 'any'; // Default case for unknown types
    }
}


// Function to generate Angular model code
function generateAngularModelClass($table, $columns)
{
    $properties = array();
    foreach ($columns as $row) {
        $columnName = $row['COLUMN_NAME'];
        $columnType = $row['DATA_TYPE'];
        $properties[] = array($columnName, $columnType);
    }

    $className = ucfirst($table);

    $code = "import { Filter } from './common/filter.model';\n\nexport class $className {\n";

    foreach ($properties as $property) {
        list($columnName, $columnType) = $property;
        $dataType = convertColumnType($columnType);
        $code .= "  {$columnName}?: $dataType;\n";
    }

    $code .= "}\n\n";

    $code .= "export class {$className}Filter extends Filter {\n";

    foreach ($properties as $property) {
        list($columnName, $columnType) = $property;
        $dataType = convertColumnType($columnType);
        $code .= "  {$columnName}?: $dataType;\n";
    }

    $code .= "}\n";

    $path = "build/angular/models";
    $filename = $table . ".model.ts";
    writeFile($path, $filename, $code);
}

// Function to generate Angular interface code
function generateAngularInterfaceClass($table, $columns)
{
    $properties = array();
    foreach ($columns as $row) {
        $columnName = $row['COLUMN_NAME'];
        $columnType = $row['DATA_TYPE'];
        $properties[] = array($columnName, $columnType);
    }

    $className = ucfirst($table);

    $code = "import { GENERAL_STATUS } from '../enums';\n\nexport interface I$className {\n";

    foreach ($properties as $property) {
        list($columnName, $columnType) = $property;
        $dataType = convertColumnType($columnType);
        $code .= "  {$columnName}?: $dataType;\n";
    }

    $code .= "}\n";


    $path = "build/angular/interfaces";
    $filename = $table . ".interface.ts";
    writeFile($path, $filename, $code);
}

// Function to generate Angular model code
function generateAngularServiceClass($table, $columns)
{
    $serviceName = ucfirst($table) . "Service";
    $className = ucfirst($table);
    $filterClassName = ucfirst($table) . "Filter";
    $baseUrl = "API_CONSTANTS.SECURE_BASE_URL + \"/$table\"";

    $serviceCode = <<<EOD
import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { isEmpty, jsonToQueryString } from "@mis/utils";
import { API_CONSTANTS } from "@api/configs";
import { $className, $filterClassName } from "../models/$table.model";

@Injectable({
  providedIn: "root",
})
export class $serviceName {
  baseUrl = $baseUrl;

  constructor(private http: HttpClient) { }

  getAll(filterConditions: $filterClassName = {}) {
    let urlQueryParams = "";
    if (!isEmpty(filterConditions)) {
      urlQueryParams = jsonToQueryString(filterConditions);
    }
    return this.http.get(this.baseUrl + urlQueryParams);
  }

  getDetails(id: number) {
    return this.http.get(this.baseUrl + "/" + id);
  }

  create(createData: $className) {
    return this.http.post(this.baseUrl, createData);
  }

  update(id: number, updateData: $className) {
    return this.http.put(this.baseUrl + "/" + id, updateData);
  }

  softDelete(id: number) {
    return this.http.put(this.baseUrl + "/" + id, { astatus: 1 });
  }

  delete(id: number) {
    return this.http.delete(this.baseUrl + "/" + id);
  }
}

EOD;
    $path = "build/angular/services";
    $filename = $table . ".service.ts";
    writeFile($path, $filename, $serviceCode);
}



function generateAngularCode($table, $columns)
{
    echo "<li>Angular code generation started. ->";
    generateAngularModelClass($table, $columns);
    generateAngularInterfaceClass($table, $columns);
    generateAngularServiceClass($table, $columns);
    echo "Finished angular code genration.</li>";
}
