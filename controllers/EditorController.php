<?php

namespace api\controllers;

use Yii;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

use api\models\Goals;
use api\models\Tree;
use api\models\Docs;
use api\models\DocsFile;
use api\models\DocsType;
use api\models\TreeGoals;

class EditorController extends BaseController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }

    /**
     * Return the associative array of a tree
     */
    public function actionGettree()
    {
        $modelTree = new Tree();
        $tree = $modelTree->getTree();

        if ($tree) {
            return $this->sendSuccess($tree);
        } else {
            return $this->sendError();
        }
    }

    /**
     * Adding a Tree Node
     * @return array
     * @throws \Exception
     */
    public function actionAdditemtree()
    {
        $data = Yii::$app->request->post();

        $tree = new Tree();

        if ($tree->addItemTree($data)) {

            if ($tree->type === Tree::TYPE_DOCUMENT) {
                $document = new Docs();
                $document->addDocument($tree->id);
            }

            return $this->sendSuccess([
                'id' => $tree->id,
                'parent' => $tree->parent,
                'type' => Tree::setTypeTreeName($tree->type)
            ]);

        } else {
            return $this->sendError($tree->getErrors());
        }
    }

    /**
     * Editing a Tree Node
     * @return array
     */
    public function actionUpdateitemtree()
    {
        $data = Yii::$app->request->post();

        $tree = Tree::findOne(['id' => $data['id']]);

        if ($tree->updateItemTree($data)) {
            return $this->sendSuccess();
        } else {
            return $this->sendError($tree->getErrors());
        }
    }

    /**
     * Delete the node
     * @return array
     */
    public function actionRemovefolder()
    {
        $treeId = (int)Yii::$app->request->post('idFolder');

        if (Tree::removeFolder($treeId)) {
            return $this->sendSuccess();
        } else {
            return $this->sendError();
        }
    }

    /**
     * Returns a list of document types
     * @return array
     */
    public function actionGetdocumenttypes()
    {
        $types = DocsType::find()->orderBy(['name' => SORT_ASC])->asArray()->all();
        return $this->sendSuccess($types);
    }

    /**
     * Return document data
     * @return array
     */
    public function actionGetdocumentdata()
    {
        $treeId = (int)Yii::$app->request->post('tree_id');

        $docData = Docs::find()->where(['tree_id' => $treeId])->with('type', 'file')->asArray()->one();

        if ($docData) {
            $docData['parent'] = Tree::getParentTreeData($treeId);
            return $this->sendSuccess($docData);
        } else {
            return $this->sendError();
        }
    }

    /**
     * Updating Data in a Node Document
     */
    public function actionUpdatedocument()
    {
        $post = Yii::$app->request->post();

        $document = Docs::findOne(['id' => $post['id']]);

        $document->updateDocument($post);

        if ($document->save()) {
            return $this->sendSuccess();
        } else {
            return $this->sendError($document->getErrors());
        }
    }

    /**
     * Loading a file for a document
     */
    public function actionUploaddocumentfile()
    {
        $model = new DocsFile();

        if ($model->uploadFile()) {
            return $this->sendSuccess([
                'id' => $model->id,
                'nameFile' => $model->file,
                'nameFileOrigin' => $model->name
            ]);
        } else {
            return $this->sendError($this->getErrors());
        }
    }

    /**
     * Give the file a stream
     * @param $file
     * @return $this
     */
    public function actionFilecontentreturn($file)
    {
        $dataFile = null;
        if ($stream = fopen(Yii::getAlias('@webroot/uploads/docs/' . $file), 'r')) {
            return Yii::$app->response->sendStreamAsFile($stream, $file);
            fclose($stream);
        }
    }

    /**
     * Adding a category or goal
     * @return array
     */
    public function actionAdditemtreegoal()
    {
        $data = Yii::$app->request->post();

        $tree = new TreeGoals();

        if ($tree->addItemTree($data)) {

            if ($tree->type === TreeGoals::TYPE_GOAL) {
                $goal = new Goals();
                $goal->addGoal($data, $tree->id);
            }

            return $this->sendSuccess([
                'id' => $tree->id,
                'numberOrder' => $tree->ord,
                'parent' => $tree->parent,
                'type' => TreeGoals::setTypeTreeName($tree->type)
            ]);

        } else {
            return $this->sendError($tree->getErrors());
        }
    }

    /**
     * Editing a category or target in a tree
     * @return array
     */
    public function actionUpdateitemtreegoal()
    {
        $data = Yii::$app->request->post();

        $tree = TreeGoals::findOne(['id' => $data['id'],]);

        if ($tree->updateItemTree($data)) {
            return $this->sendSuccess();
        } else {
            return $this->sendError($tree->getErrors());
        }
    }

    /**
     * Returns target data
     * @return array
     */
    public function actionGetgoaldata()
    {
        $goalId = (int)Yii::$app->request->post('id');

        $goalData = Goals::find()->select('id, name, description')->where(['tree_goals_id' => $goalId])->asArray()->one();

        if ($goalData) {
            return $this->sendSuccess($goalData);
        } else {
            return $this->sendError([]);
        }
    }

    /**
     * Editing a goal
     * @return array
     */
    public function actionUpdategoal()
    {
        $post = Yii::$app->request->post();
        $goal = Goals::findOne((int)$post['id']);

        if ($goal->updateGoal($post)) {
            $goalTree = TreeGoals::findOne($goal->tree_goals_id);
            $goalTree->updateItemTree($post);
            return $this->sendSuccess();
        } else {
            return $this->sendError($goal->getErrors());
        }
    }

    /**
     * Deleting a target from a tree
     * @return array
     */
    public function actionRemovegoal()
    {
        $goalId = (int)Yii::$app->request->post('id');
        Goals::removeGoal($goalId);
        return $this->sendSuccess();
    }

    /**
     * Return the target tree
     * @return array
     */
    public function actionGettreegoals()
    {
        $treeId = (int)Yii::$app->request->post('tree_id');

        $treeGoals = new TreeGoals();
        $treeGoals = $treeGoals->getTreeGoals($treeId);

        if ($treeGoals) {
            return $this->sendSuccess($treeGoals);
        } else {
            return $this->sendError();
        }
    }

    /**
     * Return data goal for info
     * @return array
     */
    public function actionGetgoaldescription()
    {
        $goalTreeId = (int)Yii::$app->request->post('goal_tree_id');
        $goalData = Goals::find()->select('name,description')->where(['tree_goals_id' => $goalTreeId])->asArray()->one();

        if ($goalData) {
            return $this->sendSuccess($goalData);
        } else {
            return $this->sendError(['msg' => 'Dont find goal']);
        }
    }

    /**
     * Remove a document from the main tree
     * @return array
     */
    public function actionRemovegoaldocument()
    {
        $documentId = (int)Yii::$app->request->post('document_id');

        $documentTreeId = Docs::removeGoalDocument($documentId);

        if ($documentTreeId) {
            return $this->sendSuccess([
                'tree_id' => $documentTreeId
            ]);
        } else {
            return $this->sendError();
        }
    }

    /**
     * Delete target document folder
     * @return array
     */
    public function actionRemovetreegoalfolder()
    {
        $treeGoalFolderId = Yii::$app->request->post('tree_goal_folder_id');
        if (TreeGoals::removeGoalFolder($treeGoalFolderId)) {
            return $this->sendSuccess();
        } else {
            return $this->sendError();
        }
    }

}