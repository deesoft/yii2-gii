<?php
/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var dee\gii\generators\inertia\Generator $generator */

echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerID');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'inlineSearch')->checkbox();
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');

$js = <<<JS
    \$('#generator-searchmodelclass').change(function(){
        var showInline = \$(this).val().trim() == '';
        \$('.field-generator-inlinesearch').toggle(showInline);
    });
JS;

$this->registerJs($js);