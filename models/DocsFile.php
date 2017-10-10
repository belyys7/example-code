<?php

namespace api\models;

use Yii;

use yii\db\ActiveRecord;

use yii\web\UploadedFile;

/**
 * This is the model class for table "docs_file".
 *
 * @property integer $id
 * @property string $name
 */
class DocsFile extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'docs_file';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file', 'name'], 'required'],
            [['file'], 'file', 'checkExtensionByMimeType'=>false, 'extensions' => 'pdf']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file' => 'File',
            'name' => 'Name'
        ];
    }

    /**
     * Give file extension
     * @param $file
     * @return mixed
     */
    public static function getFileExtension($file)
    {
        $path_info = pathinfo($file);
        return $path_info['extension'];
    }

    /**
     * Upload document file
     * @return bool
     */
    public function uploadFile()
    {
        $this->file = UploadedFile::getInstanceByName('file');

        $this->name = $this->file->name;

        if ($this->file && $this->validate()) {

            $fileNewName = Yii::$app->security->generateRandomString(14) . '.' . DocsFile::getFileExtension($this->file->name);
            $this->file->saveAs('uploads/docs/' . $fileNewName);
            $this->file = $fileNewName;

            return ($this->validate()) ? $this->save(false) : false;
        }
    }

}