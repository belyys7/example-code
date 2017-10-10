<?php

namespace api\models;

use Yii;

use yii\db\ActiveRecord;

use api\models\TreeGoals;

/**
 * This is the model class for table "goals".
 *
 * @property integer $id
 * @property integer $tree_goals_id
 * @property string $name
 * @property string $description
 */
class Goals extends ActiveRecord
{
    const SCENARIO_ADD_GOAL = 'add_goal';
    const SCENARIO_EDIT_GOAL = 'edit_goal';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'goals';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tree_goals_id'], 'required'],
            [['tree_goals_id'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tree_goals_id' => 'Tree Goals ID',
            'name' => 'Name',
            'description' => 'Description',
        ];
    }

    /**
     * Adding a target to the target tree
     * @param $data
     * @param $treeId
     * @return bool
     */
    public function addGoal($data, $treeId)
    {
        $this->scenario = self::SCENARIO_ADD_GOAL;
        $this->name = $data['name'];
        $this->tree_goals_id = $treeId;
        return ($this->validate()) ? $this->save(false) : false;
    }

    /**
     * Update goal
     * @param $data
     * @return bool
     */
    public function updateGoal($data)
    {
        $this->scenario = self::SCENARIO_EDIT_GOAL;
        $this->load($data, '');
        return ($this->validate()) ? $this->save(false) : false;
    }

    /**
     * Delete goal
     * @param $goalId
     */
    public static function removeGoal($goalId)
    {
        $currentGoal = self::find()->select('tree_goals_id')->where(['id' => $goalId])->asArray()->one();

        if ($currentGoal) {

            $currentTreeGoal = TreeGoals::find()->select('id,tree_id, parent')->where(['id' => $currentGoal['tree_goals_id']])->asArray()->one();

            self::deleteAll(['id' => $goalId]);
            TreeGoals::deleteAll(['id' => $currentTreeGoal['id']]);

            $currentLevelGoals = TreeGoals::find()->
            select('id')->
            where([
                'tree_id' => $currentTreeGoal['tree_id'],
                'parent' => $currentTreeGoal['parent'],
                'type' => TreeGoals::TYPE_GOAL
            ])->
            orderBy(['ord' => SORT_ASC])->
            asArray()->all();

            $count = 1;
            if ($currentLevelGoals) {
                foreach ($currentLevelGoals as $item) {
                    $updateGoal = TreeGoals::find()->where(['id' => $item['id']])->one();
                    $updateGoal->ord = $count;
                    $updateGoal->update();
                    $count++;
                }
            }

        }
    }

    /**
     * SCENARIOS
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADD_GOAL] = ['tree_goals_id'];
        $scenarios[self::SCENARIO_EDIT_GOAL] = ['id','tree_goals_id','name'];
        return $scenarios;
    }

}
