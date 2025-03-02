<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\gii\generators\inertia\Generator $generator */

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
?>
<script setup>
const {toUrl} = window;

const props = defineProps({
    model: Object,    
});

const form = useForm({
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
                    <Link :href="toUrl.home" class="text-decoration-none"><v-icon>mdi-home</v-icon></Link> /
                    <Link :href="toUrl('<?= $baseRoute ?>')" >List <?= $modelName ?></Link> /
                    <span >View <?= $modelName ?></span>
                </p>           
            </v-col>
            <v-col cols="12">
                <form >
                    <v-card>
                        <v-toolbar density="default">
                            <v-btn density="compact" icon="mdi-arrow-left" @click="toUrl.back()"></v-btn>
                            <v-toolbar-title >View <?= $modelName ?></v-toolbar-title>
                        </v-toolbar> 
                        <v-card-text>
                            <v-row>
<?php foreach($inputs as $input):
    $input['field'] = false;
    $input['readonly'] = true;
    $input['variant'] = 'solo';
?>
                                <v-col class="py-1" xl="3" md="4" sm="6" cols="12">
                                    <?= Html::tag($input['type'] ? 'v-text-field': 'v-switch', '', $input) ?> 
                                </v-col>
<?php endforeach; ?>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </form>
            </v-col>
        </v-row>
    </v-container>
</template>