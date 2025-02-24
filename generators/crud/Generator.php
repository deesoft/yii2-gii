<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace dee\gii\generators\crud;

use Yii;
use yii\web\Controller;
use yii\gii\CodeFile;
use yii\db\BaseActiveRecord;

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
    public $modelNsSearch = [];
    private $_viewPath;
    private $_controllerClass;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Dee CRUD Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates a controller and views that implement CRUD (Create, Read, Update, Delete)
            operations for the specified data model.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(\yii\gii\Generator::rules(), [
            [['controllerID', 'modelClass', 'searchModelClass', 'baseControllerClass'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'controllerID', 'baseControllerClass', 'indexWidgetType'], 'required'],
            [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
            [['modelClass', 'baseControllerClass', 'searchModelClass'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['modelClass'], 'validateClass', 'params' => ['extends' => BaseActiveRecord::className()]],
            [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
            [['controllerID'], 'match', 'pattern' => '/^[a-z][a-z0-9\\-\\/]*$/', 'message' => 'Only a-z, 0-9, dashes (-) and slashes (/) are allowed.'],
            [['indexWidgetType'], 'in', 'range' => ['grid', 'list']],
            [['modelClass'], 'validateModelClass'],
            [['enableI18N'], 'boolean'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'controllerID' => 'Controller ID',
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

        $viewPath = $this->getViewPath();
        $templatePath = $this->getTemplatePath() . '/views';
        foreach (scandir($templatePath) as $file) {
            if (empty($this->searchModelClass) && $file === '_search.php') {
                continue;
            }
            if (is_file($templatePath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $files[] = new CodeFile("$viewPath/$file", $this->render("views/$file"));
            }
        }

        return $files;
    }

    /**
     * @return string the action view file path
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
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
            $this->_viewPath = $module->getViewPath() . '/' . $id;
        }
        return $this->_viewPath;
    }

    /**
     * @inheritdoc
     */
    public function autoCompleteData()
    {
        return [
            'modelClass' => function () {
                $result = [];
                foreach ($this->modelNsSearch as $ns) {
                    $ns = trim($ns, '\\');
                    $path = Yii::getAlias('@' . str_replace('\\', '/', $ns));
                    $n = strlen($path) + 1;
                    $files = FileHelper::findFiles($path, ['only' => ['*.php'], 'recursive' => true]);
                    foreach ($files as $file) {
                        $class = $ns . '\\' . str_replace('/', '\\', substr($file, $n, -4));
                        if (class_exists($class) && is_subclass_of($class, ActiveRecord::class)) {
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
            $this->_viewPath = $module->getViewPath() . '/' . $id;
    
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
}