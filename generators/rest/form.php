<?php
/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var dee\gii\generators\rest\Generator $generator */

echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerID');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'inlineSearch')->checkbox();
echo $form->field($generator, 'generateSwagger')->checkbox();
echo $form->field($generator, 'swaggerDefinition');
echo $form->field($generator, 'apiEndpoint');
echo $form->field($generator, 'tags');
echo $form->field($generator, 'security');
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');

$js = <<<JS
    \$('#generator-searchmodelclass').change(function(){
        var showInline = \$(this).val().trim() == '';
        \$('.field-generator-inlinesearch').toggle(showInline);
    });
    \$('#generator-generateswagger').change(function(){
        var showDef = \$(this).is(':checked');
        \$('.field-generator-swaggerdefinition').toggle(showDef);
        \$('.field-generator-tags').toggle(showDef);
        \$('.field-generator-apiendpoint').toggle(showDef);
        \$('.field-generator-security').toggle(showDef);
    });
JS;

$this->registerJs($js);