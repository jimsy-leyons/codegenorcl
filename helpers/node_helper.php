<?php
class Param
{
  public $name;
  public $fieldName;
  public $type;

  public function __construct($name, $dataType, $fieldName = false)
  {
    $this->name = $name;
    $this->fieldName = $fieldName ? $fieldName : $name;
    $this->type = $dataType;
  }
}

function camelize($input)
{
  return strtolower($input[0]) . substr(str_replace(' ', '', ucwords(preg_replace('/[\s_]+/', ' ', $input))), 1);
}

function  generateParams($fieldDatas)
{
  $params = [];
  foreach ($fieldDatas as $fieldData) {
    $name = $fieldData['COLUMN_NAME'];
    $type = typeDeterminer($fieldData['DATA_TYPE']);
    $propertyName = camelize($name);
    $newParam = new Param($propertyName, $type, $name);
    array_push($params, $newParam);
  }
  return $params;
}

function typeDeterminer($type)
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
      return 'number';

    case 'varchar2':
    case 'char':
    case 'nvarchar2':
    case 'nchar':
    case 'clob':
      return 'string';

    case 'date':
    case 'timestamp':
    case 'timestamp with time zone':
    case 'timestamp with local time zone':
      return 'Date';

    case 'raw':
    case 'long raw':
      return 'ArrayBuffer'; // Used for binary data

    case 'blob':
      return 'Blob'; // Used for binary large objects

    default:
      return 'any';
  }
}

function generateAliasCode($resourseName, $nameFor, $pathFor, $imports, $params)
{

  $aliasMaps = '';

  if (!empty($params)) {
    foreach ($params as $param) {

      $aliasMaps .= '
      ' . $param->name . ': \'' . $param->fieldName . '\',';
    }
  }

  $data = '
export const ' . $nameFor['mAlias'] . ' = { ' .
    $aliasMaps . '
}
';
  $path =  $pathFor['alias'] . '.ts';
  $fileSaved = saveFile($path, $data);
};

