<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\helpers\Html;

/**
 * This is the model class for table "comment".
 *
 * @property integer $id
 * @property string $content
 * @property integer $status
 * @property integer $create_time
 * @property string $author
 * @property string $email
 * @property string $url
 * @property integer $post_id
 *
 * @property Post $post
 */
class Comment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    const STATUS_PENDING=1;
    const STATUS_APPROVED=2;
    
    public static function tableName()
    {
        return 'comment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        [['content', 'author', 'email'], 'required'],
        [['author', 'email', 'url'], 'string', 'max' => 128],
        ['email','email'],
        [['content'], 'string'],
        ['url','url'],
        [['status', 'create_time', 'post_id'], 'integer'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        'id' => 'ID',
        'content' => 'Comment',
        'status' => 'Status',
        'create_time' => 'Create Time',
        'author' => 'Name',
        'email' => 'Email',
        'url' => 'Website',
        'post_id' => 'Post',
        ];
    }

    public function behaviors(){
        return [
        'timestamp' => [
        'class' => TimestampBehavior::className(),
        'attributes' => [
        ActiveRecord::EVENT_BEFORE_INSERT => ['create_time'],
            //ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
        ],
        ]   
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id' => 'post_id']);
    }

    /**
     * Approves a comment.
     */
    public function approve()
    {
        $this->status=Comment::STATUS_APPROVED;
        $this->update(['status']);
    }

        /**
     * @param Post the post that this comment belongs to. If null, the method
     * will query for the post.
     * @return string the permalink URL for this comment
     */
        public function getUrl($post=null)
        {
            if($post===null)
                $post=$this->post;
            return $post->url.'#c'.$this->id;
        }

    /**
     * @return string the hyperlink display for the current comment's author
     */
    public function getAuthorLink()
    {
        if(!empty($this->url))
            return Html::a(Html::encode($this->author),$this->url);
        else
            return Html::encode($this->author);
    }

}
