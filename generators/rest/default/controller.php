<?php
/**
 * This is the template for generating a CRUD controller class file.
 */

use yii\db\ActiveRecordInterface;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator dee\gii\generators\rest\Generator */

$controllerClass = StringHelper::basename($generator->getControllerClass());
$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $searchModelAlias = $searchModelClass . 'Search';
}

/* @var $class ActiveRecordInterface */
$class = $generator->modelClass;
$pks = $class::primaryKey();
$urlParams = $generator->generateUrlParams();
$actionParams = $generator->generateActionParams();
$actionParamComments = $generator->generateActionParamComments();
$searchConditions = $generator->generateInlineSearchConditions();

$deleteFunc = 'delete';
$refModel = new ReflectionClass($generator->modelClass);
if($refModel->hasMethod('softDelete')){
    $deleteFunc = 'softDelete';
}

list($swaggerClass, $properties1, $properties2, $required, 
    $paramType, $tags, $apiEndpoint, $indexParams, $security, $sortAttrs) = $generator->generateDefinition();

echo "<?php\n";
?>

namespace <?= StringHelper::dirname(ltrim($generator->getControllerClass(), '\\')) ?>;

use Yii;
use <?= ltrim($generator->modelClass, '\\') ?>;
<?php if (!empty($generator->searchModelClass)): ?>
use <?= ltrim($generator->searchModelClass, '\\') . (isset($searchModelAlias) ? " as $searchModelAlias" : "") ?>;
<?php else: ?>
use yii\data\ActiveDataProvider;
<?php endif; ?>
use <?= ltrim($generator->baseControllerClass, '\\') ?>;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 *
<?php if($generator->generateSwagger):?>
 * @SWG\Definition(
 *   definition="<?= $swaggerClass ?>",
<?php if(count($required)): ?>
 *   required={<?= implode(', ', $required)?>},
<?php endif; ?>
<?php foreach ($properties1 as $property): ?>
 *   @SWG\Property(property="<?= $property['name']?>", type="<?= $property['type']?>"),
<?php endforeach;?>
 * ),
 *
 * @SWG\Definition(
 *   definition="<?= $swaggerClass ?>Request",
<?php if(count($required)): ?>
 *   required={<?= implode(', ', $required)?>},
<?php endif; ?>
<?php foreach ($properties2 as $property): ?>
 *   @SWG\Property(property="<?= $property['name']?>", type="<?= $property['type']?>"),
<?php endforeach;?>
 * )
 *
<?php endif; ?>
 * <?= $controllerClass ?> implements the CRUD actions for <?= $modelClass ?> model.
 */