function generateModelCode($resourseName, $nameFor, $pathFor, $imports, $params)
{
  $cappedResourseName = ucfirst($resourseName);
  $tableName = $resourseName;
  if (!empty($options)) {
    if (!empty($options['tableName'])) {
      $tableName = $options['tableName'];
    }
  }
  $parameterDeclarations = '';
  $parameterInitializations = '';
  $whereConditions = '';

  if (!empty($params)) {
    foreach ($params as $param) {

      $parameterDeclarations .= ('  
private ' . ($param->fieldName ? $param->fieldName : $param->name) . ': ' . $param->type . ';');
      $parameterInitializations .= ('
' . '       ' . $resourseName . 'Data.' . $param->name . ' ? this.' . ($param->fieldName ? $param->fieldName : $param->name) . ' = ' . $resourseName . 'Data.' . $param->name . ' : "";');
      $whereConditions .= ('
        if (filterQuery.' . $param->name . ') {
            whereCondition.push(`' . $tableName . '.' . ($param->fieldName ? $param->fieldName : $param->name) . ' = ' . (($param->type == "number") ? '' : '\'') . '${filterQuery.' . $param->name . '}' . (($param->type == "number") ? '' : '\'') . '`);
        }
                ');
    }
  }

  $data = ''
    . 'import { MysqlResponse, MysqlService } from \'@mis/services/mysql.service\';
import { selectResult } from \'@/mis/helpers/select.helper\';
import { QueryParams } from \'@/mis/dtos/queryparams.dto\';    
import { ' . $nameFor['filterInterface'] . ' } from \'@' . $pathFor['interface'] . '\';
' . $imports['sql'] . '
' . $imports['alias'] . '
' . $imports['dto'] . '
const tableName = "' . $tableName . '";
const mysqlService = new MysqlService();
const ' . $nameFor['tSqls'] . ' = new ' . $nameFor['mSqls'] . '();

class ' . $nameFor['mModel'] . ' {
'
    .
    $parameterDeclarations . '

constructor(' . $resourseName . 'Data?: ' . $nameFor['mDto'] . ') {
    if (' . $resourseName . 'Data) {
' .
    $parameterInitializations
    . '    }
    }

public async create' . $cappedResourseName . '(' . $resourseName . 'Data: ' . $nameFor['mModel'] . '): Promise<MysqlResponse> {
    const create' . $cappedResourseName . 'Query = `INSERT INTO ${tableName} SET ?`;
    const ' . $resourseName . 'Inserted: MysqlResponse = await mysqlService.query(create' . $cappedResourseName . 'Query, ' . $resourseName . 'Data);
    return ' . $resourseName . 'Inserted;
}

public async get' . $cappedResourseName . 'ById(' . $resourseName . 'Id: number): Promise<MysqlResponse> {
    const select' . $cappedResourseName . 'Query = ' . $nameFor['tSqls'] . '.detailSelect;
    const modifiedSelect' . $cappedResourseName . 'Query = select' . $cappedResourseName . 'Query + `WHERE ' . $tableName . '.id = ${' . $resourseName . 'Id}`;
    const ' . $resourseName . 'Selected: MysqlResponse = await mysqlService.query(modifiedSelect' . $cappedResourseName . 'Query);
    return ' . $resourseName . 'Selected;
}

public async getAll' . $cappedResourseName . '(filterQuery: QueryParams): Promise<any> {
    filterQuery.table = tableName;
    filterQuery.columns = [\'*\'];
    filterQuery.columns = filterQuery.columns.length > 0 ? filterQuery.columns : [\'*\'];
    filterQuery.aliasMap = ' . $nameFor['mAlias'] . ';
    filterQuery.joins=[];
  
    let result = selectResult(filterQuery);
    return result;
}

public async update' . $cappedResourseName . 'ById(' . $resourseName . 'Id: number, ' . $resourseName . 'Data: ' . $nameFor['mModel'] . '): Promise<MysqlResponse> {
    const update' . $cappedResourseName . 'Query = `UPDATE ${tableName} SET ? WHERE ' . $tableName . '.id = ${' . $resourseName . 'Id}`;
    const ' . $resourseName . 'Updated: MysqlResponse = await mysqlService.query(update' . $cappedResourseName . 'Query, ' . $resourseName . 'Data);
    return ' . $resourseName . 'Updated;
}

public async update' . $cappedResourseName . 'Where(filterQuery: ' . $nameFor['filterInterface'] . ', ' . $resourseName . 'Data: ' . $nameFor['mModel'] . '): Promise<MysqlResponse> {
    let whereSqls = this.getWhereConditionsFor(filterQuery);
    whereSqls = ` WHERE ` + (whereSqls ? whereSqls : \'false\');

    const update' . $cappedResourseName . 'Query = `UPDATE ${tableName} SET ? ${whereSqls}`;
    const ' . $resourseName . 'Updated: MysqlResponse = await mysqlService.query(update' . $cappedResourseName . 'Query, ' . $resourseName . 'Data);
    return ' . $resourseName . 'Updated;
}

public async delete' . $cappedResourseName . '(' . $resourseName . 'Id: number): Promise<MysqlResponse> {
    const delete' . $cappedResourseName . 'Query = `DELETE FROM ${tableName} WHERE ' . $tableName . '.id = ${' . $resourseName . 'Id}`;
    const ' . $resourseName . 'Deleted: MysqlResponse = await mysqlService.query(delete' . $cappedResourseName . 'Query);
    return ' . $resourseName . 'Deleted;
}

public async deleteAll' . $cappedResourseName . '(): Promise<MysqlResponse> {
    const delete' . $cappedResourseName . 'sQuery = `DELETE * FROM ${tableName}`;
    const ' . $resourseName . 'sDeleted: MysqlResponse = await mysqlService.query(delete' . $cappedResourseName . 'sQuery);
    return ' . $resourseName . 'sDeleted;
}

}
export default ' . $nameFor['mModel'] . ';';
  $path =  $pathFor['model'] . '.ts';
  $fileSaved = saveFile($path, $data);
}

function generateDtoCode($resourseName, $nameFor, $pathFor, $imports, $params)
{
  $parameterDeclarations = '';

  if (!empty($params)) {
    foreach ($params as $param) {
      $parameterDeclarations .= ('
	public ' . $param->name . '?: ' . $param->type . ';');
    }
  }

  $data = ''
    . 'export class ' . $nameFor['mDto'] . ' {
' . $parameterDeclarations . '
}
';
  $path =  $pathFor['dto'] . '.ts';
  $fileSaved = saveFile($path, $data);
}

function generateInterfaceCode($resourseName, $nameFor, $pathFor, $imports, $params)
{
  $parameterDeclarations = '';
  if (!empty($params)) {
    foreach ($params as $param) {
      $parameterDeclarations .= ('	' . $param->name . '?: ' . $param->type . ';
');
    }
  }

  $data = ''
    . 'export interface ' . $nameFor['mInterface'] . ' {
' . $parameterDeclarations . '}
  ';

  $data .= '
  '
    . 'export interface ' . $nameFor['filterInterface'] . ' {
' . $parameterDeclarations . '	offset?: number;
	limit?: number;
}
';
  $path =  $pathFor['interface'] . '.ts';
  $fileSaved = saveFile($path, $data);
}


function generateSqlCode($resourseName, $nameFor, $pathFor, $imports, $options)
{
  $tableName = $resourseName;
  if (!empty($options)) {
    if (!empty($options['tableName'])) {
      $tableName = $options['tableName'];
    }
  }

  $selectDeclarations = '';

  if (!empty($params)) {
    foreach ($params as $param) {

      $selectDeclarations .= ('
                ' . $tableName . '.' . $param->fieldName . ' AS ' . $param->name . ',');
    }
  }

  $selectDeclarations = substr_replace($selectDeclarations, "", -1);

  $data = ''
    . 'export class ' . $nameFor['mSqls'] . ' {
    public countselect: string = `SELECT 
    count(' . $tableName . '.id) AS totalResults  
    FROM 
    ' . $tableName . ' `;
    public generalSelect: string = `SELECT ' . $selectDeclarations . ' 
    FROM 
    ' . $tableName . ' `;
    public detailSelect: string = `SELECT ' . $selectDeclarations . '
     FROM 
     ' . $tableName . ' `;
}
  ';
  $path =  $pathFor['sql'] . '.ts';
  $fileSaved = saveFile($path, $data);
}


function generateServiceCode($resourseName, $nameFor, $pathFor, $imports)
{
  $cappedResourseName = ucfirst($resourseName);

  $data = ''
    . 'import {
  resolveMultipleMysqlSelect,
  resolveMysqlCreate,
  resolveMysqlModifications,
  resolveSingleMysqlSelect,
} from \'@/mis/helpers/mysql.helper\';
import { FunctionResult } from \'@/mis/dtos/functionresult.dto\';
' . $imports['dto'] . '
import { ' . $nameFor['mInterface'] . ', ' . $nameFor['filterInterface'] . ' } from \'@' . $pathFor['interface'] . '\';
' . $imports['model'] . '

class ' . $nameFor['mService'] . ' {
private ' . $nameFor['tModel'] . ' = new ' . $nameFor['mModel'] . '();

public create' . $cappedResourseName . 'Service = async (' . $nameFor['tDto'] . ': ' . $nameFor['mDto'] . '): Promise<FunctionResult<any>> => {
try {
  let result = new FunctionResult();
  result.status = false;
  const ' . $resourseName . 'Data = new ' . $nameFor['mModel'] . '(' . $nameFor['tDto'] . ');
  const insert' . $cappedResourseName . 'Data = await this.' . $nameFor['tModel'] . '.create' . $cappedResourseName . '(' . $resourseName . 'Data);
  const insert' . $cappedResourseName . 'DataResolved = resolveMysqlCreate(insert' . $cappedResourseName . 'Data);
  if (insert' . $cappedResourseName . 'DataResolved?.status) {
    result.status = true;
    if (insert' . $cappedResourseName . 'DataResolved?.insertId) {
      return this.get' . $cappedResourseName . 'ByIdService(insert' . $cappedResourseName . 'DataResolved?.insertId);
    }
  }
  return result;
} catch (error) {
  throw error;
}
};

public get' . $cappedResourseName . 'Service = async (' . $resourseName . 'Filter: ' . $nameFor['filterInterface'] . '): Promise<FunctionResult<any>> => {
try {
  let result = new FunctionResult();
  result.status = false;
  const findAll' . $cappedResourseName . 'Data = await this.' . $nameFor['tModel'] . '.getAll' . $cappedResourseName . '(' . $resourseName . 'Filter);
  const findAll' . $cappedResourseName . 'DataResolved = resolveMultipleMysqlSelect(findAll' . $cappedResourseName . 'Data);
  if (findAll' . $cappedResourseName . 'DataResolved?.status) {
    result.status = true;
    result.data = findAll' . $cappedResourseName . 'DataResolved;
  }
  return result;
} catch (error) {
  throw error;
}
};

public get' . $cappedResourseName . 'ByIdService = async (' . $resourseName . 'Id: string | number): Promise<FunctionResult<any>> => {
try {
  let result = new FunctionResult();
  result.status = false;
  ' . $resourseName . 'Id = Number(' . $resourseName . 'Id);
  const findOne' . $cappedResourseName . 'Data = await this.' . $nameFor['tModel'] . '.get' . $cappedResourseName . 'ById(' . $resourseName . 'Id);
  const findOne' . $cappedResourseName . 'DataResolved = resolveSingleMysqlSelect(findOne' . $cappedResourseName . 'Data);
  if (findOne' . $cappedResourseName . 'DataResolved?.status) {
    result.status = true;
    result.data = findOne' . $cappedResourseName . 'DataResolved;
  }
  return result;
} catch (error) {
  throw error;
}
};

public update' . $cappedResourseName . 'Service = async (' . $resourseName . 'Id: string | number, ' . $nameFor['tDto'] . ': ' . $nameFor['mDto'] . '): Promise<FunctionResult<any>> => {
try {
  let result = new FunctionResult();
  result.status = false;
  ' . $resourseName . 'Id = Number(' . $resourseName . 'Id);
  const ' . $resourseName . 'Object = new ' . $nameFor['mModel'] . '(' . $nameFor['tDto'] . ');
  const update' . $cappedResourseName . 'Data = await this.' . $nameFor['tModel'] . '.update' . $cappedResourseName . 'ById(' . $resourseName . 'Id, ' . $resourseName . 'Object);
  const update' . $cappedResourseName . 'DataResolved = resolveMysqlModifications(update' . $cappedResourseName . 'Data);
  if (update' . $cappedResourseName . 'DataResolved?.status) {
    result.status = true;
    if (update' . $cappedResourseName . 'DataResolved?.result?.affectedRows > 0) {
      return this.get' . $cappedResourseName . 'ByIdService(' . $resourseName . 'Id);
    }
  }
  return result;
} catch (error) {
  throw error;
}
};

public update' . $cappedResourseName . 'WhereService = async (' . $resourseName . 'Filter: ' . $nameFor['filterInterface'] . ', ' . $nameFor['tDto'] . ': ' . $nameFor['mDto'] . '): Promise<FunctionResult<any>> => {
try {
  let result = new FunctionResult();
  result.status = false;
  const ' . $resourseName . 'Object = new ' . $nameFor['mModel'] . '(' . $nameFor['tDto'] . ');
  const update' . $cappedResourseName . 'Data = await this.' . $nameFor['tModel'] . '.update' . $cappedResourseName . 'Where(' . $resourseName . 'Filter, ' . $resourseName . 'Object);
  const update' . $cappedResourseName . 'DataResolved = resolveMysqlModifications(update' . $cappedResourseName . 'Data);
  if (update' . $cappedResourseName . 'DataResolved?.status) {
    result.status = true;
    result.data = update' . $cappedResourseName . 'DataResolved.result;
  }
  return result;
} catch (error) {
  throw error;
}
};

public delete' . $cappedResourseName . 'Service = async (' . $resourseName . 'Id: string | number): Promise<FunctionResult<any>> => {
try {
  let result = new FunctionResult();
  result.status = false;
  ' . $resourseName . 'Id = Number(' . $resourseName . 'Id);
  const delete' . $cappedResourseName . 'Data = await this.' . $nameFor['tModel'] . '.delete' . $cappedResourseName . '(' . $resourseName . 'Id);
  const delete' . $cappedResourseName . 'DataResolved = resolveMysqlModifications(delete' . $cappedResourseName . 'Data);
  if (delete' . $cappedResourseName . 'DataResolved?.status) {
    result.status = true;
    result.data = delete' . $cappedResourseName . 'DataResolved.result;
  }
  return result;
} catch (error) {
  throw error;
}
};
}
export default ' . $nameFor['mService'] . ';
';
  $path =  $pathFor['service'] . '.ts';
  $fileSaved = saveFile($path, $data);
}

function generateControllerCode($resourseName, $nameFor, $pathFor, $imports)
{
  $cappedResourseName = ucfirst($resourseName);

  $data = ''
    . 'import { NextFunction, Request, Response } from \'express\';
import { InternalServerError, ResultsNotFoundError } from \'@/mis/dtos/customerrors.dto\';
import { ERROR_MSGS_GENERAL } from \'@/mis/constants/errors.enum\';
import {
    mysqlManyToResultMany,
    mysqlOneToResultOne
  } from \'@/mis/helpers/mysql.helper\';
' . $imports['dto'] . '
import { ' . $nameFor['mInterface'] . ', ' . $nameFor['filterInterface'] . ' } from \'@' . $pathFor['interface'] . '\';
' . $imports['service'] . '

class ' . $nameFor['mController'] . ' {
private ' . $nameFor['tService'] . ' = new ' . $nameFor['mService'] . '();

public create' . $cappedResourseName . ' = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    let ' . $nameFor['tDto'] . ' = req.body as ' . $nameFor['mDto'] . ';
    const insert' . $cappedResourseName . 'Data = await this.' . $nameFor['tService'] . '.create' . $cappedResourseName . 'Service(' . $nameFor['tDto'] . ');
    if (insert' . $cappedResourseName . 'Data?.status) {
      next({ data: mysqlOneToResultOne(insert' . $cappedResourseName . 'Data.data) });
    } else {
      throw new InternalServerError({ message: ERROR_MSGS_GENERAL?.CREATION_FAIL });
    }
  } catch (error) {
   next({ error: error });
  }
};

public get' . $cappedResourseName . ' = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const ' . $resourseName . 'Filter: ' . $nameFor['filterInterface'] . ' = req.query as ' . $nameFor['filterInterface'] . ';
    const findAll' . $cappedResourseName . 'Data = await this.' . $nameFor['tService'] . '.get' . $cappedResourseName . 'Service(' . $resourseName . 'Filter);
    if (findAll' . $cappedResourseName . 'Data?.status) {
      next({ data: mysqlManyToResultMany(findAll' . $cappedResourseName . 'Data?.data) });
    } else {
      throw new ResultsNotFoundError({});
    }
  } catch (error) {
    next({ error: error });
  }
};

public get' . $cappedResourseName . 'ById = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const ' . $resourseName . 'Id = req.params.id;
    const findOne' . $cappedResourseName . 'Data = await this.' . $nameFor['tService'] . '.get' . $cappedResourseName . 'ByIdService(' . $resourseName . 'Id);
    if (findOne' . $cappedResourseName . 'Data?.status) {
      next({ data: mysqlOneToResultOne(findOne' . $cappedResourseName . 'Data?.data) });
    } else {
      throw new ResultsNotFoundError({});
    }
  } catch (error) {
   next({ error: error });
  }
};

public update' . $cappedResourseName . ' = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const ' . $resourseName . 'Id = req.params.id;
    const ' . $nameFor['tDto'] . ' = req.body as ' . $nameFor['mDto'] . ';
    const update' . $cappedResourseName . 'Data = await this.' . $nameFor['tService'] . '.update' . $cappedResourseName . 'Service(' . $resourseName . 'Id, ' . $nameFor['tDto'] . ');
    if (update' . $cappedResourseName . 'Data?.status) {
      next({ data: mysqlOneToResultOne(update' . $cappedResourseName . 'Data.data) });
    } else {
      throw new InternalServerError({ message: ERROR_MSGS_GENERAL?.UPDATION_FAIL });
    }
  } catch (error) {
    next({ error: error });
  }
};

