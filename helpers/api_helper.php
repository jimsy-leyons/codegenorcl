<?php
require "library/filewrite.php";

function generateControllerClass($table, $columns)
{
    $controllerName = ucwords($table);
    $serviceName = ucwords($table) . "Service";
    $controllerContent = "<?php\n\n";
    $controllerContent .= "namespace App\Controllers\Api\\Secured;\n\n";
    $controllerContent .= "use App\Controllers\BaseController;\n";
    $controllerContent .= "use App\Libraries\Services\\$serviceName;\n";
    $controllerContent .= "use CodeIgniter\HTTP\ResponseInterface;\n";
    $controllerContent .= "use Exception;\n";
    $controllerContent .= "use App\Libraries\ApiError;\n\n";
    $controllerContent .= "class $controllerName extends BaseController\n";
    $controllerContent .= "{\n";
    $controllerContent .= "    private \$recordService;\n\n";
    $controllerContent .= "    public function __construct()\n";
    $controllerContent .= "    {\n";
    $controllerContent .= "        \$this->recordService = new $serviceName();\n";
    $controllerContent .= "    }\n\n";
    $controllerContent .= "    public function index()\n";
    $controllerContent .= "    {\n";
    $controllerContent .= "        try {\n";
    $controllerContent .= "            \$params = \$this->request->getGet();\n";
    $controllerContent .= "            \$result = \$this->recordService->findAll(\$params);\n";
    $controllerContent .= "            return \$this->sendResponse(\$result);\n";
    $controllerContent .= "        } catch (Exception \$exception) {\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_INTERNAL_SERVER_ERROR,\n";
    $controllerContent .= "                'Internal Server Error',\n";
    $controllerContent .= "                \$exception->getMessage()\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);\n";
    $controllerContent .= "        }\n";
    $controllerContent .= "    }\n\n";
    $controllerContent .= "    public function create()\n";
    $controllerContent .= "    {\n";
    $controllerContent .= "        try {\n";
    $controllerContent .= "            \$rules = [\n";
    $controllerContent .= "                'NAME' => 'required',\n";
    $controllerContent .= "            ];\n";
    $controllerContent .= "            \$input = \$this->getRequestInput(\$this->request);\n\n";
    $controllerContent .= "            if (!\$this->validateRequest(\$input, \$rules)) {\n";
    $controllerContent .= "                \$error = new ApiError(\n";
    $controllerContent .= "                    ApiError::CODE_BAD_REQUEST,\n";
    $controllerContent .= "                    'Validation Error',\n";
    $controllerContent .= "                    'Invalid input data.',\n";
    $controllerContent .= "                    \$this->validator->getErrors()\n";
    $controllerContent .= "                );\n";
    $controllerContent .= "                return \$this->sendResponse([], \$error, ResponseInterface::HTTP_BAD_REQUEST);\n";
    $controllerContent .= "            }\n\n";
    $controllerContent .= "            \$createdRecord = \$this->recordService->create(\$input);\n";
    $controllerContent .= "            if (\$createdRecord) {\n";
    $controllerContent .= "                return \$this->sendResponse(\$createdRecord);\n";
    $controllerContent .= "            }\n\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_BAD_REQUEST,\n";
    $controllerContent .= "                'Creation Error',\n";
    $controllerContent .= "                'Unable to create the record.',\n";
    $controllerContent .= "                \$this->validator->getErrors()\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_BAD_REQUEST);\n";
    $controllerContent .= "        } catch (Exception \$exception) {\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_INTERNAL_SERVER_ERROR,\n";
    $controllerContent .= "                'Internal Server Error',\n";
    $controllerContent .= "                \$exception->getMessage()\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);\n";
    $controllerContent .= "        }\n";
    $controllerContent .= "    }\n\n";
    $controllerContent .= "    public function show(\$key = null)\n";
    $controllerContent .= "    {\n";
    $controllerContent .= "        \$data = \$this->recordService->findBykey(\$key);\n";
    $controllerContent .= "        if (\$data) {\n";
    $controllerContent .= "            return \$this->sendResponse(\$data);\n";
    $controllerContent .= "        } else {\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_NOT_FOUND,\n";
    $controllerContent .= "                'Not Found',\n";
    $controllerContent .= "                'No record found.'\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_NOT_FOUND);\n";
    $controllerContent .= "        }\n";
    $controllerContent .= "    }\n\n";
    $controllerContent .= "    public function update(\$key = null)\n";
    $controllerContent .= "    {\n";
    $controllerContent .= "        try {\n";
    $controllerContent .= "            \$input = \$this->getRequestInput(\$this->request);\n";
    $controllerContent .= "            \$result = \$this->recordService->update(\$input, \$key);\n\n";
    $controllerContent .= "            if (\$result) {\n";
    $controllerContent .= "                return \$this->show(\$key);\n";
    $controllerContent .= "            }\n\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_INTERNAL_SERVER_ERROR,\n";
    $controllerContent .= "                'Update Error',\n";
    $controllerContent .= "                'Unable to update the record.'\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);\n";
    $controllerContent .= "        } catch (Exception \$exception) {\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_INTERNAL_SERVER_ERROR,\n";
    $controllerContent .= "                'Internal Server Error',\n";
    $controllerContent .= "                \$exception->getMessage()\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);\n";
    $controllerContent .= "        }\n";
    $controllerContent .= "    }\n\n";
    $controllerContent .= "    public function delete(\$id = null)\n";
    $controllerContent .= "    {\n";
    $controllerContent .= "        try {\n";
    $controllerContent .= "            \$result = \$this->recordService->delete(\$id);\n";
    $controllerContent .= "            if (\$result) {\n";
    $controllerContent .= "                return \$this->sendResponse(['Record deleted successfully']);\n";
    $controllerContent .= "            }\n\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_INTERNAL_SERVER_ERROR,\n";
    $controllerContent .= "                'Deletion Error',\n";
    $controllerContent .= "                'Unable to delete the record.'\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);\n";
    $controllerContent .= "        } catch (Exception \$exception) {\n";
    $controllerContent .= "            \$error = new ApiError(\n";
    $controllerContent .= "                ApiError::CODE_INTERNAL_SERVER_ERROR,\n";
    $controllerContent .= "                'Internal Server Error',\n";
    $controllerContent .= "                \$exception->getMessage()\n";
    $controllerContent .= "            );\n";
    $controllerContent .= "            return \$this->sendResponse([], \$error, ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);\n";
    $controllerContent .= "        }\n";
    $controllerContent .= "    }\n";
    $controllerContent .= "}\n";

    $path = "build/api/app/Controllers/Api/Secured";
    $filename = ucfirst($table) . ".php";
    writeFile($path, $filename, $controllerContent);
}

