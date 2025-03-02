<?php

use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\gii\generators\inertia\Generator $generator */

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
$class = $generator->modelClass;
$pks = $class::primaryKey();
$urlParams = [];
foreach ($pks as $pk) {
    $urlParams[] = "$pk:row.$pk";
}
$urlParams = implode(', ', $urlParams);
?>
<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    data: Object,    
});
const columns = [
    {field:'no', title:'NO'},
<?php 
$count = 0;
foreach($gridColumns as $col):
$count++;
?>
    <?= ($count > 6 ? '// ':'') . $col?>,
<?php endforeach; ?>
    {field:'action', title:'Action'},
];

function deleteRow(row){
    confirm('Yakin akan menghapus data ini?').then(()=>{
        axios.post(toUrl.post('<?= $baseRoute ?>/delete', {<?= $urlParams ?>}));
    });
}
</script>
<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12">
                <p>
                    <Link :href="toUrl.home" class="text-decoration-none"><v-icon>mdi-home</v-icon></Link> /
                    <span >List <?= $modelName ?></span>
                </p>
            </v-col>
            <v-col cols="12">
                <v-card>
                    <v-toolbar density="default">
                        <v-btn density="compact" icon="mdi-reload" @click="router.reload()"></v-btn>
                        <v-toolbar-title><?= $modelName ?></v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <QuerySearchText reload style="min-width: 250px;" ></QuerySearchText>
                        </v-toolbar-items>
                        <v-btn density="compact" icon="mdi-plus" :to="toUrl('<?= $baseRoute ?>/create')"></v-btn>
                    </v-toolbar>
                    <v-divider/>
                    <GridView :data="data" :columns="columns" reload>
                        <template #d-no="row">{{ row._no }}</template>
                        <template #d-action="row">
                            <v-btn density="compact" size="small" icon="mdi-eye" :to="toUrl('<?= $baseRoute ?>/view', {<?= $urlParams ?>})"></v-btn>
                            <v-btn density="compact" size="small" icon="mdi-pencil" :to="toUrl('<?= $baseRoute ?>/update', {<?= $urlParams ?>})"></v-btn>
                            <v-btn density="compact" size="small" icon="mdi-delete" @click="deleteRow(row)"></v-btn>                            
                        </template>
                    </GridView>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>