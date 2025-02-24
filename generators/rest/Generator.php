<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace dee\gii\generators\rest;

use Yii;
use yii\db\ActiveRecord;
use yii\web\Controller;
use yii\gii\CodeFile;
use yii\db\BaseActiveRecord;
use yii\db\Schema;
use yii\helpers\FileHelper;

/**
 * Generates CRUD
 *
 * @property string $controllerClass The controller class to be generated. This property is
 * read-only.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 */
class Generator extends \yii\gii\generators\crud\Generator
{
    public $controllerID;
    public $inlineSearch;
    public $generateSwagger = true;
    public $swaggerDefinition;
    public $tags;
    public $apiEndpoint;
    public $security;
    public $excludeColumns = ['created_at', 'created_by', 'updated_at','updated_by', 'is_deleted'];

    public $modelNsSearch = [];
    private $_controllerClass;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Dee REST API Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates a rest controller for the specified data model.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(\yii\gii\Generator::rules(), [
            [['controllerID', 'modelClass', 'searchModelClass', 'baseControllerClass', 'security', 'tags'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'controllerID', 'baseControllerClass'], 'required'],
            [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
            [['modelClass', 'baseControllerClass', 'searchModelClass', 'swaggerDefinition'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['modelClass'], 'validateClass', 'params' => ['extends' => BaseActiveRecord::className()]],
            [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
            [['controllerID'], 'match', 'pattern' => '/^[a-z][a-z0-9\\-\\/]*$/', 'message' => 'Only a-z, 0-9, dashes (-) and slashes (/) are allowed.'],
            [['searchModelClass'], 'validateNewClass'],
            [['modelClass'], 'validateModelClass'],
            [['enableI18N', 'inlineSearch', 'generateSwagger'], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
            [['tags', 'apiEndpoint'], 'string'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'controllerID' => 'Controller ID',
            'generateSwagger' => 'Generate Swagger',
            'swaggerDefinition' => 'Swagger Definition',
            'tags' => 'Swagger Tags',
            'security' => 'Swagger Security Schema',
            'apiEndpoint' => 'Swagger REST Api Endpoint'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'controllerID' => 'Controller ID should be in lower case and may contain module ID(s) separated by slashes. For example:
                <ul>
                    <li><code>order</code> generates <code>OrderController.php</code></li>
                    <li><code>order-item</code> generates <code>OrderItemController.php</code></li>
                    <li><code>admin/user</code> generates <code>UserController.php</code> under <code>admin</code> directory.</li>
                </ul>',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function autoCompleteData()
    {
        return [
            'modelClass' => function () {
                $result = [];
                foreach($this->modelNsSearch as $ns){
                    $ns = trim($ns, '\\');
                    $path = Yii::getAlias('@'.str_replace('\\','/',$ns));
                    $n = strlen($path) + 1;
                    $files = FileHelper::findFiles($path,['only' => ['*.php'], 'recursive' => true]);
                    foreach($files as $file){
                        $class = $ns . '\\' . str_replace('/','\\',substr($file, $n, -4));
                        if(class_exists($class) && is_subclass_of($class, ActiveRecord::class)){
                            $result[] = $class;
                        }
                    }
                }
                return $result;
            },
        ];
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->getControllerClass(), '\\')) . '.php');

        $files = [
            new CodeFile($controllerFile, $this->render('controller.php')),
        ];

        if (!empty($this->searchModelClass)) {
            $searchModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->searchModelClass, '\\') . '.php'));
            $files[] = new CodeFile($searchModel, $this->render('search.php'));
        }

        return $files;
    }

    public function generateDefinition()
    {
        $class = $this->modelClass;
        /* @var $tableSchema \yii\db\TableSchema*/
        $tableSchema = $class::getTableSchema();
        $properties1 = $properties2 = $required = [];
        $swaggerClass = $this->swaggerDefinition ? : \yii\helpers\StringHelper::basename($this->modelClass);
        $endPoint = $this->apiEndpoint ? : '/' . trim($this->controllerID, '/');
        if($this->tags){
            $tags = preg_split('/\s*[,;]\s*/', $this->tags);
            $tags = implode(', ', array_map('json_encode', $tags));
        }else{
            $tags = json_encode(\yii\helpers\Inflector::underscore($swaggerClass));
        }
        $paramType = [];
        $indexParams = [];
        $sortAttrs = [];
        foreach ($tableSchema->columns as $column) {
            if(!$column->allowNull && !$column->autoIncrement && $column->defaultValue === null){
                $required[] = json_encode($column->name);
            }
            $type = null;
            $isArray = true;
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_TINYINT:
                    $type = 'integer';
                    break;
                case Schema::TYPE_BOOLEAN:
                    $type = 'boolean';
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $type = 'number';
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $type = 'string';
                    break;
                case Schema::TYPE_JSON:
                    $type = 'object';
                    break;
                case Schema::TYPE_CHAR:
                case Schema::TYPE_STRING:
                    if($column->name == 'number' || $column->name == 'status'){
                        $isArray = true;
                        $type = 'string';
                        break;
                    }
                default:
                    $type = $column->phpType;
                    $isArray = false;
                    break;
            }
            if($column->isPrimaryKey){
                $paramType[] = $type;
            }
            $properties1[] = ['name' => $column->name, 'type' => $type];
            if(!$column->autoIncrement && !in_array($column->name, $this->excludeColumns)){
                $properties2[] = ['name' => $column->name, 'type' => $type];
            }
            if($type != 'object' && !in_array($column->name, $this->excludeColumns)){
                $indexParams[] = ['name' => $column->name, 'type' => $type, 'isArray' => $isArray];
                $sortAttrs[] = "'{$column->name}'";
            }
        }
        $paramType = count($paramType) == 1 ? $paramType[0] : 'string';
        $security = false;
        if($this->security){
            $security = implode(', ', array_map(function($v){
                    return "{\"{$v}\": {}}";
            }, preg_split('/\s*[,;]\s*/', $this->security)));
        }
        return [$swaggerClass, $properties1, $properties2, $required, $paramType, $tags, $endPoint, $indexParams, $security, $sortAttrs];
    }

