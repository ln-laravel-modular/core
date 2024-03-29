<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Core extends Model
{
    use HasFactory, FamilyTree, StatusDraft;

    protected $prefix;
    protected $dates = [
        'created_at',
        'updated_at',
        'release_at',
        'deleted_at'
    ];
    protected $fillable = [
        'created_at',
        'updated_at',
        'release_at',
        'deleted_at'
    ];
    protected static $unguarded = false;
    function __construct()
    {
        $this->setPrefix();
    }

    protected static function newFactory()
    {
        return \Modules\Core\Database\factories\CoreFactory::new();
    }

    function setPrefix($prefix = null)
    {
        $this->prefix = $prefix ?? Config::get(strtolower(ModuleHelper::current()) . '.db_prefix');
    }
    function getPrefix()
    {
        return $this->prefix;
    }
    public function getTable()
    {
        // 没有定义表前缀
        if (empty($this->prefix)) return $this->table;
        // 表前缀与当前表名前部分一致
        if (substr($this->table, 0, strlen($this->prefix)) === $this->prefix) return $this->table;
        // 默认
        return $this->prefix . $this->table;
    }
}


/**
 * 族谱
 */
trait FamilyTree
{
    /**
     * 父类
     */
    public function parent()
    {
        return $this->hasOne(static::class, $this->primaryKey, $this->parentColumn,);
    }

    public function parent_exists()
    {
    }
    public function parents()
    {
        return $this->hasOne(static::class, $this->primaryKey, $this->parentColumn,)->with('parent');
    }
    public function root()
    {
        return $this->hasOne(static::class, $this->primaryKey, $this->parentColumn,)->with('root');
    }
    /**
     * Retrieve the root parent of the current category.
     * The root parent of a category that has no parent is that category itself.
     *
     * @return \App\Models\Category
     */
    public function getRoot()
    {
        $bubble_keys = [$this[$this->getKeyName()]];
        // $this->bubbule_keys = $this->active_keys ?? [$this[$this->getKeyName()]];
        if ($this->root) {
            if ($this->root->root) {
                $this->root = $this->root->getRoot();
                // dump($this->root->active_keys);
            }
            // array_unshift($bubble_keys);
            $this->root->bubble_keys = array_merge($this->root->bubble_keys ?? [$this->root[$this->getKeyName()]], $bubble_keys);
            return $this->root;
        }
        $this->bubble_keys = array_merge($this->bubble_keys ?? [], $bubble_keys);
        return $this;
        // if ($this->root && $this->root->root) {
        //   return $this->getRoot();
        // }
        // return $this->root;
    }
    /**
     * 子集
     */
    public function children()
    {
        return $this->hasMany(static::class, $this->parentColumn, $this->primaryKey);
    }

    public function children_count()
    {
    }
}

trait TableRelationship
{
    public function metas()
    {
        return $this
            ->hasMany(\App\Models\Relationship::class, $this->prefix . '_' . $this->primaryKey, $this->primaryKey)
            ->leftJoin($this->prefix . "_metas", "_relationships." . $this->prefix . "_mid", '=', $this->prefix . "_metas.mid");
    }

    public function contents()
    {
        return $this
            ->hasMany(\App\Models\Relationship::class, $this->prefix . '_' . $this->primaryKey, $this->primaryKey)
            ->leftJoin($this->prefix . "_contents", "_relationships." . $this->prefix . "_cid", '=', $this->prefix . "_contents.cid");
    }

    public function links()
    {
        return $this
            ->hasMany(\App\Models\Relationship::class, $this->prefix . '_' . $this->primaryKey, $this->primaryKey)
            ->leftJoin($this->prefix . "_links", "_relationships." . $this->prefix . "_lid", '=', $this->prefix . "_links.lid");
    }

    public function relationships()
    {
        return $this->hasMany(\App\Models\Relationship::class, $this->prefix . '_' . $this->primaryKey, $this->primaryKey);
    }

    public function logs()
    {
    }
}

/**
 * 草稿
 */
trait StatusDraft
{
    /**
     * 草稿
     */
    public function draft()
    {
        return $this->hasOne(static::class, $this->parentColumn, $this->primaryKey)->with($this->fieldColumns ?? [])->where([['status', 'draft']]);
    }

    /**
     * 草稿列表，草稿记录
     */
    public function drafts()
    {
        return $this->hasMany(static::class, $this->parentColumn, $this->primaryKey)->with($this->fieldColumns ?? [])->where([['status', 'draft']]);
    }

    /**
     * 检测是否存在草稿记录
     */
    public function draft_exists()
    {
    }
}
