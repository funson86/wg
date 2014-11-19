<?php

namespace funson86\blog\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use funson86\blog\Module;

/**
 * This is the model class for table "blog_catalog".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $title
 * @property string $surname
 * @property string $banner
 * @property integer $is_nav
 * @property integer $sort_order
 * @property integer $page_size
 * @property string $template
 * @property string $redirect_url
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 *
 * @property BlogPost[] $blogPosts
 */
class BlogCatalog extends \yii\db\ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETED = -1;
    const IS_NAV_YES = 1;
    const IS_NAV_NO = 0;
    const PAGE_TYPE_LIST = 'list';
    const PAGE_TYPE_PAGE = 'page';
    private $_statusLabel;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'blog_catalog';
    }

    /**
     * create_time, update_time to now()
     * crate_user_id, update_user_id to current login user id
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => 'update_time',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'is_nav', 'sort_order', 'page_size', 'status'], 'integer'],
            [['title', 'surname'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['title', 'banner', 'template', 'redirect_url'], 'string', 'max' => 255],
            [['surname'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Module::t('blog', 'ID'),
            'parent_id' => Module::t('blog', 'Parent ID'),
            'title' => Module::t('blog', 'Title'),
            'surname' => Module::t('blog', 'Surname'),
            'banner' => Module::t('blog', 'Banner'),
            'is_nav' => Module::t('blog', 'Is Nav'),
            'sort_order' => Module::t('blog', 'Sort Order'),
            'page_size' => Module::t('blog', 'Page Size'),
            'template' => Module::t('blog', 'Template'),
            'redirect_url' => Module::t('blog', 'Redirect Url'),
            'status' => Module::t('blog', 'Status'),
            'create_time' => Module::t('blog', 'Create Time'),
            'update_time' => Module::t('blog', 'Update Time'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBlogPosts()
    {
        return $this->hasMany(BlogPost::className(), ['catalog_id' => 'id']);
    }

    public function getPostsCount()
    {
        return $this->count(BlogPost::className(), ['catalog_id' => 'id']);
    }

    /**
     * Before save.
     * create_time update_time
     */
    /*public function beforeSave($insert)
    {
        if(parent::beforeSave($insert))
        {
            // add your code here
            return true;
        }
        else
            return false;
    }*/

    /**
     * After save.
     *
     */
    /*public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        // add your code here
    }*/

    /**
     * @inheritdoc
     */
    public static function getArrayStatus()
    {
        return [
            self::STATUS_INACTIVE => Module::t('blog', 'STATUS_INACTIVE'),
            self::STATUS_ACTIVE => Module::t('blog', 'STATUS_ACTIVE'),
            self::STATUS_DELETED => Module::t('blog', 'STATUS_DELETED'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getStatusLabel($status = null)
    {
        if($status)
        {
            $statuses = self::getArrayStatus();
            return $statuses[$status];
        }
        else
            return;
    }

    /**
     * @inheritdoc
     */
    public static function getArrayIsNav()
    {
        return [
            self::IS_NAV_YES => Module::t('blog', 'YES'),
            self::IS_NAV_NO => Module::t('blog', 'NO'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getIsNavLabel($isNav = null)
    {
        if($isNav)
        {
            $arrayIsNav = self::getArrayIsNav();
            return $arrayIsNav[$isNav];
        }
        else
            return;
    }


    static public function get($parentId = 0, $array = array(), $level = 0, $add = 2, $repeat = '　')
    {
        $strRepeat = '';
        if ($level>1) {
            for($j = 0; $j < $level; $j ++)
            {
                $strRepeat .= $repeat;
            }
        }
        if($level>0)
            $strRepeat .= '';

        $newArray = array ();
        $tempArray = array ();
        foreach ( ( array ) $array as $v )
        {
            if ($v['parent_id'] == $parentId)
            {
                $newArray [] = array ('id' => $v['id'], 'title' => $v['title'], 'parent_id' => $v['parent_id'],  'sort_order' => $v['sort_order'],
                    'banner' => $v['banner'], //'postsCount'=>$v['postsCount'],
                    'is_nav' => $v['is_nav'], 'template' => $v['template'],
                    'status' => $v['status'], 'create_time' => $v['create_time'], 'update_time' => $v['update_time'], 'redirect_url' => $v['redirect_url'], 'str_repeat' => $strRepeat, 'str_label' => $strRepeat.$v['title'],);

                $tempArray = self::get ( $v['id'], $array, ($level + $add) );
                if ($tempArray)
                {
                    $newArray = array_merge ( $newArray, $tempArray );
                }
            }
        }
        return $newArray;
    }

    static public function getCatalog($parentId=0,$array = array())
    {
        $newArray=array();
        foreach ((array)$array as $v)
        {
            if ($v['parent_id']==$parentId)
            {
                $newArray[$v['id']]=array(
                    'text'=>$v['title'].' 导航['.($v['is_nav'] ? Module::t('common', 'CONSTANT_YES') : Module::t('common', 'CONSTANT_NO')).'] 排序['.$v['sort_order'].'] 类型['.($v['page_type'] == 'list' ? Module::t('common', 'PAGE_TYPE_LIST') : Module::t('common', 'PAGE_TYPE_PAGE')).'] 状态['.F::getStatus2($v['status']).'] [<a href="'.Yii::app()->createUrl('/catalog/update',array('id'=>$v['id'])).'">修改</a>][<a href="'.Yii::app()->createUrl('/catalog/create',array('id'=>$v['id'])).'">增加子菜单</a>]&nbsp;&nbsp[<a href="'.Yii::app()->createUrl('/catalog/delete',array('id'=>$v['id'])).'">删除</a>]',
                    //'children'=>array(),
                );

                $tempArray = self::getCatalog($v['id'],$array);
                if($tempArray)
                {
                    $newArray[$v['id']]['children']=$tempArray;
                }
            }
        }
        return $newArray;
    }

    static public function getCatalogIdStr($parentId=0,$array = array())
    {
        $str = $parentId;
        foreach ((array)$array as $v)
        {
            if ($v['parent_id']==$parentId)
            {

                $tempStr = self::getCatalogIdStr($v['id'],$array);
                if($tempStr)
                {
                    $str .= ','.$tempStr;
                }
            }
        }
        return $str;
    }

    static public function getRootCatalogId($id=0,$array = array())
    {
        if(0 == $id)
        {
            return 0;
        }

        foreach ((array)$array as $v)
        {
            if ($v['id']==$id)
            {
                $parentId = $v['parent_id'];
                if(0 == $parentId)
                    return $id;
                else
                    return self::getRootCatalogId($parentId,$array);
            }
        }
    }

    static public function getCatalogSub2($id=0,$array = array())
    {
        if(0 == $id)
        {
            return 0;
        }

        $arrayResult = array();
        $rootId = self::getRootCatalogId($id, $array);
        foreach ((array)$array as $v)
        {
            if ($v['parent_id']==$rootId)
            {
                array_push($arrayResult, $v);
            }
        }

        return $arrayResult;
    }

    static public function getBreadcrumbs($id=0,$array = array())
    {
        if(0 == $id)
        {
            return;
        }

        $arrayResult = self::getPathToRoot($id, $array);

        return array_reverse($arrayResult);
    }

    static public function getPathToRoot($id=0,$array = array())
    {
        if (0 == $id) {
            return array();
        }

        $arrayResult = array();
        $parent_id = 0;
        foreach ((array)$array as $v) {
            if ($v['id'] == $id) {
                $parent_id = $v['parent_id'];
                if (self::PAGE_TYPE_LIST == $v['page_type'])
                    $arrayResult = array($v['title'] => array('list', id => $v['id']));
                elseif (self::PAGE_TYPE_PAGE == $v['page_type'])
                    $arrayResult = array($v['title'] => array('page', id => $v['id']));
            }
        }

        if (0 < $parent_id) {
            $arrayTemp = self::getPathToRoot($parent_id, $array);

            if (!empty($arrayTemp))
                $arrayResult += $arrayTemp;
        }

        if (!empty($arrayResult))
            return $arrayResult;
        else
            return;
    }
}