public update' . $cappedResourseName . 'Where = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const ' . $nameFor['tDto'] . ' = req.body as ' . $nameFor['mDto'] . ';
    const ' . $resourseName . 'Filter: ' . $nameFor['filterInterface'] . ' = req.query as ' . $nameFor['filterInterface'] . ';
    const update' . $cappedResourseName . 'Data = await this.' . $nameFor['tService'] . '.update' . $cappedResourseName . 'WhereService(' . $resourseName . 'Filter, ' . $nameFor['tDto'] . ');
    if (update' . $cappedResourseName . 'Data?.status) {
      next({ data: update' . $cappedResourseName . 'Data.data });
    } else {
      throw new InternalServerError({ message: ERROR_MSGS_GENERAL?.UPDATION_FAIL });
    }
  } catch (error) {
    next({ error: error });
  }
};

public delete' . $cappedResourseName . ' = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const ' . $resourseName . 'Id = req.params.id;
    const delete' . $cappedResourseName . 'Data = await this.' . $nameFor['tService'] . '.delete' . $cappedResourseName . 'Service(' . $resourseName . 'Id);
    if (delete' . $cappedResourseName . 'Data?.status) {
      next({ data: delete' . $cappedResourseName . 'Data?.data });
    } else {
      throw new InternalServerError({ message: ERROR_MSGS_GENERAL?.DELETION_FAIL });
    }
  } catch (error) {
    next({ error: error });
  }
};
}

