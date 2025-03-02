<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\gii\generators\inertia\Generator $generator */

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
?>
<script setup>

const props = defineProps({
    model: Object,
});
const form = useForm('create<?= $modelClass?>', {
<?php foreach($forms as $key => $value):?>
    <?= $key?>: <?= $value?>,
<?php endforeach; ?>
});
</script>
<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12">
                <p>
                    <Link :href="toUrl.base" class="text-decoration-none"><v-icon>mdi-home</v-icon></Link> /
                    <Link :href="toUrl('<?= $baseRoute ?>')" >List <?= $modelName ?></Link> /
                    <span >Create <?= $modelName ?></span>
                </p>
            </v-col>
            <v-col cols="12">
                <form @submit.prevent="form.post($page.url)">
                    <v-card>
                        <v-toolbar density="default">
                            <v-btn density="compact" icon="mdi-arrow-left" @click="toUrl.back()">
                            </v-btn>
                            <v-toolbar-title >Create <?= $modelName ?></v-toolbar-title>
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
                                <v-col class="py-1" xl="3" md="4" sm="6" cols="12">
                                    <?= $input ?> 
                                </v-col>
<?php endforeach; ?>
                            </v-row>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-toolbar density="compact">
                            <v-spacer></v-spacer>
                            <v-btn :loading="form.processing" variant="flat" color="primary" type="submit">Save</v-btn>
                        </v-toolbar>
                    </v-card>
                </form>
            </v-col>
        </v-row>
    </v-container>
</template>