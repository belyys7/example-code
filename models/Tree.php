<?php

namespace api\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tree".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $name
 * @property integer $parent
 */
class Tree extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_REMOVE = 0;

    const TYPE_FOLDER = 1;
    const TYPE_DOCUMENT = 2;

    const ROOT_ITEM_NAME = "Root";

    const SCENARIO_ADD_TREE = 'add_item_tree';
    const SCENARIO_EDIT_TREE = 'edit_item_tree';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tree';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'name', 'parent', 'type'], 'required'],
            [['user_id', 'parent', 'status', 'type'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'parent' => 'Parent',
            'status' => 'Status',
            'type' => 'Type',
        ];
    }

    /**
     * Returns a tree
     * @return array
     */
    public function getTree()
    {
        $arrayTree = $this::find()
            ->select('id, parent, type, name')
            ->where([
                //'user_id' => Yii::$app->user->identity->id,
                'status' => self::STATUS_ACTIVE
            ])
            ->orderBy([
                'type' => SORT_ASC,
                'name' => SORT_ASC
            ])
            ->asArray()->all();
        return $this->createAssoccTree($arrayTree);
    }

    /**
     * Converts a one-dimensional array of a tree to a nested associative array of a tree
     * @param $array
     * @param int $parent
     * @return array
     */
    private function createAssoccTree($array, $parent = 0)
    {
        $a = [];
        foreach ($array as $v) {
            if ($parent == $v['parent'])
            {
                $b = $this->createAssoccTree($array, $v['id']);
                if (!empty($b)) {
                    $a[] = [
                        'id' => $v['id'],
                        'parent' => $v['parent'],
                        'ord' => count($a),
                        'name' => $v['name'],
                        'type' => self::setTypeTreeName($v['type']),
                        'children' => $b
                    ];
                }
                else {
                    $a[] = [
                        'id' => $v['id'],
                        'parent' => $v['parent'],
                        'ord' => count($a),
                        'name' => $v['name'],
                        'type' => self::setTypeTreeName($v['type']),
                        'children' => []
                    ];
                }
            }
        }
        return $a;
    }

    /**
     * Delete item tree
     * @param $treeId
     * @return int
     */
    public static function removeFolder($treeId)
    {
        return self::updateAll(['status' => self::STATUS_REMOVE], ['id' => $treeId]);
    }

    /**
     * Sets the name instead of the node type identifier
     * @param $type
     * @return string
     */
    public static function setTypeTreeName($type)
    {
        if ($type == self::TYPE_FOLDER) {
            return 'folder';
        }
        if ($type == self::TYPE_DOCUMENT) {
            return 'doc';
        }
    }

    /**
     * Return the name of the parent to the tree node
     * @param $treeId
     * @return array
     */
    public static function getParentTreeData($treeId) {

        $parentNameItem = self::ROOT_ITEM_NAME;

        $currentTreeItem = self::find()->select(['parent'])->where(['id' => $treeId])->asArray()->one();

        if ($currentTreeItem['parent']) {
            $currentTreeItem = self::find()->select(['name'])->where(['id' => $currentTreeItem['parent']])->one();
            $parentNameItem = $currentTreeItem->name;
        }

        return ['name' => $parentNameItem];
    }


    /**
     * Adding a Tree Node
     * @param $data
     * @return bool
     */
    public function addItemTree($data)
    {
        $this->scenario = self::SCENARIO_ADD_TREE;

        $this->load($data, '');
        $this->user_id = Yii::$app->user->identity->id;

        return ($this->validate()) ? $this->save(false) : false;
    }

    /**
     * Updating the tree node
     * @param $data
     * @return bool|false|int
     */
    public function updateItemTree($data)
    {
        $this->scenario = self::SCENARIO_EDIT_TREE;
        $this->load($data, '');
        return ($this->validate()) ? $this->update(false) : false;
    }

    /**
     * SCENARIOS
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADD_TREE] = ['name', 'parent', 'type', 'status', 'user_id'];
        $scenarios[self::SCENARIO_EDIT_TREE] = ['name', 'parent'];
        return $scenarios;
    }

}