export default ' . $nameFor['mController'] . ';
';
  $path =  $pathFor['controller'] . '.ts';
  $fileSaved = saveFile($path, $data);
}

function generateRouteCode($resourseName, $nameFor, $pathFor, $imports,  $doAuthentication = true)
{
  $cappedName = ucfirst($resourseName);
  $authMiddleware = '';
  if ($doAuthentication) {
    $authMiddleware = 'import authMiddleware from \'@middlewares/auth.middleware\';';
  }

  $data = ''
    . 'import { Router } from \'express\';
import Routes from \'@mis/interfaces/routes.interface\';

' . $authMiddleware . '
import validationMiddleware from \'@middlewares/validation.middleware\';
' . $imports['controller'] . '
' . $imports['dto'] . '
  
class ' . $nameFor['mRoute'] . ' implements Routes {
public path = \'/' . $resourseName . '\';
public router = Router();
public ' . $nameFor['tController'] . ' = new ' . $nameFor['mController'] . '();
  
constructor(version="") {
  this.initializeRoutes(version);
}
  
private initializeRoutes(version) {
  this.router.get(`${version}${this.path}`,' . (($doAuthentication) ? ' authMiddleware,' : '') . ' this.' . $nameFor['tController'] . '.get' . $cappedName . ');
  this.router.get(`${version}${this.path}/:id(\\\d+)`,' . (($doAuthentication) ? ' authMiddleware,' : '') . ' this.' . $nameFor['tController'] . '.get' . $cappedName . 'ById);
  this.router.post(`${version}${this.path}`,' . (($doAuthentication) ? ' authMiddleware,' : '') . ' validationMiddleware(' . $nameFor['mDto'] . ', \'body\'), this.' . $nameFor['tController'] . '.create' . $cappedName . ');
  this.router.put(`${version}${this.path}/:id(\\\d+)`,' . (($doAuthentication) ? ' authMiddleware,' : '') . ' validationMiddleware(' . $nameFor['mDto'] . ', \'body\', true), this.' . $nameFor['tController'] . '.update' . $cappedName . ');
  this.router.put(`${version}${this.path}/where`,' . (($doAuthentication) ? ' authMiddleware,' : '') . ' validationMiddleware(' . $nameFor['mDto'] . ', \'body\', true), this.' . $nameFor['tController'] . '.update' . $cappedName . 'Where);
  this.router.delete(`${version}${this.path}/:id(\\\d+)`,' . (($doAuthentication) ? ' authMiddleware,' : '') . ' this.' . $nameFor['tController'] . '.delete' . $cappedName . ');
}
}
  
export default ' . $nameFor['mRoute'] . ';
';
  $path =  $pathFor['route'] . '.ts';
  $fileSaved = saveFile($path, $data);
}


function checkOrCreateNodeDirectory($path = false)
{
  $rootPath = 'build/node/';
  $buildPath = $rootPath .  (!empty($path) ? $path : "");
  if (!file_exists($buildPath)) {
    mkdir($buildPath, 0777, true);
  }
  return $buildPath;
}

function assurePaths($buildPath)
{
  checkOrCreateNodeDirectory($buildPath . "aliases");
  checkOrCreateNodeDirectory($buildPath . "routes");
  checkOrCreateNodeDirectory($buildPath . "controllers");
  checkOrCreateNodeDirectory($buildPath . "models");
  checkOrCreateNodeDirectory($buildPath . "dtos");
  checkOrCreateNodeDirectory($buildPath . "services");
  checkOrCreateNodeDirectory($buildPath . "sqls");
  checkOrCreateNodeDirectory($buildPath . "interfaces");
}

function saveFile($path, $data)
{
  $isWritten = file_put_contents('build/node/' . $path, $data);
  return $isWritten;
}

function generatePathsFor($entity)
{
  $result['alias'] = "aliases" . "/" . $entity . ".alias";
  $result['route'] = "routes" . "/" . $entity . ".route";
  $result['controller'] = "controllers" . "/" . $entity . ".controller";
  $result['model'] = "models" . "/" . $entity . ".model";
  $result['dto'] = "dtos" . "/" . $entity . ".dto";
  $result['interface'] = "interfaces" . "/" . $entity . ".interface";
  $result['service'] = "services" . "/" . $entity . ".service";
  $result['sql'] = "sqls" . "/" . $entity . ".sql";
  return $result;
}

function generateImportsFor($entity, $pathTo, $nameFor)
{
  $result['alias'] = 'import ' . $nameFor['mAlias'] . ' from \'@' . $pathTo['alias'] . '\';';
  $result['controller'] = 'import ' . $nameFor['mController'] . ' from \'@' . $pathTo['controller'] . '\';';
  $result['model'] = 'import ' . $nameFor['mModel'] . ' from \'@' . $pathTo['model'] . '\';';
  $result['dto'] = 'import { ' . $nameFor['mDto'] . ' } from \'@' . $pathTo['dto'] . '\';';
  $result['interface'] = 'import ' . $nameFor['mInterface'] . ' from \'@' . $pathTo['interface'] . '\';';
  $result['service'] = 'import ' . $nameFor['mService'] . ' from \'@' . $pathTo['service'] . '\';';
  $result['sql'] = 'import { ' . $nameFor['mSqls'] . ' } from \'@' . $pathTo['sql'] . '\';';
  return $result;
}

function generateNameFor($entity)
{
  $cappedName = ucfirst($entity);
  $result['mRoute'] = $cappedName . 'Route';
  $result['mController'] = $cappedName . 'Controller';
  $result['tController'] = $entity . 'Controller';
  $result['mService'] = $cappedName . 'Service';
  $result['tService'] = $entity . 'Service';
  $result['mModel'] =  $cappedName . 'Model';
  $result['mAlias'] =  $cappedName . 'Alias';
  $result['tModel'] =  $entity . 'Model';
  $result['mDto'] = $cappedName . 'Dto';
  $result['tDto'] = $entity . 'Dto';
  $result['mInterface'] = $cappedName;
  $result['filterInterface'] = $cappedName . 'Filter';
  $result['mSqls'] = $cappedName . 'Sqls';
  $result['tSqls'] = $entity . 'Sqls';
  return $result;
}

function generateNodeCode($table, $columns)
{
  echo "<li>Node code generation started. ->";
  $resourseName = $table;
  $pathFor = generatePathsFor($resourseName);
  $nameFor = generateNameFor($resourseName);
  $importFor = generateImportsFor($resourseName, $pathFor, $nameFor);
  assurePaths('');
  $options = array(
    'tableName' => $table
  );
  $params = generateParams($columns);
  generateRouteCode($resourseName, $nameFor, $pathFor, $importFor);
  generateControllerCode($resourseName, $nameFor, $pathFor, $importFor);
  generateServiceCode($resourseName, $nameFor, $pathFor, $importFor);
  generateModelCode($resourseName, $nameFor, $pathFor, $importFor, $params);
  generateAliasCode($resourseName, $nameFor, $pathFor, $importFor, $params);
  generateDtoCode($resourseName, $nameFor, $pathFor, $importFor, $params);
  generateInterfaceCode($resourseName, $nameFor, $pathFor, $importFor, $params);
  generateSqlCode($resourseName, $nameFor, $pathFor, $importFor, $options);
  echo "Finished node code genration.</li>";
}