function generateServiceClass($table, $columns, $foreignKeys = [])
{
    $joins = [];
    if (count($foreignKeys) > 0) {
        $tableUpperCase = strtoupper($table);
        foreach ($foreignKeys as $fk) {
            $joins[] = "new OrclJoin('" . $fk['referenced_table'] . "', '$tableUpperCase." . $fk['column'] . " = " . $fk['referenced_table'] . "." . $fk['referenced_column'] . "', 'LEFT')";
        }
    }
    $serviceName = ucwords($table) . "Service";
    $modelName = ucwords($table) . "Model";
    $serviceContent = "<?php\n";
    $serviceContent .= "namespace App\\Libraries\\Services;\n\n";
    $serviceContent .= count($joins) > 0 ? "use App\\Entities\\OrclJoin;\n" : "";
    $serviceContent .= "use App\\Models\\$modelName;\n";
    $serviceContent .= "use CodeIgniter\\Database\\Exceptions\\DatabaseException;\n";
    $serviceContent .= "use Exception;\n\n";

    $serviceContent .= "class $serviceName\n";
    $serviceContent .= "{\n";
    $serviceContent .= "    private \$model;\n";
    $serviceContent .= "    private \$defaultColumns;\n";
    $serviceContent .= "    private \$joins;\n\n";

    $serviceContent .= "    public function __construct()\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        \$this->model = new $modelName();\n";
    $serviceContent .= "        \$this->_initJoinsAndColumns();\n";
    $serviceContent .= "    }\n\n";

    $serviceContent .= "    private function _initJoinsAndColumns()\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        // Define default columns and joins for queries\n";
    $serviceContent .= "        \$this->defaultColumns = []; // Customize default columns\n";
    $serviceContent .= "        \$this->joins = [" .
        (count($joins) > 0 ? implode(", \n", $joins) : "") .
        "];          // Customize joins if needed\n";
    $serviceContent .= "    }\n\n";

    $serviceContent .= "    /**\n";
    $serviceContent .= "     * Fetch all records based on filters/conditions.\n";
    $serviceContent .= "     */\n";
    $serviceContent .= "    public function findAll(\$where)\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        try {\n";
    $serviceContent .= "            // Fetch all data based on conditions and joins\n";
    $serviceContent .= "            \$result = OgetAllByJoin(\$this->model, \$where, \$this->defaultColumns, \$this->joins);\n";
    $serviceContent .= "            return \$result ?: []; // Return empty array if no result\n";
    $serviceContent .= "        } catch (DatabaseException \$exception) {\n";
    $serviceContent .= "            throw new Exception(\$exception->getMessage(), \$exception->getCode());\n";
    $serviceContent .= "        } catch (Exception \$exception) {\n";
    $serviceContent .= "            throw new Exception('Error fetching records: ' . \$exception->getMessage(), 0, \$exception);\n";
    $serviceContent .= "        }\n";
    $serviceContent .= "    }\n\n";

    $serviceContent .= "    /**\n";
    $serviceContent .= "     * Fetch a single record by ID.\n";
    $serviceContent .= "     */\n";
    $serviceContent .= "    public function findByKey(\$key)\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        try {\n";
    $serviceContent .= "            if (empty(\$key)) {\n";
    $serviceContent .= "                throw new Exception('Key cannot be empty');\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            // Define where clause using the primary key\n";
    $serviceContent .= "            \$where = [\$this->model->primaryKey => \$key];\n";
    $serviceContent .= "            \$data = OgetOneByJoin(\$this->model, \$where, \$this->defaultColumns, \$this->joins);\n\n";
    $serviceContent .= "            return \$data ?: null; // Return null if not found\n";
    $serviceContent .= "        } catch (DatabaseException \$exception) {\n";
    $serviceContent .= "            throw new Exception(\$exception->getMessage(), \$exception->getCode());\n";
    $serviceContent .= "        } catch (Exception \$exception) {\n";
    $serviceContent .= "            throw new Exception('Error fetching records: ' . \$exception->getMessage(), 0, \$exception);\n";
    $serviceContent .= "        }\n";
    $serviceContent .= "    }\n\n";

    $serviceContent .= "    /**\n";
    $serviceContent .= "     * Create a new record in the database.\n";
    $serviceContent .= "     */\n";
    $serviceContent .= "    public function create(\$data)\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        try {\n";
    $serviceContent .= "            if (empty(\$data)) {\n";
    $serviceContent .= "                throw new Exception('No data provided for insertion');\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            \$this->model->insert(\$data);\n";
    $serviceContent .= "            if (\$this->model->affectedRows() > 0) {\n";
    $serviceContent .= "                return \$this->findByKey(\$data[\$this->model->primaryKey]); // Return inserted record\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            return false; // Insert failed\n";
    $serviceContent .= "        } catch (DatabaseException \$exception) {\n";
    $serviceContent .= "            throw new Exception(\$exception->getMessage(), \$exception->getCode());\n";
    $serviceContent .= "        } catch (Exception \$exception) {\n";
    $serviceContent .= "            throw new Exception('Error creating record: ' . \$exception->getMessage(), 0, \$exception);\n";
    $serviceContent .= "        }\n";
    $serviceContent .= "    }\n\n";

    $serviceContent .= "    /**\n";
    $serviceContent .= "     * Update an existing record in the database.\n";
    $serviceContent .= "     */\n";
    $serviceContent .= "    public function update(\$data, \$key = null)\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        try {\n";
    $serviceContent .= "            if (empty(\$data)) {\n";
    $serviceContent .= "                throw new Exception('No data provided for update');\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            \$key = \$key ?? \$data[\$this->model->primaryKey] ?? null;\n";
    $serviceContent .= "            if (!\$key) {\n";
    $serviceContent .= "                throw new Exception('Invalid data: Missing key for update');\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            \$result = \$this->model->save(\$data);\n";
    $serviceContent .= "            if (\$result) {\n";
    $serviceContent .= "                return \$this->findByKey(\$key); // Return the updated record\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            return false; // Update failed\n";
    $serviceContent .= "        } catch (DatabaseException \$exception) {\n";
    $serviceContent .= "            throw new Exception(\$exception->getMessage(), \$exception->getCode());\n";
    $serviceContent .= "        } catch (Exception \$exception) {\n";
    $serviceContent .= "            throw new Exception('Error updating record: ' . \$exception->getMessage(), 0, \$exception);\n";
    $serviceContent .= "        }\n";
    $serviceContent .= "    }\n\n";

    $serviceContent .= "    /**\n";
    $serviceContent .= "     * Delete a record from the database.\n";
    $serviceContent .= "     */\n";
    $serviceContent .= "    public function delete(\$id)\n";
    $serviceContent .= "    {\n";
    $serviceContent .= "        try {\n";
    $serviceContent .= "            if (empty(\$id)) {\n";
    $serviceContent .= "                throw new Exception('ID is required for deletion');\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            \$result = \$this->model->delete(\$id);\n";
    $serviceContent .= "            if (\$result && \$this->model->affectedRows() > 0) {\n";
    $serviceContent .= "                return true; // Successful deletion\n";
    $serviceContent .= "            }\n\n";
    $serviceContent .= "            return false; // Deletion failed\n";
    $serviceContent .= "        } catch (DatabaseException \$exception) {\n";
    $serviceContent .= "            throw new Exception(\$exception->getMessage(), \$exception->getCode());\n";
    $serviceContent .= "        } catch (Exception \$exception) {\n";
    $serviceContent .= "            throw new Exception('Error deleting record: ' . \$exception->getMessage(), 0, \$exception);\n";
    $serviceContent .= "        }\n";
    $serviceContent .= "    }\n";

    $serviceContent .= "}\n";

    $path = "build/api/app/Libraries/Services";
    $filename = ucfirst($table) . "Service.php";
    writeFile($path, $filename, $serviceContent);
}

