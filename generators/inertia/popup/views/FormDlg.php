<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\gii\generators\inertia\Generator $generator */

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
$class = $generator->modelClass;
$pks = $class::primaryKey();
?>
<script setup>
import { router } from "@inertiajs/vue3";

const state = reactive({
    show: false,
<?php foreach($pks as $pk): ?>
    <?= $pk ?>: null,
<?php endforeach; ?>
});
const form = useForm('create<?= $modelClass?>', {
<?php foreach($forms as $key => $value):?>
    <?= $key?>: null,
<?php endforeach; ?>
});

function open(row){
    form.reset();
    form.clearErrors();
    if(row){
<?php foreach($pks as $pk): ?>
        state.<?= $pk ?>= row.<?= $pk?>;
<?php endforeach; ?>

<?php foreach($forms as $key => $value):?>
        form.<?= $key?>= row.<?= $key?>;
<?php endforeach; ?>
    }
    state.show = true;
}

const createUrl = toUrl.post('<?= $baseRoute ?>/create');
function save(){
<?php
    $ifs = $ps = [];
    foreach($pks as $pk){
        $ifs[] = "state.$pk";
        $ps[] = "$pk: state.$pk";
    }
    $if = implode(' && ', $ifs);
    if(count($ifs) > 1){
        $if = "($if)";
    }
    $paramUrl = implode(', ', $ps);
?>
    let url = <?= $if ?> ? toUrl.post('<?= $baseRoute ?>/update', {<?= $paramUrl?>}) : createUrl;
    axios.post(url, form.data()).then(r => {
        state.show = false;
        router.reload();
    }).catch(error => {
        form.setError(error.response.data);
    });
}
defineExpose({open});
</script>
<template>
    <v-dialog v-model="state.show" @keydown.esc="state.show = false" max-width="450">
        <v-card>
            <v-toolbar class="gradient-orange" density="compact" flat>
                <v-toolbar-title class="white--text">
                    {{ <?= $if ?> ? 'Edit' : 'New' }} <?= $modelName ?>
                </v-toolbar-title>
            </v-toolbar>
            <v-progress-linear indeterminate v-if="form.processing"></v-progress-linear>
            <v-divider/>
            <v-card-text>
                <v-row>
<?php foreach($inputs as $input):
    $field = \yii\helpers\ArrayHelper::remove($input, 'field');
    $tag = $input['type'] ? 'v-text-field': 'v-switch';
    if($field){
        $input = "<$tag " . Html::renderTagAttributes($input) . " @input=\"form.clearErrors('$field')\"" . "></$tag>";
    } else {
        $input = Html::tag($tag, '', $input);
    }
?>
                    <v-col class="py-1" cols="12">
                        <?= $input ?> 
                    </v-col>
<?php endforeach; ?>
                </v-row>
            </v-card-text>
            <v-card-actions class="pt-0">
                <v-spacer></v-spacer>
                <v-btn color="green" text @click.native="state.show = false">Close</v-btn>
                <v-btn dark color="error darken-1" text @click.native="save">Save</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>