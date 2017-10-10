<?php

namespace api\models;

use Yii;

use yii\db\ActiveRecord;

use api\components\UploadFileUrlBehavior;

use api\models\DocsType;
use api\models\DocsFile;
use api\models\DocsUrl;
use api\models\Tree;

/**
 * This is the model class for table "docs".
 *
 * @property integer $id
 * @property integer $tree_id
 * @property integer $docs_type_id
 * @property integer $docs_file_id
 * @property string $name
 * @property string $description
 * @property string $number
 * @property string $revision
 * @property string $date
 * @property string $status
 * @property string $organization
 * @property string $author
 */
class Docs extends ActiveRecord
{
    const SCENARIO_ADD_DOCUMENT = 'add_document';
    const SCENARIO_EDIT_DOCUMENT = 'edit_document';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['UploadFileUrlBehavior'] = [
            'class' => UploadFileUrlBehavior::className()
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'docs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tree_id', 'docs_type_id', 'docs_url_id', 'docs_file_id', 'name', 'date', 'status'], 'required'],
            [['tree_id', 'docs_type_id', 'docs_url_id', 'docs_file_id'], 'integer'],
            [['description'], 'string'],
            [['date', 'status'], 'safe'],
            [['date', 'status'], 'date'],
            [['name', 'number', 'revision', 'organization', 'author'], 'string', 'max' => 255],
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
            'docs_type_id' => 'Docs Type ID',
            'docs_file_id' => 'Docs File ID',
            'name' => 'Name',
            'description' => 'Description',
            'number' => 'Number',
            'revision' => 'Revision',
            'date' => 'Date',
            'status' => 'Status',
            'organization' => 'Organization',
            'author' => 'Author',
        ];
    }

    /**
     * Change the date format
     * @param $date
     * @param $format
     * @return false|string
     */
    public static function prepareDateFormat($date, $format)
    {
        if ($date == 'set date') {
            return '';
        } else {
            return date($format, strtotime($date));
        }
    }

    /**
     * Adding a document
     * @param $treeId
     */
    public function addDocument($treeId)
    {
        $this->scenario = Docs::SCENARIO_ADD_DOCUMENT;
        $this->tree_id = $treeId;
        $this->save();
    }

    /**
     * Updating the document
     * @param $data
     * @return array|bool
     */
    public function updateDocument($data)
    {
        $this->scenario = self::SCENARIO_EDIT_DOCUMENT;

        $this->load($data, '');

        $this->date = self::prepareDateFormat($data['date'], 'Y-m-d');
        $this->status = self::prepareDateFormat($data['status'], 'Y-m-d');

        if ($data['file_url']) {

            $fileBehavior = $this->getBehavior('UploadFileUrlBehavior');
            $fileBehavior->path = 'uploads/docs/';
            $fileBehavior->urlFile = $data['file_url'];

            if ($fileBehavior->uploadFileUrl(new DocsFile())) {
                $this->docs_file_id = $fileBehavior->idFile;
                return true;
            } else {
                $error['file_url'][0] = 'No valid url for file pdf';
                $errors = array_merge($this->getErrors(), $error);
                return $errors;
            }

        } else {
            $this->docs_file_id = $data['docs_file_id'];
        }
    }

    /**
     * Delete a document
     * @param $documentId
     * @return mixed
     */
    public static function removeGoalDocument($documentId)
    {
        $document = self::find()->select('tree_id')->where(['id' => $documentId])->asArray()->one();

        if (isset($document['tree_id'])) {
            $tree = Tree::findOne(['id' => $document['tree_id']]);
            if ($tree) {
                $tree->status = Tree::STATUS_REMOVE;
                $tree->update();
                return $document['tree_id'];
            }
        }
    }

    /**
     * Scenarios
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADD_DOCUMENT] = ['tree_id'];
        $scenarios[self::SCENARIO_EDIT_DOCUMENT] = ['id', 'tree_id', 'name'];
        return $scenarios;
    }

    /* Relations */

    public function getType()
    {
        return $this->hasOne(DocsType::className(), ['id' => 'docs_type_id']);
    }

    public function getFile()
    {
        return $this->hasOne(DocsFile::className(), ['id' => 'docs_file_id']);
    }

}