function generateModelClass($table, $columns)
{
    $allowedFields = array_map(function ($column) {
        return $column['COLUMN_NAME'];
    }, $columns);

    $modelClass = "<?php\n\n"
        . "namespace App\Models;\n\n"
        . "use CodeIgniter\Model;\n\n"
        . "class " . ucfirst($table) . "Model extends Model\n"
        . "{\n"
        . "    protected \$table = '" . strtoupper($table) . "';\n"
        . "    protected \$primaryKey = '" . $columns[0]['COLUMN_NAME'] . "';\n"
        . "    protected \$allowedFields = ['" . implode("','", $allowedFields) . "'];\n\n"
        . "    protected \$validationMessages = [];\n"
        . "    protected \$skipValidation = false;\n\n"
        . "    protected \$beforeInsert = [];\n"
        . "    protected \$afterInsert = [];\n"
        . "    protected \$beforeUpdate = [];\n"
        . "    protected \$afterUpdate = [];\n"
        . "    protected \$beforeFind = [];\n"
        . "    protected \$afterFind = [];\n"
        . "    protected \$beforeDelete = [];\n"
        . "    protected \$afterDelete = [];\n"
        . "}";

    $path = "build/api/app/Models";
    $filename = ucfirst($table) . "Model.php";
    writeFile($path, $filename, $modelClass);
}

function generateRouteClass($table, $columns)
{

    $routeCode = "<?php\n"
        . "namespace App\\Routes\\Secured;\n\n"
        . "class " . ucfirst($table) . "Routes\n"
        . "{\n"
        . "\tpublic function __construct(\$routes)\n"
        . "\t{\n"
        . "\t\treturn [\n"
        . "\t\t\t\$routes->resource('$table')\n"
        . "\t\t];\n"
        . "\t}\n"
        . "}\n";

    $path = "build/api/app/Routes/Secured";
    $filename = ucfirst($table) . "Routes.php";
    writeFile($path, $filename, $routeCode);
}

function generateApiCode($table, $columns, $foreignKeys = [])
{

    echo "<li>API code generation started. -> ";
    generateControllerClass($table, $columns);
    generateServiceClass($table, $columns, $foreignKeys);
    generateModelClass($table, $columns);
    generateRouteClass($table, $columns);
    echo "Finished API code genration.</li>";
}
