<?php

namespace api\models;

use Yii;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "tree_goals".
 *
 * @property integer $id
 * @property integer $tree_id
 * @property integer $parent
 * @property integer $user_id
 * @property integer $type
 * @property integer $status
 * @property string $name
 */
class TreeGoals extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_REMOVE = 0;

    const TYPE_CATEGORY = 1;
    const TYPE_GOAL = 2;

    const SCENARIO_ADD_TREE = 'add_tree';
    const SCENARIO_EDIT_TREE = 'edit_tree';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tree_goals';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tree_id', 'parent', 'user_id', 'type', 'name'], 'required'],
            [['tree_id', 'parent', 'user_id', 'type', 'status'], 'integer'],
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
            'tree_id' => 'Tree ID',
            'parent' => 'Parent',
            'user_id' => 'User ID',
            'type' => 'Type',
            'status' => 'Status',
            'name' => 'Name',
        ];
    }

    /**
     * Return a tree with categories and goals for the node
     * @param $treeId
     * @return array
     */
    public function getTreeGoals($treeId)
    {
        $arrayTree = $this::find()
            ->select('id, parent, ord, type, name')
            ->where([
                'tree_id' => $treeId,
                'status' => self::STATUS_ACTIVE
            ])
            ->orderBy([
                'type' => SORT_ASC,
                'ord' => SORT_ASC,
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
                        'numberOrder' => $v['ord'],
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
                        'numberOrder' => $v['ord'],
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
     * Sets the name instead of the node type identifier
     * @param $type
     * @return string
     */
    public static function setTypeTreeName($type)
    {
        if ($type == self::TYPE_CATEGORY) {
            return 'category';
        }
        if ($type == self::TYPE_GOAL) {
            return 'goal';
        }
    }

    /**
     * The following ORD for insertion
     * @param $parent
     * @param $treeId
     * @return int|string
     */
    public function getOrdParent($parent, $treeId)
    {
        $count = self::find()->where([
            'tree_id' => $treeId,
            'parent' => $parent,
            'type' => 2
        ])->count();
        return $count + 1;
    }

    /**
     * Adds a node node tree
     * @param $data
     * @return bool
     */
    public function addItemTree($data)
    {
        $this->scenario = self::SCENARIO_ADD_TREE;

        $this->load($data, '');

        if ($this->validate()) {

            if ($this->type === self::TYPE_GOAL) {
                $this->ord = $this->getOrdParent($this->parent, $this->tree_id);
            }

            $this->save(false);
            return true;
        }
    }

    /**
     * Updates the node of the target tree
     * @param $data
     * @return bool
     */
    public function updateItemTree($data)
    {
        $this->scenario = self::SCENARIO_EDIT_TREE;
        $this->load($data, '');
        return ($this->validate()) ? $this->save(false) : false;
    }

    /**
     * Deleting a goal category
     * @param $treeGoalFolderId
     */
    public static function removeGoalFolder($treeGoalFolderId)
    {
        $treeGoal = self::findOne(['id' => $treeGoalFolderId]);
        if ($treeGoal) {
            $treeGoal->status = self::STATUS_REMOVE;
            $treeGoal->update();
        }
    }

    /**
     * SCENARIOS
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADD_TREE] = ['tree_id', 'name', 'parent', 'type', 'status'];
        $scenarios[self::SCENARIO_EDIT_TREE] = ['name'];
        return $scenarios;
    }

}