class <?= $controllerClass ?> extends <?= StringHelper::basename($generator->baseControllerClass) . "\n" ?>
{

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return[
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'update' => ['POST', 'PUT'],
            'delete' => ['POST', 'DELETE'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function accessControls()
    {
        return [];
    }
    
    /**
<?php if($generator->generateSwagger):?>
     * @SWG\Get(path="<?= $apiEndpoint ?>",
     *     tags={<?= $tags ?>},
     *     summary="Retrieves the collection of <?= $modelClass ?> resources.",
     *     @SWG\Parameter(in="query", name="expand",type="string"),
     *     @SWG\Parameter(in="query", name="q",type="string"),
     *     @SWG\Parameter(in="query", name="page",type="integer"),
     *     @SWG\Parameter(in="query", name="sort",type="string"),
<?php foreach ($indexParams as $property):?>
<?php if($property['isArray']): ?>
     *     @SWG\Parameter(in="query", name="<?= $property['name'] ?>[]",
     *         type="array",
     *         collectionFormat="multi",
     *         @SWG\Items(type="<?= $property['type'] ?>"),
     *     ),
<?php else: ?>
     *     @SWG\Parameter(in="query", name="<?= $property['name'] ?>",type="<?= $property['type'] ?>"),
<?php endif;?>
<?php endforeach; ?>
     *     @SWG\Response(
     *         response = 200,
     *         description = "<?= $modelClass ?> collection response",
     *         @SWG\Schema(type="object",
     *              @SWG\Property(property="items", type="array", @SWG\Items(ref = "#/definitions/<?= $swaggerClass ?>")),
     *              @SWG\Property(property="_meta", type="object"),
     *              @SWG\Property(property="_links", type="object"),
     *         ),
     *     ),
<?php if($security):?>
     *     security={<?= $security ?>},
<?php endif;?>
     * )
     *
<?php endif; ?>
     * List of <?= $modelClass ?> 
     */
    public function actionIndex()
    {
<?php if (!empty($generator->searchModelClass)): ?>
        $searchModel = new <?= isset($searchModelAlias) ? $searchModelAlias : $searchModelClass ?>();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
<?php else: ?>
        $query = <?= $modelClass ?>::find();
<?php if($generator->inlineSearch): ?>
        $request = Yii::$app->getRequest();
        <?= implode("\n        ", $searchConditions) ?>
<?php endif; ?>
        $sortAttrs = [
            <?= implode(",\n            ", $sortAttrs)?>    
        ];
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'attributes' => $sortAttrs,
            ]
        ]);
<?php endif; ?>
        return $dataProvider;
    }

    /**
<?php if($generator->generateSwagger):?>
     * @SWG\Get(path="<?= $apiEndpoint ?>/{id}",
     *     tags={<?= $tags ?>},
     *     summary="Retrieves the <?= $modelClass ?> resource.",
     *     @SWG\Parameter(in="path", name="id",type="<?= $paramType ?>"),
     *     @SWG\Parameter(in="query", name="expand",type="string"),
     *     @SWG\Response(
     *         response = 200,
     *         description = "<?= $modelClass ?> response",
     *         @SWG\Schema(ref = "#/definitions/<?= $swaggerClass ?>")
     *     ),
<?php if($security):?>
     *     security={<?= $security ?>},
<?php endif;?>
     * )
     *
<?php endif; ?>
     * View <?= $modelClass ?> 
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $model;
    }

    /**
<?php if($generator->generateSwagger):?>
     * @SWG\Post(path="<?= $apiEndpoint ?>",
     *     tags={<?= $tags ?>},
     *     summary="Create new <?= $modelClass ?> .",
     *     consumes={"application/json"},
     *     @SWG\Parameter(in="body", name="body",@SWG\Schema(ref = "#/definitions/<?= $swaggerClass ?>Request")),
     *     @SWG\Response(
     *         response = 200,
     *         description = "User model response",
     *         @SWG\Schema(ref = "#/definitions/<?= $swaggerClass ?>")
     *     ),
<?php if($security):?>
     *     security={<?= $security ?>},
<?php endif;?>
     * ),
     *
<?php endif; ?>
     * Creates a new <?= $modelClass ?> model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new <?= $modelClass ?>();
        $model->load(Yii::$app->request->post(), '');
        if ($model->save()) {
            Yii::$app->getResponse()->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

    /**
<?php if($generator->generateSwagger):?>
     * @SWG\Put(path="<?= $apiEndpoint ?>/{id}",
     *     tags={<?= $tags ?>},
     *     summary="Update an existing <?= $modelClass ?> resource.",
     *     @SWG\Parameter(in="path", name="id",type="<?= $paramType ?>"),
     *     @SWG\Parameter(in="body", name="body",@SWG\Schema(ref = "#/definitions/<?= $swaggerClass ?>Request")),
     *     @SWG\Response(
     *         response = 200,
     *         description = "<?= $modelClass ?> response",
     *         @SWG\Schema(ref = "#/definitions/<?= $swaggerClass ?>")
     *     ),
<?php if($security):?>
     *     security={<?= $security ?>},
<?php endif;?>
     * ),
     *
<?php endif; ?>
     * Updates an existing <?= $modelClass ?> model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->load(Yii::$app->request->post(), '');
        if ($model->save()) {
            Yii::$app->getResponse()->setStatusCode(200);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

    /**
<?php if($generator->generateSwagger):?>
     * @SWG\Delete(path="<?= $apiEndpoint ?>/{id}",
     *     tags={<?= $tags ?>},
     *     summary="Delete an existing <?= $modelClass ?> resource.",
     *     @SWG\Parameter(in="path", name="id",type="<?= $paramType ?>"),
     *     @SWG\Response(
     *         response = 204,
     *         description = "<?= $modelClass ?> response"
     *     ),
<?php if($security):?>
     *     security={<?= $security ?>},
<?php endif;?>
     * ),
     *
<?php endif; ?>
     * Deletes an existing <?= $modelClass ?> model.
     * If deletion is successful, return true.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model-><?= $deleteFunc?>() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
        return true;
    }

    /**
     * Finds the <?= $modelClass ?> model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return <?=                   $modelClass ?> the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
<?php if(count($pks) === 1): ?>
        if (($model = <?= $modelClass ?>::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
<?php else: ?>
        $values = explode(',', $id);
        $keys = <?= $modelClass ?>::primaryKey();
        if (($model = <?= $modelClass ?>::findOne(array_combine($keys, $values))) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
<?php endif; ?>
    }
}
