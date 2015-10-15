<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use common\models\Tag;

/**
 * This is the model class for table "post".
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property string $tags
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $author_id
 *
 * @property Comment[] $comments
 * @property User $author
 */
class Post extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    
    const STATUS_DRAFT=1;
    const STATUS_PUBLISHED=2;
    const STATUS_ARCHIVED=3;
    
    public static function tableName()
    {
        return 'post';
    }
    public function behaviors(){
        return [
                    'timestamp' => [
                        'class' => TimestampBehavior::className(),
                        'attributes' => [
                            ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                            ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
                        ],
                    ],
                    [
                        'class' => BlameableBehavior::className(),
                        'createdByAttribute' => 'author_id',
                        'updatedByAttribute' => 'author_id',
                    ],
                ];
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'content', 'status'], 'required'],
            ['title','string','max'=>128],
            ['status','in', 'range'=>[1,2,3]],
            ['tags', 'match', 'pattern'=>'/^[\w\s,]+$/',
                'message'=>'В тегах можно использовать только буквы.'],
            ['tags', function($attribute,$params){
                $this->tags=Tag::array2string(array_unique(Tag::string2array($this->tags)));
                }],
            
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'tags' => 'Tags',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'author_id' => 'Author ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::className(), ['post_id' => 'id']);
    }

    public function getCommentCount()
    {
        return $this->hasMany(Comment::className(), ['post_id' => 'id'])->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'author_id']);
    }

    public function getUrl()
    {
        return Yii::$app()->urlManager->createUrl([ 'post/view', 
            'id'=>$this->id,
            'title'=>$this->title]);
    }


  
}