    /**
     * @inheritdoc
     */
    public function successMessage()
    {
        $route = '/' . $this->controllerID . '/index';
        $link = \yii\helpers\Html::a('try it now', [$route], ['target' => '_blank']);

        return "The controller has been generated successfully. You may $link.";
    }

    /**
     * @return string the controller class
     */
    public function getControllerClass()
    {
        if ($this->_controllerClass === null) {
            $module = Yii::$app;
            $id = $this->controllerID;
            while (($pos = strpos($id, '/')) !== false) {
                $mId = substr($id, 0, $pos);
                if (($m = $module->getModule($mId)) !== null) {
                    $module = $m;
                    $id = substr($id, $pos + 1);
                } else {
                    break;
                }
            }
    
            $pos = strrpos($id, '/');
            if ($pos === false) {
                $prefix = '';
                $className = $id;
            } else {
                $prefix = substr($id, 0, $pos + 1);
                $className = substr($id, $pos + 1);
            }

            $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
            $className = ltrim($module->controllerNamespace . '\\' . str_replace('/', '\\', $prefix) . $className, '\\');
            $this->_controllerClass = $className;
        }
        return $this->_controllerClass;
    }

    /**
     * An inline validator that checks if the attribute value refers to a valid namespaced class name.
     * The validator will check if the directory containing the new class file exist or not.
     * @param string $attribute the attribute being validated
     * @param array $params the validation options
     */
    public function validateNewClass($attribute, $params)
    {
        $class = ltrim($this->$attribute, '\\');
        if (($pos = strrpos($class, '\\')) === false) {
            $this->addError($attribute, "The class name must contain fully qualified namespace name.");
        } else {
            $ns = substr($class, 0, $pos);
            $path = Yii::getAlias('@' . str_replace('\\', '/', $ns), false);
            if ($path === false) {
                $this->addError($attribute, "The class namespace is invalid: $ns");
            } elseif (!is_dir($path) && !empty ($params['path_exists'])) {
                $this->addError($attribute, "Please make sure the directory containing this class exists: $path");
            }
        }
    }

    /**
     * Generates search conditions
     * @return array
     */
    public function generateInlineSearchConditions()
    {
        $columns = [];
        if (($table = $this->getTableSchema()) === false) {
            $class = $this->modelClass;
            /* @var $model \yii\db\BaseActiveRecord */
            $model = new $class();
            foreach ($model->attributes() as $attribute) {
                $columns[$attribute] = 'unknown';
            }
        } else {
            foreach ($table->columns as $column) {
                $columns[$column->name] = $column->type;
            }
        }

        $likeConditions = [];
        $hashConditions = [];
        $qConditions = [];
        foreach ($columns as $column => $type) {
            if(in_array($column, $this->excludeColumns)){
                continue;
            }
            switch ($type) {
                case Schema::TYPE_TINYINT:
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_BOOLEAN:
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $hashConditions[] = "'{$column}' => \$request->get('{$column}'),";
                    break;
                case Schema::TYPE_JSON:
                    break;
                case Schema::TYPE_STRING:                
                case Schema::TYPE_CHAR:
                    if($column == 'status' || $column == 'number'){
                        $hashConditions[] = "'{$column}' => \$request->get('{$column}'),";
                        break;
                    }
                default:
                    $likeKeyword = $this->getClassDbDriverName() === 'pgsql' ? 'ilike' : 'like';
                    $likeConditions[] = "->andFilterWhere(['{$likeKeyword}', '{$column}', \$request->get('{$column}')])";
                    $qConditions[] = "['{$likeKeyword}', '{$column}', \$q],";
                    break;
            }
        }

        $conditions = [];
        if (!empty($hashConditions)) {
            $conditions[] = "\$query->andFilterWhere([\n"
                . str_repeat(' ', 12) . implode("\n" . str_repeat(' ', 12), $hashConditions)
                . "\n" . str_repeat(' ', 8) . "]);\n";
        }
        if (!empty($likeConditions)) {
            $conditions[] = "\$query" . implode("\n" . str_repeat(' ', 12), $likeConditions) . ";\n";
        }

        if (!empty($qConditions)) {
            $qConditions = implode("\n" . str_repeat(' ', 16), $qConditions);
            $conditions[] = <<<TXT
if (\$q = \$request->get('q')) {
            \$query->andWhere([
                'OR',
                $qConditions
            ]);
        }\n\n
TXT;
        }
        return $conditions;
    }

    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(),[
            'inlineSearch', 'generateSwagger', 'security',
        ]);
    }
}