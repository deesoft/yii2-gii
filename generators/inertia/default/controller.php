<?php
/**
 * This is the template for generating a CRUD controller class file.
 */

use yii\db\ActiveRecordInterface;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\gii\generators\inertia\Generator $generator*/

$controllerClass = StringHelper::basename($generator->getControllerClass());
$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $searchModelAlias = $searchModelClass . 'Search';
}

/** @var ActiveRecordInterface $class*/
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

$viewPath = $generator->getViewPath();
echo "<?php\n";
?>

namespace <?= StringHelper::dirname(ltrim($generator->getControllerClass(), '\\')) ?>;

<?php
$uses = [
    'yii\web\Response',
    'Yii',
    'ext\inertia\Inertia',
    ltrim($generator->modelClass, '\\'),
    'yii\web\NotFoundHttpException',
    ltrim($generator->baseControllerClass, '\\'),
];
if(!empty($generator->searchModelClass)){
    $uses[] = ltrim($generator->searchModelClass, '\\') . (isset($searchModelAlias) ? " as $searchModelAlias" : "");
}else{
    $uses[] = 'yii\data\ActiveDataProvider';
}

asort($uses);
foreach ($uses as $use) {
    echo "use $use;\n";
}
?>

/**
 *
 * <?= $controllerClass ?> implements the CRUD actions for <?= $modelClass ?> model.
 */
class <?= $controllerClass ?> extends <?= StringHelper::basename($generator->baseControllerClass) . "\n" ?>
{
    /**
     * @inheritDoc
     */
    protected function verbs()
    {
        return [
            'delete' => ['POST'],
        ];
    }
    
    /**
     * List of <?= $modelClass ?> 
     * @return string|Response
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
        return Inertia::render('<?= $viewPath ?>/index', [
            'data' => $dataProvider
        ]);
    }

    /**
     * Displays a single <?= $modelClass ?> model.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(<?= $actionParams ?>)
    {
        return Inertia::render('<?= $viewPath ?>/view', [
            'model' => $this->findModel(<?= $actionParams ?>),
        ]);
    }

    /**
     * Creates a new <?= $modelClass ?> model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new <?= $modelClass ?>();

        if ($this->request->isPost) {
            if ($model->load($this->request->post(), '') && $model->save()) {
                return $this->redirect(['view', <?= $urlParams ?>]);
            }
        }

        return Inertia::render('<?= $viewPath ?>/create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing <?= $modelClass ?> model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate(<?= $actionParams ?>)
    {
        $model = $this->findModel(<?= $actionParams ?>);

        if ($this->request->isPost) {
            if ($model->load($this->request->post(), '') && $model->save()) {
                return $this->redirect(['view', <?= $urlParams ?>]);
            }
        }

        return Inertia::render('<?= $viewPath ?>/update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing <?= $modelClass ?> model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete(<?= $actionParams ?>)
    {
        $this->findModel(<?= $actionParams ?>)->delete();

        return $this->asJson(true);
    }

    /**
     * Finds the <?= $modelClass ?> model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return <?= $modelClass ?> the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(<?= $actionParams ?>)
    {
<?php
$condition = [];
foreach ($pks as $pk) {
    $condition[] = "'$pk' => \$$pk";
}
$condition = '[' . implode(', ', $condition) . ']';
?>
        if (($model = <?= $modelClass ?>::findOne(<?= $condition ?>)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(<?= $generator->generateString('The requested page does not exist.') ?>);
    }
}
